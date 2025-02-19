<?php
function gsr_preview_results($search, $replace, $use_regex, $selected_tables) {
    global $wpdb;

    if (in_array('all', (array)$selected_tables)) {
        $selected_tables = $wpdb->get_col("SHOW TABLES");
    }

    if (empty($selected_tables)) {
        echo "<p><strong>Please select at least one table.</strong></p>";
        return;
    }

    echo "<h3>Preview of Changes</h3>";

    echo '<form id="apply-changes" method="post">';
    echo '<input type="hidden" name="search_text" value="' . esc_attr($search) . '">';
    echo '<input type="hidden" name="replace_text" value="' . esc_attr($replace) . '">';
    echo '<input type="hidden" name="use_regex" value="' . ($use_regex ? '1' : '0') . '">';

    foreach ($selected_tables as $table) {
        echo '<input type="hidden" name="tables[]" value="' . esc_attr($table) . '">';
    }

    foreach ($selected_tables as $table) {
        echo "<h3>Table: " . esc_html($table) . "</h3>";

        $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");

        foreach ($columns as $column) {
            $sql = "SELECT * FROM `$table` WHERE `$column` LIKE %s";
            $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'));

            if ($results) {
                echo "<h4>Column: {$column}</h4><ul>";
                foreach ($results as $row) {
                    $old_value = $row->$column ?? '';
                    if ($old_value === '') {
                        continue;
                    }

                    $new_value = $use_regex ? preg_replace("/{$search}/i", $replace, $old_value) : str_ireplace($search, $replace, $old_value);

                    echo "<li><strong>Before:</strong> " . esc_html($old_value) . "<br>";
                    echo "<strong>After:</strong> " . esc_html($new_value) . "</li>";
                }
                echo "</ul>";
            }
        }
    }

    // Add Apply Changes button
    echo '<input type="submit" name="gsr_apply" value="Apply Changes" class="button-primary">';
    echo '</form>';

    // Process form submission (Check if Apply Changes was clicked)
    if (isset($_POST['gsr_apply'])) {
        error_log("DEBUG: Apply button clicked. Running gsr_apply_changes()."); // Check if function is called
        gsr_apply_changes($_POST['search_text'], $_POST['replace_text'], isset($_POST['use_regex']), $_POST['tables']);
    }
}