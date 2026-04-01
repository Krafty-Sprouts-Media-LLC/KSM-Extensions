# Changelog

All notable changes to KSM Extensions will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Semantic Versioning Rules:

MAJOR (X.0.0) - Breaking changes
MINOR (X.Y.0) - New features (backward compatible)
PATCH (X.Y.Z) - Bug fixes

Following Semantic Versioning (SemVer):

MAJOR.MINOR.PATCH (e.g., 2.8.0)
Each MINOR version = New feature (backward compatible)
Each feature deserves its own version number
=====================================================================================



## [2.0.11] - 19/02/2026

### Added
- **Featured image manager icon assets**: Added `edit.svg` and `trash.svg` files under `extensions/featured-image-manager/assets/icons/` so admin thumbnail actions use file-based SVG icons by default.

### Technical Details
- Added `extensions/featured-image-manager/assets/icons/edit.svg` and `extensions/featured-image-manager/assets/icons/trash.svg`.
- Runtime fallback SVGs remain in place as a safety net if icon files are ever missing.

## [2.0.10] - 19/02/2026

### Fixed
- **Featured image admin action icons unclear/small**: Added inline SVG fallbacks when icon files are missing and increased icon contrast/size in the admin thumbnail overlay so edit/delete actions are visually distinct.

### Technical Details
- `extensions/featured-image-manager/includes/class-featured-image-manager.php`: `get_thumb_action_icon()` now returns built-in fallback SVGs when files are unavailable; overlay icon styles now use distinct action colors (edit blue, delete red) and larger icon size.

## [2.0.9] - 19/02/2026

### Fixed
- **Missing script dependency (`ksm-admin-dialogs`)**: Removed unregistered dependency from Featured Image Manager admin thumbnail script and Duplicate Finder script so both assets enqueue reliably without dependency warnings.

### Technical Details
- `extensions/featured-image-manager/includes/class-featured-image-manager.php`: `ksm-featured-thumb-admin` dependencies now use only registered WordPress handles (`jquery`, `wp-util`, `media-editor`).
- `extensions/duplicate-finder/includes/class-duplicate-finder.php`: `ksm-extensions-duplicate-finder` dependency list now uses only `jquery`.

## [2.0.8] - 19/02/2026

### Fixed
- **WordPress 6.7 translation notice**: "Translation loading for the ksm-extensions domain was triggered too early" — plugin bootstrap and text domain loading now run at `init`. Text domain loads at `init` priority 0 and plugin execution at priority 1, so translations are loaded at or after `init` as required.

### Technical Details
- `ksm-extensions.php`: `run_ksm_extensions()` is hooked to `init` priority 1 instead of running at plugin load.
- `includes/class-ksm-extensions-core.php`: `set_locale()` hooks `load_plugin_textdomain` to `init` priority 0 instead of `plugins_loaded`.

## [2.0.7] - 29/01/2026

### Fixed
- **Media File Size – "View Variants" not loading in Media Library**: Script depended on `ksm-admin-dialogs` (never registered), so the script could fail to load. Removed that dependency (only `jquery` now). Added AJAX fallback: when variant data is not in the page payload (e.g. grid view or lazy-loaded rows), clicking "View Variants" fetches data via `ksm_extensions_media_size_get_variants` and then shows the modal. Variant button handler now runs on Media Library page even when list table is not present.

### Technical Details
- `extensions/media-file-size/includes/class-media-file-size.php`: enqueue dependency `ksm-admin-dialogs` removed; new AJAX action `ajax_get_variants()` and `getVariantsAction` / `variantsError` in localized script. `assets/js/media-file-size.js`: `showVariantsModal()` extracted; click handler uses in-page data first, then requests variants via AJAX; `initVariantButtons()` runs regardless of list table so "View Variants" works in all views.

## [2.0.6] - 29/01/2026

### Added
- **plugin-update-checker integration**: YahnisElsts/plugin-update-checker (v5.6) is now bundled under `lib/plugin-update-checker/` and loaded from the main plugin file. Optional: define `KSM_EXTENSIONS_UPDATE_REPO` in wp-config.php (e.g. your GitHub repo URL or JSON metadata URL) to enable update checks; filter `ksm_extensions_update_repo` and `ksm_extensions_update_branch` (default `master`) are available.

