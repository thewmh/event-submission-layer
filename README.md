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

### 🚧 Planned Enhancements

#### High Priority (Immediate Impact)
- **Security Enhancements**: Rate limiting, advanced validation, file upload support
- **UX Improvements**: Loading states, better error handling, confirmation dialogs
- **Core Features**: Event categories, status management, bulk actions

#### Medium Priority (Quality of Life)
- **Performance**: Pagination, caching, database optimization
- **Advanced Features**: Recurring events, locations, email notifications
- **Code Quality**: Modular structure, settings page, REST API

#### Future Enhancements (Advanced)
- **Analytics**: Admin dashboard with statistics
- **Integrations**: Google Calendar sync, webhooks
- **Accessibility**: ARIA labels, keyboard navigation, i18n

## Installation

1. **Prerequisites**
   - WordPress 6.2+
   - PHP 7.4+
   - Sugar Calendar Lite plugin

2. **Install the Plugin**
   ```bash
   cd wp-content/plugins/
   git clone [repository-url] event-manager
   # or download and unzip the plugin files
   ```

3. **Activate**
   - Go to WordPress Admin → Plugins
   - Activate "Event Submission Layer"

4. **Setup**
   - Plugin automatically creates required pages and roles on activation
   - Assign "Event Submitter" role to users who should manage events

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

### File Structure
```
event-manager.php/
├── event-manager.php          # Main plugin file
├── assets/
│   ├── css/
│   │   └── flatpickr.min.css  # Date picker styles
│   └── js/
│       └── flatpickr.min.js   # Date picker script
├── README.md                  # This file
└── [future files]
```

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