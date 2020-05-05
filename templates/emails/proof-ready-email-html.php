<?php
/**
 * Customer "Proof Ready" order status email
 */
$order = new WC_order( $item_data->order_id );
$opening_paragraph = __( '%s, Your proof is ready! The details of the item are as follows:', 'proof-ready-email' );

?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php
$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
$billing_last_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_last_name : $order->get_billing_last_name();
if ( $order && $billing_first_name && $billing_last_name ) : ?>
    <p><?php printf( $opening_paragraph, $billing_first_name . ' ' . $billing_last_name ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
    <tbody>
    <tr>
        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Ordered Product', 'proof-ready-email' );
            ?></th>
        <td style="text-align:left; border: 1px solid #eee;"><?php echo $item_data->product_title; ?></td>
    </tr>
    <tr>
        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Quantity', 'proof-ready-email' );
            ?></th>
        <td style="text-align:left; border: 1px solid #eee;"><?php echo $item_data->qty; ?></td>
    </tr>
    <tr>
        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Total', 'proof-ready-email' );
            ?></th>
        <td style="text-align:left; border: 1px solid #eee;"><?php echo $item_data->total; ?></td>
    </tr>
    </tbody>
</table>

<p><?php _e( 'This is an email sent as the order status has been changed to "Proof Ready".', 'proof-ready-email' );
?></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer' ); ?>
