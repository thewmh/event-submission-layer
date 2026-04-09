# Event Submission Layer for Sugar Calendar

A WordPress plugin that provides a frontend event submission and management system for Sugar Calendar Lite. Allows users with the "Event Submitter" role to create, edit, and manage their events through a user-friendly dashboard.

## Features

### ✅ Current Features
- **Role-based Access Control**: Custom "Event Submitter" role with specific capabilities
- **Frontend Event Submission**: Clean forms for creating and editing events
- **User Dashboard**: Private dashboard for managing personal events
- **Sugar Calendar Integration**: Full integration with Sugar Calendar Lite's event system
- **Date/Time Picker**: Flatpickr-powered date/time selection with enhanced UX
- **Security**: Nonce verification, input sanitization, and permission checks
- **Responsive Design**: Works on desktop and mobile devices
- **Admin Settings Page**: Configure plugin options including frontend enable/disable and default event status
- **AJAX Submissions**: No page reloads on form submissions for better UX
- **Internationalization**: Translation-ready with text domain support
- **Requirement Validation**: Checks for PHP version and dependencies on activation

### 🚧 Planned Enhancements

#### High Priority (Immediate Impact)
- **Security Enhancements**: Rate limiting, advanced validation, file upload support
- **UX Improvements**: Loading states, better error handling, confirmation dialogs
- **Core Features**: Event categories, status management, bulk actions

#### Medium Priority (Quality of Life)
- **Performance**: Pagination, caching, database optimization
- **Advanced Features**: Recurring events, locations, email notifications
- **Code Quality**: Modular structure, settings page, REST API

## Admin Settings

The plugin includes an admin settings page accessible via **WordPress Admin** → **Event Submission**. Available options:

- **Enable Frontend Submission**: Toggle frontend event submission on/off
- **Default Event Status**: Set the default status for new events (Publish or Pending Review)

## User Guide for Event Submitters

If your WordPress administrator has assigned you the "Event Submitter" role, you can create and manage events directly from the website's frontend without needing to access the WordPress admin area. This guide explains how to use the event management features in simple, step-by-step instructions.

### Important Notes Before You Start
- You can only view and manage events that you have created yourself
- All dates and times are displayed in a user-friendly format, but are stored in UTC (Coordinated Universal Time) internally
- Make sure you're logged in to your account before trying to access event features
- If you encounter any issues, contact your site administrator

### Accessing Your Events Dashboard
Your personal events dashboard is available at: `https://yoursite.com/events-dashboard`

This page shows:
- A list of all events you've created
- Options to edit or delete each event
- A button to add new events
- Any success or error messages from your recent actions

### How to Add a New Event
1. **Navigate to the Add Event Page**: Click the "Add New Event" button on your dashboard, or visit: `https://yoursite.com/add-event`
2. **Fill in the Event Details**:
   - **Event Title**: Enter a clear, descriptive name for your event
   - **Description**: Provide details about what the event is about (optional but recommended)
   - **Start Date & Time**: Click on the date/time field and select when your event begins
   - **End Date & Time**: Select when your event ends (must be after the start time)
3. **Submit Your Event**: Click the "Submit Event" button to save
4. **Confirmation**: You'll be redirected back to your dashboard with a success message

**Tip**: The date/time picker shows dates in a format like "April 15, 2026 at 2:30 PM" for easy reading, but stores the exact time you selected.

### How to Edit an Existing Event
1. **Find the Event**: On your dashboard, locate the event you want to change
2. **Click Edit**: Click the "Edit" link next to that event
3. **Make Changes**: Update any of the fields (title, description, dates) as needed
4. **Save Changes**: Click the "Update Event" button
5. **Confirmation**: You'll return to your dashboard with a confirmation message

### How to Delete an Event
1. **Find the Event**: Locate the event you want to remove on your dashboard
2. **Click Delete**: Click the "Delete" link next to the event
3. **Confirm Deletion**: A confirmation dialog will appear - click OK to proceed
4. **Confirmation**: The event will be permanently removed, and you'll see a success message

**Warning**: Deleted events cannot be recovered. Make sure you really want to remove the event before confirming.

### Troubleshooting Common Issues
- **"Please log in to submit an event"**: Make sure you're logged in to your account
- **"Frontend submission is disabled"**: Contact your administrator - they may have temporarily disabled event submission
- **Date/time errors**: Ensure the end time is after the start time, and that you've selected valid dates
- **Permission errors**: You can only edit or delete events you've created yourself

### Getting Help
If you need assistance:
- Check this guide again for step-by-step instructions
- Contact your site administrator for technical support
- Ensure your browser is up to date for the best experience with the date/time picker

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Sugar Calendar Lite plugin (active)

The plugin will display admin notices if requirements are not met.

## Installation

