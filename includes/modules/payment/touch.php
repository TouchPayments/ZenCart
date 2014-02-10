<?php
/**
 * touch.php
 *
 * Touch Payments integration module for ZenCart
 *
 * @copyright Copyright 2013 Touch Payments
 */

define( 'MODULE_PAYMENT_TOUCH_SERVER_LIVE', 'app.touchpayments.com.au' );
define( 'MODULE_PAYMENT_TOUCH_SERVER_TEST', 'test.touchpayments.com.au' );

require_once __DIR__ . '/../../Touch/Address.php';
require_once __DIR__ . '/../../Touch/Client.php';
require_once __DIR__ . '/../../Touch/Customer.php';
require_once __DIR__ . '/../../Touch/Item.php';
require_once __DIR__ . '/../../Touch/Order.php';
require_once __DIR__ . '/../../Touch/Api.php';

class touch extends base
{
    /**
     * $code string repesenting the payment method
     *
     * @var string
     */
    var $code;

    /**
     * $title is the displayed name for this payment method
     *
     * @var string
     */
    var $title;

    /**
     * $description is a soft name for this payment method
     *
     * @var string
     */
    var $description;

    /**
     * $enabled determines whether this module shows or not... in catalog.
     *
     * @var boolean
     */
    var $enabled;

    /**
     * @var Touch_Api
     */
    var $api;

    /**
     * @var boolean
     */
    var $is_api_active;

    /**
     * @var float
     */
    var $cart_limit;

