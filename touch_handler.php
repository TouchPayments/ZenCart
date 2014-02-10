<?php
$show_all_errors = true;

require_once('includes/configure.php');
require_once('includes/version.php');
require_once('includes/application_top.php');

require_once __DIR__ . '/includes/Touch/ErrorCodes.php';

session_start();
// Validate Cart for checkout
$_SESSION['valid_to_checkout'] = true;
$_SESSION['cart']->get_products(true);
//// eof: Load ZenCart configuration

// Stock Check to prevent checkout if cart contents rules violations exist
if (STOCK_CHECK == 'true' && STOCK_ALLOW_CHECKOUT != 'true' && isset($_SESSION['cart'])) {
    $products = $_SESSION['cart']->get_products();
    for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
        if (zen_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
            zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
            break;
        }
    }
}

// if cart contents has changed since last pass, reset
if (isset($_SESSION['cart']->cartID)) {
    if (isset($_SESSION['cartID'])) { // This will only be set if customer has been to the checkout_shipping page. Will *not* be set if starting via EC Shortcut button, so don't want to redirect in that case.
        if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
            if (isset($_SESSION['shipping'])) {
                unset($_SESSION['shipping']);
                $messageStack->add_session('checkout_shipping', TEXT_RESELECT_SHIPPING, 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
            }
        }
    }
}

require_once('includes/modules/payment/touch.php');
$touch = new touch();


$token = $_GET['token'];

//// Create ZenCart order
if ($token) {

    // Variable initialization
    $ts = time();

    /**
     * New Transaction
     *
     * This is for when Zen Cart sees a transaction for the first time.
     * This doesn't necessarily mean that the transaction is in a
     * COMPLETE state, but rather than it is new to the system
     */

    // Load ZenCart shipping class
    require_once(DIR_WS_CLASSES . 'shipping.php');

    // Load ZenCart payment class
    require_once(DIR_WS_CLASSES . 'payment.php');
    $payment_modules = new payment($_SESSION['payment']);

    $shipping_modules = new shipping($_SESSION['shipping']);

    // Load ZenCart order class
    require(DIR_WS_CLASSES . 'order.php');
    $order = new order();

    // Load ZenCart order_total class
    require(DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total();

    $order_totals = $order_total_modules->process();

    // Create ZenCart order
    $zcOrderId = $order->create($order_totals);

    // Add products to order
    $order->create_add_products($zcOrderId, 2);


    $result = $touch->api->getOrderByTokenStatus($token);

    if (isset($result->error) || !in_array($result->result->status, array('new', 'pending'))) {
        $message = null;
        if (isset($result->reasonCancelled)) {
            $message = 'Touch Payments returned and said:' . $result->reasonCancelled;
        } else {
            $message = 'Got an error:' . var_export($result, true);
        }

        $sql
            = "UPDATE `" . TABLE_ORDERS . "`
        SET `orders_status` = '0'
        WHERE `orders_id` = '" . (int)$zcOrderId . "'";
        $db->Execute($sql);

        $sqlArray = array(
            'orders_id'         => (int)$zcOrderId,
            'orders_status_id'  => 0,
            'date_added'        => date('Y-m-d H:i:s', $ts),
            'customer_notified' => '0',
            'comments'          => $message,
        );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sqlArray);

        // Redirect to the payment page
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));

    } else {
        /**
         * adjust the touch fee that comes back from
         * the API in case the fee has changed
         */
        if ((float)$result->result->fee > 0) {
            if (isset($_SESSION['touch_fee']) && $_SESSION['touch_fee'] != $result->result->fee) {

                // Adjust grand total accordingly
                $order->order_total = $order->order_total - $_SESSION['touch_fee'] + $result->result->fee;
                update_post_meta($order->id, '_order_total', $order->order_total);

                // Reset fee
                $_SESSION['touch_fee'] = $result->result->fee;
            }
        }
        /**
         * - Approve the order in touch
         * - set a transaction ID
         * - set Order to paid
         */
        if ($_GET['sms-code']) {
            $apprReturn = $touch->api->approveOrderBySmsCode(
                $token,
                $zcOrderId,
                $order->info['total'] - $_SESSION['touch_fee'],
                $_GET['sms-code']
            );

            // If the code entered is invalid then we have to ask for the code again
            if ($apprReturn->error && $apprReturn->error->code == Touch_ErrorCodes::ERR_WRONG_SMS_CODE) {
                zen_redirect('/index.php?main_page=checkout_confirmation&status=sms-error');
                exit;
            }
        } else {
            $apprReturn = $touch->api->approveOrder(
                $token,
                $zcOrderId,
                $order->info['total'] - $_SESSION['touch_fee']
            );
        }


        if ($apprReturn->result->status == 'approved') {

            $sqlArray = array(
                'orders_id'         => (int)$zcOrderId,
                'orders_status_id'  => 0,
                'date_added'        => 'now()',
                'customer_notified' => '0',
                'comments'          => 'Order approved by Touch Payments.',
            );
            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sqlArray);

            // Email customer
            $order->send_order_email($zcOrderId, 2);

            // Empty cart
            $_SESSION['cart']->reset(true);

            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, 'referer=touch', 'SSL'));

        } else {
            if ($apprReturn->error) {

                $sql
                    = "UPDATE `" . TABLE_ORDERS . "`
                        SET `orders_status` = '0'
                        WHERE `orders_id` = '" . (int)$zcOrderId . "'";
                $db->Execute($sql);

                $sqlArray = array(
                    'orders_id'         => (int)$zcOrderId,
                    'orders_status_id'  => 0,
                    'date_added'        => date('Y-m-d H:i:s', $ts),
                    'customer_notified' => '0',
                    'comments'          => $apprReturn->error->message,
                );
                zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sqlArray);

                // Redirect to the payment page
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
            }
        }
    }

}
