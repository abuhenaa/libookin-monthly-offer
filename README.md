# LiBookin Monthly Offer

A comprehensive WordPress plugin for creating monthly ebook bundles with integrated charity voting system for WooCommerce Product Bundles.

## Description

LiBookin Monthly Offer allows customers who purchase bundle products to vote for their preferred charity. The charity with the most votes each month becomes the featured charity for the following month. This creates an engaging community experience while supporting charitable causes.

## Features

### Core Functionality
- **Custom Post Type**: Dedicated "Charity" post type with logo support
- **Vote on Thank You Page**: Customers vote after purchasing bundle products
- **Secure Voting System**: One vote per order, with complete validation
- **Results Popup**: Monthly results displayed to users via popup modal
- **Vote Counter Widget**: Homepage widget showing current vote standings
- **Admin Dashboard**: Complete management interface for charities and votes
- **Automated Processing**: Cron jobs handle monthly winner selection

### Security Features
- Nonce verification for all AJAX requests
- Order ownership validation
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping
- One vote per order enforcement

### Integration
- Works seamlessly with WooCommerce
- Designed for WooCommerce Product Bundles (woosb)
- Compatible with guest checkout
- Supports both logged-in and guest users

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher
- WooCommerce Product Bundles plugin (for bundle detection)

## Installation

1. Upload the `libookin-monthly-offer` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Monthly Offer > Manage Charities** to add charities
4. Create your monthly bundle product using WooCommerce Product Bundles
5. The voting system will automatically appear on the thank you page for bundle purchases

## Configuration

### Creating Charities

1. Go to **Monthly Offer > Add New Charity**
2. Enter the charity name
3. Add a description
4. Set a featured image (charity logo)
5. Publish the charity

### Shortcodes

#### Vote Counter Widget
Display current voting results on any page or widget:

```
[libookin_vote_counter]
```

Optional parameters:
- `limit` - Number of charities to show (default: 3)

Example:
```
[libookin_vote_counter limit="5"]
```

#### Current Charity Widget
Display the current winning charity:

```
[libookin_current_charity]
```

Optional parameters:
- `show_votes` - Show vote count (default: yes)
- `show_description` - Show charity description (default: yes)

Example:
```
[libookin_current_charity show_votes="yes" show_description="no"]
```

## Database Structure

### Tables Created

#### wp_libookin_votes
Stores individual votes:
- `id` - Unique vote ID
- `order_id` - WooCommerce order ID
- `user_id` - WordPress user ID (0 for guests)
- `charity_id` - Charity post ID
- `vote_date` - Timestamp of vote

#### wp_libookin_vote_results
Stores aggregated vote counts:
- `id` - Unique result ID
- `charity_id` - Charity post ID
- `month_year` - Month in Y-m format
- `vote_count` - Total votes for the month

## Admin Interface

### Dashboard
Access via **Monthly Offer > Dashboard**

Shows:
- Total votes this month
- Number of active charities
- Current leading charity
- Real-time vote standings

### Vote Results
Access via **Monthly Offer > Vote Results**

Features:
- Monthly vote summaries
- Detailed vote history
- CSV export functionality
- Filter by month

### Manage Charities
Access via **Monthly Offer > Manage Charities**

Standard WordPress post management for charities:
- Add/Edit/Delete charities
- Set charity logos
- View charity status

## Cron Jobs

### Daily Cache Refresh
- **Hook**: `libookin_daily_cache_refresh`
- **Frequency**: Daily
- **Function**: Updates vote result cache

### Monthly Processing
- **Hook**: `libookin_monthly_process`
- **Frequency**: 1st of each month
- **Function**: Determines winner, sends notifications, resets popup flags

## Voting Process

### User Flow
1. Customer purchases a bundle product
2. Order is completed/processing
3. Customer is redirected to thank you page
4. Voting section appears with 2-3 charity options
5. Customer clicks "Vote" on their preferred charity
6. Vote is recorded via AJAX
7. Success message displays
8. Vote is counted toward monthly totals

