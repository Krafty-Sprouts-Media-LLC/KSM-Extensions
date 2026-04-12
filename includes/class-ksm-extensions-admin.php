<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-admin.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.4
 * Last Modified: 12/04/2026
 * Description: Admin interface and menu management.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.0.0
 */
class KSM_Extensions_Admin
{

    /**
     * The module loader instance.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Module_Loader
     */
    private $module_loader;

    /**
     * The extensions manager instance.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Manager
     */
    private $extensions_manager;

    /**
     * The assets instance.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Assets
     */
    private $assets;

    /**
     * Array of module settings pages to register.
     *
     * @since 1.0.7
     * @var array
     */
    private $module_settings_pages = array();

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_ksm_extensions_toggle_module', array($this, 'ajax_toggle_module'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Set the module loader.
     *
     * @since 1.0.0
     * @param KSM_Extensions_Module_Loader $module_loader Module loader instance.
     */
    public function set_module_loader($module_loader)
    {
        $this->module_loader = $module_loader;
    }

    /**
     * Set the extensions manager.
     *
     * @since 1.0.0
     * @param KSM_Extensions_Manager $extensions_manager Extensions manager instance.
     */
    public function set_extensions_manager($extensions_manager)
    {
        $this->extensions_manager = $extensions_manager;
    }

    /**
     * Set the assets instance.
     *
     * @since 1.0.0
     * @param KSM_Extensions_Assets $assets Assets instance.
     */
    public function set_assets($assets)
    {
        $this->assets = $assets;
    }

    /**
     * Add admin menu.
     *
     * @since 1.0.0
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('KSM Extensions', 'ksm-extensions'),
            __('KSM Extensions', 'ksm-extensions'),
            'manage_options',
            'ksm-extensions',
            array($this, 'display_admin_page'),
            'dashicons-admin-plugins',
            80
        );

        add_submenu_page(
            'ksm-extensions',
            __('Extensions', 'ksm-extensions'),
            __('Extensions', 'ksm-extensions'),
            'manage_options',
            'ksm-extensions',
            array($this, 'display_admin_page')
        );

        add_submenu_page(
            'ksm-extensions',
            __('Settings', 'ksm-extensions'),
            __('Settings', 'ksm-extensions'),
            'manage_options',
            'ksm-extensions-settings',
            array($this, 'display_settings_page')
        );

        // Register module settings pages under Settings submenu
        $this->register_module_settings_pages();
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     */
    public function register_settings()
    {
        register_setting('ksm_extensions_settings', 'ksm_extensions_settings');
    }

    /**
     * Register module settings pages.
     * Modules can register their settings pages via the 'ksm_extensions_register_settings_page' action.
     * Settings pages are added to WordPress Settings menu (options-general.php).
     *
     * @since 1.0.7
     */
    private function register_module_settings_pages()
    {
        // Allow modules to register their settings pages
        do_action('ksm_extensions_register_settings_page', $this);
        
        // Register all collected settings pages under WordPress Settings menu
        foreach ($this->module_settings_pages as $page) {
            add_options_page(
                $page['page_title'],
                $page['menu_title'],
                'manage_options',
                $page['menu_slug'],
                $page['callback']
            );
        }
    }

    /**
     * Register a module settings page.
     * Called by modules via the 'ksm_extensions_register_settings_page' action.
     * Only registers if the module is activated.
     *
     * @since 1.0.7
     * @param string $page_title The page title.
     * @param string $menu_title The menu title.
     * @param string $menu_slug The menu slug.
     * @param callable $callback The callback function to render the page.
     * @param string $module_slug The module slug to check activation status.
     */
    public function register_module_settings_page($page_title, $menu_title, $menu_slug, $callback, $module_slug = '')
    {
        // Only register if module is activated
        if (!empty($module_slug) && $this->module_loader && !$this->module_loader->is_module_activated($module_slug)) {
            return;
        }

        // Avoid duplicate menu entries (e.g. Media Counter registered by media-counter, featured-image-manager, duplicate-finder)
        foreach ($this->module_settings_pages as $existing) {
            if (isset($existing['menu_slug']) && $existing['menu_slug'] === $menu_slug) {
                return;
            }
        }

        $this->module_settings_pages[] = array(
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'menu_slug' => $menu_slug,
            'callback' => $callback,
        );
    }

    /**
     * Display admin page.
     *
     * @since 1.0.0
     */
    public function display_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $modules = $this->module_loader ? $this->module_loader->get_modules() : array();
        $module_states = $this->module_loader ? $this->module_loader->get_module_states() : array();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php esc_html_e('Manage your KSM Extensions below. Toggle extensions on or off to enable or disable their functionality.', 'ksm-extensions'); ?></p>

