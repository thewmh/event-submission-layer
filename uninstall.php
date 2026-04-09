<?php
/**
 * Uninstall Event Submission Layer
 *
 * This file is called when the plugin is uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('esl_add_event_page_id');
delete_option('esl_events_dashboard_page_id');
delete_option('esl_options');

// Remove the custom role
remove_role('event_submitter');

// Delete the created pages
$page_ids = [
    get_option('esl_add_event_page_id'),
    get_option('esl_events_dashboard_page_id'),
];

foreach ($page_ids as $page_id) {
    if ($page_id) {
        wp_delete_post($page_id, true);
    }
}

// Optionally, delete all events created by the plugin, but that might be destructive, so skip.

// Clean up transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_esl_form_message_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_esl_form_message_%'");
