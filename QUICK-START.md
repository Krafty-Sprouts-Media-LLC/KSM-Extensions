# KSM Extensions - Quick Start Guide

**🎉 KSM Extensions v1.0.0 is ready to use!**

---

## ⚡ Quick Setup (3 Steps)

### Step 1: Rename the Plugin Folder
```bash
# Navigate to plugins directory
cd wp-content/plugins/

# Rename medialytic to ksm-extensions
mv medialytic ksm-extensions
```

**Or manually:**
1. Go to `wp-content/plugins/`
2. Rename `medialytic` folder to `ksm-extensions`

---

### Step 2: Activate the Plugin
1. Go to WordPress Admin → **Plugins**
2. Find **KSM Extensions**
3. Click **Activate**

---

### Step 3: Explore the Admin Interface
1. Go to **KSM Extensions** in the WordPress admin menu
2. View all installed modules
3. Go to **KSM Extensions → Core Extensions**
4. Toggle the core extensions as needed:
   - ☑️ **Disable Image Scaling** (for high-res images)
   - ☑️ **Force Center Alignment** (auto-center images)

---

## 📦 What's Included

### Modules (Automatically Active)
- ✅ **Post Notes** - Add notes to posts
- ✅ **Missed Scheduled Posts** - Auto-publish missed posts
- ✅ **Show Modified Date** - Display modified dates in admin

### Core Extensions (Toggle in Settings)
- ⚙️ **Disable Image Scaling**
- ⚙️ **Force Center Alignment**

---

## 🔧 Testing Checklist

### Test Modules
- [ ] **Post Notes**: Edit a post → Check for "Post Note" meta box
- [ ] **Missed Scheduled Posts**: Works automatically in background
- [ ] **Show Modified Date**: Check Posts list → See "Modified" column

### Test Core Extensions
- [ ] **Disable Image Scaling**: Upload a large image → Check if it's not scaled
- [ ] **Force Center Alignment**: Add image to post → Check if it's centered

---

## 📚 Documentation

- **Full README**: `README-KSM-EXTENSIONS.md`
- **Changelog**: `CHANGELOG-KSM-EXTENSIONS.md`
- **Build Summary**: `BUILD-SUMMARY.md`

---

## 🆘 Troubleshooting

### Plugin doesn't appear after renaming?
- Make sure you renamed the folder to `ksm-extensions` (lowercase, with hyphen)
- Refresh the Plugins page in WordPress

### Modules not loading?
- Check that `modules/` directory exists
- Verify module files have correct headers
- Check PHP error logs

### Core extensions not working?
- Make sure you've toggled them ON in **KSM Extensions → Core Extensions**
- Save the settings
- Clear any caching plugins

---

## 🎯 Next Steps

### Optional Cleanup
Once KSM Extensions is working:
1. Deactivate old Medialytic plugin (if still active)
2. Delete `medialytic.php` (old main file)
3. Delete `utilizely/` subdirectory
4. Keep only KSM Extensions files

### Add More Modules
See `KSM-EXTENSIONS-README.md` for module development guide.

---

## 💡 Support

**Email:** support@kraftysprouts.com  
**Website:** https://kraftysprouts.com

---

**That's it! You're all set! 🚀**
