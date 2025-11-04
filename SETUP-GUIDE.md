# LiBookin Monthly Offer - Quick Setup Guide

## Installation Steps

### 1. Activate the Plugin
1. Go to **WordPress Admin > Plugins**
2. Find "LiBookin Monthly Offer"
3. Click **Activate**
4. The plugin will automatically create database tables

### 2. Add Charities
1. Go to **Monthly Offer > Add New Charity**
2. Enter charity details:
   - **Title**: Charity name (e.g., "Red Cross")
   - **Description**: Information about the charity
   - **Featured Image**: Upload charity logo
3. Click **Publish**
4. Repeat for at least 2-3 charities

### 3. Create Monthly Bundle Product
1. Go to **Products > Add New**
2. Create a product using WooCommerce Product Bundles
3. Set product type to **Product Bundle** (woosb)
4. Configure bundle items
5. Set price and publish

### 4. Add Shortcodes (Optional)
Add vote counter to homepage or any page:
```
[libookin_vote_counter]
```

Show current winning charity:
```
[libookin_current_charity]
```

## Testing the Voting System

### Test Purchase Flow
1. Add your bundle product to cart
2. Complete checkout (you can use WooCommerce test mode)
3. After order completion, go to thank you page
4. You should see voting options for charities
5. Click "Vote" on a charity
6. Verify success message appears

### Check Vote Recording
1. Go to **Monthly Offer > Vote Results**
2. Verify your vote appears in the detailed votes table
3. Check that vote count incremented

### Test Results Popup
1. Clear browser cookies
2. Visit your site on the 1st day of month (or later)
3. Popup should appear showing winning charity
4. Click close button to dismiss

## Admin Features

### Dashboard
- View total votes this month
- See active charities count
- Check current leader
- Monitor real-time standings

### Vote Results
- Filter by month
- View detailed vote history
- Export CSV reports
- Identify voting patterns

### Manage Charities
- Add/Edit/Delete charities
- Upload charity logos
- Manage charity content

## Cron Jobs

The plugin uses two cron jobs:

1. **Daily Cache Refresh** - Updates vote result cache
2. **Monthly Processing** - Runs on 1st of month to determine winner

### Manual Testing Cron
Add this to functions.php temporarily:
```php
add_action('admin_init', function() {
    if (isset($_GET['test_monthly_cron']) && current_user_can('manage_options')) {
        Libookin_MO_Cron::manual_trigger_monthly_process();
        wp_die('Monthly cron executed');
    }
});
```

Then visit: `yoursite.com/wp-admin/?test_monthly_cron=1`

## Troubleshooting

### Voting Section Not Showing
✅ Check WooCommerce is active
✅ Verify product type is 'woosb' (bundle)
✅ Confirm order status is 'completed' or 'processing'
✅ Ensure charities are published

### Votes Not Saving
✅ Check JavaScript console for errors
✅ Verify database tables exist
✅ Confirm AJAX URL is correct
✅ Check user permissions

### Popup Not Appearing
✅ Clear browser cookies
✅ Check date is past 1st of month
✅ Verify winner exists for previous month
✅ Check user meta for flags

## Security Checklist

✅ Nonce verification on all AJAX requests
✅ Prepared SQL statements (no SQL injection)
✅ Output escaping (no XSS vulnerabilities)
✅ Capability checks for admin functions
✅ Order ownership validation
✅ One vote per order enforcement

## Required Plugins

- ✅ **WooCommerce** (v5.0+) - Required
- ✅ **WooCommerce Product Bundles** - Required for bundle detection
- ⚪ **Loco Translate** - Optional for translations

## Database Tables

After activation, verify these tables exist:
- `wp_libookin_votes`
- `wp_libookin_vote_results`

## Default Settings

| Setting | Value |
|---------|-------|
| Charities shown on thank you page | 3 (random) |
| Vote counter widget limit | 3 charities |
| Eligible order statuses | completed, processing |
| Popup display frequency | Once per month |
| Cache refresh | Daily |
| Monthly processing | 1st of month |

## Next Steps

1. ✅ Create multiple charities (minimum 3)
2. ✅ Create at least one bundle product
3. ✅ Test the voting flow
4. ✅ Add shortcodes to your pages
5. ✅ Customize CSS if needed
6. ✅ Monitor votes in admin dashboard

## Support

For issues or questions:
- Review the main README.md
- Check WordPress error logs
- Verify WooCommerce is working
- Contact plugin developer

## Version Info

**Current Version**: 1.0.0
**Last Updated**: 2024
**Minimum Requirements**:
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+

---

**Plugin is now ready to use!** 🎉
