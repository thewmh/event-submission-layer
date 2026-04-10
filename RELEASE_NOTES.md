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