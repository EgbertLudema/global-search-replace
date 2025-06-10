<?php
/**
 * Plugin Name: Webwijs - Global search & replace
 * Description: Met deze plugin kun je woorden globaal zoeken en vervangen, hierbij krijg je eerst een preview van de zoekresultaten te zien waarna je kunt selecteren wat je wil vervangen of alles wil vervangen.
 * Version: 1
 * Author: Webwijs
 * Author URI: https://www.webwijs.nu
 **/

// Gemaakt door: Egbert Ludema
// Opgeleverd: Juni 2025
// Stage opdracht tijdens de stage periode van Feb 2025 tot Jun 2025 bij Webwijs.

// Load dependencies
include_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';
include_once plugin_dir_path(__FILE__) . 'includes/search-replace.php';
include_once plugin_dir_path(__FILE__) . 'includes/database-handler.php';

/**
 * Enqueue admin scripts & styles
 */
function gsr_enqueue_scripts() {
    // Enqueue JS
    wp_enqueue_script('gsr-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], '1.0', true);

    // Localize AJAX URL for JavaScript
    wp_localize_script('gsr-admin-js', 'gsr_ajax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);

    // Enqueue CSS
    wp_enqueue_style('admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin.min.css', [], '0.1');
}
add_action('admin_enqueue_scripts', 'gsr_enqueue_scripts');

/**
 * Handle AJAX request for applying changes
 */
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

    // Check which button was pressed
    if (isset($_POST['gsr_apply_selected']) && !empty($_POST['selected_rows'])) {
        foreach ($_POST['selected_rows'] as $table => $rows) {
            foreach ($rows as $row_id => $column) {
                gsr_apply_row_change($search, $replace, $use_regex, $table, $row_id, $column);
            }
        }
    } else {
        gsr_apply_changes($search, $replace, $use_regex, $tables);
    }

    wp_send_json_success(["message" => "Changes Applied Successfully"]);
}