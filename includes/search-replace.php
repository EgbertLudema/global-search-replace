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

    echo "<div id='preview-container' class='wrap'>";
        echo "<h3>Preview of Changes</h3>";

        echo '<form id="apply-changes" method="post">';
            echo '<input type="hidden" name="search_text" value="' . esc_attr($search) . '">';
            echo '<input type="hidden" name="replace_text" value="' . esc_attr($replace) . '">';
            echo '<input type="hidden" name="use_regex" value="' . ($use_regex ? '1' : '0') . '">';

            foreach ($selected_tables as $table) {
                echo '<input type="hidden" name="tables[]" value="' . esc_attr($table) . '">';
            }

            foreach ($selected_tables as $table) {
                echo '<div class="table-container">';
                    echo "<div class='table-header'>";
                        echo "<input type='checkbox' name='selected_tables[]' value='" . esc_attr($table) . "'> ";
                        echo "<h3>Table: " . esc_html($table) . "</h3>";
                    echo "</div>";

                    $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");

                    foreach ($columns as $column) {
                        $sql = "SELECT * FROM `$table` WHERE `$column` LIKE %s";
                        $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'));

                        if ($results) {
                            echo "<h4>Column: {$column}</h4>";
                            echo "<ul class='before-after-list'>";
                                foreach ($results as $row) {
                                    $old_value = $row->$column ?? '';
                                    if ($old_value === '') {
                                        continue;
                                    }

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

                                    $allowed_tags = [
                                        'span' => ['class' => []],
                                        'strong' => [],
                                        'em' => [],
                                        'b' => [],
                                        'i' => [],
                                        'p' => [],
                                        'br' => [],
                                        'ul' => [],
                                        'ol' => [],
                                        'li' => [],
                                        'a' => ['href' => [], 'title' => []]
                                    ];

                                    echo "<li>";
                                        echo "<div class='before'><strong>Before:</strong> " . wp_kses($old_value_highlighted, $allowed_tags) . "</div>";
                                        echo "<div class='after'><strong>After:</strong> " . wp_kses($new_value_highlighted, $allowed_tags) . "</div>";
                                    echo "</li>";

                                }
                            echo "</ul>";
                        }
                    }
                echo "</div>";
            }

            // Apply Changes buttons
            echo '<div class="apply_buttons">';
                echo '<input type="submit" name="gsr_apply" value="Apply all Changes" class="button-secondary">';
                echo '<input type="submit" name="gsr_apply_selected" value="Apply selected Tables" class="button-primary">';
            echo '</div>';
        echo '</form>';
    echo "</div>";

    // Process form submission (Check if Apply Changes was clicked)
    if (isset($_POST['gsr_apply'])) {
        gsr_apply_changes($_POST['search_text'], $_POST['replace_text'], isset($_POST['use_regex']), $_POST['tables']);
    } else if (isset($_POST['gsr_apply_selected'])) {
        if (!empty($_POST['selected_tables'])) {
            gsr_apply_changes($_POST['search_text'], $_POST['replace_text'], isset($_POST['use_regex']), $_POST['selected_tables']);
        }
    }
}