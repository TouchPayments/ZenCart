<?php
/**
 * Lanugage defines for Touch Payments payment module
 *
 * @copyright Copyright 2013 Touch Payments
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

define( 'MODULE_PAYMENT_TOUCH_TEXT_ADMIN_TITLE', 'Touch Payments' );
define( 'MODULE_PAYMENT_TOUCH_TEXT_CATALOG_TITLE', 'Touch Payments' );

if( IS_ADMIN_FLAG === true )
    define( 'MODULE_PAYMENT_TOUCH_TEXT_DESCRIPTION',
        '<div style="padding:20px;text-align:center;"><img src="/' . DIR_WS_IMAGES .'touch/touch-logo@2X.png" width="205" height="34"><br /><br />'.
        'Click "install" above to enable Touch Payments support and "edit" to tell Zen Cart your Touch Payments settings</div>');
else
    define( 'MODULE_PAYMENT_TOUCH_TEXT_DESCRIPTION', '<strong>Touch</strong>');

define( 'MODULE_PAYMENT_TOUCH_BUTTON_IMG', DIR_WS_IMAGES .'touch/logo_small.png' );
define( 'MODULE_PAYMENT_TOUCH_BUTTON_ALT', 'Checkout with Touch Payments' );
define( 'MODULE_PAYMENT_TOUCH_ACCEPTANCE_MARK_TEXT', 'Select Touch to pay only after receiving your order so you can touch your latest online purchase before being charged for it.' );

define( 'MODULE_PAYMENT_TOUCH_TEXT_CATALOG_LOGO',
    'Touch Payments '.
    '<img src="'. MODULE_PAYMENT_TOUCH_BUTTON_IMG .'"'.
    ' alt="'. MODULE_PAYMENT_TOUCH_BUTTON_ALT .'"'.
    ' title="' . MODULE_PAYMENT_TOUCH_BUTTON_ALT .'"'.
    ' style="vertical-align: text-bottom; border: 0px;" border="0"/>&nbsp;'.
    '<div style="margin: 10px; padding: 10px; border: 1px solid #eee;">' . MODULE_PAYMENT_TOUCH_ACCEPTANCE_MARK_TEXT . '</div>' );

define( 'MODULE_PAYMENT_TOUCH_ENTRY_FIRST_NAME', 'First Name:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_LAST_NAME', 'Last Name:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_BUSINESS_NAME', 'Business Name:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_NAME', 'Address Name:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_STREET', 'Address Street:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_CITY', 'Address City:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_STATE', 'Address State:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_ZIP', 'Address Zip:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_COUNTRY', 'Address Country:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_EMAIL_ADDRESS', 'Payer Email:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_EBAY_ID', 'Ebay ID:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PAYER_ID', 'Payer ID:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PAYER_STATUS', 'Payer Status:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_ADDRESS_STATUS', 'Address Status:' );

define( 'MODULE_PAYMENT_TOUCH_ENTRY_PAYMENT_TYPE', 'Payment Type:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PAYMENT_STATUS', 'Payment Status:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PENDING_REASON', 'Pending Reason:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_INVOICE', 'Invoice:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PAYMENT_DATE', 'Payment Date:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_CURRENCY', 'Currency:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_GROSS_AMOUNT', 'Gross Amount:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PAYMENT_FEE', 'Payment Fee:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_CART_ITEMS', 'Cart items:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_TXN_TYPE', 'Trans. Type:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_TXN_ID', 'Trans. ID:' );
define( 'MODULE_PAYMENT_TOUCH_ENTRY_PARENT_TXN_ID', 'Parent Trans. ID:' );

define( 'MODULE_PAYMENT_TOUCH_PURCHASE_DESCRIPTION_TITLE', STORE_NAME .' purchase, Order #' );
