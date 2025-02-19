<?php
/**
* Plugin Name: Webwijs - Global search & replace
* Description: Met deze plugin kun je woorden globally zoeken en vervangen
* Version: 1
* Author: Webwijs - Egbert
* Author URI: https://www.webwijs.nu
**/

/**
 * Include scripts and styles
 */

// Load dependencies
include_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';
include_once plugin_dir_path(__FILE__) . 'includes/search-replace.php';
include_once plugin_dir_path(__FILE__) . 'includes/database-handler.php';

add_action( 'admin_enqueue_scripts', function(){
    wp_enqueue_script( 'main-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], '0.1', false );
    wp_enqueue_style( 'admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin.min.css', '0.1' );
} );

add_action('wp_ajax_apply_changes', 'gsr_apply_changes_ajax');

function gsr_apply_changes_ajax() {
    if (!isset($_POST['search_text'], $_POST['replace_text'], $_POST['tables'])) {
        wp_send_json_error(["message" => "Missing required fields"]);
        return;
    }

    $search = sanitize_text_field($_POST['search_text']);
    $replace = sanitize_text_field($_POST['replace_text']);
    $use_regex = isset($_POST['use_regex']);
    $tables = $_POST['tables'];

    error_log("AJAX request received: Search [$search] Replace [$replace] on Tables: " . print_r($tables, true));

    gsr_apply_changes($search, $replace, $use_regex, $tables);

    wp_send_json_success(["message" => "Changes Applied Successfully"]);
}