### Technical Details
- Moved existing `plugin-update-checker/` from plugin root into `lib/plugin-update-checker/`. Main plugin requires `lib/plugin-update-checker/plugin-update-checker.php` only when `KSM_EXTENSIONS_UPDATE_REPO` is set; uses `PucFactory::buildUpdateChecker()` with slug `ksm-extensions`. No update checks run until the constant is defined.

## [2.0.5] - 29/01/2026

### Fixed
- **Media Counter Post types**: Unchecking all post types and saving no longer resets them to "Post" and "Page". When no checkboxes are checked, the form does not send `post_types`; the sanitizer now saves an empty array instead of falling back to the default, so the saved selection is respected.

### Technical Details
- `extensions/media-counter/includes/class-core.php`: in `sanitize_settings()`, `post_types` fallback changed from `array( 'post', 'page' )` to `array()` when the key is missing or not an array.

## [2.0.4] - 29/01/2026

### Changed
- **Settings UI: checkboxes only (no toggles)**. Extension settings pages now use a single control type: checkboxes. Multi-select (e.g. post types) stays as pill checkboxes; single on/off options use checkbox rows (`.ksm-checkbox-row`: checkbox + label). Media Counter and Featured Image Manager toggles were converted to checkbox rows. Toggle CSS is kept for backward compatibility but settings forms use only checkboxes.

### Removed
- **Media Counter Debug section**: The "Debug" section and "Enable debug logging" option were removed from the Media Counter settings page. The option remains in code (default false) for internal use; no UI is shown.

### Technical Details
- Media Counter: all `.ksm-toggle` replaced with `.ksm-checkbox-row`; Debug section removed from `render_settings_page()`. Featured Image Manager: three toggles (admin column, auto assign, feed enabled) replaced with checkbox rows. `admin/assets/css/settings.css`: added `.ksm-checkbox-row` and `.ksm-checkbox-row__label` styles.

## [2.0.3] - 29/01/2026

### Removed
- **Eyebrow markup and CSS**: All `ksm-eyebrow` / eyebrow-style labels removed from extension settings pages (Auto Upload Images, Featured Image Manager, Image Title & Alt, Duplicate Finder). Hero and section headings now use only the main title (h1) or section title (h3). Eyebrow rules removed from `admin/assets/css/settings.css`. Demo E and README updated to match.

### Technical Details
- PHP: removed `<p class="ksm-eyebrow">...</p>` from hero and fieldset headers in auto-upload-images, featured-image-manager, image-title-alt, duplicate-finder. CSS: removed `.ksm-admin__hero .ksm-eyebrow` and `.ksm-fieldset__header .ksm-eyebrow` / `.ksm-section .ksm-eyebrow` blocks. Demos: removed hero-eyebrow and section-eyebrow from demo-e-ksm-refined.html and README.

## [2.0.2] - 29/01/2026

### Changed
- **Media Counter settings page**: Removed "Analytics" eyebrow from the top; hero now shows only the title "Media Counter" and the subhead. Simplified section structure: one heading per section (Post types, What to count, Display, Tracking, Debug) with no eyebrow + subheader duplication. Removed the note about "Disable image scaling and Force center alignment" from this page (those options live only under KSM Extensions → Settings).

### Technical Details
- `extensions/media-counter/includes/class-core.php`: hero no longer outputs `ksm-eyebrow`; sections use single `h3.ksm-section-title`; Core Extensions note paragraph removed. Settings CSS: `.ksm-section-title` included in section heading styles.

## [2.0.1] - 29/01/2026

### Fixed
- **Media Counter settings appearing multiple times under Settings**: Deduplicate by `menu_slug` in `register_module_settings_page()`. Media Counter Core is instantiated by media-counter, featured-image-manager, and duplicate-finder; each was registering the same page — now only the first registration is kept.
- **Settings page layout**: Toggle row was forced to 48px width (entangled options). Fixed by applying fixed dimensions to `.ksm-toggle__track` only; the label row now uses full width with label text on the left and switch on the right. Added `order: -1` on the label span so visual order matches. Thumb positioning corrected with `position: absolute` inside the track.
- **Hero / “Analytics” placement**: Added `padding-top: 8px` on the settings wrap and clarified hero margins so the eyebrow and title sit clearly in the hero block below any WP admin notices.

