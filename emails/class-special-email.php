<?php
/**
 * Special Email
 *
 * A special email sent to the admin when an order status is changed to "Special".
 *
 * @class       Special_Email
 * @extends     WC_Email
 *
 */
class Special_Email extends WC_Email {

    function __construct() {

        // Add email ID, title, description, heading, subject
        $this->id                   = 'special_email';
        $this->customer_email       = true;         // intended to be sent to customer
        $this->title                = __( 'Special', 'special-email' );
        $this->description          = __( 'This email is sent to cusstomers when an order status is changed to Special.', 'special-email' );

        $this->heading              = __( 'Special', 'special-email' );
        $this->subject              = __( '[{blogname}] Order for {product_title} (Order {order_number}) - {order_date}', 'special-email' );

        // email template path
        $this->template_html    = 'emails/special-email-html.php';
        $this->template_plain   = 'emails/plain/special-email-plain.php';

        // Triggers for this email
        add_action( 'custom_special_email_notification', array( $this, 'queue_notification' ) );
        add_action( 'custom_special_email_trigger_notification', array( $this, 'trigger' ) );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->template_base = CUSTOM_TEMPLATE_PATH;
        // default recipient to null. This field will be set when trigger pulls order information
        // and sets it to customer email
        $this->recipient     = null;
        // placeholders for form fields
        $this->placeholders  = array(
            '{order_date}'              => '',
            '{order_number}'            => '',
            '{order_billing_full_name}' => '',
        );


    }

    public function queue_notification( $order_id ) {

        $order = new WC_order( $order_id );
        $items = $order->get_items();
        // foreach item in the order
        foreach ( $items as $item_key => $item_value ) {
            // add an event for the item email, pass the item ID so other details can be collected as needed
            wp_schedule_single_event( time(), 'custom_special_email_trigger', array( 'item_id' => $item_key ) );
        }
    }

    // This function collects the data and sends the email
    function trigger( $item_id ) {

        $send_email = true;
        // validations
        if ( $item_id && $send_email ) {
            // create an object with item details like name, quantity etc.
            $this->object = $this->create_object( $item_id );

            // replace the merge tags with valid data
            $key = array_search( '{product_title}', $this->find );
            if ( false !== $key ) {
                unset( $this->find[ $key ] );
                unset( $this->replace[ $key ] );
            }

            $this->find[]    = '{product_title}';
            $this->replace[] = $this->object->product_title;

            if ( $this->object->order_id ) {

                $this->find[]    = '{order_date}';
                $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );

                $this->find[]    = '{order_number}';
                $this->replace[] = $this->object->order_id;
            } else {

                $this->find[]    = '{order_date}';
                $this->replace[] = __( 'N/A', 'special-email' );

                $this->find[]    = '{order_number}';
                $this->replace[] = __( 'N/A', 'special-email' );
            }

            // if no recipient is set, do not send the email
            if ( ! $this->get_recipient() ) {
                return;
            }
            // send the email
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(),
                array() );

        }
    }

    // Create an object with the data to be passed to the templates
    public function create_object( $item_id ) {

        global $wpdb;

        $item_object = new stdClass();

        // order ID
        $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                            WHERE order_item_id = %d";
        $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );

        $order_id = 0;
        if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
            $order_id = $get_order_id[0]->order_id;
        }
        $item_object->order_id = $order_id;

        $order = new WC_order( $order_id );
        $this->recipient     = $order->get_billing_email();

        // order date
        $post_data = get_post( $order_id );
        $item_object->order_date = $post_data->post_date;

        // product ID
        $item_object->product_id = wc_get_order_item_meta( $item_id, '_product_id' );

        // product name
        $_product = wc_get_product( $item_object->product_id );
        $item_object->product_title = $_product->get_title();

        // qty
        $item_object->qty = wc_get_order_item_meta( $item_id, '_qty' );

        // total
        $item_object->total = wc_price( wc_get_order_item_meta( $item_id, '_line_total' ) );

        // email adress
        $item_object->billing_email = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_email : $order->get_billing_email();

        // customer ID
        $item_object->customer_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->user_id : $order->get_user_id();

        return $item_object;

    }

    // return the html content
    function get_content_html() {
        ob_start();
        wc_get_template( $this->template_html, array(
            'item_data'       => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content()
        ), 'custom-templates', $this->template_base );
        return ob_get_clean();
    }

    // return the plain content
    function get_content_plain() {
        ob_start();
        wc_get_template( $this->template_plain, array(
            'item_data'       => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content()
        ), 'custom-templates', $this->template_base );
        return ob_get_clean();
    }

    // return the subject
    function get_subject() {

        $order = new WC_order( $this->object->order_id );
        return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );

    }

    // return the email heading
    public function get_heading() {

        $order = new WC_order( $this->object->order_id );
        return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );

    }

    // form fields that are displayed in WooCommerce->Settings->Emails
    function init_form_fields() {
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', 'special-email' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this email notification', 'special-email' ),
                'default' 		=> 'yes'
            ),
            'subject' => array(
                'title' 		=> __( 'Subject', 'special-email' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'special-email' ), $this->subject ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'heading' => array(
                'title' 		=> __( 'Email Heading', 'special-email' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'special-email' ), $this->heading ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'special-email' ),
                'description' => __( 'Text to appear below the main email content.', 'special-email' ) . ' ' .
                    $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'special-email' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title' 		=> __( 'Email type', 'special-email' ),
                'type' 			=> 'select',
                'description' 	=> __( 'Choose which format of email to send.', 'special-email' ),
                'default' 		=> 'html',
                'class'			=> 'email_type',
                'options'		=> array(
                    'plain'		 	=> __( 'Plain text', 'special-email' ),
                    'html' 			=> __( 'HTML', 'special-email' ),
                    'multipart' 	=> __( 'Multipart', 'special-email' ),
                )
            )
        );
    }

}
return new Special_Email();
?>
