<?php
/**
 * WP Mixpanel Short Codes
 *
 * @package     WP Mixpanel
 * @subpackage  Short Codes
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WP_Mixpanel_Shortcodes {

	function __construct() {

		add_shortcode( 'mixpanel', array( $this, 'shortcode' ) );
	}


	public function shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'event'      => '',
			'properties' => ''
		), $atts );

		$props = array();
		$properties = explode( ',', $atts['properties'] );
		foreach( $properties as $prop ) :
			$ps = explode( ':', $prop );
			$props[ $ps[0] ] = $ps[1];
		endforeach;

		return wp_mixpanel()->track( $atts['event'], $props, false );
	}

}