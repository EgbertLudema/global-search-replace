<?php
function gsr_apply_changes($search, $replace, $use_regex, $selected_tables) {
    global $wpdb;

    error_log("DEBUG: gsr_apply_changes() called.");
    error_log("DEBUG: Search: $search, Replace: $replace, Use Regex: " . ($use_regex ? "Yes" : "No"));
    error_log("DEBUG: Selected Tables: " . print_r($selected_tables, true));

    if (in_array('all', (array)$selected_tables)) {
        $selected_tables = $wpdb->get_col("SHOW TABLES");
    }

    if (empty($selected_tables)) {
        error_log("ERROR: No tables selected.");
        echo "<p><strong>Please select at least one table.</strong></p>";
        return;
    }

    foreach ($selected_tables as $table) {
        error_log("Processing Table: $table");

        $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");
        foreach ($columns as $column) {
            $sql = "SELECT * FROM `$table` WHERE `$column` LIKE %s";
            $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'));

            foreach ($results as $row) {
                $old_value = $row->$column ?? '';
                if ($old_value === '') {
                    continue;
                }

                // Handle Serialized & JSON Data
                if (is_serialized($old_value)) {
                    $unserialized = unserialize($old_value);
                    $new_unserialized = recursive_replace($search, $replace, $unserialized, $use_regex);
                    $new_value = serialize($new_unserialized);
                } elseif (is_json($old_value)) {
                    $decoded_json = json_decode($old_value, true);
                    $new_json = recursive_replace($search, $replace, $decoded_json, $use_regex);
                    $new_value = json_encode($new_json);
                } else {
                    // Standard string replace (case-insensitive)
                    $new_value = $use_regex
                        ? preg_replace("/{$search}/i", $replace, $old_value)
                        : str_ireplace($search, $replace, $old_value);
                }

                // Perform the update
                $update_result = $wpdb->update(
                    "`$table`",
                    [$column => $new_value],
                    ['ID' => $row->ID],
                    ['%s'],
                    ['%d']
                );

                if ($update_result === false) {
                    error_log("ERROR: Failed to update $table.$column for ID: " . $row->ID);
                } else {
                    error_log("SUCCESS: Updated $table.$column for ID: " . $row->ID);
                }
            }
        }
    }

    // Refresh Slugs & Permalinks
    refresh_wordpress_slugs($search, $replace);

    // Clear Cache (Yoast SEO, Transients)
    clear_wp_cache();

    echo "<p><strong>Changes Applied Successfully!</strong></p>";
}

if (!function_exists('is_serialized')) {
    function is_serialized($data) {
        return ($data == 'b:0;' || @unserialize($data) !== false);
    }
}

function is_json($data) {
    json_decode($data);
    return (json_last_error() == JSON_ERROR_NONE);
}

function recursive_replace($search, $replace, $data, $use_regex) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = recursive_replace($search, $replace, $value, $use_regex);
        }
    } elseif (is_string($data)) {
        return $use_regex ? preg_replace("/{$search}/i", $replace, $data) : str_ireplace($search, $replace, $data);
    }
    return $data;
}

function refresh_wordpress_slugs($search, $replace) {
    global $wpdb;

    // Update `post_name` (slugs)
    $wpdb->query("UPDATE wp_posts SET post_name = REPLACE(post_name, '{$search}', '{$replace}')");

    // Regenerate permalinks
    flush_rewrite_rules();
}

function clear_wp_cache() {
    delete_transient('wpseo_total_unindexed_count');
    delete_transient('wpseo_unindexed_post_count');
    delete_transient('wpseo_unindexed_term_count');

    // Clear WordPress object cache
    wp_cache_flush();
}