### Method 1: WordPress Admin (Recommended for End Users)
1. Download the latest release zip from [GitHub Releases](https://github.com/thewmh/event-submission-layer/releases)
2. Go to WordPress Admin → **Plugins** → **Add New** → **Upload Plugin**
3. Choose the downloaded `.zip` file and click **Install Now**
4. Activate the plugin

### Method 2: WP-CLI (For Developers)
```bash
# Install from GitHub release
wp plugin install https://github.com/thewmh/event-submission-layer/releases/download/v1.0.1/event-submission-layer-v1.0.1.zip --activate

# Or install from local build
wp plugin install /path/to/event-submission-layer-v1.0.1.zip --activate
```

### Method 3: Manual Installation
1. Download and unzip the release from GitHub
2. Upload the `event-submission-layer/` folder to `wp-content/plugins/`
3. Go to WordPress Admin → **Plugins** and activate

### Method 4: Development Installation
```bash
# Clone repository
cd wp-content/plugins/
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer

# Install dependencies and build
npm install
npm run build

# Activate in WordPress
wp plugin activate event-submission-layer
```

### Prerequisites
- WordPress 6.2+
- PHP 7.4+
- Sugar Calendar Lite plugin

## Distribution

### GitHub Releases
When you create a new release on GitHub:
1. Go to **Releases** → **Create a new release**
2. Tag version: `v1.0.0`
3. Title: `Event Submission Layer v1.0.0`
4. Description: Release notes
5. **Publish release**

GitHub Actions will automatically:
- Build the plugin
- Create the distribution zip
- Attach it to the release

### WordPress.org (Future)
For official WordPress.org distribution:
1. Ensure GPL-2.0+ license compliance
2. Remove development files (`.github/`, `package.json`, etc.)
3. Submit through [WordPress Plugin Directory](https://wordpress.org/plugins/developers/)

## Usage

### For Event Submitters
1. **Access Dashboard**: Visit `/events-dashboard` (created automatically)
2. **Add Events**: Click "Add New Event" to create events
3. **Manage Events**: Edit or delete your events from the dashboard

### For Administrators
1. **User Management**: Assign "Event Submitter" role to users
2. **Monitor Activity**: Check logs for submission activity
3. **Content Management**: Events appear in Sugar Calendar and WordPress admin

## Configuration

### Page URLs
- **Events Dashboard**: `/events-dashboard` (private page)
- **Add Event Form**: `/add-event` (private page)

### User Roles
- **Event Submitter**: Can create, edit, delete their own events
- **Administrator**: Full access to all events and settings

## Technical Details

### Dependencies
- **Sugar Calendar Lite**: Core event management
- **Flatpickr**: Date/time picker (bundled)
- **WordPress Core**: User roles, post types, nonces

### Database
- Uses WordPress posts table (`sc_event` post type)
- Sugar Calendar's custom event table for date/time data
- WordPress user meta for temporary messages

### Security
- Nonce verification on all forms
- Input sanitization and validation
- Permission checks for all operations
- CSRF protection

## Development

### Prerequisites
- Node.js 14+
- npm or yarn
- WordPress 6.2+
- PHP 7.4+
- Sugar Calendar Lite

### Setup
```bash
# Clone the repository
git clone https://github.com/thewmh/event-submission-layer.git event-submission-layer
cd event-submission-layer

# Install dependencies
npm install

# Build the plugin
npm run build
```

### Development Workflow
```bash
# Install dependencies and build assets
npm run install-assets

# Development build
npm run dev

# Production build
npm run build
```

### File Structure
```
event-submission-layer/
├── event-submission-layer.php          # Main plugin file
├── package.json               # NPM configuration and scripts
├── README.md                  # Documentation
├── .gitignore                 # Git ignore rules
├── node_modules/              # NPM dependencies (not committed)
├── dist/                      # Built plugin (not committed)
│   ├── event-submission-layer.php      # Plugin file
│   ├── README.md             # Documentation
│   └── assets/               # Bundled assets
│       ├── css/
│       │   └── flatpickr.min.css
│       └── js/
│           └── flatpickr.min.js
└── [source files for development]
```

### Build Process
The build process creates a clean, distributable plugin in the `dist/` directory:

1. **Clean**: Removes old build artifacts
2. **Copy Assets**: Copies Flatpickr from `node_modules/` to `dist/assets/`
3. **Copy Plugin**: Copies core plugin files to `dist/`

The `dist/` folder contains everything needed for installation and can be zipped for distribution.

### Distribution
```bash
# Build for distribution
npm run build

# Create zip file
cd dist && zip -r ../event-submission-layer-v1.0.1.zip .

# Or use the GitHub Actions artifact from the Actions tab
```

### Continuous Integration
This repository uses GitHub Actions for automated building and testing. Every push to `main`/`master` will:

- Install dependencies
- Build the plugin
- Upload the distributable artifact

Check the Actions tab in GitHub to download the latest build.

### Key Functions
- `esl_add_role()`: Creates the event submitter role
- `esl_create_plugin_pages()`: Sets up required pages
- `event_submit_form` shortcode: Event creation/editing form
- `events_dashboard` shortcode: User event management

### Hooks & Filters
- `wp_enqueue_scripts`: Loads Flatpickr assets
- `admin_init`: Blocks admin access for submitters
- `pre_get_posts`: Modifies dashboard queries
- `init`: Processes form submissions

## Enhancement Roadmap

### Phase 1: Security & UX (Week 1-2)
- [ ] Rate limiting for form submissions
- [ ] Advanced input validation
- [ ] File upload support for event images
- [ ] Loading states and better feedback
- [ ] Confirmation dialogs (SweetAlert2)

### Phase 2: Core Features (Week 3-4)
- [ ] Event categories and tags
- [ ] Event status management (draft/published)
- [ ] Bulk actions (delete multiple events)
- [ ] Pagination for event lists
- [ ] Search and filter functionality

### Phase 3: Performance & Quality (Week 5-6)
- [ ] Database query optimization
- [ ] Caching implementation
- [ ] Modular code structure
- [ ] Settings page for admins
- [ ] REST API endpoints

### Phase 4: Advanced Features (Week 7-8+)
- [ ] Recurring events
- [ ] Event locations/venues
- [ ] Email notifications
- [ ] Admin analytics dashboard
- [ ] Third-party integrations

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

GPL v2 or later

## Support

For issues and feature requests, please create an issue in the repository.

## Changelog

### Version 1.0.0
- Initial release
- Basic event submission and management
- Flatpickr date/time picker integration
- Role-based access control
- Sugar Calendar Lite integration