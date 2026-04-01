# Missed Scheduled Posts Module

A KSM Extensions module that catches scheduled posts that have been missed and publishes them automatically.

## Description

This module automatically detects and publishes WordPress posts that have missed their scheduled publication time. It effectively resolves the 'missed scheduled post' error, ensuring that your content is reliably published on time, all while maintaining top-notch website performance.

## How It Works

WordPress relies on "WordPress cron jobs" (simulated cron) to publish scheduled posts. Because these are not real server-level cron jobs, they can sometimes fail, leading to "missed schedule" errors.

This module:
- Checks for missed scheduled posts every 15 minutes (configurable)
- Automatically publishes any posts that have missed their scheduled time
- Uses efficient loopback requests to minimize performance impact
- Includes fallback mechanisms for various hosting environments
- Works seamlessly with full page caching

## Features

- **Automatic Detection**: Scans for missed scheduled posts every 15 minutes
- **Batch Processing**: Processes up to 20 posts per run for efficiency
- **Performance Optimized**: Minimal server impact with smart timing
- **Caching Compatible**: Works with full page caching solutions
- **Configurable**: Check frequency can be customized via filter

## Installation

This module is automatically loaded when placed in the `modules/` directory of the KSM Extensions plugin. No additional installation steps are required.

## Configuration

The check frequency can be modified using the `ksm_extensions_missed_scheduled_posts_frequency` filter:

```php
add_filter( 'ksm_extensions_missed_scheduled_posts_frequency', function( $frequency ) {
    // Change from default 900 seconds (15 minutes) to 600 seconds (10 minutes)
    return 600;
} );
```

## Technical Details

### Namespace
`KSM_Extensions\Modules\MissedScheduledPosts`

### Classes
- `KSM_Extensions_MissedScheduledPosts_Core` - Core functionality
- `KSM_Extensions_MissedScheduledPosts_Review` - Review prompt functionality

### Options
- `ksm_extensions_missed_scheduled_posts_last_run` - Stores last run timestamp
- `ksm_extensions_mss_review_prompt_removed` - Review prompt dismissal flag
- `ksm_extensions_mss_review_prompt_delay` - Review prompt delay data

### Actions
- `ksm_extensions_missed_scheduled_posts` - AJAX action for processing

## Requirements

- KSM Extensions 1.0.0 or higher
- WordPress 5.0 or higher
- PHP 7.2 or higher

## Credits

**Original Plugin:** Missed Scheduled Posts Publisher by WPBeginner  
**Original Plugin URI:** https://wordpress.org/plugins/missed-scheduled-posts-publisher/  
**Original Contributors:** WPbeginner, smub, jaredatch, peterwilsoncc, tommcfarlin  
**Original Version:** 2.1.0

**Adapted for KSM Extensions by:** Krafty Sprouts Media, LLC

This module maintains all original functionality while being fully integrated into the KSM Extensions framework with KSM Extensions naming conventions. Full credit is given to the original authors and contributors.

## Version

Current Version: 1.0.0

## License

GPL v2 or later (same as KSM Extensions and original plugin)

