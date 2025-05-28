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

    $has_results = false;

    echo "<div id='preview-container' class='wrap'>";
        echo "<h3>Preview of Changes</h3>";

        echo '<form id="apply-changes" method="post">';
            echo '<input type="hidden" name="search_text" value="' . esc_attr($search) . '">';
            echo '<input type="hidden" name="replace_text" value="' . esc_attr($replace) . '">';
            echo '<input type="hidden" name="use_regex" value="' . ($use_regex ? '1' : '0') . '">';

            foreach ($selected_tables as $table) {
                echo '<input type="hidden" name="tables[]" value="' . esc_attr($table) . '">';
                $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");

                foreach ($columns as $column) {
                    $sql = "SELECT * FROM `$table` WHERE `$column` LIKE %s";
                    $params = '%' . $wpdb->esc_like($search) . '%';

                    // Speciaal voor wp_posts: revisions uitsluiten
                    if ($table === 'wp_posts') {
                        $sql .= " AND post_type != 'revision'";
                    }

                    $results = $wpdb->get_results($wpdb->prepare($sql, $params));

                    if ($results) {
                        $has_results = true;
                        echo "<div class='table-container'>";
                            echo "<div class='table-header'><h3>Table: " . esc_html($table) . "</h3></div>";
                            echo "<h4>Column: {$column}</h4>";
                            echo "<ul class='before-after-list'>";

                                foreach ($results as $row) {
                                    $old_value = $row->$column ?? '';
                                    if ($old_value === '') {
                                        continue;
                                    }

                                    // Prepare highlights
                                    $search_highlighted = "<span class='highlighted'>{$search}</span>";
                                    $replace_highlighted = "<span class='highlighted'>{$replace}</span>";

                                    if ($use_regex) {
                                        $new_value = preg_replace("/{$search}/i", $replace, $old_value);
                                        $old_value_highlighted = preg_replace("/{$search}/i", $search_highlighted, $old_value);
                                        $new_value_highlighted = preg_replace("/{$replace}/i", $replace_highlighted, $new_value);
                                    } else {
                                        $new_value = str_ireplace($search, $replace, $old_value);
                                        $old_value_highlighted = str_ireplace($search, $search_highlighted, $old_value);
                                        $new_value_highlighted = str_ireplace($replace, $replace_highlighted, $new_value);
                                    }

                                    // Determine primary key
                                    $primary_key = 'id';
                                    if (property_exists($row, 'ID')) {
                                        $primary_key = 'ID';
                                    } elseif (property_exists($row, 'meta_id')) {
                                        $primary_key = 'meta_id';
                                    } elseif (property_exists($row, 'comment_ID')) {
                                        $primary_key = 'comment_ID';
                                    } elseif (property_exists($row, 'option_id')) {
                                        $primary_key = 'option_id';
                                    }

                                    $row_id = $row->$primary_key;

                                    $allowed_tags = [
                                        'span' => ['class' => []], 'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'p' => [], 'br' => [],
                                        'ul' => [], 'ol' => [], 'li' => [], 'a' => ['href' => [], 'title' => []]
                                    ];

                                    echo "<li>";
                                        echo "<input type='checkbox' name='selected_rows[{$table}][{$row_id}]' value='{$column}'>";
                                        echo "<div class='before'><strong>Before:</strong> " . wp_kses($old_value_highlighted, $allowed_tags) . "</div>";
                                        echo "<div class='after'><strong>After:</strong> " . wp_kses($new_value_highlighted, $allowed_tags) . "</div>";
                                    echo "</li>";
                                }
                            echo "</ul>";
                        echo "</div>";
                    }
                }
            }

            if ($has_results) {
                echo '<div class="apply_buttons">';
                    echo '<input type="submit" name="gsr_apply_all" value="Apply all Changes" class="button-secondary">';
                    echo '<input type="submit" name="gsr_apply_selected" value="Apply selected Rows" class="button-primary">';
                echo '</div>';
            } else {
                echo '<p><strong>No results of "' . $search . '" found.</strong></p>';
            }

        echo '</form>';
    echo "</div>";

    // Verwerking van submit
    if (isset($_POST['gsr_apply_selected'])) {
        // Apply selected rows/columns ONLY
        if (!empty($_POST['selected_rows'])) {
            foreach ($_POST['selected_rows'] as $table => $rows) {
                foreach ($rows as $row_id => $column) {
                    gsr_apply_row_change($_POST['search_text'], $_POST['replace_text'], isset($_POST['use_regex']), $table, $row_id, $column);
                }
            }
        }
    } elseif (isset($_POST['gsr_apply_all'])) {
        // Apply all changes for selected tables
        gsr_apply_changes($_POST['search_text'], $_POST['replace_text'], isset($_POST['use_regex']), $_POST['tables']);
    }
}