### Technical Details
- `class-ksm-extensions-admin.php`: skip adding a settings page if its `menu_slug` is already in `module_settings_pages`.
- `admin/assets/css/settings.css`: toggle dimensions moved from `.ksm-toggle` to `.ksm-toggle .ksm-toggle__track`; thumb `position: absolute`; label `order: -1`; wrap `padding-top: 8px`.

## [2.0.0] - 29/01/2026

### Added
- **Settings page design (2.0.0 – KSM refined / demo-e)**: Unified UI for all extension settings pages
  - New `admin/assets/css/settings.css` applied to Media Counter, Image Title & Alt, Featured Image Manager, and Auto Upload Images
  - Constrained width (760px), hero spacing, card with rounded corners and light shadow, section borders, pill-style checkboxes for post types, aligned toggles, card footer with submit button
  - Wrap class `ksm-settings-page` added so the design applies only on extension settings; existing ksm-* markup is styled without PHP refactors

### Technical Details
- Assets: enqueue `ksm-extensions-settings` when hook is `settings_page_*` and contains `ksm-extensions`
- Extension settings views: add class `ksm-settings-page` to the main wrap in Media Counter, Image Title & Alt, Featured Image Manager, Auto Upload Images

## [1.0.15] - 29/01/2026

### Changed
- **Admin page (KSM Extensions → Extensions)**: Removed Version and Author columns from the extensions table. Single source of truth for version is `ksm-extensions.php` only; extension files no longer declare their own version.
- **Folder rename**: `modules/` renamed to `extensions/` so the plugin structure matches the product name (KSM Extensions). Extension loader and any cross-extension requires now use `extensions/` path.
- **Menu and copy**: Submenu "Modules" renamed to "Extensions"; dashboard widget and empty-state copy updated to refer to "extensions" and "extensions/ directory".

### Technical Details
- Removed `Version` and `@version` from all nine extension main file headers (media-counter, duplicate-finder, featured-image-manager, auto-upload-images, image-title-alt, media-file-size, missed-scheduled-posts, post-notes, show-modified-date).
- `class-ksm-extensions-module-loader.php`: path `modules` → `extensions`; extensions directory created if missing.
- `duplicate-finder.php` and `featured-image-manager.php`: require path `modules/media-counter/...` → `extensions/media-counter/...`.

## [1.0.14] - 29/01/2026

### Changed
- **Disable image scaling & Force center alignment**: Single source of truth — these options now live only on **KSM Extensions → Settings** (Core Extensions). Removed the duplicate "Image behaviour (when this module is active)" section from the Media Counter settings page. Media Counter settings page now shows a short note pointing users to KSM Extensions → Settings for those two options.

### Technical Details
- Media Counter Core: removed `disable_image_scaling` and `force_center_alignment` from options, init_hooks, sanitize, and the settings form; removed `set_default_image_align()` and `auto_center_images()` from this class (behaviour is applied by `KSM_Extensions_Manager` from main Settings).

## [1.0.13] - 29/01/2026

### Added
- **Media Counter settings page**: Full settings UI for the Media Counter module (merged image/embed counter from Medialytic)
  - Settings page at **Settings → Media Counter** with: Post types, What to count (images/videos/embeds), Display (admin columns, dashboard widget), Tracking (historical, cache duration), Debug. Activation is only via **KSM Extensions → Modules** (no duplicate Enable toggle)
  - Registered with KSM Extensions admin; Settings link for Media Counter on **KSM Extensions → Modules** page

### Fixed
- **Duplicate Finder Settings link**: Settings link for Duplicate Finder now points to **Media → Find Duplicates** (`upload.php?page=ksm-extensions-duplicate-finder`) instead of `options-general.php`, so the page loads correctly
- **Redundant "Enable" toggles**: Removed duplicate "Enable [Module]" checkboxes from module settings pages (Featured Image Manager, Image Title & Alt, Auto Upload Images). Activation is controlled only from **KSM Extensions → Modules** (Status column). Module settings pages now only configure options; when a module is activated on the Modules page it runs without requiring a second enable inside its settings

