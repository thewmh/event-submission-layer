<?php
/**
 * Plugin Name: Event Submission Layer
 * Plugin URI: https://github.com/thewmh/event-submission-layer
 * Description: Frontend event submission and management for Sugar Calendar Lite. Allows users with the 'event_submitter' role to create, edit, and manage events from the frontend.
 * Version: 1.0.5
 * Author: William
 * Author URI: https://wizardunicorn.ninja
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-submission-layer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) exit;

/**
 * Load text domain for internationalization
 */
add_action('plugins_loaded', 'esl_load_textdomain');
function esl_load_textdomain() {
    load_plugin_textdomain('event-submission-layer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

/**
 * Check plugin requirements
 */
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . __('Event Submission Layer requires PHP 7.4 or higher.', 'event-submission-layer') . '</p></div>';
    });
    return;
}

add_action('plugins_loaded', function() {
    if (!function_exists('sugar_calendar_add_event')) {
        if (!get_option('esl_sc_notice_dismissed')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible esl-sc-notice" style="position:relative;">'
                    . '<p>' . __('Event Submission Layer requires the Sugar Calendar plugin to be active.', 'event-submission-layer') . '</p>'
                    . '<button type="button" class="notice-dismiss esl-dismiss-sc-notice" data-nonce="' . wp_create_nonce('esl_dismiss_sc_notice') . '"><span class="screen-reader-text">' . __('Dismiss this notice.', 'event-submission-layer') . '</span></button>'
                    . '</div>';
            });
        }

        add_action('wp_ajax_esl_dismiss_sc_notice', function() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'esl_dismiss_sc_notice')) {
                wp_send_json_error(['message' => 'Invalid nonce.']);
            }
            update_option('esl_sc_notice_dismissed', 1);
            wp_send_json_success();
        });

        add_action('admin_enqueue_scripts', function() {
            wp_add_inline_script('jquery', "
                jQuery(function($) {
                    $(document).on('click', '.esl-dismiss-sc-notice', function() {
                        var nonce = $(this).data('nonce');
                        $.post(ajaxurl, { action: 'esl_dismiss_sc_notice', nonce: nonce }, function() {
                            $('.esl-sc-notice').fadeOut();
                        });
                    });
                });
            ");
        });
    } else {
        delete_option('esl_sc_notice_dismissed');
    }
});

/**
 * 🔐 ROLE SETUP
 */
function esl_add_role() {
    add_role('event_submitter', 'Event Submitter', [
        'read'                  => true,
        'edit_posts'            => false,
        'delete_posts'          => false,
        'create_posts'          => false,

        // Sugar Calendar sc_event post type caps
        'read_event'            => true,
        'edit_event'            => true,
        'delete_event'          => true,
        'edit_events'           => true,
        'edit_others_events'    => false,
        'publish_events'        => true,
        'read_private_events'   => true,
        'delete_events'         => true,
        'delete_published_events' => true,
        'delete_private_events' => false,
        'edit_private_events'   => true,
        'edit_published_events' => true,
        'create_events'         => true,
    ]);
}

function esl_create_plugin_pages() {
    $pages = [
        'events_dashboard' => [
            'post_title'   => 'Events Dashboard',
            'post_name'    => 'events-dashboard',
            'post_content' => '[events_dashboard]',
            'post_status'  => 'private',
            'post_type'    => 'page',
        ],
        'add_event' => [
            'post_title'   => 'Add New Event',
            'post_name'    => 'add-event',
            'post_content' => '[event_submit_form]',
            'post_status'  => 'private',
            'post_type'    => 'page',
        ],
    ];

    $author = get_current_user_id() ?: 1;

    foreach ($pages as $option_key => $page_args) {
        $existing_page = get_page_by_path($page_args['post_name'], OBJECT, 'page');

        if ($existing_page && !empty($existing_page->ID)) {
            update_option('esl_' . $option_key . '_page_id', $existing_page->ID);
            continue;
        }

        $page_args['post_author'] = $author;
        $page_id = wp_insert_post($page_args);

        if (!is_wp_error($page_id) && $page_id) {
            update_option('esl_' . $option_key . '_page_id', $page_id);
        }
    }
}

