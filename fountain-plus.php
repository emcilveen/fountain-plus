<?php
/**
 * @package Fountain Plus
 */
/*
Plugin Name: Fountain Plus
Plugin URI: https://elimcilveen.com/
Description: An improved parser for Fountain-formatted scripts.
Version: 0.0.0
Author: Eli McIlveen
Author URI: http://elimcilveen.com
*/


// Block direct access
if ( !function_exists( 'add_action' ) ) {
	echo 'This is a plugin.';
	exit;
}

require('fountain.php');

if ( ! defined( 'WP_CONTENT_URL' ) )
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

add_filter('the_content', 'build_fountain', 1, 1);
add_filter('comment_text', 'build_fountain', 1, 1);

function build_fountain($text) {
    $wrap_before = '';
    $wrap_after  = '';

    $text = fountainToHTML($text, $wrap_before, $wrap_after);  // see fountain.php for details
    return $text;
}


// Options & Admin Stuff
$default_options  = array('width' => '400', 'bg_color' => '#FFFFFC', 'text_color' => '#000000', 'border_style' => 'Simple', 'alignment' => 'Left');

if(!get_option('fountain_options')) {
    update_option('fountain_options', $default_options); // create the defaults
}