            <div class="ksm-extensions-modules">
                <?php if (empty($modules)): ?>
                    <p><?php esc_html_e('No extensions found. Add extensions to the extensions/ directory.', 'ksm-extensions'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;"><?php esc_html_e('Status', 'ksm-extensions'); ?></th>
                                <th><?php esc_html_e('Name', 'ksm-extensions'); ?></th>
                                <th><?php esc_html_e('Description', 'ksm-extensions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module_slug => $module): 
                                $is_active = isset($module_states[$module_slug]) && $module_states[$module_slug] === true;
                                ?>
                                <tr data-module="<?php echo esc_attr($module_slug); ?>">
                                    <td>
                                        <label class="ksm-module-toggle">
                                            <input type="checkbox" 
                                                   class="ksm-module-toggle-input" 
                                                   data-module="<?php echo esc_attr($module_slug); ?>"
                                                   <?php checked($is_active, true); ?> />
                                            <span class="ksm-module-toggle-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($module['name']); ?></strong>
                                        <div class="row-actions">
                                            <?php 
                                            // Check if module has settings page
                                            $settings_url = $this->get_module_settings_url($module_slug);
                                            if ($settings_url): ?>
                                                <span class="settings">
                                                    <a href="<?php echo esc_url($settings_url); ?>">
                                                        <?php esc_html_e('Settings', 'ksm-extensions'); ?>
                                                    </a>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($module['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display settings page.
     *
     * @since 1.0.1
     */
    public function display_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission
        if (isset($_POST['ksm_extensions_settings_nonce']) && wp_verify_nonce($_POST['ksm_extensions_settings_nonce'], 'ksm_extensions_settings')) {
            $options = array(
                'disable_image_scaling' => isset($_POST['disable_image_scaling']),
                'force_center_alignment' => isset($_POST['force_center_alignment']),
            );
            $this->extensions_manager->update_options($options);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'ksm-extensions') . '</p></div>';
        }

        $disable_image_scaling = $this->extensions_manager->get_option('disable_image_scaling', false);
        $force_center_alignment = $this->extensions_manager->get_option('force_center_alignment', false);

        ?>
        <div class="wrap ksm-wrap ksm-settings-page">
            <div class="ksm-admin ksm-admin-layout">
                <main class="ksm-main-content">
                    <header class="ksm-admin__hero">
                        <div>
                            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                            <p class="ksm-admin__subhead"><?php esc_html_e('Configure global settings for KSM Extensions.', 'ksm-extensions'); ?></p>
                        </div>
                    </header>

                    <form method="post" action="" class="ksm-card">
                        <?php wp_nonce_field('ksm_extensions_settings', 'ksm_extensions_settings_nonce'); ?>
                        <div class="ksm-card__body">
                            <section class="ksm-section">
                                <h3 class="ksm-section-title"><?php esc_html_e('Core Extensions', 'ksm-extensions'); ?></h3>
                                <p class="ksm-field__description"><?php esc_html_e('Image behaviour when extensions that use these options are active.', 'ksm-extensions'); ?></p>
                                <label class="ksm-checkbox-row" for="ksm-disable-image-scaling">
                                    <input type="checkbox" id="ksm-disable-image-scaling" name="disable_image_scaling" value="1" <?php checked($disable_image_scaling, true); ?> />
                                    <span class="ksm-checkbox-row__label"><?php esc_html_e('Disable image scaling — prevent WordPress from automatically creating scaled versions of large images.', 'ksm-extensions'); ?></span>
                                </label>
                                <p class="description" style="margin-top: 0; margin-bottom: 14px;"><?php esc_html_e('Useful for infographics and high-resolution images.', 'ksm-extensions'); ?></p>
                                <label class="ksm-checkbox-row" for="ksm-force-center-alignment">
                                    <input type="checkbox" id="ksm-force-center-alignment" name="force_center_alignment" value="1" <?php checked($force_center_alignment, true); ?> />
                                    <span class="ksm-checkbox-row__label"><?php esc_html_e('Force center alignment — automatically center-align all images in post content.', 'ksm-extensions'); ?></span>
                                </label>
                                <p class="description" style="margin-top: 0;"><?php esc_html_e('Sets default alignment and applies the aligncenter class.', 'ksm-extensions'); ?></p>
                            </section>
                        </div>
                        <div class="ksm-card__footer">
                            <?php submit_button(); ?>
                        </div>
                    </form>
                </main>
            </div>
        </div>
        <?php
    }

    /**
     * Get module settings slug if it exists.
     *
     * @since 1.0.7
     * @param string $module_slug The module slug.
     * @return string|false The settings page slug or false if not found.
     */
    private function get_module_settings_slug($module_slug)
    {
        $settings_map = array(
            'media-counter' => 'ksm-extensions-media-counter',
            'image-title-alt' => 'ksm-extensions-image-title-alt',
            'featured-image-manager' => 'ksm-extensions-featured-images',
            'auto-upload-images' => 'ksm-extensions-auto-upload-images',
            'duplicate-finder' => 'ksm-extensions-duplicate-finder',
        );

        return isset($settings_map[$module_slug]) ? $settings_map[$module_slug] : false;
    }

    /**
     * Get the full URL for a module's settings or admin page.
     * Duplicate Finder lives under Media (upload.php); others under Settings (options-general.php).
     *
     * Only returns a URL when the module is activated. Inactive modules do not register their
     * settings screen; linking there would hit an unregistered page and trigger a permissions error.
     *
     * @since 1.0.13
     * @param string $module_slug The module slug.
     * @return string|false The full admin URL or false if no settings page or module inactive.
     */
    private function get_module_settings_url($module_slug)
    {
        if (!$this->module_loader || !$this->module_loader->is_module_activated($module_slug)) {
            return false;
        }

        $slug = $this->get_module_settings_slug($module_slug);
        if (!$slug) {
            return false;
        }
        if ('duplicate-finder' === $module_slug) {
            return admin_url('upload.php?page=' . $slug);
        }
        return admin_url('options-general.php?page=' . $slug);
    }

    /**
     * AJAX handler for toggling module activation.
     *
     * @since 1.0.1
     */
    public function ajax_toggle_module()
    {
        check_ajax_referer('ksm_extensions_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'ksm-extensions')));
        }

        $module_slug = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
        $activate = isset($_POST['activate']) && $_POST['activate'] === 'true';

        if (empty($module_slug)) {
            wp_send_json_error(array('message' => __('Module name is required.', 'ksm-extensions')));
        }

        if (!$this->module_loader) {
            wp_send_json_error(array('message' => __('Module loader not available.', 'ksm-extensions')));
        }

        $result = $activate 
            ? $this->module_loader->activate_module($module_slug)
            : $this->module_loader->deactivate_module($module_slug);

        if ($result) {
            wp_send_json_success(array(
                'message' => $activate 
                    ? sprintf(__('Module "%s" activated successfully.', 'ksm-extensions'), $module_slug)
                    : sprintf(__('Module "%s" deactivated successfully.', 'ksm-extensions'), $module_slug),
                'active' => $activate
            ));
        } else {
            wp_send_json_error(array(
                'message' => $activate
                    ? sprintf(__('Failed to activate module "%s".', 'ksm-extensions'), $module_slug)
                    : sprintf(__('Failed to deactivate module "%s".', 'ksm-extensions'), $module_slug)
            ));
        }
    }

    /**
     * Add dashboard widget.
     *
     * @since 1.0.1
     */
    public function add_dashboard_widget()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'ksm_extensions_dashboard',
            __('KSM Extensions', 'ksm-extensions'),
            array($this, 'display_dashboard_widget')
        );
    }

    /**
     * Display dashboard widget content.
     *
     * @since 1.0.1
     */
    public function display_dashboard_widget()
    {
        $modules = $this->module_loader ? $this->module_loader->get_modules() : array();
        $module_states = $this->module_loader ? $this->module_loader->get_module_states() : array();
        
        $active_count = 0;
        $total_count = count($modules);

        foreach ($modules as $module_slug => $module) {
            if (isset($module_states[$module_slug]) && $module_states[$module_slug] === true) {
                $active_count++;
            }
        }

        ?>
        <div class="ksm-extensions-dashboard-widget">
            <p>
                <strong><?php esc_html_e('Extensions:', 'ksm-extensions'); ?></strong>
                <?php echo esc_html($active_count); ?> / <?php echo esc_html($total_count); ?> <?php esc_html_e('active', 'ksm-extensions'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ksm-extensions')); ?>" class="button button-secondary">
                    <?php esc_html_e('Manage Extensions', 'ksm-extensions'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ksm-extensions-settings')); ?>" class="button button-secondary">
                    <?php esc_html_e('Settings', 'ksm-extensions'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
