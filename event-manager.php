<?php
/**
 * Plugin Name: Event Submission Layer
 * Description: Frontend event submission + management for Sugar Calendar Lite
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

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

        // allow viewing the private dashboard page
        'read_private_pages'    => true,
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
        'read_private_pages' => true,
    ];

    foreach ($caps as $cap => $grant) {
        if ($grant && !$role->has_cap($cap)) {
            $role->add_cap($cap);
        }
    }
}
add_action('wp_enqueue_scripts', function () {
    // Only load on pages with our forms
    if (is_page(['add-event', 'events-dashboard'])) {
        wp_enqueue_style('flatpickr-css', plugin_dir_url(__FILE__) . 'assets/css/flatpickr.min.css', [], '4.6.13');
        wp_enqueue_script('flatpickr-js', plugin_dir_url(__FILE__) . 'assets/js/flatpickr.min.js', [], '4.6.13', true);
        
        // Add custom script for initialization
        wp_add_inline_script('flatpickr-js', "
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Flatpickr on datetime-local inputs
                flatpickr('input[type=\"datetime-local\"]', {
                    enableTime: true,
                    dateFormat: 'Y-m-dTH:i',
                    time_24hr: false,
                    minuteIncrement: 15,
                    altInput: true,
                    altFormat: 'F j, Y at h:i K',
                    defaultDate: new Date(),
                    onChange: function(selectedDates, dateStr, instance) {
                        // Update the original input with the format expected by the form
                        instance.input.value = dateStr;
                    }
                });
            });
        ");
    }
});

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
 * 🧾 SHORTCODE: EVENT SUBMISSION FORM
 */