### Validation Rules
- Order must exist and be valid
- Order status must be "completed" or "processing"
- Order must belong to the user (or match order key for guests)
- Order must contain at least one bundle product (woosb type)
- Only one vote allowed per order ID
- Charity must be published and valid

## Popup System

### Results Popup
- Displays once per user per month
- Shows after the 1st of each month
- Features the winning charity from previous month
- Includes vote count and charity details
- Link to monthly offer page
- Can be dismissed

### Popup Control
- Cookie: `libookin_vote_result_shown_{month_year}`
- User Meta: `libookin_vote_result_shown_{Y-m}`
- Both methods used for redundancy

## Customization

### Styling
- Frontend styles: `/assets/css/styles.css`
- Admin styles: `/assets/css/admin-styles.css`

### JavaScript
- Frontend scripts: `/assets/js/scripts.js`
- Admin scripts: `/assets/js/admin-scripts.js`

### Filters and Hooks

#### Available Filters
```php
// Modify number of charities shown for voting
apply_filters( 'libookin_vote_charity_limit', 3 );

// Modify eligible order statuses
apply_filters( 'libookin_eligible_order_statuses', array( 'completed', 'processing' ) );
```

#### Available Actions
```php
// After vote is recorded
do_action( 'libookin_vote_recorded', $vote_id, $order_id, $charity_id );

// After monthly processing
do_action( 'libookin_monthly_processed', $winner_data );
```

## Troubleshooting

### Voting Section Not Appearing
- Verify WooCommerce is active
- Confirm order contains bundle product (woosb type)
- Check order status is "completed" or "processing"
- Ensure charities are published

### Votes Not Being Recorded
- Check JavaScript console for errors
- Verify AJAX URL is correct
- Confirm nonce is valid
- Check database tables exist

### Popup Not Showing
- Clear browser cookies
- Check if past the 1st of the month
- Verify winner was determined for previous month
- Check popup flags in user meta

### Cron Jobs Not Running
- Verify WP-Cron is enabled
- Check server cron configuration
- Use WP-Cron Control plugin for testing
- Review error logs for issues

## Developer Information

### File Structure
```
libookin-monthly-offer/
├── assets/
│   ├── css/
│   │   ├── styles.css
│   │   └── admin-styles.css
│   └── js/
│       ├── scripts.js
│       └── admin-scripts.js
├── includes/
│   ├── class-admin.php
│   ├── class-charity-post-type.php
│   ├── class-cron.php
│   ├── class-database.php
│   ├── class-shortcodes.php
│   ├── class-vote-display.php
│   └── class-vote-handler.php
├── languages/
├── libookin-monthly-offer.php
└── README.md
```

### Class Overview

#### Main Classes
- `Libookin_Monthly_Offer` - Main plugin class
- `Libookin_MO_Database` - Database operations
- `Libookin_Charity_Post_Type` - Custom post type
- `Libookin_Vote_Handler` - AJAX vote processing
- `Libookin_Vote_Display` - Frontend display
- `Libookin_MO_Admin` - Admin interface
- `Libookin_MO_Shortcodes` - Shortcode handlers
- `Libookin_MO_Cron` - Scheduled tasks

### Constants
- `LIBOOKIN_MO_VERSION` - Plugin version
- `LIBOOKIN_MO_PLUGIN_DIR` - Plugin directory path
- `LIBOOKIN_MO_PLUGIN_URL` - Plugin URL
- `LIBOOKIN_MO_PLUGIN_BASENAME` - Plugin basename

## Translation

The plugin is translation-ready and uses the text domain `libookin-monthly-offer`.

To translate:
1. Use POEdit or Loco Translate
2. Locate the plugin in the translation tool
3. Add your language translations
4. Save to `/languages/` folder

## Support

For issues, questions, or feature requests:
- Check the documentation above
- Review the troubleshooting section
- Contact the plugin developer

## Changelog

### Version 1.0.0
- Initial release
- Custom charity post type
- Vote system with AJAX
- Thank you page integration
- Results popup system
- Admin dashboard
- Vote counter shortcode
- Current charity shortcode
- Cron job automation
- CSV export functionality

## Credits

Developed by Abu Hena for LiBookin

## License

GPL2 - GNU General Public License v2 or later