    /**
     * The Touch Payments payment gateway constructor
     *
     * @return touch
     */
    function touch()
    {
        // Variable initialization
        global $order;
        $this->code = 'touch';
        $this->codeVersion = '1.0.0';

        // Set payment module title in Admin
        if (IS_ADMIN_FLAG === true) {
            $this->title = MODULE_PAYMENT_TOUCH_TEXT_ADMIN_TITLE;

            // Check if in test mode
            if (IS_ADMIN_FLAG === true && MODULE_PAYMENT_TOUCH_SERVER == 'Test') {
                $this->title .= '<span class="alert"> (test mode active)</span>';
            }
        } // Set payment module title in Catalog
        else {
            $this->title = MODULE_PAYMENT_TOUCH_TEXT_CATALOG_TITLE;
        }

        // Set other payment module variables
        $this->description = MODULE_PAYMENT_TOUCH_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_TOUCH_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_TOUCH_STATUS == 'True') ? true : false);

        if ((int)MODULE_PAYMENT_TOUCH_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_TOUCH_ORDER_STATUS_ID;
        }

        // Set posting destination destination
        if (MODULE_PAYMENT_TOUCH_SERVER == 'Test') {
            $this->form_action_url = 'https://' . MODULE_PAYMENT_TOUCH_SERVER_TEST;
        } else {
            $this->form_action_url = 'https://' . MODULE_PAYMENT_TOUCH_SERVER_LIVE;
        }

        $this->api = new Touch_Api($this->form_action_url . '/api', MODULE_PAYMENT_TOUCH_MERCHANT_KEY, '/touch_handler.php?token=');

        $this->form_action_url .= '/check/index/token/';


        // Cart limit that will enable Touch as a payment method
        $result = $this->api->getMaximumCheckoutValue();
        $this->cart_limit = $result->result;

        // See if the API is active at all
        $result = $this->api->isApiActive();
        $this->is_api_active = $result->result;

        if (is_object($order)) {
            $this->update_status();
        }

        // Check for right version
        if (PROJECT_VERSION_MAJOR != '1' && substr(PROJECT_VERSION_MINOR, 0, 3) != '3.9') {
            $this->enabled = false;
        }
    }

    /**
     * Calculate whether this module should display to customers or not.
     */
    function update_status()
    {
        global $order;
        $is_available = false;

        if (MODULE_PAYMENT_TOUCH_MERCHANT_KEY != '' && $this->is_api_active
            && $order->info['total'] <= $this->cart_limit
        ) {
            $is_available = true;
        }

        $this->enabled = $is_available;
    }

    /**
     * Triggers when an order status changes. We use it to set the order as Shipped in Touch
     *
     * @param $oID
     * @param $status
     * @param $comments
     * @param $customer_notified
     * @param $oldStatus
     *
     * @return boolean
     */
    function _doStatusUpdate($oID, $status, $comments, $customer_notified, $oldStatus)
    {
        global $messageStack;

        if ($status == MODULE_PAYMENT_TOUCH_ORDER_SHIPPED_STATUS_ID) {

            $response = $this->api->setOrderItemsShipped($oID);
            $sqlArray = array();

            if (isset($response->error)) {
                $addMessage = '';
                if (isset($response->error->message)) {
                    $addMessage = $response->error->message;
                }

                $message = 'Touch Payments could not activate the order!! ' . $addMessage;
                $messageStack->add_session($message, 'warning');

                $sqlArray = array(
                    'orders_id'         => $oID,
                    'orders_status_id'  => 0,
                    'date_added'        => 'now()',
                    'customer_notified' => '0',
                    'comments'          => $message,
                );

            } else {
                $sqlArray = array(
                    'orders_id'         => $oID,
                    'orders_status_id'  => MODULE_PAYMENT_TOUCH_ORDER_SHIPPED_STATUS_ID,
                    'date_added'        => 'now()',
                    'customer_notified' => '0',
                    'comments'          => 'Order activated in Touch Payments.',
                );
            }

            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sqlArray);
        }
    }

    /**
     * @return string
     */
    function javascript_validation()
    {
        return (false);
    }

    /**
     * Displays payment method name along with Credit Card Information
     * Submission Fields (if any) on the Checkout Payment Page.
     *
     * @return array
     */
    function selection()
    {
        return array(
            'id'     => $this->code,
            'module' => MODULE_PAYMENT_TOUCH_TEXT_CATALOG_LOGO,
            'icon'   => MODULE_PAYMENT_TOUCH_TEXT_CATALOG_LOGO
        );
    }

    /**
     * Generate the Touch Payments order and send it to Touch
     *
     * @throws Exception
     * @return boolean
     */
    function pre_confirmation_check()
    {
        // Variable initialization
        global $order;

        // If we have an SMS code here is because the user is sending the order already
        if (isset($_POST['sms-code'])) {
            zen_redirect('/touch_handler.php?token=' . $_POST['token'] . '&sms-code=' . $_POST['sms-code']);
            exit;
        } elseif (isset($_GET['status']) && $_GET['status'] == 'sms-error') {
            $this->form_action_url = '';
            return true;
        }


        $fee = $this->api->getFee($order->info['total']);
        if ($fee) {
            $_SESSION['touch_fee'] = $fee->result;
        }

        $response = $this->api->generateOrder($this->getTouchOrder());
        $_SESSION['current_token'] = $response;

        if (isset($response->error)) {
            throw new Exception($response->error->message);
        }

        if (isset($response->result->approvalSmsSent) && $response->result->approvalSmsSent) {
            $this->form_action_url = '';
        } elseif (!empty($response->result->token)) {
            $this->form_action_url .= $response->result->token;
        }

        // We need to persist the order id so we can retrieve it from token when calling back
        session_start();
        $_SESSION[$response->result->token] = $order->id;

        return (true);
    }

    /**
     * Skipped
     *
     * @return bool
     */
    function confirmation()
    {
        return (false);
    }

    /**
     * @return string
     */
    function process_button()
    {
        if (!empty($_SESSION['current_token'])) {
            $response = $_SESSION['current_token'];

            if (isset($response->result->approvalSmsSent)) {
                $html = '<input type="hidden" name="token" value="' . $_SESSION['current_token']->result->token . '">
                    <div style="text-align: center; margin-bottom: 10px;margin-top: 20px;">
                        <img src="/images/touch/touch-logo@2X.png" width="203" height="34">
                    </div>
                    <p>We have sent you a code via SMS to the mobile number you registered with Touch Payments. Please enter it
                    in the box below to complete your purchase.
                    <div style="text-align: center; padding: 10px auto 30px;">';

                if (isset($_GET['status']) && $_GET['status'] == 'sms-error') {
                    $html .= '<div style="color: #CC3E62">The code you entered is incorrect. Please try again</div>';
                }

                $html .= '<input type="text" name="sms-code" placeholder="Enter your code here"
                            style="font-size: 18px;width: 200px;height:35px;line-height:35px;margin-top:1px">
                    </div>';

                return $html;
            }
        }

        return '';
    }

    function getTouchOrder()
    {
        global $order;
        $addressShipping = new Touch_Address();
        $shipping = $order->delivery;

        $addressShipping->addressOne = $shipping['street_address'];
        $addressShipping->addressTwo = $shipping['suburb'];
        $addressShipping->suburb = $shipping['city'];
        $addressShipping->postcode = $shipping['postcode'];
        $addressShipping->firstName = $shipping['firstname'];
        $addressShipping->lastName = $shipping['lastname'];
        $addressShipping->setState($shipping['state']);


        $addressBilling = new Touch_Address();
        $billing = $order->customer;
        $addressBilling->addressOne = $billing['street_address'];
        $addressBilling->addressTwo = $billing['suburb'];
        $addressBilling->suburb = $billing['city'];
        $addressBilling->postcode = $billing['postcode'];
        $addressBilling->firstName = $billing['firstname'];
        $addressBilling->lastName = $billing['lastname'];
        $addressBilling->setState($billing['state']);

        $items = $order->products;

        $touchItems = array();

        /**
         * $item Mage_Sales_Model_Quote_Item
         */
        foreach ($items as $item) {
            $productId = array_shift(explode(':', $item['id']));

            $touchItem = new Touch_Item();
            $touchItem->sku = empty($item['model']) ? $productId : $item['model'];
            $touchItem->quantity = $item['qty'];
            $touchItem->description = $item['name'];
            $touchItem->price = $item['final_price'];

            preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', zen_get_products_image($productId), $image);
            if (count($image) > 1) {
                $touchItem->image = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $image[1];
            }

            $touchItems[] = $touchItem;

        }

        $customer = new Touch_Customer();

        $customer->email = $billing['email_address'];
        $customer->firstName = $billing['firstname'];
        $customer->lastName = $billing['lastname'];
        $customer->telephoneMobile = $billing['telephone'];

        $touchOrder = new Touch_Order();
        $grandTotal = $order->info['total'] - (isset($_SESSION['touch_fee']) ? $_SESSION['touch_fee'] : 0);

        $touchOrder->addressBilling = $addressBilling;
        $touchOrder->addressShipping = $addressShipping;
        $touchOrder->grandTotal = $grandTotal;
        $touchOrder->shippingCosts = $order->info['shipping_cost'] + $order->info['shipping_tax'];
        $touchOrder->gst = $order->info['tax'];
        $touchOrder->items = $touchItems;
        $touchOrder->customer = $customer;

        return $touchOrder;

    }

    function before_process()
    {
        // If page was called correctly with "referer" tag
        if (isset($_GET['referer']) && strcasecmp($_GET['referer'], 'touch') == 0) {
            $this->notify('NOTIFY_PAYMENT_TOUCH_RETURN_TO_STORE');

            // Redirect to the checkout success page
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
        } else {
            // Redirect to the payment page
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }
    }

    /**
     * @param $zf_domain
     *
     * @return bool
     */
    function check_referrer($zf_domain)
    {
        return (true);
    }

    /**
     * @return bool
     */
    function after_process()
    {
        // Set 'order not created' flag
        $_SESSION['order_created'] = '';

        return (false);
    }

    /**
     * @return boolean
     */
    function output_error()
    {
        return (false);
    }

    /**
     * Check to see whether module is installed
     *
     * @return boolean
     */
    function check()
    {
        // Variable initialization
        global $db;

        if (!isset($this->_check)) {
            $check_query = $db->Execute(
                "SELECT `configuration_value`
                FROM " . TABLE_CONFIGURATION . "
                WHERE `configuration_key` = 'MODULE_PAYMENT_TOUCH_STATUS'"
            );
            $this->_check = $check_query->RecordCount();
        }

        return ($this->_check);
    }

    /**
     * Installs Touch Payments module in zenCart and creates necessary
     * configuration fields which need to be supplied by store owner.
     */
    function install()
    {
        // Variable Initialization
        global $db;

        //// Insert configuration values
        // MODULE_PAYMENT_TOUCH_STATUS (Default = False)
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Enable Touch Payments?', 'MODULE_PAYMENT_TOUCH_STATUS', 'False', 'Do you want to enable Touch Payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now() )"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Merchant Key', 'MODULE_PAYMENT_TOUCH_MERCHANT_KEY', '', 'Your Merchant Key from Touch Payments', '6', '0', now() )"
        );
        // MODULE_PAYMENT_TOUCH_SERVER (Default = Test)
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Environment', 'MODULE_PAYMENT_TOUCH_SERVER', 'Test', 'Select the environment to use', '6', '0', 'zen_cfg_select_option(array(\'Live\', \'Test\'), ', now() )"
        );
        // MODULE_PAYMENT_TOUCH_SORT_ORDER (Default = 0)
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Sort Display Order', 'MODULE_PAYMENT_TOUCH_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())"
        );

        // MODULE_PAYMENT_TOUCH_PREPARE_ORDER_STATUS_ID
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added )
            VALUES( 'Set Order Shipped Status', 'MODULE_PAYMENT_TOUCH_ORDER_SHIPPED_STATUS_ID', '3', 'Select the status that reflects the order has been Shipped', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())"
        );

        //// Create tables
        $tables = array();
        $result = $db->Execute("SHOW TABLES LIKE 'touch%'");
        $fieldName = 'Tables_in_' . DB_DATABASE . ' (touch%)';

        while (!$result->EOF) {
            $tables[] = $result->fields[$fieldName];
            $result->MoveNext();
        }

        $this->notify('NOTIFY_PAYMENT_TOUCH_INSTALLED');
    }

    /**
     * Uninstalls the Touch Payments module from ZenCart
     */
    function remove()
    {
        // Variable Initialization
        global $db;

        // Remove all configuration variables
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
            WHERE `configuration_key` LIKE 'MODULE\_PAYMENT\_TOUCH\_%'"
        );

        $this->notify('NOTIFY_PAYMENT_TOUCH_UNINSTALLED');
    }

    /**
     * @return array
     */
    function keys()
    {
        // Variable initialization
        $keys = array(
            'MODULE_PAYMENT_TOUCH_STATUS',
            'MODULE_PAYMENT_TOUCH_MERCHANT_KEY',
            'MODULE_PAYMENT_TOUCH_SERVER',
            'MODULE_PAYMENT_TOUCH_SORT_ORDER',
            'MODULE_PAYMENT_TOUCH_ORDER_SHIPPED_STATUS_ID',
        );

        return ($keys);
    }

    /**
     * @param $insert_id
     *
     * @return bool
     */
    function after_order_create($insert_id)
    {
        return (false);
    }
}