### Technical Details
- Added `get_module_settings_url()` in `class-ksm-extensions-admin.php` so Duplicate Finder uses `upload.php` and other modules use `options-general.php`
- Media Counter Core: `register_settings_page()`, `register_settings()`, `render_settings_page()`, `OPTION_KEY` constant; settings slug `ksm-extensions-media-counter` added to admin map
- Featured Image Manager, Image Title & Alt, Auto Upload Images: `enabled` option forced to `true` when module is loaded and in sanitize; Enable section removed from settings UI

## [1.0.12] - 30/12/2025

### Fixed
- **CRITICAL: Admin Access Permission Error**: Fixed "Sorry, you are not allowed to access this page" error on KSM Extensions admin pages
  - **Root Cause 1**: Module files were using PHP namespaces which prevented the global `ksm_extensions_module_*_init()` functions from being found by the module loader
  - **Root Cause 2**: Module initialization order was incorrect - modules were being loaded after admin hooks were registered, preventing module settings pages from being registered on the `admin_menu` action
  - Removed namespaces from all module main files (auto-upload-images, show-modified-date, post-notes, missed-scheduled-posts, media-file-size, media-counter, image-title-alt, featured-image-manager, duplicate-finder)
  - Added proper `use` statements to import namespaced classes from include files
  - Fixed initialization order in `KSM_Extensions_Core` to load modules before registering admin hooks

