<?php
function gsr_apply_changes($search, $replace, $use_regex, $selected_tables) {
    global $wpdb;

    if (empty($selected_tables)) {
        error_log("ERROR: No tables selected.");
        return;
    }

    // Manually define primary keys for core WordPress tables
    $primary_key_overrides = [
        'wp_postmeta' => 'meta_id',
        'wp_posts' => 'ID',
        'wp_comments' => 'comment_ID',
        'wp_users' => 'ID',
        'wp_options' => 'option_id',
        'wp_terms' => 'term_id',
        'wp_termmeta' => 'meta_id',
        'wp_term_taxonomy' => 'term_taxonomy_id',
    ];

    foreach ($selected_tables as $table) {
        if (empty($table)) {
            // error_log("ERROR: Skipping empty table name.");
            continue;
        }

        // error_log("Processing Table: $table");

        // Ensure table name is properly formatted
        $table = esc_sql($table);

        // Use manual primary key override if available
        $primary_key = $primary_key_overrides[$table] ?? null;

        // If no override exists, try to detect primary key dynamically
        if (!$primary_key) {
            $primary_key = $wpdb->get_var("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                           WHERE TABLE_NAME = '$table' 
                                           AND COLUMN_KEY = 'PRI'");
        }

        if (!$primary_key) {
            // error_log("WARNING: No primary key found for table $table, skipping updates.");
            continue;
        }

        // error_log("INFO: Primary Key Detected - `$primary_key` for `$table`");

        // Get all columns for the table
        $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");

        if (empty($columns)) {
            // error_log("WARNING: No columns found for table $table, skipping.");
            continue;
        }

        foreach ($columns as $column) {
            $sql = "SELECT `$primary_key`, `$column` FROM `$table` WHERE `$column` LIKE %s";
            $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'));

            if (empty($results)) {
                // error_log("INFO: No matches found in `$table`.`$column`.");
                continue;
            }

            foreach ($results as $row) {
                if (!isset($row->$column)) {
                    // error_log("ERROR: Column `$column` does not exist in `$table`, skipping.");
                    continue;
                }

                $old_value = $row->$column;
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

                // Ensure primary key value exists
                if (!isset($row->$primary_key)) {
                    // error_log("ERROR: Primary key `$primary_key` not found in table `$table`, skipping update.");
                    continue;
                }

                // Perform the update
                $update_result = $wpdb->update(
                    "$table",
                    [$column => $new_value],
                    [$primary_key => $row->$primary_key],
                    ['%s'],
                    ['%d']
                );

                if ($update_result === false) {
                     error_log("ERROR: Failed to update `$table`.`$column` for `$primary_key`: " . $row->$primary_key);
                }
            }
        }
    }
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