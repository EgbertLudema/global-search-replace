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
            continue;
        }

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
            continue;
        }

        // Get all columns for the table
        $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");

        if (empty($columns)) {
            continue;
        }

        foreach ($columns as $column) {
            $sql = "SELECT `$primary_key`, `$column` FROM `$table` WHERE `$column` LIKE %s";
            if ($table === 'wp_posts') {
                $sql .= " AND post_type != 'revision'";
            }

            $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'));

            if (empty($results)) {
                continue;
            }

            foreach ($results as $row) {
                if (!isset($row->$column)) {
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

function gsr_apply_row_change($search, $replace, $use_regex, $table, $row_id, $column) {
    global $wpdb;

    $primary_key = 'ID'; // Default fallback
    if ($table === 'wp_postmeta') $primary_key = 'meta_id';
    if ($table === 'wp_comments') $primary_key = 'comment_ID';
    if ($table === 'wp_options') $primary_key = 'option_id';
    if ($table === 'wp_terms') $primary_key = 'term_id';
    if ($table === 'wp_termmeta') $primary_key = 'meta_id';
    if ($table === 'wp_term_taxonomy') $primary_key = 'term_taxonomy_id';

    $current_value = $wpdb->get_var($wpdb->prepare(
        "SELECT `$column` FROM `$table` WHERE `$primary_key` = %d", $row_id
    ));

    if ($current_value) {
        $new_value = $use_regex
            ? preg_replace("/{$search}/i", $replace, $current_value)
            : str_ireplace($search, $replace, $current_value);

        if ($new_value !== $current_value) {
            $wpdb->update(
                $table,
                [$column => $new_value],
                [$primary_key => $row_id]
            );
        } else {
            error_log("DEBUG: No change needed for $table row $row_id column $column");
        }
    } else {
        error_log("DEBUG: No current value found for $table row $row_id column $column");
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