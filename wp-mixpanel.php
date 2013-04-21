<?php
/**
 * Plugin Name: WP Mixpanel
 * Plugin URI: http://pippinsplugins.com/wp-mixpanel
 * Description: Easily track events on your WordPress site through Mixpanel
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Version: 1.0
 * Text Domain: wp-mixpanel
 * Domain Path: languages
 *
 * WP Mixpanel is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Mixpanel is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Mixpanel. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package WP Mixpanel
 * @category Core
 * @author Pippin Williamson
 * @version 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'WP_Mixpanel' ) ) :


/**
 * Main WP_Mixpanel Class
 *
 * @since 1.0
 */
final class WP_Mixpanel {

	/** Singleton *************************************************************/

	/**
	 * @var The one true WP_Mixpanel
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * @var string The plugin's version
	 * @since 1.0
	 */
	private $version;


	/**
	 * @var string The plugin's directory
	 * @since 1.0
	 */
	public $plugin_dir;

	/**
	 * @var string The Mixpanel project ID number
	 * @since 1.0
	 */
	public $mixpanel_project_id;


	/**
	 * @var string The Mixpanel API URL
	 * @since 1.0
	 */
	public $mixpanel_api_url = 'http://api.mixpanel.com/';


	/**
	 * Main WP_Mixpanel Instance
	 *
	 * Insures that only one instance of WP_Mixpanel exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 * @uses WP_Mixpanel::setup_properties() Setup the globals needed
	 * @uses WP_Mixpanel::includes() Include the required files
	 * @uses WP_Mixpanel::setup_actions() Setup the hooks and actions
	 * @see wp_mixpanel()
	 * @return The one true WP_Mixpanel
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WP_Mixpanel;
			self::$instance->setup_properties();
			self::$instance->includes();
			self::$instance->load_textdomain();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function setup_properties() {

		// Plugin version
		$this->version     = '1.0';

		// Plugin Folder Path
		$this->plugin_dir  = plugin_dir_path( __FILE__ );

	}


	/**
	 * Include required files
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function includes() {

		require_once $this->plugin_dir . 'includes/shortcodes.php';

	}


	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'wp_mixpanel_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'wp-mixpanel' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'wp-mixpanel', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/wp-mixpanel/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/wp-mixpanel folder
			load_textdomain( 'wp-mixpanel', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
			load_textdomain( 'wp-mixpanel', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'wp-mixpanel', false, $lang_dir );
		}
	}

	/**
	 * Load short codes, actions, filters, etc
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function init() {

		$shortcodes = new WP_Mixpanel_Shortcodes;

	}


	/**
	 * Set the project API/ID key
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	public function set_api_key( $key = '' ) {
		$this->mixpanel_project_id = $key;
	}


	/**
	 * Track an event
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	public function track_event( $event = '', $properties = array() ) {

		$params = array(
			'event'      => $event,
			'properties' => $properties
		);

		if ( !isset( $params['properties']['token'] ) ){
            $params['properties']['token'] = $this->mixpanel_project_id;
        }

		$url = $this->mixpanel_api_url . 'track/?data=' . base64_encode( json_encode( $params ) );

		$post_params = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $params,
			'cookies'     => array()
    	);

		$response = wp_remote_post( $url, $post_params );

	}



	/**
	 * Track a person. If the distinct_id already exists, the user will be updated
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	public function track_person( $distinct = '', $properties = array() ) {

		if( empty( $distinct ) )
			return; // A distinct_id is required

		$defaults = array(
			'first_name' => '',
			'last_name'  => '',
			'email'      => '',
			'ip'         => ''
		);

		$person_details = wp_parse_args( $properties, $defaults );

		$params = array(
			'$set'            => array(
				'$first_name' => $person_details['first_name'],
				'$last_name'  => $person_details['last_name'],
				'$email'      => $person_details['email']
			),
			'$token'          => $this->mixpanel_project_id,
			'$distinct_id'    => $distinct,
			'$ip'             => $person_details['ip']
		);

		// Set custom properties
		foreach( $properties as $key => $prop ) {
			if( ! isset( $params['$set'][ '$' . $key ] ) && $key !== 'ip' )
				$params['$set'][ $key ]  = $prop;
		}

		$url = $this->mixpanel_api_url . 'engage/?data=' . base64_encode( json_encode( $params ) );

		$post_params = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $params,
			'cookies'     => array()
    	);

		$response = wp_remote_post( $url, $post_params );
	}


	/**
	 * Track a transaction
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	public function track_transaction( $distinct = '', $transaction = array() ) {

		if( empty( $distinct ) )
			return; // A distinct_id is required

		$defaults = array(
			'time'   => date( 'c' ), // ISO timestamp
			'amount' => '0.00',
		);

		$details = wp_parse_args( $transaction, $defaults );

		$params = array(
			'$append'           => array(
				'$transactions' => array(
					'$time'     => $details['time'],
					'$amount'   => $details['amount']
				),
			),
			'$token'          => $this->mixpanel_project_id,
			'$distinct_id'    => $distinct
		);

		$url = $this->mixpanel_api_url . 'engage/?data=' . base64_encode( json_encode( $params ) );

		$post_params = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $params,
			'cookies'     => array()
    	);

		$response = wp_remote_post( $url, $post_params );

	}

}

endif; // End if class_exists check


/**
 * The main function responsible for returning the one true WP_Mixpanel
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $mixpanel = wp_mixpanel(); ?>
 *
 * @since 1.4
 * @return object The one true WP_Mixpanel Instance
 */
function wp_mixpanel() {
	return WP_Mixpanel::instance();
}

// Get wp_mixpanel Running
wp_mixpanel();