# Changelog

All notable changes to the LiBookin Monthly Offer plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-04

### Added
- Initial plugin release
- Custom post type `libookin_charity` for managing charities
- Charity management with logo support
- Vote submission system with AJAX
- Thank you page voting interface
- Automatic bundle product detection (woosb)
- Vote validation and security checks
- One vote per order enforcement
- Results popup system
- Monthly winner announcement
- Cookie and user meta tracking for popup display
- Admin dashboard with statistics
- Vote results page with filtering
- CSV export functionality
- Two shortcodes: `[libookin_vote_counter]` and `[libookin_current_charity]`
- Daily cache refresh cron job
- Monthly processing cron job
- Complete database structure with two custom tables
- Frontend styles with responsive design
- Admin interface styles
- Frontend JavaScript with AJAX handling
- Admin JavaScript with enhanced functionality
- Translation ready with text domain
- Comprehensive security measures (nonces, prepared statements, escaping)
- Complete documentation (README.md, SETUP-GUIDE.md)
- Uninstall script for clean removal

### Security
- Nonce verification on all AJAX requests
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping
- Order ownership validation
- User capability checks for admin functions
- Secure vote recording process

### Developer Features
- Well-organized class structure
- Singleton pattern for all classes
- WordPress coding standards compliance
- Filter and action hooks for extensibility
- Comprehensive inline documentation
- Modular file organization

### Database
- Created `wp_libookin_votes` table
- Created `wp_libookin_vote_results` table
- Proper indexing for performance
- Foreign key relationships

### Integration
- WooCommerce integration
- WooCommerce Product Bundles support
- Guest checkout support
- Order status validation
- Seamless thank you page integration

### UI/UX
- Modern, responsive design
- Animated vote counter widget
- Elegant results popup
- User-friendly admin interface
- Progress bars with percentage display
- Hover effects and transitions
- Mobile-optimized layout

### Files Added
- `libookin-monthly-offer.php` - Main plugin file
- `includes/class-database.php` - Database operations
- `includes/class-charity-post-type.php` - Custom post type
- `includes/class-vote-handler.php` - AJAX vote processing
- `includes/class-vote-display.php` - Frontend display
- `includes/class-admin.php` - Admin interface
- `includes/class-shortcodes.php` - Shortcode handlers
- `includes/class-cron.php` - Scheduled tasks
- `assets/css/styles.css` - Frontend styles
- `assets/css/admin-styles.css` - Admin styles
- `assets/js/scripts.js` - Frontend JavaScript
- `assets/js/admin-scripts.js` - Admin JavaScript
- `uninstall.php` - Clean uninstall script
- `README.md` - Complete documentation
- `SETUP-GUIDE.md` - Quick setup instructions
- `CHANGELOG.md` - Version history

## [Unreleased]

### Planned Features
- Email notifications to voters about results
- Multi-language support with pre-translated files
- Integration with popular page builders
- Advanced analytics and reporting
- Custom email templates
- Social sharing for charities
- Charity import/export functionality
- Vote scheduling options
- Anonymous voting option
- Vote modification/cancellation
- Charity categories/tags
- Featured charity widget
- Vote history for users
- API endpoints for external integrations

### Future Improvements
- Chart.js integration for visual statistics
- Enhanced admin dashboard with graphs
- Bulk charity management
- Charity verification badges
- Vote trending analysis
- Performance optimizations
- Additional shortcode parameters
- Custom CSS editor in settings
- White label options

---

## Version Numbering

- **Major version** (X.0.0): Breaking changes or major feature additions
- **Minor version** (1.X.0): New features, backward compatible
- **Patch version** (1.0.X): Bug fixes and minor improvements

## Support

For version-specific issues:
- Check the version you're running
- Review changes in this changelog
- Update to the latest version if available
- Report issues with version information

---

**Current Version: 1.0.0**
