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

// require('vars.php');
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

if ( is_admin() ) { // admin actions
    add_action('admin_menu', 'fountain_plus_menu');
    add_action('admin_init', 'fountain_register_settings');
}



function fountain_register_settings() {
    global $default_options;

    $args = array(
        'sanitize_callback' => 'sanitize_fountain_options',
    );

    register_setting('fountain_plus_group', 'fountain_plus_options',  $args);
    add_settings_section('fountain_plus_main', 'Fountain Plus Settings', 'fountain_plus_settings_text', 'fountain_plus');
    add_settings_field('script_style', 'Script Style', 'fountain_plus_script_style', 'fountain_plus', 'fountain_plus_main');
    add_settings_field('punctuation', 'Punctuation', 'fountain_plus_punctuation', 'fountain_plus', 'fountain_plus_main');
    add_settings_section('fountain_plus_features', 'Optional Features', 'fountain_plus_features_text', 'fountain_plus');
    add_settings_field('use_additions', 'Additions', 'fountain_plus_use_additions', 'fountain_plus', 'fountain_plus_features');
    add_settings_field('use_deletions', 'Deletions', 'fountain_plus_use_deletions', 'fountain_plus', 'fountain_plus_features');
}

function fountain_plus_settings_text() {
    // echo "<p>Description will go here.</p>\n";
}

function fountain_plus_features_text() {
    // echo "<h3>Optional features</h3>\n";
}

function fountain_plus_script_style() {
    global $script_style_options, $default_options;
    $options = get_option('fountain_plus_options', $default_options);

    $html = '<select name="fountain_plus_options[script_style]">';
    foreach ($script_style_options as $s) {
        $selected = ($options['script_style'] == $s) ? ' selected' : '';
        $html .= "<option name=\"$s\" $selected>$s</option>";
    }
    $html .= "</select>\n";
    echo $html;
}

function fountain_plus_punctuation() {
    global $punctuation_options, $default_options;
    $options = get_option('fountain_plus_options', $default_options);

    $html = '<select name="fountain_plus_options[punctuation]">';
    foreach ($punctuation_options as $s) {
        $selected = ((isset($options['punctuation']) ? $options['punctuation'] : null) == $s) ? ' selected' : '';
        $html .= "<option name=\"$s\" $selected>$s</option>";
    }
    $html .= "</select>\n";
    echo $html;
}

function fountain_plus_use_additions() {
    global $default_options;
    $options = get_option('fountain_plus_options', $default_options);

    $html = '<input type="checkbox" id="fountain_plus_use_additions" name="fountain_plus_options[use_additions]" value="1"' . checked( 1, isset($options['use_additions']) ? $options['use_additions'] : null, false ) . '/>';
    $html .= ' <label for="fountain_plus_use_additions">Additions</label>';
    echo $html;
}

function fountain_plus_use_deletions() {
    global $default_options;
    $options = get_option('fountain_plus_options', $default_options);

    $html = '<input type="checkbox" id="fountain_plus_use_deletions" name="fountain_plus_options[use_deletions]" value="1"' . checked( 1, isset($options['use_deletions']) ? $options['use_deletions'] : null, false ) . '/>';
    $html .= ' <label for="fountain_plus_use_deletions">Deletions</label>';
    echo $html;
}

function sanitize_select($value, $options, $default) {
    if (! $value && ! in_array($value, $options)) {
        $sanitized_value = $default;
    } else {
        $sanitized_value = $value;
    }
    return $sanitized_value;
}

//  Validate and sanitize input
function sanitize_fountain_options($input) {
    $sanitized_input = array();

    $sanitized_input['script_style'] = sanitize_select(
        isset($input['script_style']) ? $input['script_style'] : null,
        $script_style_options,
        isset($default_options['script_style']) ? $default_options['script_style'] : null
    );
    $sanitized_input['punctuation'] = sanitize_select(
        isset($input['punctuation']) ? $input['punctuation'] : null,
        $punctuation_options,
        isset($default_options['punctuation']) ? $default_options['punctuation'] : null
    );

    $sanitized_input['use_additions'] = (isset($input['use_additions']) ? $input['use_additions'] : null) ? '1' : '';
    $sanitized_input['use_deletions'] = (isset($input['use_deletions']) ? $input['use_deletions'] : null) ? '1' : '';

    return $sanitized_input;
}

function build_fountain($text) {
    $wrap_before = '';
    $wrap_after  = '';

    $text = fountainToHTML($text, $wrap_before, $wrap_after);  // see fountain.php for details
    return $text;
}


function fountain_plus_options_panel() {
    global $default_options;
    $settings = get_option('fountain_plus_options', $default_options);

    ?>
    <div class="wrap">
        <h2>Fountain Plus Options</h2>
        <form method="post" action="options.php">
            <?php 
                settings_fields('fountain_plus_options');
                do_settings_sections('fountain_plus');
                settings_fields( 'fountain_plus_group' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function fountain_plus_menu() {
    add_options_page('Fountain Plus Options', 'Fountain Plus', 8, __FILE__, 'fountain_plus_options_panel');
}

function fountain_plus_save_options() {
    var_dump($_POST);
    // Get all the options from the $_POST
    foreach ($fountain_plus_options as $key => $value) {
        $fountain_plus_options[$key] = isset($_POST[$key]) ? $_POST[$key] : null;
    }

    update_option('fountain_plus_options', $fountain_plus_options);
}

if ((isset($_POST['action']) ? $_POST['action'] : null) == 'save_options'){
    fountain_plus_save_options();
}
