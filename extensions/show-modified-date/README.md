# Show Modified Date Module

A Posthelply module that displays a sortable modified date column in WordPress admin lists for posts, pages, and media.

## Description

This module adds a new, sortable column to the WordPress admin lists that shows when posts, pages, or media items were last modified, along with the username of the person who made the last update.

## Features

- **Modified Date Column**: Adds a "Modified Date" column to posts, pages, and media library lists
- **Sortable**: Column can be sorted by modified date
- **Author Display**: Shows the username who performed the last update
- **Multi-Support**: Works with posts, pages, and media library
- **Clean Display**: Shows date, time, and author in a clean format

## How It Works

The module hooks into WordPress admin list filters and actions to:
1. Add a "Modified Date" column to the admin lists
2. Display the modified date, time, and author for each item
3. Make the column sortable by modified date

## Installation

This module is automatically loaded when placed in the `modules/` directory of the Posthelply plugin. No additional installation steps are required.

## Usage

Once activated, the module automatically:
- Adds the "Modified Date" column to Posts list
- Adds the "Modified Date" column to Pages list
- Adds the "Modified Date" column to Media Library
- Makes all columns sortable by clicking the column header

## Technical Details

### Namespace
`Posthelply\Modules\ShowModifiedDate`

### Class
- `Posthelply_Show_Modified_Date` - Main module class

### Filters Used
- `manage_posts_columns` - Add column to posts
- `manage_pages_columns` - Add column to pages
- `manage_media_columns` - Add column to media
- `manage_edit-post_sortable_columns` - Make posts column sortable
- `manage_edit-page_sortable_columns` - Make pages column sortable
- `manage_upload_sortable_columns` - Make media column sortable

### Actions Used
- `manage_posts_custom_column` - Display column content for posts
- `manage_pages_custom_column` - Display column content for pages
- `manage_media_custom_column` - Display column content for media

## Requirements

- Posthelply 1.0.0 or higher
- WordPress 3.0.1 or higher
- PHP 7.2 or higher

## Credits

**Original Plugin:** Show modified Date in admin lists by Apasionados.es  
**Original Plugin URI:** https://wordpress.org/plugins/show-modified-date-in-admin-lists/  
**Original Author:** Apasionados.es  
**Original Version:** 1.4

**Adapted for Posthelply by:** Krafty Sprouts Media, LLC

This module maintains all original functionality while being fully integrated into the Posthelply framework with Posthelply naming conventions. Full credit is given to the original author.

## Version

Current Version: 1.0.0

## License

GPL v2 or later (same as Posthelply and original plugin)

