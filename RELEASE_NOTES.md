# Event Submission Layer v1.0.5 - Release Notes

**Release Date:** April 10, 2026
**Version:** 1.0.5
**Repository:** [thewmh/event-submission-layer](https://github.com/thewmh/event-submission-layer)
**License:** GPL-2.0-or-later

## Fixes

- **Fixed settings checkbox that could not be unchecked**: The "Enable Frontend Submission" checkbox appeared to uncheck and save successfully, but would revert to checked on the next page load. This is a classic WordPress Settings API behaviour: unchecked checkboxes are not submitted by the browser, so without a sanitization callback the key was simply absent from the saved option, causing the render fallback (`1`) to always win. Added `esl_sanitize_options()` as the `sanitize_callback` for `register_setting()`, which explicitly writes `enable_frontend => 0` when the field is missing from POST.
- **Fixed "Frontend submission is disabled" on fresh installs**: On a new activation, `esl_options` did not exist in the database, so `get_option('esl_options')` returned `false`. Both the `events_dashboard` and `event_submit_form` shortcodes check `empty($options['enable_frontend'])`, which evaluated to `true` for `false`, causing every event_submitter to see "Frontend submission is disabled" despite the admin settings page showing the checkbox as checked. The activation hook now seeds `esl_options` with `enable_frontend => 1` when no option exists, so the plugin works immediately after activation.
- **Tightened settings option rendering**: The checkbox render fallback changed from `1` to `0`. The real default is now the value seeded at activation; the render function should never need to manufacture a default.

## Planned Enhancements

The following improvements have been identified and are deferred to a future release:

- **Modularize the plugin**: Extract the single-file architecture into separate include files (`roles.php`, `pages.php`, `admin.php`, `shortcodes.php`, `processing.php`, `helpers.php`). Deferred until basic test coverage exists to validate the refactor safely.
- **Add test coverage**: No PHPUnit or JS tests currently exist. Priority is unit tests for `esl_process_event_submission()` and the role/cap logic.
- **Pagination on events dashboard**: The dashboard fetches all user events with `posts_per_page => -1`, which will degrade performance at scale.
- **Extract inline styles to a CSS file**: All UI styling is currently via inline `style` attributes, making the interface difficult to customise from themes. Introduce `assets/css/esl-plugin.css`.
- **Remove committed build artifact**: `event-submission-layer-v1.0.0.zip` is tracked in git and should be removed; add `*.zip` to `.gitignore`.
- **Strict comparison for author checks**: Several author ID comparisons use `==` instead of `===`. Convert to `(int) $post->post_author === $user_id`.
- **Fix misleading `dev`/`watch` npm scripts**: Both currently just run `npm run build` with no file watching. Either implement actual watch behaviour or remove the aliases.

---

# Event Submission Layer v1.0.4 - Release Notes

**Release Date:** April 10, 2026
**Version:** 1.0.4
**Repository:** [thewmh/event-submission-layer](https://github.com/thewmh/event-submission-layer)
**License:** GPL-2.0-or-later

## Fixes

- **Resolved plugin load-order issues**: The Sugar Calendar dependency check previously ran at PHP parse time, before all plugins had loaded. This caused a file-level `return` that silently prevented all hooks from registering — including the admin-access redirect for `event_submitter` users and the plugin activation hook. Moved the check to `plugins_loaded` where it belongs.
- **Fixed admin error banner appearing on every page**: The Sugar Calendar dependency error was showing as a persistent, site-wide admin notice even when Sugar Calendar was active (because the check fired too early). Replaced with a one-time dismissible notice that only appears when Sugar Calendar is genuinely absent, and automatically resets if Sugar Calendar is removed and re-added later.
- **Fixed `event_submitter` users seeing the WP dashboard**: The `admin_init` redirect hook was never registering due to the load-order bug above. This is now resolved.
- **Fixed role capability sync for existing installs**: `esl_ensure_role_caps()` was defined but never hooked, making it dead code. It is now called on `plugins_loaded` to keep role capabilities in sync across code updates without requiring plugin reactivation. It also removes the `read_private_pages` capability from any installs where it was granted directly on the role (v1.0.1 behaviour), ensuring the scoped `user_has_cap` filter introduced in v1.0.3 is the sole access control path.
- **Fixed CSRF vulnerability on event deletion**: Delete links now include a per-event WordPress nonce via `wp_nonce_url()`. The delete handler verifies the nonce before any database operation and calls `wp_die()` on failure, preventing CSRF attacks where a crafted link could silently delete a user's event.
- **Removed redundant AJAX nonce**: An `esl_ajax_nonce` was being generated, localized to the frontend, and sent as a `security` field — but was never verified server-side. The form's existing `esl_nonce` (serialized via `FormData`) is the verified credential. Removed the unused nonce from `wp_localize_script` and `esl-ajax.js`.
- **Fixed uninstall page cleanup**: Options were deleted before their values were read, causing `wp_delete_post()` to always receive `false` — meaning plugin-created pages were never actually removed on uninstall. Read order is now corrected. Also cleans up the new `esl_sc_notice_dismissed` option.
- **Added missing output escaping**: Hidden `event_id` input in the dashboard edit form now uses `esc_attr()`, aligning with WordPress coding standards.

## Planned Enhancements

The following improvements have been identified and are deferred to a future release:

- **Modularize the plugin**: Extract the single-file architecture into separate include files (`roles.php`, `pages.php`, `admin.php`, `shortcodes.php`, `processing.php`, `helpers.php`). Deferred until basic test coverage exists to validate the refactor safely.
- **Add test coverage**: No PHPUnit or JS tests currently exist. Priority is unit tests for `esl_process_event_submission()` and the role/cap logic.
- **Pagination on events dashboard**: The dashboard fetches all user events with `posts_per_page => -1`, which will degrade performance at scale.
- **Extract inline styles to a CSS file**: All UI styling is currently via inline `style` attributes, making the interface difficult to customise from themes. Introduce `assets/css/esl-plugin.css`.
- **Remove committed build artifact**: `event-submission-layer-v1.0.0.zip` is tracked in git and should be removed; add `*.zip` to `.gitignore`.
- **Strict comparison for author checks**: Several author ID comparisons use `==` instead of `===`. Convert to `(int) $post->post_author === $user_id`.
- **Fix misleading `dev`/`watch` npm scripts**: Both currently just run `npm run build` with no file watching. Either implement actual watch behaviour or remove the aliases.

---

# Event Submission Layer v1.0.3 - Release Notes

**Release Date:** April 9, 2026  
**Version:** 1.0.3  
**Repository:** [thewmh/event-submission-layer](https://github.com/thewmh/event-submission-layer)  
**License:** GPL-2.0-or-later

## 🧩 Fixes

- **Private page access fixed**: Event Submitter users no longer gain broad `read_private_pages` access across the site
- **Scoped access**: Grant private page view only for plugin-specific pages `events-dashboard` and `add-event`
- **Role security tightened**: Prevents Event Submitters from seeing unrelated private pages while retaining required plugin functionality

---

# Event Submission Layer v1.0.2 - Release Notes

- **Admin Settings Page**: Added a comprehensive settings page in WordPress admin for configuring plugin options
- **AJAX Form Submission**: Implemented AJAX-powered form submissions for better user experience without page reloads
- **Configurable Event Status**: Admins can now set the default status for new events (Publish or Pending Review)
- **Enhanced Plugin Header**: Added complete plugin metadata including author, license, and compatibility information
- **Internationalization Support**: Added text domain and translation-ready strings
- **Uninstall Cleanup**: Proper uninstall hook to clean up options, roles, and pages on plugin removal
- **Requirement Checks**: Added checks for PHP version and Sugar Calendar dependency with admin notices
- **Improved Error Handling**: Better error messages and validation with user-friendly feedback

## 🛠️ Technical Improvements

- **Code Refactoring**: Extracted form processing logic into reusable functions
- **Security Enhancements**: Improved nonce handling and input validation
- **Asset Management**: Organized JavaScript and CSS assets in proper directory structure
- **Settings API Integration**: Used WordPress Settings API for admin configuration
- **AJAX Integration**: Proper WordPress AJAX handlers for frontend submissions

## 📦 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the latest release zip from [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)
2. Go to **WordPress Admin** → **Plugins** → **Add New** → **Upload Plugin**
3. Choose the downloaded `.zip` file and click **Install Now**
4. Activate the plugin

### Method 2: WP-CLI
```bash
wp plugin install https://github.com/thewmh/event-submission-layer/releases/download/v1.0.1/event-submission-layer-v1.0.1.zip --activate
```

### Method 3: Manual Installation
1. Download and unzip the release from GitHub
2. Upload the `event-submission-layer/` folder to `wp-content/plugins/`
3. Activate through WordPress Admin

### Method 4: Development Installation
```bash
cd wp-content/plugins/
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer
npm install && npm run build
wp plugin activate event-submission-layer
```

---

# Event Submission Layer v1.0.0 - Release Notes

### Method 1: WordPress Admin (Recommended)
1. Download the latest release zip from [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)
2. Go to **WordPress Admin** → **Plugins** → **Add New** → **Upload Plugin**
3. Choose the downloaded `.zip` file and click **Install Now**
4. Activate the plugin

### Method 2: WP-CLI
```bash
wp plugin install https://github.com/thewmh/event-submission-layer/releases/download/v1.0.1/event-submission-layer-v1.0.1.zip --activate
```

### Method 3: Manual Installation
1. Download and unzip the release from GitHub
2. Upload the `event-submission-layer/` folder to `wp-content/plugins/`
3. Activate through WordPress Admin

### Method 4: Development Installation
```bash
cd wp-content/plugins/
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer
npm install && npm run build
wp plugin activate event-submission-layer
```

---

# Event Submission Layer v1.0.0 - Release Notes

**Release Date:** April 8, 2026  
**Version:** 1.0.0  
**Repository:** [thewmh/event-submission-layer](https://github.com/thewmh/event-submission-layer)  
**License:** GPL-2.0-or-later

## 🎉 Overview

The Event Submission Layer is a comprehensive WordPress plugin that provides a frontend event submission and management system for Sugar Calendar Lite. This initial release brings professional-grade event management capabilities to WordPress sites with an emphasis on user experience, security, and developer-friendly architecture.

## ✨ Key Features

### Core Functionality
- **Frontend Event Submission**: Clean, intuitive forms for creating and editing events
- **User Dashboard**: Private dashboard for event submitters to manage their events
- **Role-Based Access Control**: Custom "Event Submitter" role with specific capabilities
- **Sugar Calendar Integration**: Full integration with Sugar Calendar Lite's event system

### User Experience
- **Enhanced Date/Time Picker**: Flatpickr-powered date/time selection with improved UX
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Loading States & Feedback**: Real-time feedback for form submissions and actions
- **Confirmation Dialogs**: User-friendly confirmations for destructive actions

### Security & Performance
- **Nonce Verification**: CSRF protection on all forms and actions
- **Input Sanitization**: Comprehensive validation and sanitization of user inputs
- **Permission Checks**: Granular permission system for all operations
- **Optimized Database Queries**: Efficient data handling and caching

## 📦 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the latest release zip from [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)
2. Go to **WordPress Admin** → **Plugins** → **Add New** → **Upload Plugin**
3. Choose the downloaded `.zip` file and click **Install Now**
4. Activate the plugin

### Method 2: WP-CLI
```bash
wp plugin install https://github.com/thewmh/event-submission-layer/releases/download/v1.0.0/event-submission-layer-v1.0.0.zip --activate
```

### Method 3: Manual Installation
1. Download and unzip the release from GitHub
2. Upload the `event-submission-layer/` folder to `wp-content/plugins/`
3. Activate through WordPress Admin

### Method 4: Development Installation
```bash
cd wp-content/plugins/
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer
npm install && npm run build
wp plugin activate event-submission-layer
```

## 🔧 Requirements

- **WordPress**: 6.2 or higher
- **PHP**: 7.4 or higher
- **Sugar Calendar Lite**: Required for event storage and management
- **Modern Browser**: For the enhanced date/time picker interface

## 🚀 Usage

### For Event Submitters
1. **Access Dashboard**: Visit `/events-dashboard` (created automatically)
2. **Create Events**: Use "Add New Event" to submit events
3. **Manage Events**: Edit or delete events from your personal dashboard

### For Administrators
1. **User Management**: Assign "Event Submitter" role to users
2. **Content Oversight**: Events appear in Sugar Calendar and WordPress admin
3. **Monitor Activity**: Check logs for submission activity

## 🏗️ Technical Architecture

### Dependencies
- **Sugar Calendar Lite**: Core event management backend
- **Flatpickr**: Modern date/time picker library (bundled)
- **WordPress Core**: User roles, post types, and security functions

### Database Integration
- Uses WordPress posts table (`sc_event` post type)
- Leverages Sugar Calendar's custom event metadata tables
- WordPress user meta for temporary session data

### Build System
- **NPM/Node.js**: Dependency management and build pipeline
- **GitHub Actions**: Automated CI/CD with release management
- **Distribution Zips**: Automated creation of installable packages

## 🔒 Security Features

- **CSRF Protection**: Nonce verification on all forms
- **Input Validation**: Comprehensive sanitization and validation
- **Permission System**: Role-based access control
- **Secure Coding**: Following WordPress coding standards

## 📋 Planned Enhancements

### Phase 1 (Security & UX - Q2 2026)
- Rate limiting for form submissions
- Advanced input validation
- Better error handling and user feedback
- Loading states and progress indicators

### Phase 2 (Core Features - Q3 2026)
- Event categories and taxonomy support
- Status management workflow
- Bulk actions for event management
- Email notifications system

### Phase 3 (Advanced Features - Q4 2026)
- Recurring events support
- Location management
- Google Calendar integration
- Analytics dashboard

## 🐛 Known Issues & Limitations

- Requires Sugar Calendar Lite as a dependency
- Date/time picker requires JavaScript enabled
- Mobile experience could be further optimized
- No bulk import/export functionality yet

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/thewmh/event-submission-layer/blob/master/CONTRIBUTING.md) for details.

### Development Setup
```bash
git clone https://github.com/thewmh/event-submission-layer.git
cd event-submission-layer
npm install
npm run build
```

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/thewmh/event-submission-layer/issues)
- **Documentation**: [README.md](https://github.com/thewmh/event-submission-layer/blob/master/README.md)
- **Releases**: [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)

## 🙏 Acknowledgments

- **Sugar Calendar Team**: For the excellent event management foundation
- **Flatpickr**: For the beautiful date/time picker component
- **WordPress Community**: For the robust plugin ecosystem

---

**Event Submission Layer v1.0.0** - Bringing professional event management to WordPress frontend. 🎯</content>