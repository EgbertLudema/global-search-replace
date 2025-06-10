<?php
function gsr_admin_menu() {
    add_menu_page(
        'Global search & replace',
        'Search & replace',
        'gsr_access',
        'gsr-settings',
        'gsr_settings_page',
        'dashicons-search',
        100
    );
}
add_action('admin_menu', 'gsr_admin_menu');

add_action('init', function () {
    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('gsr_access')) {
        $admin_role->add_cap('gsr_access');
    }
});

function gsr_settings_page() {
    global $wpdb;
    $current_user = wp_get_current_user();
    $klant_exists = get_role('klant') !== null;
    $is_klant = $klant_exists && in_array('klant', (array) $current_user->roles);

    // Set allowed tables for users with "klant" role
    $allowed_tables = ['wp_posts', 'wp_postmeta'];
    $tables = $wpdb->get_col("SHOW TABLES");

    ?>
    <div id="search_replace_container">
        <div class="gsr_wrap wrap">
            <h2>Global search & replace</h2>
            <form class="sr-form" method="post" action="">
                <div class="row">
                    <label for="search_text">Search for:</label>
                    <input type="text" id="search_text" name="search_text" required>

                    <label for="replace_text">Replace with:</label>
                    <input type="text" id="replace_text" name="replace_text" required>
                </div>

                <?php if (!$is_klant): ?>
                    <div class="row">
                        <label class="info" for="use_regex">
                            <input type="checkbox" id="use_regex" name="use_regex"> Use Regex
                            <span class="questionmark">?</span>
                            <div class="tool-tip">
                                Enable this option to use <strong>Regular Expressions (Regex)</strong> for advanced search patterns.<br><br>
                                - <strong>Example 1:</strong> `/[a-z]+/` → Matches any lowercase word.<br>
                                - <strong>Example 2:</strong> `/\d{4}/` → Matches a four-digit number.<br>
                                - <strong>Example 3:</strong> `/^Hello/` → Matches text starting with "Hello".<br><br>
                                <strong>⚠️ Use carefully, as Regex patterns are case-sensitive and can affect multiple matches. ⚠️</strong>
                            </div>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="row flex flex-col">
                    <label class="select-tables" for="tables">
                        <label class="select-tables" for="tables">
                            Select Tables, Hold
                            <div class="key"><span class="lower-row-text">control</span></div>
                            or
                            <div class="key"><span class="lower-row-text">command</span><span class="absolute-right icon">&#8984;</span></div>
                            to select multiple tables.
                        </label>
                    </label>
                    <select name="tables[]" id="tables" multiple size="10">
                        <?php if (!$is_klant): ?>
                            <option value="all">-- All Tables --</option>
                        <?php endif; ?>
                        <?php foreach ($tables as $table) : ?>
                            <?php if (!$is_klant || in_array($table, $allowed_tables)) : ?>
                                <option value="<?php echo esc_attr($table); ?>">
                                    <?php echo esc_html($table); ?>
                                </option>
                            <?php endif; ?>
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
