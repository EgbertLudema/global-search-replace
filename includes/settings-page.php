<?php
function gsr_admin_menu() {
    add_menu_page(
        'Global Search & Replace',
        'Search & Replace',
        'manage_options',
        'gsr-settings',
        'gsr_settings_page',
        'dashicons-search',
        100
    );
}
add_action('admin_menu', 'gsr_admin_menu');

function gsr_settings_page() {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES"); // Fetch all table names

    ?>
    <div id="search_replace_container">
        <div class="wrap">
            <h2>Global Search & Replace</h2>
            <form class="sr-form" method="post" action="">
                <div class="row">
                    <label for="search_text">Search for:</label>
                    <input type="text" id="search_text" name="search_text" required>

                    <label for="replace_text">Replace with:</label>
                    <input type="text" id="replace_text" name="replace_text" required>
                </div>

                <div class="row">
                    <label class="info" for="use_regex">
                        <input type="checkbox" id="use_regex" name="use_regex"> Use Regex
                        <span class="questionmark">?</span>
                        <div class="tool-tip">
                            Enable this option to use **Regular Expressions (Regex)** for advanced search patterns.<br>
                            - **Example 1:** `/[a-z]+/` → Matches any lowercase word.<br>
                            - **Example 2:** `/\d{4}/` → Matches a four-digit number.<br>
                            - **Example 3:** `/^Hello/` → Matches text starting with "Hello".<br>
                            ⚠️ Use carefully, as Regex patterns are case-sensitive and can affect multiple matches.
                        </div>
                    </label>
                </div>

                <div class="row flex flex-col">
                    <label for="tables">Select Tables (Hold Ctrl/Cmd to select multiple):</label>
                    <select name="tables[]" id="tables" multiple size="10">
                        <option value="all">-- All Tables --</option>
                        <?php foreach ($tables as $table) : ?>
                            <option value="<?php echo esc_attr($table); ?>">
                                <?php echo esc_html($table); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="submit" name="gsr_submit" value="Preview Changes" class="button-primary">
            </form>
        </div>
        <?php
        if (isset($_POST['gsr_submit'])) {
            include_once plugin_dir_path(__FILE__) . 'search-replace.php';
            gsr_preview_results($_POST['search_text'], $_POST['replace_text'], isset($_POST['use_regex']), $_POST['tables']);
        }
        ?>
    </div>
    <?php
}