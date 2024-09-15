<?php


// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit;
}


/**
 * Calls the class.
 */
function sliced_call_edit_profile_class() {
    new Sliced_Edit_Profile();
}
add_action( 'sliced_loaded', 'sliced_call_edit_profile_class' );


/** 
 * The Class.
 */
class Sliced_Edit_Profile {

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct() {

        add_action( 'sliced_before_edit_profile_form', array( $this, 'frontend_edit_profile' ) );

    }

    /**
     * Register the form and fields for our front-end submission form.
     *
     * @since 1.0.0
     */
    public function frontend_edit_profile() {

        /* Get user info. */
        global $current_user, $wp_roles;

        $error = array();   
        $output = '';   


        /* If profile was saved, update profile. */
        if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'update-user' ) {
            
            if ( ! isset( $_POST['update_user_nonce'] ) || ! wp_verify_nonce( $_POST['update_user_nonce'], 'update-user' ) )
            die;

            /* Update user password. */
            if ( !empty($_POST['pass1'] ) || !empty( $_POST['pass2'] ) ) {
                if ( $_POST['pass1'] == $_POST['pass2'] )
                    wp_update_user( array( 'ID' => $current_user->ID, 'user_pass' => esc_attr( $_POST['pass1'] ) ) );
                else
                    $error[] = __('The passwords you entered do not match.', 'sliced-invoices-client-area');
            }

            /* Update user information. */
            if ( !empty( $_POST['url'] ) )
                wp_update_user( array( 'ID' => $current_user->ID, 'user_url' => esc_url( $_POST['url'] ) ) );
            if ( !empty( $_POST['email'] ) ){
                if (!is_email(esc_attr( $_POST['email'] )))
                    $error[] = __('Email address is not valid.', 'sliced-invoices-client-area');
                elseif(email_exists(esc_attr( $_POST['email'] )) != $current_user->ID )
                    $error[] = __('Email address is already taken.', 'sliced-invoices-client-area');
                else{
                    wp_update_user( array ('ID' => $current_user->ID, 'user_email' => esc_attr( $_POST['email'] )));
                }
            }

            if ( !empty( $_POST['first-name'] ) )
                update_user_meta( $current_user->ID, 'first_name', esc_attr( $_POST['first-name'] ) );
            if ( !empty( $_POST['last-name'] ) )
                update_user_meta($current_user->ID, 'last_name', esc_attr( $_POST['last-name'] ) );
            if ( !empty( $_POST['business'] ) ) {
                update_user_meta( $current_user->ID, '_sliced_client_business', esc_attr( $_POST['business'] ) );
            } else {
                $error[] = __('Business/Client Name must not be empty.', 'sliced-invoices-client-area');
            }
            if ( !empty( $_POST['address'] ) )
                update_user_meta( $current_user->ID, '_sliced_client_address', wp_kses_post( $_POST['address'] ) );
            if ( !empty( $_POST['extra_info'] ) )
                update_user_meta( $current_user->ID, '_sliced_client_extra_info', wp_kses_post( $_POST['extra_info'] ) );

            // display the error or success message
            if ( count($error) == 0 ) {
                $output .= '<p class="sliced-message success">' . __( 'Your client details were successfully updated.', 'sliced-invoices-client-area' ) . '</p>';
            } else { 
                $output .= '<p class="sliced-message error">';
                foreach ($error as $key => $value) {
                    $output .= $value . '<br>';
                }
                $output .= '</p>';
            };

            echo $output;
         
        }


    }


}