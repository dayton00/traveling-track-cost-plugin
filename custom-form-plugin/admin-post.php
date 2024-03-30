<?php
// Include WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Check if the action parameter is set
if (isset($_POST['action']) && $_POST['action'] === 'custom_form_handle_delete') {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'custom_form_handle_delete_nonce')) {
        wp_die('Security check');
    }

    // Process delete action
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_form_data';
        $wpdb->delete($table_name, array('id' => $delete_id));
    }
}

// Redirect back to the previous page
wp_safe_redirect(wp_get_referer());
exit;
