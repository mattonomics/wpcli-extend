<?php
/*
Plugin Name: WP-CLI Extension
Version: 0.1-alpha
Description: Extend the functionality of WP-CLI on a per site or network basis.
Author: Matt Gross
Author URI: http://mattonomics.com
Plugin URI: http://mattonomics.com
Text Domain: wpcli_ext
License: GPLv2
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	
	class wpcli_extend extends WP_CLI_COMMAND {
		
		public function resalt() {
			WP_CLI::line( __( 'Fetching the new keys…', 'wpcli_ext' ) );
			
			$response = wp_remote_get('https://api.wordpress.org/secret-key/1.1/salt/');
			
			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				WP_CLI::error( __( 'Failed to fetch the new keys.', 'wpcli_ext' ) );
				return false;
			}
			
			$new_keys = trim( wp_remote_retrieve_body( $response ) );
			$new_keys_array = explode( "\n", $new_keys );
			
			$wp_config = trailingslashit( ABSPATH ) . 'wp-config.php';
			$current_file = trim( file_get_contents( $wp_config ) );
			$current_file_array = explode( "\n", $current_file );
			
			$definitions = implode( '|', array(
				'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'
			) );
			
			WP_CLI::line( __( 'Replacing old keys…', 'wpcli_ext' ) );
			
			$new_file_array = array();
			foreach ( $current_file_array as $position => $line ) {
				if ( preg_match( '/('. $definitions. ')/', $line ) ) {
					$new_file_array[$position] = array_shift( $new_keys_array );
				} else {
					$new_file_array[$position] = $current_file_array[$position];
				}
			}
			
			if ( empty( $new_keys_array ) ) {
				if ( file_put_contents( $wp_config, implode( "\n", $new_file_array ) ) ) {
					// say something with cli
					WP_CLI::success( __( 'New salts added!', 'wpcli_ext' ) );
				}
			} else {
				WP_CLI::error( __( 'Failed to add new salts.', 'wpcli_ext' ) );
			}
		}
	}

	WP_CLI::add_command( 'extend', 'wpcli_extend' );
	
}