<?php
/**
 * An email notifying that the order status is now "Special"
 */
$order = new WC_order( $item_data->order_id );

echo "= " . $email_heading . " =\n\n";

$opening_paragraph = __( 'An order, made by %s, has now been marked Special. The details of the item are as follows:',
    'special-email' );

$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
$billing_last_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_last_name : $order->get_billing_last_name();
if ( $order && $billing_first_name && $billing_last_name ) {
    echo sprintf( $opening_paragraph, $billing_first_name . ' ' . $billing_last_name ) . "\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Ordered Product: %s', 'special-email' ), $item_data->product_title ) . "\n";

echo sprintf( __( 'Quantity: %s', 'special-email' ), $item_data->qty ) . "\n";

echo sprintf( __( 'Total: %s', 'special-email' ), $item_data->total ) . "\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo __( 'This is an email sent as the order status has been changed to "Special".', 'special-email' ) .
    "\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
