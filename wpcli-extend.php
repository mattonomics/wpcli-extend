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
			
			$new_keys 			= trim( wp_remote_retrieve_body( $response ) );
			$new_keys_array 	= explode( "\n", $new_keys );
			
			$wp_config 			= trailingslashit( ABSPATH ) . 'wp-config.php';
			$current_file 		= trim( file_get_contents( $wp_config ) );
			$current_file_array	= explode( "\n", $current_file );
			
			$definitions 		= implode( '|', array(
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
					WP_CLI::success( __( 'New salts added!', 'wpcli_ext' ) );
				}
			} else {
				WP_CLI::error( __( 'Failed to add new salts.', 'wpcli_ext' ) );
			}
		}
		
		/**
	     * Resets the database and preserves settings.
	     * 
	     * ## OPTIONS
	     * 
	     * [--preserve_plugins]
	     * : Set this to preserve the currently activated plugins.
		 *
	     * [--admin_user=admin]
	     * : The admin's username.
		 *
		 * [--admin_password=password]
	     * : The admin's password.
		 *
		 * [--permalink_structure=/%postname%/]
	     * : Permalink structure.
		 *
	     * ## EXAMPLES
	     * 
	     * wp extend reset --preserve_plugins
	     *
	     * @synopsis [--preserve_plugins]
	     */
		
		public function reset( $args, $flags ) {
			
			WP_CLI::confirm( 'Are you sure you want to reset the database?' );
			
			$options = array(
				'--url=' . site_url(),
				'--title=' . str_replace( ' ', '\ ', get_option( 'blogname' ) ),
				'--admin_user=' . ( !empty( $flags['admin_user'] ) ? $flags['admin_user'] : 'admin' ),
				'--admin_password=' . ( !empty( $flags['admin_password'] ) ? $flags['admin_password'] : 'password' ),
				'--admin_email=' . get_option( 'admin_email' )
			);
			
			$activated = array();
			if ( isset( $flags['preserve_plugins'] ) ) {
				foreach ( get_option( 'active_plugins' ) as $plugin ) {
					$plugin_name = explode( '/', $plugin );
					$plugin_name = array_shift( $plugin_name );
					if ( strpos( $plugin_name, '.' ) !== false) {
						$plugin_name = explode( '.', $plugin_name );
						array_pop( $plugin_name );
						$activated[] = implode( '', $plugin_name );
					} else {
						$activated[] = $plugin_name;
					}
				}
			}
			
			$permalinks = '/%postname%/';
			if ( !empty( $flags['permalink_structure'] ) ) {
				$permalinks = $flags['permalink_structure'];
			}
			
			WP_CLI::launch( 'wp db reset --yes' );
			
			WP_CLI::launch( 'wp core install ' . implode( ' ', $options ) );
			
			WP_CLI::launch( "wp option update permalink_structure $permalinks" );
			
			flush_rewrite_rules();
			
			if ( !empty( $activated ) ) {
				foreach ( $activated as $activate ) {
					WP_CLI::launch( "wp plugin activate $activate" );
				}
			}
			
			WP_CLI::success( 'Database has been reset.' );
		}
	}

	WP_CLI::add_command( 'extend', 'wpcli_extend' );
	
}