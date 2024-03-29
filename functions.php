<?php

//ENQUEUE CSS/JS
require get_template_directory() . '/inc/enqueue.php';

//THEME SUPPORT FUNCTIONS
require get_template_directory() . '/inc/theme-support.php';

//WALKER FUNCTIONS
require get_template_directory() . '/inc/walker.php';

//CUSTOM POST TYPE FUNCTIONS
require get_template_directory() . '/inc/custom-post-type.php';

//CUSTOM AJAX FUNCTIONS
require get_template_directory() . '/inc/ajax.php'; 

//CUSTOM WOOCOMMERCE FUNCTIONS
require get_template_directory() . '/inc/woocommerce-functions.php'; 

//SHORTCODE FUNCTIONS
require get_template_directory() . '/inc/shortcodes.php'; 



/** @constant string THEME_NAME **/
define( 'THEME_NAME', get_option('stylesheet') );
/**
 * Custom script
 */
 function my_scripts_method() {
    wp_enqueue_script(
        'custom-script',
        get_stylesheet_directory_uri() . '/js/main.js',
        array( 'jquery' ),
        '1.2'
    );
    if ( !is_admin() ) {
        /** */
		wp_localize_script( 'custom-script', 'ajax', array(
            'url' =>            admin_url( 'admin-ajax.php' ),
            'ajax_nonce' =>     wp_create_nonce( 'noncy_nonce' ),
            'assets_url' =>     get_stylesheet_directory_uri(),
		) );
    }	
}
add_action( 'wp_enqueue_scripts', 'my_scripts_method' );
/**
 * Ajax newsletter
 * 
 * @url http://www.thenewsletterplugin.com/forums/topic/ajax-subscription
 */
function realhero_ajax_subscribe() {
    check_ajax_referer( 'noncy_nonce', 'nonce' );
    $data = urldecode( $_POST['data'] );
    if ( !empty( $data ) ) :
        $data_array = explode( "&", $data );
        $fields = [];
        foreach ( $data_array as $array ) :
            $array = explode( "=", $array );
            $fields[ $array[0] ] = $array[1];
        endforeach;
    endif;
    if ( !empty( $fields ) ) :
        global $wpdb;
		
		// check if already exists
		
		/** @var int $count **/
		$count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}newsletter WHERE email = %s", $fields['ne'] ) );
		
		if( $count > 0 ) {
	        $output = array(
	            'status'    => 'error',
	            'msg'       => __( 'Already in a database.', THEME_NAME )
	        );
        } elseif( !defined( 'NEWSLETTER_VERSION' ) ) {
            $output = array(
	            'status'    => 'error',
	            'msg'       => __( 'Please install & activate newsletter plugin.', THEME_NAME )
	        );           
        } else {
            /**
             * Generate token
             */
            
            /** @var string $token */
            $token =  wp_generate_password( rand( 10, 50 ), false );
	        $wpdb->insert( $wpdb->prefix . 'newsletter', array(
	                'email'         => $fields['ne'],
	                'status'        => $fields['na'],
                    'http_referer'  => $fields['nhr'],
                    'token'         => $token,
	            )
            );
            $opts = get_option('newsletter');
            $opt_in = (int) $opts['noconfirmation'];
            // This means that double opt in is enabled
            // so we need to send activation e-mail
            if ($opt_in == 0) {
                $newsletter = Newsletter::instance();
                $user = NewsletterUsers::instance()->get_user( $wpdb->insert_id );
                NewsletterSubscription::instance()->mail($user->email, $newsletter->replace($opts['confirmation_subject'], $user), $newsletter->replace($opts['confirmation_message'], $user));
            }
	        $output = array(
	            'status'    => 'success',
	            'msg'       => __( 'Thank you!', THEME_NAME )
	        );	
		}
		
    else :
        $output = array(
            'status'    => 'error',
            'msg'       => __( 'An Error occurred. Please try again later.', THEME_NAME  )
        );
    endif;
	
    wp_send_json( $output );
}
add_action( 'wp_ajax_realhero_subscribe', 'realhero_ajax_subscribe' );
add_action( 'wp_ajax_nopriv_realhero_subscribe', 'realhero_ajax_subscribe' );


