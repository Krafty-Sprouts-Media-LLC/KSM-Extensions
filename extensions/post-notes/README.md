# Post Notes Module

A Posthelply module that allows adding a single editable note per post/article. Notes are displayed in both the post edit screen (as a meta box) and the post list table (as a column with a visual badge indicator).

## Description

This module enables users to add notes to posts and pages for quick reference and organization. Notes are stored as post meta and can be easily added, edited, or removed from the post edit screen. A visual badge indicator in the post list table shows which posts have notes attached.

## Features

- **Meta Box in Post Edit Screen**: Add and edit notes directly in the post edit screen
- **Notes Column**: Visual badge indicator in the post list table showing which posts have notes
- **Single Note Per Post**: One editable/replaceable note per post/article
- **Works with Posts and Pages**: Full support for both posts and pages
- **Secure Storage**: Notes are stored as private post meta (with underscore prefix)
- **Tooltip Preview**: Hover over the badge to see the full note content

## How It Works

The module hooks into WordPress admin actions and filters to:
1. Add a meta box to the post/page edit screen with a textarea for notes
2. Save notes securely when posts are saved
3. Add a "Notes" column to the post list table
4. Display a visual badge indicator for posts that have notes

## Installation

This module is automatically loaded when placed in the `modules/` directory of the Posthelply plugin. No additional installation steps are required.

## Usage

Once activated, the module automatically:
- Adds a "Post Note" meta box to the Posts edit screen (in the sidebar)
- Adds a "Post Note" meta box to the Pages edit screen (in the sidebar)
- Adds a "Notes" column to the Posts list table
- Adds a "Notes" column to the Pages list table

To add a note:
1. Edit any post or page
2. Find the "Post Note" meta box in the sidebar
3. Enter your note in the textarea
4. Save or update the post

Posts with notes will display a badge icon (✏️) in the Notes column of the list table. Hover over the badge to see the full note content.

## Technical Details

### Namespace
`Posthelply\Modules\PostNotes`

### Class
- `Posthelply_Post_Notes` - Main module class

### Post Meta Key
- `_posthelply_post_note` - Private post meta key (with underscore prefix)

### Actions Used
- `add_meta_boxes` - Register meta box for posts and pages
- `save_post` - Save note when post is saved
- `manage_posts_custom_column` - Display column content for posts
- `manage_pages_custom_column` - Display column content for pages
- `admin_head` - Add inline styles for badge

### Filters Used
- `manage_posts_columns` - Add Notes column to posts list
- `manage_pages_columns` - Add Notes column to pages list

### Security Features
- Nonce verification for form submissions
- Capability checks before saving notes
- Autosave protection (notes not saved during autosaves)
- Post revision protection
- Proper data sanitization using `sanitize_textarea_field()`

## Requirements

- Posthelply 1.0.0 or higher
- WordPress 5.0 or higher
- PHP 7.2 or higher

## Version

Current Version: 1.0.0

## License

GPL v2 or later (same as Posthelply)

