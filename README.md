# Event Submission Layer for Sugar Calendar

A WordPress plugin that provides a frontend event submission and management system for Sugar Calendar Lite. Users with the custom "Event Submitter" role can create, edit, and delete their own events from the site frontend — no WordPress admin access required.

## Features

- **Role-based access control** — Custom `event_submitter` role with scoped Sugar Calendar capabilities; admin access is blocked and users are redirected to the frontend dashboard
- **Frontend event submission** — Clean form for creating and editing events with Flatpickr-powered date/time picker (15-minute increments, human-readable format)
- **User dashboard** — Private dashboard listing all of the user's events with edit and delete actions
- **Sugar Calendar integration** — Writes directly to Sugar Calendar Lite's post type and custom event table, with a verify-and-retry sync mechanism on updates
- **AJAX form submission** — Events are submitted without page reload; falls back to standard POST if JavaScript is unavailable
- **Admin settings page** — Configure frontend enable/disable and default event status (Publish or Pending Review) via **WordPress Admin → Event Submission**
- **Dependency notice** — One-time dismissible admin notice if Sugar Calendar is not active; resets automatically if Sugar Calendar is later removed and re-added
- **Internationalization** — Translation-ready with text domain `event-submission-layer`
- **Automatic page creation** — Creates the `/events-dashboard` and `/add-event` private pages on activation
- **Clean uninstall** — Removes all options, pages, roles, and transients on plugin deletion

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- [Sugar Calendar Lite](https://wordpress.org/plugins/sugar-calendar-lite/) (must be active)

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release zip from [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Choose the downloaded `.zip` file and click **Install Now**
4. Activate the plugin

### Method 2: WP-CLI

```bash
wp plugin install https://github.com/thewmh/event-submission-layer/releases/download/v1.0.4/event-submission-layer-v1.0.4.zip --activate
```

### Method 3: Manual Installation

1. Download and unzip the latest release from [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)
2. Upload the `event-submission-layer/` folder to `wp-content/plugins/`
3. Go to **WordPress Admin → Plugins** and activate

### Method 4: Development Installation

```bash
cd wp-content/plugins/
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer
npm install && npm run build
wp plugin activate event-submission-layer
```

## Configuration

### Admin Settings

Go to **WordPress Admin → Event Submission** to configure:

| Setting | Description |
|---------|-------------|
| **Enable Frontend Submission** | Toggle event submission and dashboard on/off for all event submitters |
| **Default Event Status** | Set whether new events are published immediately or held as Pending Review |

### Auto-Created Pages

On activation the plugin creates two private WordPress pages:

| Page | URL | Content |
|------|-----|---------|
| Events Dashboard | `/events-dashboard` | `[events_dashboard]` shortcode |
| Add New Event | `/add-event` | `[event_submit_form]` shortcode |

Both pages are set to `private` status and are only accessible to logged-in users with the `event_submitter` role.

### User Roles & Capabilities

The `event_submitter` role is created on activation and removed on deactivation. It grants:

- `read` — basic WordPress read access
- Sugar Calendar capabilities: `read_event`, `edit_event`, `delete_event`, `edit_events`, `publish_events`, `read_private_events`, `edit_private_events`, `edit_published_events`, `create_events`, `delete_events`, `delete_published_events`

Access to the two private plugin pages is granted via a scoped `user_has_cap` filter rather than a blanket `read_private_pages` capability, so event submitters cannot read other private pages on the site.

Role capabilities are synced from code on every `plugins_loaded` without requiring plugin reactivation.

## User Guide for Event Submitters

If your WordPress administrator has assigned you the "Event Submitter" role, you can create and manage events directly from the website frontend without needing to access the WordPress admin area.

### Important Notes

- You can only view and manage events that you have created yourself
- All dates and times are stored in UTC internally; the date picker displays them in your local format
- You must be logged in to access any event features
- Contact your site administrator if you encounter any issues

### Accessing Your Dashboard

Your events dashboard is at: `https://yoursite.com/events-dashboard`

It shows:
- All events you have created with edit and delete actions
- An "Add New Event" button
- Success or error messages from recent actions

### Adding an Event

1. Click **Add New Event** on your dashboard, or visit `/add-event`
2. Fill in:
   - **Event Title** (required)
   - **Description** (optional)
   - **Start Date & Time** (required)
   - **End Date & Time** (required)
3. Click **Submit Event**
4. You'll be redirected to your dashboard with a confirmation message

### Editing an Event

1. On your dashboard, click **Edit** next to the event
2. Update any fields as needed
3. Click **Update Event**
4. You'll return to your dashboard with a confirmation message

### Deleting an Event

1. On your dashboard, click **Delete** next to the event
2. Confirm the deletion in the dialog
3. You'll be redirected to your dashboard with a confirmation message

> **Warning:** Deleted events cannot be recovered.

### Troubleshooting

| Message | Cause |
|---------|-------|
| "Please log in to submit an event" | You are not logged in |
| "Frontend submission is disabled" | An administrator has disabled event submission in the plugin settings |
| "Event submission is currently unavailable" | Sugar Calendar is not active — contact your administrator |
| "You do not have permission to edit this event" | You can only edit events you created |
| Date/time errors | Ensure end time is after start time and both fields are filled |

## Uninstall Behavior

When the plugin is deleted via **WordPress Admin → Plugins**, the following is cleaned up:

- Plugin options (`esl_add_event_page_id`, `esl_events_dashboard_page_id`, `esl_options`, `esl_sc_notice_dismissed`)
- The two auto-created pages (`/events-dashboard`, `/add-event`)
- The `event_submitter` role
- All `esl_form_message_*` transients

## Technical Details

### Dependencies

| Dependency | Version | Source |
|------------|---------|--------|
| Sugar Calendar Lite | Required | WordPress plugin (external) |
| Flatpickr | ^4.6.13 | Bundled via npm |
| jQuery | WordPress bundled | WordPress core |

### Database

- Events are stored as `sc_event` posts in the WordPress posts table
- Date/time data is written to Sugar Calendar's custom event table via `sugar_calendar_add_event()` / `sugar_calendar_update_event()`
- Post meta keys `sc_event_date_time`, `sc_event_end_date_time`, `start`, `end` are kept in sync for back-compat
- Flash messages (success/error after redirects) use WordPress transients with a 60-second TTL
- Plugin configuration is stored in the `esl_options` option via the WordPress Settings API

### Security

| Protection | Implementation |
|-----------|----------------|
| Form submission CSRF | `wp_nonce_field()` / `wp_verify_nonce()` with action `esl_submit_event` |
| Event deletion CSRF | `wp_nonce_url()` / `wp_verify_nonce()` with per-event action `esl_delete_event_{ID}` |
| Input sanitization | `sanitize_text_field()`, `sanitize_textarea_field()` on all POST input |
| Output escaping | `esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()` on all output |
| Permission checks | Author ownership verified before edit/delete; `current_user_can()` for capability checks |
| Admin access | `event_submitter` users are redirected from wp-admin to `/events-dashboard` via `admin_init` |
| Private page access | Scoped via `user_has_cap` filter — only grants access to the two plugin pages |

### File Structure

```
event-submission-layer/
├── .github/
│   └── workflows/
│       └── build.yml               # GitHub Actions CI/CD
├── assets/
│   └── js/
│       └── esl-ajax.js             # Frontend AJAX form handler
├── event-submission-layer.php      # Main plugin file (all server-side logic)
├── uninstall.php                   # Cleanup on plugin deletion
├── package.json                    # npm config and build scripts
├── package-lock.json
├── README.md
└── RELEASE_NOTES.md                # Detailed per-version changelog
```

Built output (`dist/`) and dependencies (`node_modules/`) are not committed.

### Key Functions

| Function | Description |
|----------|-------------|
| `esl_add_role()` | Creates the `event_submitter` role with all required capabilities |
| `esl_ensure_role_caps()` | Syncs role capabilities from code on `plugins_loaded`; removes legacy `read_private_pages` |
| `esl_create_plugin_pages()` | Creates the dashboard and add-event private pages on activation |
| `esl_get_add_event_page_url()` | Resolves the add-event page URL with multiple fallbacks |
| `esl_process_event_submission()` | Core create/update handler — validates, creates/updates WP post, syncs Sugar Calendar event table |
| `esl_get_sc_event_row()` | Fetches the Sugar Calendar event row for a post ID with two fallback strategies |
| `esl_event_data_matches()` | Compares expected event data against an SC event row field-by-field |
| `esl_set_form_message()` | Stores a flash message in a transient for display after redirect |
| `esl_get_form_message()` | Retrieves and deletes the flash message transient |
| `esl_allow_private_plugin_pages()` | `user_has_cap` filter — scopes private page access to plugin pages only |
| `esl_admin_menu()` / `esl_admin_page()` | Registers the admin settings menu and page |
| `esl_register_settings()` | Registers plugin settings via the WordPress Settings API |

### Hooks & Actions

| Hook | Type | Purpose |
|------|------|---------|
| `plugins_loaded` | action | Sugar Calendar dependency check + dismissible notice; role cap sync |
| `register_activation_hook` | — | Creates role and pages on activation |
| `register_deactivation_hook` | — | Removes the `event_submitter` role on deactivation |
| `user_has_cap` | filter | Scopes `read_private_pages` to plugin pages only for event submitters |
| `wp_enqueue_scripts` | action | Loads Flatpickr and esl-ajax.js on plugin pages only |
| `admin_menu` | action | Registers the Event Submission settings menu |
| `admin_init` | action | Registers settings; redirects event submitters away from wp-admin |
| `pre_get_posts` | action | Includes private posts in the dashboard query for event submitters |
| `wp_ajax_esl_submit_event` | action | AJAX handler for event form submission |
| `wp_ajax_esl_dismiss_sc_notice` | action | AJAX handler for dismissing the SC dependency notice |
| `init` | action | Handles traditional (non-AJAX) form POST submission |
| `template_redirect` | action | Handles event deletion before page output begins |
| `shortcode: event_submit_form` | — | Renders the event create/edit form |
| `shortcode: events_dashboard` | — | Renders the user event dashboard |

## Development

### Prerequisites

- Node.js 14+
- npm
- WordPress 5.0+
- PHP 7.4+
- Sugar Calendar Lite (active)

### Setup

```bash
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer
npm install
npm run build
```

### Build

```bash
# Full build (clean + copy assets + copy plugin files to dist/)
npm run build

# Copy Flatpickr assets only
npm run copy-assets
```

The build produces a `dist/` directory containing the installable plugin:

```
dist/
├── event-submission-layer.php
├── README.md
├── RELEASE_NOTES.md
└── assets/
    ├── css/
    │   └── flatpickr.min.css
    └── js/
        └── flatpickr.min.js
```

### Continuous Integration

GitHub Actions runs on every push to `main`/`master` and on pull requests:

1. Install dependencies (`npm ci`)
2. Build (`npm run build`)
3. Upload `dist/` as a workflow artifact (30-day retention)

On a published GitHub Release, the zip is automatically attached as a release asset.

### Creating a Release

1. Go to **Releases → Draft a new release**
2. Set the tag to `v1.0.x`
3. Publish — GitHub Actions will build and attach the zip automatically

## Planned Enhancements

- **Modularize the plugin** — Split the single-file architecture into separate include files (`roles.php`, `pages.php`, `admin.php`, `shortcodes.php`, `processing.php`, `helpers.php`). Deferred until basic test coverage exists.
- **Test coverage** — No tests currently exist. Priority is PHPUnit tests for `esl_process_event_submission()` and role/cap logic.
- **Dashboard pagination** — The dashboard query uses `posts_per_page => -1`; needs pagination for users with many events.
- **CSS file** — All UI styling is currently inline. Extract to `assets/css/esl-plugin.css` to allow theme customisation.
- **Strict comparison for author checks** — Several `==` comparisons should be `(int) $post->post_author === $user_id`.
- **Rate limiting** — No submission rate limiting currently exists.
- **Event categories** — Sugar Calendar taxonomy support.
- **Email notifications** — Notify admins on new event submission.
- **Recurring events** — Not currently supported.

## Changelog

### 1.0.4
- Fixed plugin load-order bug where the Sugar Calendar dependency check ran at PHP parse time, blocking all hooks from registering (root cause of: admin error banner on every page, event_submitter users seeing the WP dashboard, and activation hook not running)
- Replaced persistent error banner with a one-time dismissible admin notice for missing Sugar Calendar dependency
- Fixed `event_submitter` users being shown the WP dashboard instead of redirected to `/events-dashboard`
- Wired up `esl_ensure_role_caps()` to `plugins_loaded` (was dead code); also removes legacy `read_private_pages` from existing installs
- Added CSRF nonce verification to event deletion
- Removed redundant unverified AJAX nonce
- Fixed uninstall page cleanup (options were deleted before page IDs were read)
- Fixed event delete redirect (moved handler to `template_redirect` so `wp_redirect()` fires before output)
- Added missing `esc_attr()` on hidden event_id input

### 1.0.3
- Scoped private page access — `event_submitter` users no longer gain broad `read_private_pages`; access is granted only for the two plugin-specific pages via `user_has_cap` filter

### 1.0.2
- Added admin settings page (enable frontend, default event status)
- Added AJAX form submission with non-JS fallback
- Added internationalization support
- Added uninstall cleanup
- Added PHP version and Sugar Calendar requirement checks
- Refactored form processing into reusable `esl_process_event_submission()`

### 1.0.1
- Bug fixes and stability improvements

### 1.0.0
- Initial release
- Frontend event submission and management
- Custom `event_submitter` role
- Sugar Calendar Lite integration
- Flatpickr date/time picker
- Auto-created private dashboard and add-event pages

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Make your changes
4. Submit a pull request

## License

GPL v2 or later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Support

For issues and feature requests, open an issue on [GitHub](https://github.com/thewmh/event-submission-layer/issues).