function esl_activate_plugin() {
    esl_add_role();
    esl_create_plugin_pages();

    // Seed default options on first activation so the frontend shortcodes are
    // not immediately gated by an uninitialised option value.
    if (get_option('esl_options') === false) {
        update_option('esl_options', ['enable_frontend' => 1, 'default_status' => 'publish']);
    }
}

function esl_get_add_event_page_url() {
    $page_id = get_option('esl_add_event_page_id');

    if ($page_id) {
        $url = get_permalink($page_id);
        if ($url) {
            return $url;
        }
    }

    $page = get_page_by_path('add-event', OBJECT, 'page');
    if ($page && !empty($page->ID)) {
        return get_permalink($page->ID);
    }

    return home_url('/add-event');
}

register_activation_hook(__FILE__, 'esl_activate_plugin');
register_deactivation_hook(__FILE__, function () {
    remove_role('event_submitter');
});

/**
 * Keep role caps synced when code is updated without reactivating plugin
 */
function esl_ensure_role_caps() {
    $role = get_role('event_submitter');
    if (!$role) {
        esl_add_role();
        return;
    }

    $caps = [
        'read' => true,
        'read_event' => true,
        'edit_event' => true,
        'delete_event' => true,
        'edit_events' => true,
        'publish_events' => true,
        'read_private_events' => true,
        'edit_private_events' => true,
        'edit_published_events' => true,
        'create_events' => true,
    ];

    foreach ($caps as $cap => $grant) {
        if ($grant && !$role->has_cap($cap)) {
            $role->add_cap($cap);
        }
    }

    // Remove read_private_pages — access is now handled by esl_allow_private_plugin_pages filter.
    if ( $role->has_cap( 'read_private_pages' ) ) {
        $role->remove_cap( 'read_private_pages' );
    }
}
add_action( 'plugins_loaded', 'esl_ensure_role_caps' );

/**
 * Grant private page read access only for plugin-specific pages.
 */
function esl_allow_private_plugin_pages( $allcaps, $caps, $args, $user ) {
    if ( empty( $caps ) || ! in_array( 'read_private_pages', $caps, true ) ) {
        return $allcaps;
    }

    if ( empty( $user ) || empty( $user->roles ) || ! in_array( 'event_submitter', (array) $user->roles, true ) ) {
        return $allcaps;
    }

    $queried = get_queried_object();
    if ( $queried instanceof WP_Post && in_array( $queried->post_name, [ 'events-dashboard', 'add-event' ], true ) ) {
        $allcaps['read_private_pages'] = true;
    }

    return $allcaps;
}
add_filter( 'user_has_cap', 'esl_allow_private_plugin_pages', 10, 4 );

