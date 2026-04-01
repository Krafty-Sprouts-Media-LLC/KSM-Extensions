# KSM Extensions

**Version:** 1.0.0  
**Author:** Krafty Sprouts Media, LLC  
**License:** GPL v2 or later

A comprehensive WordPress extension framework by Krafty Sprouts Media that houses modular plugins and core extensions for enhanced WordPress functionality.

---

## 🎯 Overview

KSM Extensions is a powerful, modular WordPress plugin framework that provides:

- **📦 Modular Architecture**: Self-contained modules that can be developed and maintained independently
- **⚙️ Core Extensions**: Global snippets for WordPress enhancements (Disable Image Scaling, Force Center Alignment)
- **🔌 Easy Extension**: Drop new modules into the `modules/` directory and they're automatically detected
- **🎨 Clean Admin UI**: Manage all modules and extensions from a single, intuitive interface

---

## 📋 Features

### **Modular System**
- **Post Notes**: Add editable notes to posts/articles with visual badge indicators
- **Missed Scheduled Posts**: Automatically publish posts that missed their scheduled time
- **Show Modified Date**: Display modified dates and last editor in admin columns

### **Core Extensions**
- **Disable Image Scaling**: Prevent WordPress from auto-scaling large images (great for infographics)
- **Force Center Alignment**: Automatically center-align all images in post content

### **Framework Features**
- Automatic module discovery and loading
- WordPress Coding Standards compliant
- Internationalization ready
- Clean, documented codebase

---

## 📦 Installation

1. Upload the `ksm-extensions` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **KSM Extensions** in the WordPress admin menu
4. Configure modules and core extensions as needed

---

## 🔧 Module Development

### Creating a New Module

1. Create a directory in `modules/` (e.g., `modules/my-module/`)
2. Create a main PHP file with the same name (e.g., `my-module.php`)
3. Add the required module headers:

```php
<?php
/**
 * Module Name: My Module
 * Module URI: https://example.com
 * Description: Module description
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Requires KSM Extensions: 1.0.0
 */

namespace KSM_Extensions\Modules\MyModule;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Prevent loading if KSM Extensions is not active.
if (!defined('KSM_EXTENSIONS_VERSION')) {
    return;
}

/**
 * Initialize the module.
 *
 * @return bool True on successful initialization.
 */
function ksm_extensions_module_my_module_init() {
    // Your module initialization code here
    return true;
}
```

4. The module will be automatically detected and loaded!

---

## 📁 Directory Structure

```
ksm-extensions/
├── ksm-extensions.php          # Main plugin file
├── README.md                    # This file
├── CHANGELOG.md                 # Version history
├── includes/                    # Core classes
│   ├── class-ksm-extensions-core.php
│   ├── class-ksm-extensions-module-loader.php
│   ├── class-ksm-extensions-manager.php
│   ├── class-ksm-extensions-admin.php
│   ├── class-ksm-extensions-assets.php
│   ├── class-ksm-extensions-activator.php
│   └── class-ksm-extensions-deactivator.php
├── admin/                       # Admin interface
│   └── assets/                  # Admin CSS/JS
│       ├── css/
│       └── js/
├── modules/                     # Module plugins directory
│   ├── post-notes/
│   ├── missed-scheduled-posts/
│   └── show-modified-date/
└── languages/                   # Translation files
```

---

## ⚙️ Core Extensions

### Disable Image Scaling
Prevents WordPress from automatically creating scaled versions of large images. Useful for:
- High-resolution photography
- Infographics
- Design portfolios
- Any scenario where image quality is paramount

### Force Center Alignment
Automatically center-aligns all images in post content by:
- Setting default image alignment to center
- Applying the `aligncenter` class to all images
- Works with both classic and block editor

---

## 🔌 Included Modules

### Post Notes
- Add a single editable note per post/article
- Notes displayed in post edit screen (meta box)
- Visual badge indicator in post list table
- Quick reference and organization

### Missed Scheduled Posts
- Catches scheduled posts that missed their publish time
- Automatically publishes them
- Based on WPBeginner's Missed Scheduled Posts Publisher
- No configuration needed - works automatically

### Show Modified Date
- Shows modified date column in admin lists
- Displays username of last editor
- Sortable column
- Based on Apasionados.es plugin

---

## 🛠️ Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher

---

## 🤝 Credits

### Framework
- **Developed by:** Krafty Sprouts Media, LLC
- **Website:** https://kraftysprouts.com

### Module Credits

**Missed Scheduled Posts**
- Based on "Missed Scheduled Posts Publisher" by WPBeginner
- Original contributors: WPbeginner, smub, jaredatch, peterwilsoncc, tommcfarlin
- [Original Plugin](https://wordpress.org/plugins/missed-scheduled-posts-publisher/)

**Show Modified Date**
- Based on "Show modified Date in admin lists" by Apasionados.es
- [Original Plugin](https://wordpress.org/plugins/show-modified-date-in-admin-lists/)

---

## 📝 License

GPL v2 or later

---

## 🔄 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.

---

## 💡 Support

For support, feature requests, or bug reports, please contact:
- **Email:** support@kraftysprouts.com
- **Website:** https://kraftysprouts.com

---

## 🚀 Roadmap

Future enhancements planned:
- Module enable/disable toggle in admin UI
- Module settings pages
- More built-in modules
- Module marketplace/repository
- Advanced module dependencies
- Module update system

---

**Made with ❤️ by Krafty Sprouts Media, LLC**