add_shortcode('event_submit_form', function () {

    if (!is_user_logged_in()) {
        return '<p>Please log in to submit an event.</p>';
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

    <form method="post" action="">
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

    if (!isset($_POST['submit_event'])) {
        return;
    }

    if (!is_user_logged_in()) {
        error_log('[ESL] Form submission: user not logged in');
        return;
    }

    if (!isset($_POST['esl_nonce']) || !wp_verify_nonce($_POST['esl_nonce'], 'esl_submit_event')) {
        error_log('[ESL] Form submission: nonce verification failed');
        return;
    }

    if (!function_exists('sugar_calendar_add_event')) {
        error_log('[ESL] Form submission: Sugar Calendar functions not available');
        return;
    }

    $user_id = get_current_user_id();

    $event_title       = sanitize_text_field($_POST['event_title'] ?? '');
    $event_description = sanitize_textarea_field($_POST['event_description'] ?? '');
    $event_start_raw   = sanitize_text_field($_POST['event_start'] ?? '');
    $event_end_raw     = sanitize_text_field($_POST['event_end'] ?? '');

    error_log('[ESL] Form submission: title=' . $event_title . ', start=' . $event_start_raw . ', end=' . $event_end_raw);

    // Validate inputs
    if (empty($event_title)) {
        esl_set_form_message('Event title is required.', 'error');
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

    if (empty($event_start_raw) || empty($event_end_raw)) {
        esl_set_form_message('Event start and end dates are required.', 'error');
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

    $start_timestamp = strtotime($event_start_raw);
    $end_timestamp   = strtotime($event_end_raw);

    if ($start_timestamp === false) {
        esl_set_form_message('Invalid start date: ' . esc_html($event_start_raw), 'error');
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

    if ($end_timestamp === false) {
        esl_set_form_message('Invalid end date: ' . esc_html($event_end_raw), 'error');
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

    if ($start_timestamp > $end_timestamp) {
        $end_timestamp = $start_timestamp;
    }

    // Build post data for sc_event post type
    $post_data = [
        'post_title'   => $event_title,
        'post_content' => $event_description,
        'post_author'  => $user_id,
        'post_type'    => 'sc_event',
        'post_status'  => 'publish',
    ];

    // Update existing event
    if (!empty($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        $post = get_post($event_id);

        if (!$post) {
            esl_set_form_message('Event not found.', 'error');
            error_log('[ESL] Update: post not found for ID ' . $event_id);
            wp_redirect(home_url('/events-dashboard'));
            exit;
        }

        if ($post->post_type !== 'sc_event') {
            esl_set_form_message('Invalid event type.', 'error');
            error_log('[ESL] Update: wrong post type for ID ' . $event_id);
            wp_redirect(home_url('/events-dashboard'));
            exit;
        }

        if ($post->post_author != $user_id && !current_user_can('edit_others_events')) {
            esl_set_form_message('You do not have permission to edit this event.', 'error');
            error_log('[ESL] Update: permission denied for user ' . $user_id . ' on event ' . $event_id);
            wp_redirect(home_url('/events-dashboard'));
            exit;
        }

        error_log('[ESL] Update: processing event_id=' . $event_id . ', user=' . $user_id);

        $post_data['ID'] = $event_id;
        $post_update = wp_update_post($post_data);

        if (is_wp_error($post_update)) {
            esl_set_form_message('Failed to update post: ' . $post_update->get_error_message(), 'error');
            error_log('[ESL] Update: wp_update_post failed: ' . print_r($post_update, true));
            wp_redirect(home_url('/events-dashboard'));
            exit;
        }

        $event_row = esl_get_sc_event_row($event_id);
        error_log('[ESL] Update: event_row=' . print_r($event_row, true));

        // Always store in UTC for Sugar Calendar internal event table.
        $event_data = [
            'object_id'      => $event_id,
            'object_type'    => 'post',
            'object_subtype' => 'sc_event',
            'title'          => $event_title,
            'content'        => $event_description,
            'status'         => 'publish',
            'start'          => gmdate('Y-m-d H:i:s', $start_timestamp),
            'end'            => gmdate('Y-m-d H:i:s', $end_timestamp),
            'all_day'        => 0,
            'start_tz'       => '',
            'end_tz'         => '',
        ];

        if (!empty($event_row) && !empty($event_row->id)) {
            $event_row_id = $event_row->id;

            $updated = sugar_calendar_update_event($event_row_id, $event_data, $event_row);
            error_log('[ESL] Update: sugar_calendar_update_event returned ' . var_export($updated, true));

            $after_event = sugar_calendar_get_event($event_row_id);

            // Check that SC event content was updated as expected.
            if (!empty($after_event) && isset($after_event->content) && ($after_event->content !== $event_data['content'])) {
                error_log('[ESL] Update: content mismatch after update; trying direct event update');
                sugar_calendar_update_event($event_row_id, ['content' => $event_data['content']], $after_event);
                $after_event = sugar_calendar_get_event($event_row_id);
            }

            $row_matches = esl_event_data_matches($event_data, $after_event);

            if (!$updated || !$row_matches) {
                // If no rows were changed or state hasn't synced, force by replacing.
                sugar_calendar_delete_event($event_row_id);
                $new_event_id = sugar_calendar_add_event($event_data);
                error_log('[ESL] Update fallback replace row. deleted=' . $event_row_id . ' added=' . var_export($new_event_id, true));
            }
        } else {
            $added_id = sugar_calendar_add_event($event_data);
            error_log('[ESL] Update: sugar_calendar_add_event returned ' . var_export($added_id, true));
        }

        // Sync post meta via Sugar Calendar back-compat keys.
        update_post_meta($event_id, 'sc_event_date_time', $start_timestamp);
        update_post_meta($event_id, 'sc_event_end_date_time', $end_timestamp);
        update_post_meta($event_id, 'start', $start_timestamp);
        update_post_meta($event_id, 'end', $end_timestamp);

        esl_set_form_message('Event updated successfully!', 'success');
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

    // New event
    $new_post_id = wp_insert_post($post_data);

    if (is_wp_error($new_post_id)) {
        esl_set_form_message('Failed to create event: ' . $new_post_id->get_error_message(), 'error');
        error_log('[ESL] Create: wp_insert_post failed: ' . print_r($new_post_id, true));
        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

    if ($new_post_id) {
        $event_data = [
            'object_id'      => $new_post_id,
            'object_type'    => 'post',
            'object_subtype' => 'sc_event',
            'title'          => $event_title,
            'content'        => $event_description,
            'status'         => 'publish',
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
            error_log('[ESL] Create: sugar_calendar_add_event failed: ' . print_r($added_id, true));
        } elseif (!$added_id) {
            error_log('[ESL] Create: sugar_calendar_add_event returned false/empty');
        }

        esl_set_form_message('Event created successfully!', 'success');
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

    $user_id = get_current_user_id();

    // DELETE
    if (isset($_GET['delete'])) {
        $event_id = intval($_GET['delete']);
        $post = get_post($event_id);

        if ($post && $post->post_author == $user_id) {
            wp_delete_post($event_id, true);
            set_transient('esl_form_message_' . $user_id, [
                'message' => 'Event deleted.',
                'type'    => 'success',
            ], 60);
        }

        wp_redirect(home_url('/events-dashboard'));
        exit;
    }

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
            echo '<a href="?delete=' . $event->ID . '" onclick="return confirm(\'Delete this event?\')">Delete</a>';
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
            <form method="post" style="max-width: 400px;">
                <?php wp_nonce_field('esl_submit_event', 'esl_nonce'); ?>

                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />

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
        <form method="post" style="max-width: 400px;">
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