add_action('wp_enqueue_scripts', function () {
    // Only load on pages with our forms
    if (is_page(['add-event', 'events-dashboard'])) {
        wp_enqueue_style('flatpickr-css', plugin_dir_url(__FILE__) . 'assets/css/flatpickr.min.css', [], '4.6.13');
        wp_enqueue_script('flatpickr-js', plugin_dir_url(__FILE__) . 'assets/js/flatpickr.min.js', [], '4.6.13', true);
        
        // Enqueue AJAX script
        wp_enqueue_script('esl-ajax', plugin_dir_url(__FILE__) . 'assets/js/esl-ajax.js', ['jquery'], '1.0.0', true);
        wp_localize_script('esl-ajax', 'esl_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
        
        // Add custom script for initialization
        wp_add_inline_script('flatpickr-js', "
            document.addEventListener('DOMContentLoaded', function() {
                // Round a Date to the next 15-minute boundary.
                function roundUpTo15(date) {
                    var ms = 15 * 60 * 1000;
                    return new Date(Math.ceil(date.getTime() / ms) * ms);
                }

                var defaultStart = roundUpTo15(new Date());

                // Shared Flatpickr config (no defaultDate — set per-instance below).
                var baseConfig = {
                    enableTime: true,
                    dateFormat: 'Y-m-dTH:i',
                    time_24hr: false,
                    minuteIncrement: 15,
                    altInput: true,
                    altFormat: 'F j, Y at h:i K',
                    onChange: function(selectedDates, dateStr, instance) {
                        instance.input.value = dateStr;
                    }
                };

                // Helper: initialise a start/end picker pair.
                // When the start date changes, copy the selected date onto the end picker
                // while preserving the end picker's current time.
                function initPair(startId, endId) {
                    var startEl = document.getElementById(startId);
                    var endEl   = document.getElementById(endId);
                    if (!startEl || !endEl) { return; }

                    var endPicker = flatpickr(endEl, Object.assign({}, baseConfig, {
                        defaultDate: startEl.value || defaultStart
                    }));

                    flatpickr(startEl, Object.assign({}, baseConfig, {
                        defaultDate: startEl.value || defaultStart,
                        onChange: function(selectedDates, dateStr, instance) {
                            instance.input.value = dateStr;

                            if (!selectedDates.length) { return; }

                            // Copy the start date onto the end picker, keeping the end time.
                            var currentEnd  = endPicker.selectedDates[0] || selectedDates[0];
                            var newEnd      = new Date(selectedDates[0]);
                            newEnd.setHours(currentEnd.getHours(), currentEnd.getMinutes(), 0, 0);

                            // If the resulting end would be before (or equal to) start, push it
                            // forward by one hour so the pair stays logically valid.
                            if (newEnd <= selectedDates[0]) {
                                newEnd = new Date(selectedDates[0].getTime() + 60 * 60 * 1000);
                            }

                            endPicker.setDate(newEnd, true);
                        }
                    }));
                }

                // add-event page form
                initPair('event_start', 'event_end');

                // events-dashboard: edit form
                initPair('event_start_edit', 'event_end_edit');

                // events-dashboard: create form
                initPair('event_start_new', 'event_end_new');
            });
        ");
    }
});

/**
 * Admin menu and settings
 */
add_action('admin_menu', 'esl_admin_menu');
function esl_admin_menu() {
    add_menu_page(
        __('Event Submission Layer', 'event-submission-layer'),
        __('Event Submission', 'event-submission-layer'),
        'manage_options',
        'event-submission-layer',
        'esl_admin_page',
        'dashicons-calendar-alt',
        30
    );
}

function esl_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Event Submission Layer Settings', 'event-submission-layer'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('esl_settings_group'); ?>
            <?php do_settings_sections('event-submission-layer'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Sanitize and normalize the esl_options array.
 *
 * WordPress does not submit unchecked checkboxes, so without an explicit
 * sanitize_callback the 'enable_frontend' key will simply be absent from the
 * saved option when the box is unchecked, making it impossible to persist a
 * disabled state.
 */
function esl_sanitize_options($input) {
    $sanitized = [];
    $sanitized['enable_frontend'] = !empty($input['enable_frontend']) ? 1 : 0;
    $sanitized['default_status']  = in_array($input['default_status'] ?? '', ['publish', 'pending'], true)
        ? $input['default_status']
        : 'publish';
    return $sanitized;
}

add_action('admin_init', 'esl_register_settings');
function esl_register_settings() {
    register_setting('esl_settings_group', 'esl_options', [
        'sanitize_callback' => 'esl_sanitize_options',
    ]);
    add_settings_section(
        'esl_main_section',
        __('Main Settings', 'event-submission-layer'),
        'esl_section_callback',
        'event-submission-layer'
    );
    add_settings_field(
        'esl_enable_frontend',
        __('Enable Frontend Submission', 'event-submission-layer'),
        'esl_enable_frontend_callback',
        'event-submission-layer',
        'esl_main_section'
    );
    add_settings_field(
        'esl_default_status',
        __('Default Event Status', 'event-submission-layer'),
        'esl_default_status_callback',
        'event-submission-layer',
        'esl_main_section'
    );
}

function esl_section_callback() {
    echo __('Configure the Event Submission Layer plugin.', 'event-submission-layer');
}

function esl_enable_frontend_callback() {
    $options = get_option('esl_options');
    $checked = isset($options['enable_frontend']) ? $options['enable_frontend'] : 0;
    echo '<input type="checkbox" id="esl_enable_frontend" name="esl_options[enable_frontend]" value="1" ' . checked(1, $checked, false) . ' />';
    echo '<label for="esl_enable_frontend">' . __('Allow users to submit events from the frontend.', 'event-submission-layer') . '</label>';
}

function esl_default_status_callback() {
    $options = get_option('esl_options');
    $status = isset($options['default_status']) ? $options['default_status'] : 'publish';
    echo '<select id="esl_default_status" name="esl_options[default_status]">';
    echo '<option value="publish" ' . selected($status, 'publish', false) . '>' . __('Publish', 'event-submission-layer') . '</option>';
    echo '<option value="pending" ' . selected($status, 'pending', false) . '>' . __('Pending Review', 'event-submission-layer') . '</option>';
    echo '</select>';
}

/**
 * Utility: compare Sugar Calendar event data to existing row.
 */
function esl_get_sc_event_row( $post_id ) {
    if ( ! function_exists( 'sugar_calendar_get_event_by_object' ) ) {
        return false;
    }

    $event_row = sugar_calendar_get_event_by_object( $post_id, 'post' );
    if ( ! empty( $event_row ) && ! empty( $event_row->id ) ) {
        return $event_row;
    }

    if ( function_exists( 'sugar_calendar_get_events' ) ) {
        $events = sugar_calendar_get_events( [
            'object_id' => $post_id,
            'object_type' => 'post',
            'number' => 1,
            'no_found_rows' => true,
            'order' => 'ASC',
            'orderby' => 'id',
        ] );

        if ( ! empty( $events ) && ! empty( $events[0]->id ) ) {
            return $events[0];
        }
    }

    return false;
}

function esl_event_data_matches( $event_data, $event_row ) {
    if ( empty( $event_row ) ) {
        return false;
    }

    $expects = [
        'object_id' => (string) $event_data['object_id'],
        'object_type' => $event_data['object_type'],
        'object_subtype' => $event_data['object_subtype'],
        'title' => $event_data['title'],
        'content' => $event_data['content'],
        'status' => $event_data['status'],
        'start' => $event_data['start'],
        'end' => $event_data['end'],
        'all_day' => (int) $event_data['all_day'],
    ];

    foreach ( $expects as $key => $value ) {
        if ( ! property_exists( $event_row, $key ) ) {
            return false;
        }

        $actual = (string) $event_row->{$key};
        if ( $key === 'all_day' ) {
            $actual = (int) $event_row->{$key};
        }

        if ( (string) $actual !== (string) $value ) {
            return false;
        }
    }

    return true;
}

/**
 * Process event submission
 *
 * TODO: Add location field support.
 *   - Add `event_location` text input to all three form variants (add-event, dashboard edit, dashboard create).
 *   - Read here: $event_location = sanitize_text_field($_POST['event_location'] ?? '');
 *   - Pass 'location' => $event_location in both $event_data arrays (new + update).
 *   - Pre-populate when editing: read from $sc_event->location.
 *   - Store in post meta: update_post_meta($post_id, 'event_location', $event_location);
 *   - Include 'location' in esl_event_data_matches() comparison if field-level matching is added.
 */
function esl_process_event_submission() {
    if (!is_user_logged_in()) {
        return ['success' => false, 'message' => __('Please log in to submit an event.', 'event-submission-layer')];
    }

    if (!isset($_POST['esl_nonce']) || !wp_verify_nonce($_POST['esl_nonce'], 'esl_submit_event')) {
        return ['success' => false, 'message' => __('Nonce verification failed.', 'event-submission-layer')];
    }

    if (!function_exists('sugar_calendar_add_event')) {
        return ['success' => false, 'message' => __('Sugar Calendar functions not available.', 'event-submission-layer')];
    }

    $user_id = get_current_user_id();

    $event_title       = sanitize_text_field($_POST['event_title'] ?? '');
    $event_description = sanitize_textarea_field($_POST['event_description'] ?? '');
    $event_start_raw   = sanitize_text_field($_POST['event_start'] ?? '');
    $event_end_raw     = sanitize_text_field($_POST['event_end'] ?? '');

    // Validate inputs
    if (empty($event_title)) {
        return ['success' => false, 'message' => __('Event title is required.', 'event-submission-layer')];
    }

    if (empty($event_start_raw) || empty($event_end_raw)) {
        return ['success' => false, 'message' => __('Event start and end dates are required.', 'event-submission-layer')];
    }

    $start_timestamp = strtotime($event_start_raw);
    $end_timestamp   = strtotime($event_end_raw);

    if ($start_timestamp === false) {
        return ['success' => false, 'message' => sprintf(__('Invalid start date: %s', 'event-submission-layer'), esc_html($event_start_raw))];
    }

    if ($end_timestamp === false) {
        return ['success' => false, 'message' => sprintf(__('Invalid end date: %s', 'event-submission-layer'), esc_html($event_end_raw))];
    }

    if ($start_timestamp > $end_timestamp) {
        $end_timestamp = $start_timestamp;
    }

    $options = get_option('esl_options');
    $default_status = $options['default_status'] ?? 'publish';

    // Build post data for sc_event post type
    $post_data = [
        'post_title'   => $event_title,
        'post_content' => $event_description,
        'post_author'  => $user_id,
        'post_type'    => 'sc_event',
        'post_status'  => $default_status,
    ];

    // Update existing event
    if (!empty($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        $post = get_post($event_id);

        if (!$post) {
            return ['success' => false, 'message' => __('Event not found.', 'event-submission-layer')];
        }

        if ($post->post_type !== 'sc_event') {
            return ['success' => false, 'message' => __('Invalid event type.', 'event-submission-layer')];
        }

        if ($post->post_author != $user_id && !current_user_can('edit_others_events')) {
            return ['success' => false, 'message' => __('You do not have permission to edit this event.', 'event-submission-layer')];
        }

        $post_data['ID'] = $event_id;
        $post_update = wp_update_post($post_data);

        if (is_wp_error($post_update)) {
            return ['success' => false, 'message' => sprintf(__('Failed to update post: %s', 'event-submission-layer'), $post_update->get_error_message())];
        }

        $event_row = esl_get_sc_event_row($event_id);

        // Always store in UTC for Sugar Calendar internal event table.
        $event_data = [
            'object_id'      => $event_id,
            'object_type'    => 'post',
            'object_subtype' => 'sc_event',
            'title'          => $event_title,
            'content'        => $event_description,
            'status'         => $default_status,
            'start'          => gmdate('Y-m-d H:i:s', $start_timestamp),
            'end'            => gmdate('Y-m-d H:i:s', $end_timestamp),
            'all_day'        => 0,
            'start_tz'       => '',
            'end_tz'         => '',
        ];

        if (!empty($event_row) && !empty($event_row->id)) {
            $event_row_id = $event_row->id;
            $updated = sugar_calendar_update_event($event_row_id, $event_data, $event_row);
            $after_event = sugar_calendar_get_event($event_row_id);

            // Check that SC event content was updated as expected.
            if (!empty($after_event) && isset($after_event->content) && ($after_event->content !== $event_data['content'])) {
                sugar_calendar_update_event($event_row_id, ['content' => $event_data['content']], $after_event);
                $after_event = sugar_calendar_get_event($event_row_id);
            }

            $row_matches = esl_event_data_matches($event_data, $after_event);

            if (!$updated || !$row_matches) {
                // If no rows were changed or state hasn't synced, force by replacing.
                sugar_calendar_delete_event($event_row_id);
                $new_event_id = sugar_calendar_add_event($event_data);
            }
        } else {
            $added_id = sugar_calendar_add_event($event_data);
        }

        // Sync post meta via Sugar Calendar back-compat keys.
        update_post_meta($event_id, 'sc_event_date_time', $start_timestamp);
        update_post_meta($event_id, 'sc_event_end_date_time', $end_timestamp);
        update_post_meta($event_id, 'start', $start_timestamp);
        update_post_meta($event_id, 'end', $end_timestamp);

        return ['success' => true, 'message' => __('Event updated successfully!', 'event-submission-layer'), 'redirect' => home_url('/events-dashboard')];
    }

    // New event
    $new_post_id = wp_insert_post($post_data);

    if (is_wp_error($new_post_id)) {
        return ['success' => false, 'message' => sprintf(__('Failed to create event: %s', 'event-submission-layer'), $new_post_id->get_error_message())];
    }

    if ($new_post_id) {
        $event_data = [
            'object_id'      => $new_post_id,
            'object_type'    => 'post',
            'object_subtype' => 'sc_event',
            'title'          => $event_title,
            'content'        => $event_description,
            'status'         => $default_status,
            'start'          => gmdate('Y-m-d H:i:s', $start_timestamp),
            'end'            => gmdate('Y-m-d H:i:s', $end_timestamp),
            'all_day'        => 0,
            'start_tz'       => '',
            'end_tz'         => '',
        ];

        $added_id = sugar_calendar_add_event($event_data);

        update_post_meta($new_post_id, 'sc_event_date_time', $start_timestamp);
        update_post_meta($new_post_id, 'sc_event_end_date_time', $end_timestamp);
        update_post_meta($new_post_id, 'start', $start_timestamp);
        update_post_meta($new_post_id, 'end', $end_timestamp);

        if (is_wp_error($added_id)) {
            // Log error
            error_log('[ESL] sugar_calendar_add_event failed: ' . $added_id->get_error_message());
        }

        return ['success' => true, 'message' => __('Event created successfully!', 'event-submission-layer'), 'redirect' => home_url('/events-dashboard')];
    }

    return ['success' => false, 'message' => __('Unknown error occurred.', 'event-submission-layer')];
}

/**
 * 🚫 BLOCK ADMIN ACCESS
 */
add_action('admin_init', function () {
    if (current_user_can('event_submitter') && !current_user_can('administrator')) {
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }
});

add_action('pre_get_posts', function($query) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_page('events-dashboard') && current_user_can('event_submitter') ) {
        $query->set('post_status', ['publish', 'private']);
    }
});

/**
 * AJAX handler for event submission
 */
add_action('wp_ajax_esl_submit_event', 'esl_ajax_submit_event');
function esl_ajax_submit_event() {
    $result = esl_process_event_submission();
    if ($result['success']) {
        wp_send_json_success(['redirect' => $result['redirect'], 'message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * 🧾 SHORTCODE: EVENT SUBMISSION FORM
 */
add_shortcode('event_submit_form', function () {

    if (!is_user_logged_in()) {
        return '<p>Please log in to submit an event.</p>';
    }

    $options = get_option('esl_options');
    if (empty($options['enable_frontend'])) {
        return __('Frontend submission is disabled.', 'event-submission-layer');
    }

    if (!function_exists('sugar_calendar_add_event')) {
        return '<p>' . __('Event submission is currently unavailable.', 'event-submission-layer') . '</p>';
    }

    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    $event = $event_id ? get_post($event_id) : null;
    $title = '';
    $description = '';
    $start = '';
    $end = '';

    if ($event && $event->post_type === 'sc_event' && $event->post_author == get_current_user_id()) {
        $title = $event->post_title;
        $description = $event->post_content;
        $sc_event = function_exists('sugar_calendar_get_event_by_object')
            ? sugar_calendar_get_event_by_object($event_id, 'post')
            : null;
        if ($sc_event && !empty($sc_event->start)) {
            $start = gmdate('Y-m-d\TH:i', strtotime($sc_event->start));
            $end = gmdate('Y-m-d\TH:i', strtotime($sc_event->end ?? $sc_event->start));
        }
    }

    ob_start();
    ?>

    <form method="post" action="" id="esl-event-form">
        <?php wp_nonce_field('esl_submit_event', 'esl_nonce'); ?>

        <?php if ($event_id): ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>" />
        <?php endif; ?>

        <div style="margin-bottom: 12px;">
            <label for="event_title">Event Title</label>
            <input type="text" id="event_title" name="event_title" placeholder="Event Title" value="<?php echo esc_attr($title); ?>" required style="width: 100%; padding: 8px;" />
        </div>

        <div style="margin-bottom: 12px;">
            <label for="event_description">Description</label>
            <textarea id="event_description" name="event_description" placeholder="Description" style="width: 100%; padding: 8px; min-height: 80px;"><?php echo esc_textarea($description); ?></textarea>
        </div>

        <div style="margin-bottom: 12px;">
            <label for="event_start">Start</label>
            <input type="datetime-local" id="event_start" name="event_start" value="<?php echo esc_attr($start); ?>" required style="width: 100%; padding: 8px;" />
        </div>

        <div style="margin-bottom: 12px;">
            <label for="event_end">End</label>
            <input type="datetime-local" id="event_end" name="event_end" value="<?php echo esc_attr($end); ?>" required style="width: 100%; padding: 8px;" />
        </div>

        <button type="submit" name="submit_event" style="padding: 8px 16px; background: #0073aa; color: white; border: none; cursor: pointer;"><?php echo $event_id ? 'Update Event' : 'Submit Event'; ?></button>
    </form>

    <?php
    return ob_get_clean();
});

// Store form messages in user meta for display after redirect
function esl_set_form_message($message, $type = 'success') {
    set_transient('esl_form_message_' . get_current_user_id(), [
        'message' => $message,
        'type'    => $type,
    ], 60); // Expire after 60 seconds
}

function esl_get_form_message() {
    $user_id = get_current_user_id();
    $msg = get_transient('esl_form_message_' . $user_id);
    if ($msg) {
        delete_transient('esl_form_message_' . $user_id);
    }
    return $msg;
}

add_action('init', function () {
    if (isset($_POST['submit_event'])) {
        $result = esl_process_event_submission();
        if ($result['success']) {
            wp_redirect($result['redirect']);
            exit;
        } else {
            esl_set_form_message($result['message'], 'error');
            wp_redirect(home_url('/events-dashboard'));
            exit;
        }
    }
});

/**
 * Handle event deletion early (before output) so wp_redirect() works reliably.
 * Shortcode-level redirects fail because headers are already sent by the time
 * shortcodes render inside the page template.
 */
add_action('template_redirect', function () {
    if (!is_page('events-dashboard') || !isset($_GET['delete'])) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    $user_id  = get_current_user_id();
    $event_id = intval($_GET['delete']);

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'esl_delete_event_' . $event_id)) {
        wp_die(__('Security check failed.', 'event-submission-layer'));
    }

    $post = get_post($event_id);

    if ($post && $post->post_author == $user_id) {
        wp_delete_post($event_id, true);
        esl_set_form_message(__('Event deleted.', 'event-submission-layer'), 'success');
    }

    wp_redirect(home_url('/events-dashboard'));
    exit;
});

/**
 * 📋 SHORTCODE: USER DASHBOARD
 */
add_shortcode('events_dashboard', function () {

    if (!is_user_logged_in()) {
        return '<p>Please log in.</p>';
    }

    $options = get_option('esl_options');
    if (empty($options['enable_frontend'])) {
        return __('Frontend submission is disabled.', 'event-submission-layer');
    }

    if (!function_exists('sugar_calendar_add_event')) {
        return '<p>' . __('Event management is currently unavailable.', 'event-submission-layer') . '</p>';
    }

    $user_id = get_current_user_id();

    $status = ['publish'];
    if (current_user_can('read_private_posts')) {
        $status[] = 'private';
    }

    $events = get_posts([
        'post_type' => 'sc_event',
        'author' => $user_id,
        'post_status' => $status,
        'posts_per_page' => -1,
    ]);

    ob_start();

    // Display messages
    $msg = esl_get_form_message();
    if ($msg) {
        $bg_color = ($msg['type'] === 'error') ? '#fee' : '#efe';
        $border_color = ($msg['type'] === 'error') ? '#c33' : '#3c3';
        echo '<div style="background: ' . $bg_color . '; border: 1px solid ' . $border_color . '; padding: 10px; margin-bottom: 15px; border-radius: 4px;">';
        echo esc_html($msg['message']);
        echo '</div>';
    }

    echo '<p><a href="' . esc_url( esl_get_add_event_page_url() ) . '" style="background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 15px;">Add New Event</a></p>';

    if (empty($events)) {
        echo '<p>No events yet.</p>';
    } else {
        foreach ($events as $event) {
            echo '<div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px;">';
            echo '<strong style="display: block; margin-bottom: 5px;">' . esc_html($event->post_title) . '</strong>';
            echo '<small style="display: block; color: #666; margin-bottom: 8px;">' . esc_html($event->post_content) . '</small>';
            echo '<a href="?edit=' . $event->ID . '" style="margin-right: 10px;">Edit</a>';
            echo '<a href="' . esc_url( wp_nonce_url( '?delete=' . $event->ID, 'esl_delete_event_' . $event->ID ) ) . '" onclick="return confirm(\'Delete this event?\')">Delete</a>';
            echo '</div>';
        }
    }

    // EDIT FORM
    if (isset($_GET['edit'])) {
        $event_id = intval($_GET['edit']);
        $event = get_post($event_id);

        if ($event && $event->post_author == $user_id) {

            // Prefer Sugar Calendar event data if available; fallback to post meta.
            $sc_event = function_exists('sugar_calendar_get_event_by_object')
                ? sugar_calendar_get_event_by_object($event_id, 'post')
                : null;

            if ($sc_event && !empty($sc_event->start)) {
                $start = strtotime($sc_event->start);
                $end   = strtotime($sc_event->end ?? $sc_event->start);
            } else {
                $start = intval(get_post_meta($event_id, 'start', true));
                $end   = intval(get_post_meta($event_id, 'end', true));
            }

            if ($start <= 0) {
                $start = time();
            }

            if ($end <= 0 || $end < $start) {
                $end = $start + 3600;
            }

            // Keep plugin form in sync with actual SC event metadata.
            update_post_meta($event_id, 'start', $start);
            update_post_meta($event_id, 'end', $end);

            ?>
            <hr style="margin: 20px 0;" />
            <h3>Edit Event</h3>
            <form method="post" style="max-width: 400px;" id="esl-event-form">
                <?php wp_nonce_field('esl_submit_event', 'esl_nonce'); ?>

                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>" />

                <div style="margin-bottom: 12px;">
                    <label for="event_title_edit">Event Title</label>
                    <input type="text" id="event_title_edit" name="event_title"
                        value="<?php echo esc_attr($event->post_title); ?>" required style="width: 100%; padding: 8px;" />
                </div>

                <div style="margin-bottom: 12px;">
                    <label for="event_description_edit">Description</label>
                    <textarea id="event_description_edit" name="event_description" style="width: 100%; padding: 8px; min-height: 80px;"><?php echo esc_textarea($event->post_content); ?></textarea>
                </div>

                <div style="margin-bottom: 12px;">
                    <label for="event_start_edit">Start</label>
                    <input type="datetime-local" id="event_start_edit" name="event_start"
                        value="<?php echo esc_attr(gmdate('Y-m-d\TH:i', $start)); ?>" required style="width: 100%; padding: 8px;" />
                </div>

                <div style="margin-bottom: 12px;">
                    <label for="event_end_edit">End</label>
                    <input type="datetime-local" id="event_end_edit" name="event_end"
                        value="<?php echo esc_attr(gmdate('Y-m-d\TH:i', $end)); ?>" required style="width: 100%; padding: 8px;" />
                </div>

                <button type="submit" name="submit_event" style="padding: 8px 16px; background: #0073aa; color: white; border: none; cursor: pointer;">Update Event</button>
            </form>
            <?php
        } else {
            if ($event) {
                echo '<p style="color: red;">You do not have permission to edit this event.</p>';
            } else {
                echo '<p style="color: red;">Event not found.</p>';
            }
        }
    }

    // CREATE FORM
    if (isset($_GET['create'])) {
        ?>
        <hr style="margin: 20px 0;" />
        <h3>Add New Event</h3>
        <form method="post" style="max-width: 400px;" id="esl-event-form">
            <?php wp_nonce_field('esl_submit_event', 'esl_nonce'); ?>

            <div style="margin-bottom: 12px;">
                <label for="event_title_new">Event Title</label>
                <input type="text" id="event_title_new" name="event_title" placeholder="Event Title" required style="width: 100%; padding: 8px;" />
            </div>

            <div style="margin-bottom: 12px;">
                <label for="event_description_new">Description</label>
                <textarea id="event_description_new" name="event_description" placeholder="Description" style="width: 100%; padding: 8px; min-height: 80px;"></textarea>
            </div>

            <div style="margin-bottom: 12px;">
                <label for="event_start_new">Start</label>
                <input type="datetime-local" id="event_start_new" name="event_start" required style="width: 100%; padding: 8px;" />
            </div>

            <div style="margin-bottom: 12px;">
                <label for="event_end_new">End</label>
                <input type="datetime-local" id="event_end_new" name="event_end" required style="width: 100%; padding: 8px;" />
            </div>

            <button type="submit" name="submit_event" style="padding: 8px 16px; background: #0073aa; color: white; border: none; cursor: pointer;">Create Event</button>
        </form>
        <?php
    }

    return ob_get_clean();
});