### Technical Details
- Module loader searches for `ksm_extensions_module_{slug}_init()` functions in global namespace
- Module init functions were in namespaces like `KSM_Extensions\Modules\ModuleName\` which made them invisible to `function_exists()`
- Changed constructor order from: `load_dependencies` → `set_locale` → `define_admin_hooks` → `load_modules`
- To: `load_dependencies` → `set_locale` → `load_extensions` → `load_modules` → `define_admin_hooks`
- Modules with namespaced include files now use `use` statements to properly reference classes

## [1.0.11] - 30/12/2025

### Fixed
- **Featured Image Manager Module**: Added required MediaCounter_Core dependency
  - Added require statement for MediaCounter Core class in featured-image-manager.php
  - Module now properly initializes when activated

## [1.0.10] - 30/12/2025

### Fixed
- **Featured Image Manager Settings Page**: Fixed "Sorry, you are not allowed to access this page" error
  - Fixed URL in Media settings bridge field from `admin.php?page=` to `options-general.php?page=` (Settings menu pages)
  - Added capability check to `render_settings_page()` method for security
  - Fixed hook name in `enqueue_admin_assets()` from `ksm-extensions_page_*` to `settings_page_*` (correct hook for `add_options_page()`)

## [1.0.9] - 30/12/2025

### Fixed
- **Duplicate Finder Module**: Fixed missing menu item in Media menu
  - Added required dependency for `KSM_Extensions_MediaCounter_Core` class
  - Module now properly loads and registers Media menu page when activated
  - The "Find Duplicates" menu item now appears under Media menu as intended

## [1.0.8] - 30/12/2025

### Fixed
- **Media File Size Module**: Fixed missing file size column display in Media Library
  - Fixed `register_column()` method that was not adding the column to the media list table
  - Fixed JavaScript object name with invalid space character (`'KSM ExtensionsMediaSize'` → `'ksmExtensionsMediaSize'`)
  - Created missing CSS and JS asset files in `assets/css/` and `assets/js/` directories
  - Updated all CSS class names from `medialytic-*` to `ksm-*` for consistency
  - Updated JavaScript variable names and AJAX action names to use KSM Extensions naming
  - Updated variant data object name from `medialyticMediaSizeVariants` to `ksmExtensionsMediaSizeVariants`

### Technical Details
- Added `assets/css/media-file-size.css` with KSM Extensions class names
- Added `assets/js/media-file-size.js` with updated AJAX actions (`ksm_extensions_media_size_index`, `ksm_extensions_media_size_index_count`)
- Fixed `wp_localize_script()` object name to use valid JavaScript identifier
- Updated `print_variant_data_script()` to use correct global variable name

## [1.0.7] - 30/12/2025

### Changed
- **Admin Menu Restructuring**: Simplified admin menu structure for better organization
  - Removed "Core Extensions" submenu (integrated into Settings page)
  - KSM Extensions menu now only contains "Modules" and "Settings" submenus
  - Module settings pages now appear in WordPress Settings menu (options-general.php) instead of KSM Extensions submenu
  - Settings pages are only registered/accessible when modules are activated (via toggle on Modules page)
  - Settings links on Modules page only appear for activated modules

### Fixed
- **Module Settings Pages Access Control**: Fixed settings pages being accessible when modules are inactive
  - Added activation status check in `register_module_settings_page()` method
  - Settings pages now only register when module is activated via Modules page toggle
  - Settings links on Modules page updated to use correct URL format (`options-general.php?page=...`)
  - Applied to Image Title & Alt, Featured Image Manager, and Auto Upload Images modules

### Technical Details
- Changed from `add_submenu_page('ksm-extensions-settings', ...)` to `add_options_page(...)` for module settings
- Modules now use `ksm_extensions_register_settings_page` action hook to register settings pages
- Added `module_slug` parameter to `register_module_settings_page()` to verify activation status
- Settings page links use `options-general.php?page=` instead of `admin.php?page=` for Settings menu pages
- Duplicate Finder remains in Media menu (uses `add_media_page()`) as intended

## [1.0.6] - 30/12/2025

### Changed
- **Complete Removal of "Utilizely" References**: Removed all remaining "utilizely" identifiers from the codebase
  - Updated all option keys from `utilizely_*` to `ksm_extensions_*` format
  - Updated all meta keys from `utilizely_*` to `ksm_extensions_*` format
  - Updated all text domains from `'utilizely'` to `'ksm-extensions'`
  - Updated all CSS classes and HTML IDs from `utilizely-*` to `ksm-*`
  - Updated all AJAX actions and nonce actions
  - Updated constants `UTILIZELY_PLUGIN_BASENAME` → `KSM_EXTENSIONS_PLUGIN_BASENAME`
  - Updated all JavaScript function names and variables
  - Updated WordPress.org plugin URLs from `plugin/Utilizely` to `plugin/ksm-extensions`
  - Replaced "Utilizely" in comments and documentation strings with "KSM Extensions"
  - Updated README files and module documentation
  - Improved code consistency with unified naming convention throughout

### Technical Details
- Meta keys: `_utilizely_post_note` → `_ksm_extensions_post_note`
- Option keys: `utilizely_missed_scheduled_posts_last_run` → `ksm_extensions_missed_scheduled_posts_last_run`
- AJAX actions: `utilizely_mss_dismiss_review_prompt` → `ksm_extensions_mss_dismiss_review_prompt`
- CSS classes: `utilizely-note-badge` → `ksm-note-badge`
- JavaScript functions: `utilizelyMssDelayReviewPrompt` → `ksmExtensionsMssDelayReviewPrompt`
- Filter names: `utilizely_missed_scheduled_posts_frequency` → `ksm_extensions_missed_scheduled_posts_frequency`

## [1.0.5] - 30/12/2025

### Changed
- **Complete Removal of "Medialytic" References**: Removed all remaining "medialytic" identifiers from the codebase
  - Updated all option keys from `medialytic_*` to `ksm_extensions_*` format
  - Updated all meta keys from `medialytic_*` to `ksm_extensions_*` format
  - Updated all text domains from `'medialytic'` to `'ksm-extensions'`
  - Updated all CSS classes and HTML IDs from `medialytic-*` to `ksm-*`
  - Updated all menu slugs and page slugs from `medialytic-*` to `ksm-extensions-*`
  - Updated all AJAX actions and nonce actions
  - Updated constants `MEDIALYTIC_PLUGIN_URL` → `KSM_EXTENSIONS_PLUGIN_URL` and `MEDIALYTIC_VERSION` → `KSM_EXTENSIONS_VERSION`
  - Removed references to non-existent `medialytic()` function and `Medialytic_Admin` class
  - Updated all column names, cache groups, and other identifiers
  - Replaced "Medialytic" in comments and documentation strings with "KSM Extensions"
  - Improved code consistency with unified naming convention throughout

### Technical Details
- Option keys: `medialytic_settings` → `ksm_extensions_media_counter_settings`
- Meta keys: `medialytic_image_count` → `ksm_extensions_media_counter_image_count`
- Constants: All `MEDIALYTIC_*` → `KSM_EXTENSIONS_*`
- CSS classes: All `medialytic-*` → `ksm-*`
- Menu slugs: All `medialytic-*` → `ksm-extensions-*`
- Removed legacy error checking code that referenced non-existent functions

## [1.0.4] - 30/12/2025

### Changed
- **Class Naming Refactoring**: Standardized all module class names to use `KSM_Extensions_` prefix
  - Renamed `Medialytic_*` classes to `KSM_Extensions_ModuleName_ClassName` format
  - Renamed `Utilizely_*` classes to `KSM_Extensions_ModuleName_ClassName` format
  - Updated all class references, instantiations, and type hints throughout the codebase
  - Updated package comments to use `KSM_Extensions` instead of `Medialytic` or `Utilizely`
  - Updated namespaces to use `KSM_Extensions\Modules\ModuleName` format
  - Improved code consistency and maintainability with unified naming convention

### Technical Details
- Media Counter: `Medialytic_Core` → `KSM_Extensions_MediaCounter_Core`
- Media Counter: `Medialytic_Image_Counter` → `KSM_Extensions_MediaCounter_ImageCounter`
- Media Counter: `Medialytic_Embed_Counter` → `KSM_Extensions_MediaCounter_EmbedCounter`
- Duplicate Finder: `Medialytic_Duplicate_Finder` → `KSM_Extensions_DuplicateFinder`
- Featured Image Manager: `Medialytic_Featured_Image_Manager` → `KSM_Extensions_FeaturedImageManager`
- Auto Upload Images: `Medialytic_Auto_Upload_Images` → `KSM_Extensions_AutoUploadImages`
- Auto Upload Images: `Medialytic_Auto_Upload_Image_Handler` → `KSM_Extensions_AutoUploadImages_Handler`
- Image Title Alt: `Medialytic_Image_Title_Alt` → `KSM_Extensions_ImageTitleAlt`
- Media File Size: `Medialytic_Media_File_Size` → `KSM_Extensions_MediaFileSize`
- Post Notes: `Utilizely_Post_Notes` → `KSM_Extensions_PostNotes`
- Show Modified Date: `Utilizely_Show_Modified_Date` → `KSM_Extensions_ShowModifiedDate`
- Missed Scheduled Posts: `Utilizely_Missed_Scheduled_Posts_Core` → `KSM_Extensions_MissedScheduledPosts_Core`
- Missed Scheduled Posts: `Utilizely_Missed_Scheduled_Posts_Review` → `KSM_Extensions_MissedScheduledPosts_Review`

## [1.0.3] - 30/12/2025

### Added
- **Module Activation System**: Complete module activation/deactivation functionality
  - Modules can now be enabled or disabled via toggle switches in the admin interface
  - Module activation state is stored in WordPress options
  - Only activated modules are loaded and initialized
  - All modules default to disabled state on fresh installation
- **Settings Page**: New dedicated Settings submenu page for global plugin configuration
  - Moved core extensions settings to Settings page
  - Improved organization of plugin settings
- **Dashboard Widget**: New dashboard widget showing module status
  - Displays active/total module count
  - Quick links to manage modules and settings
  - Visible to administrators only

### Changed
- Module loader now checks activation state before loading modules
- Core Extensions page now redirects to Settings page for better UX
- Admin interface updated with toggle switches for module activation
- Improved admin styling with toggle switch UI components

### Fixed
- Modules are no longer automatically loaded - they must be explicitly activated
- Module activation state now persists correctly across page reloads

## [1.0.2] - 30/12/2025

### Added
- **WordPress Studio Support**: Created `fix-missing-plugins-studio.php` for WordPress Studio/Playground environments
- **SQLite Database Support**: Created `fix-missing-plugins-sqlite.php` for direct SQLite database access in WordPress Studio
- SQLite script works even when WordPress can't load by directly accessing the `.ht.sqlite` database file
- WordPress Studio-specific script uses WordPress functions instead of direct database access (since Studio doesn't use MySQL)
- Updated documentation with WordPress Studio troubleshooting section

### Fixed
- **CRITICAL**: Improved `wp-config.php` parsing in `fix-missing-plugins.php` to handle:
  - Single and double quotes in define() statements
  - Escaped quotes in database credentials
  - Multi-line define() statements
  - Placeholder detection (database_name_here, username_here, etc.)
  - Better error messages showing what credentials were found
- Enhanced database connection error handling with detailed diagnostic information
- Fixed regex patterns to use non-greedy matching and handle special characters in passwords

## [1.0.1] - 30/12/2025

### Fixed
- **CRITICAL**: Added comprehensive fix script (`fix-missing-plugins.php`) to remove orphaned `medialytic` and `utilizely` plugins from active_plugins database option
- Fixed `fix-active-plugins-admin.php` to handle both `medialytic` and `utilizely` orphaned plugins
- Fix script works even when WordPress can't fully load due to missing plugins or database connection issues
- **CRITICAL**: Fixed script to parse `wp-config.php` directly instead of loading WordPress, preventing fatal errors when `db.php` has connection issues
- Script now uses direct database access when WordPress fails to load, avoiding "Call to a member function query() on null" errors

### Added
- New `fix-missing-plugins.php` script with multiple execution methods:
  - Browser access (with admin check)
  - WP-CLI support
  - Direct database access when WordPress can't load
- Enhanced error handling and user feedback in fix scripts

## [1.0.0] - 2025-12-30

### Added
- **Initial Release** of KSM Extensions framework
- **Core Framework**
  - Modular plugin architecture with automatic module discovery
  - Extensions Manager for global WordPress enhancements
  - Clean admin interface for managing modules and extensions
  - Automatic module loading from `modules/` directory
  - WordPress Coding Standards compliant codebase
  
- **Core Extensions**
  - **Disable Image Scaling**: Prevents WordPress from auto-scaling large images
  - **Force Center Alignment**: Automatically center-aligns all images in post content
  
- **Included Modules**
  - **Post Notes** (v1.0.0): Add editable notes to posts with visual indicators
  - **Missed Scheduled Posts** (v1.0.0): Auto-publish posts that missed their schedule
  - **Show Modified Date** (v1.0.0): Display modified dates and last editor in admin columns
  - **Media Counter** (v1.0.0): Unified image and embed counting with sortable admin columns
  - **Duplicate Finder** (v1.0.0): Find and remove duplicate media files
  - **Featured Image Manager** (v1.0.0): Comprehensive featured image management with fallbacks and RSS injection
  - **Auto Upload Images** (v1.0.0): Automatically import external images to Media Library
  - **Image Title & Alt Optimizer** (v1.0.0): SEO-friendly image metadata generation
  - **Media File Size** (v1.0.0): Display and manage media file sizes with indexing
  
- **Admin Interface**
  - Modules management page listing all installed modules
  - Core Extensions settings page with toggle controls
  - Clean, WordPress-native UI design
  
- **Developer Features**
  - Simple module development API
  - Automatic module registration via file headers
  - Namespace support for module isolation
  - Comprehensive inline documentation

### Credits
- Framework developed by Krafty Sprouts Media, LLC
- Missed Scheduled Posts module based on WPBeginner's plugin
- Show Modified Date module based on Apasionados.es plugin

---

## Future Releases

### Planned for v1.1.0
- Module enable/disable toggles in admin UI
- Individual module settings pages
- Module dependency management
- Enhanced error handling and logging

### Planned for v1.2.0
- Module update system
- Module marketplace integration
- Advanced module configuration options
- Performance optimizations

### Planned for v2.0.0
- React-based admin interface
- Module analytics and usage tracking
- Advanced module permissions
- Multi-site support enhancements

---

**Note:** This is the first release of KSM Extensions, consolidating functionality from Medialytic and Utilizely into a unified, extensible framework.
