<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two callbacks for creating the options page
 *
 * @since      0.1.0
 * @package    Recommendations
 * @subpackage Recommendations/admin
 * @author     Lauri Leiten <leitenlauri@gmail.com>
 * @author     Stiivo Siider <stiivosiider@gmail.com>
 * @author     Hannes Saariste <hannes.saariste@gmail.com>
 */

class Recommender_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since      0.1.0
     * @access     private
     * @var        string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since      0.1.0
     * @access     private
     * @var        string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since      0.1.0
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Registers the options for the menu and checks whether WooCommerce is still active
     *
     * @since      0.1.0
     */
    public function recommender_admin_init()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php'))
            deactivate_plugins('woocommerce-extension/stacc-recommendation.php');
        register_setting('recommender_options', 'shop_id', array('sanitize_callback'  => array( $this, 'recommender_option_sanitizer' )));
        register_setting('recommender_options', 'api_key', array('sanitize_callback'  => array( $this, 'recommender_option_sanitizer' )));
        register_setting('box_options', 'woocommerce_before_single_product_summary');
        register_setting('box_options', 'woocommerce_after_single_product_summary');
        register_setting('box_options', 'woocommerce_before_shop_loop');
        register_setting('box_options', 'woocommerce_after_shop_loop');
        register_setting('box_options', 'woocommerce_before_cart');
        register_setting('box_options', 'woocommerce_after_cart_table');
        register_setting('box_options', 'woocommerce_after_cart_totals');
        register_setting('box_options', 'woocommerce_after_cart');
        register_setting('box_options', 'woocommerce_before_single_product_summary_rows');
        register_setting('box_options', 'woocommerce_before_single_product_summary_columns');
        register_setting('box_options', 'woocommerce_after_single_product_summary_rows');
        register_setting('box_options', 'woocommerce_after_single_product_summary_columns');
        register_setting('box_options', 'woocommerce_before_shop_loop_rows');
        register_setting('box_options', 'woocommerce_before_shop_loop_columns');
        register_setting('box_options', 'woocommerce_after_shop_loop_rows');
        register_setting('box_options', 'woocommerce_after_shop_loop_columns');
        register_setting('box_options', 'woocommerce_before_cart_rows');
        register_setting('box_options', 'woocommerce_before_cart_columns');
        register_setting('box_options', 'woocommerce_after_cart_table_rows');
        register_setting('box_options', 'woocommerce_after_cart_table_columns');
        register_setting('box_options', 'woocommerce_after_cart_totals_rows');
        register_setting('box_options', 'woocommerce_after_cart_totals_columns');
        register_setting('box_options', 'woocommerce_after_cart_rows');
        register_setting('box_options', 'woocommerce_after_cart_columns');
    }

    /**
     * Adds the menu under the WooCommerce settings panel
     *
     * @since      0.1.0
     */
    public function recommender_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'STACC Options',
            'STACC',
            'manage_options',
            'recommender_options',
            array($this, 'recommender_options_page')
        );
    }

    /**
     * Redirects the user to the appropriate page in the admin panel based on the tab that's currently active.
     *
     * @since      0.3.0
     */
    public function recommender_options_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'connect_to_api';

        if( $active_tab == 'connect_to_api' )
            $this->api_auth_page();
        else
            $this->box_preferences_page();
    }

    /**
     * Creates the page for API auth settings
     *
     * @since      0.1.0
     */
    public function api_auth_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $api = new Recommender_API();
        if ( isset( $_GET['settings-updated'] ) ) {
            if ($api->has_connection()) {
                add_settings_error('recommender_messages', 'recommender_api_connection', __('API Online', 'recommender'), 'updated');
                $data = [
                    'log_sync_url' => Recommender_Endpoints::getLogURL() . '&',
                    'product_sync_url' => Recommender_Endpoints::getProductURL() . '&'
                ];
                if ($api->send_post($data, 'creds')){
                    add_settings_error('recommender_messages', 'recommender_message', __('Settings Saved - Plugin Set Up Successful', 'recommender'), 'updated');
                    update_option( 'cred_check_failed', false);
                    Recommender_WC_Log_Handler::logDebug('Settings Saved');
                } else {
                    add_settings_error('recommender_messages', 'recommender_message', __('Validation Error - Plugin Set Up Failed -Check your Shop ID and API Key', 'recommender'), 'error');
                    update_option( 'cred_check_failed', true);
                    Recommender_WC_Log_Handler::logError('Validation Error');
                }
            } else {
                add_settings_error('recommender_messages', 'recommender_api_connection', __('API Offline - Plugin Set Up Failed', 'recommender'), 'error');
                update_option( 'cred_check_failed', true);
                Recommender_WC_Log_Handler::logWarning('API Offline - Settings not saved');
            }
            settings_errors('recommender_messages');
        }

        ?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=recommender_options&tab=connect_to_api" class="nav-tab nav-tab-active">Connect to the API</a>
                <a href="?page=recommender_options&tab=box_preferences" class="nav-tab">Box Preferences</a>
            </h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('recommender_options');
                // output setting sections and their fields
                do_settings_sections('recommender_options');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Extension Version</th>
                        <td><?php echo $this->version; ?></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Shop ID</th>
                        <td><input type="text" name="shop_id" value="<?php echo esc_attr(get_option('shop_id')); ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr(get_option('api_key')); ?>"/>
                        </td>
                    </tr>
                </table>
                <?php
                // output save settings button
                submit_button('Confirm');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Creates the page for recommender box preferences.
     *
     * @since      0.3.0
     */
    public function box_preferences_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error('recommender_messages', 'recommender_message', __('Settings Saved', 'recommender'), 'updated');
            settings_errors('recommender_messages');
        }
        ?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=recommender_options&tab=connect_to_api" class="nav-tab">Connect to the API</a>
                <a href="?page=recommender_options&tab=box_preferences" class="nav-tab nav-tab-active">Box Preferences</a>
            </h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('box_options');
                // output setting sections and their fields
                do_settings_sections('box_options');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Extension Version</th>
                        <td><?php echo $this->version; ?></td>
                    </tr>
                    <tr valign="center">
                        <th scope="row" style="font-size: large">Single product view</th>
                    </tr>
                    <tr valign="top">
                        <th>ID</th>
                        <th>Box placement</th>
                        <th>Enabled</th>
                        <th>Products</th>
                        <th>Columns</th>
                    </tr>
                    <tr valign="top">
                        <th>1</th>
                        <th scope="row">Before product summary</th>
                        <th><input type="checkbox" name="woocommerce_before_single_product_summary" value="10" <?php checked( 10 == get_option( 'woocommerce_before_single_product_summary' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_before_single_product_summary_rows" value="<?php echo esc_attr(get_option('woocommerce_before_single_product_summary_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_before_single_product_summary_columns" value="<?php echo esc_attr(get_option('woocommerce_before_single_product_summary_columns', $default = 2)); ?>" min="1" max="10" </th>
                        <th></th>
                        <th></th>
                    </tr>
                    <tr valign="top">
                        <th>2</th>
                        <th scope="row">After product summary</th>
                        <th><input type="checkbox" name="woocommerce_after_single_product_summary" value="25" <?php checked( 25 == get_option( 'woocommerce_after_single_product_summary' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_after_single_product_summary_rows" value="<?php echo esc_attr(get_option('woocommerce_after_single_product_summary_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_after_single_product_summary_columns" value="<?php echo esc_attr(get_option('woocommerce_after_single_product_summary_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row" style="font-size: large">Multiple product view</th>
                    </tr>
                    <tr valign="top">
                        <th>ID</th>
                        <th>Box placement</th>
                        <th>Enabled</th>
                        <th>Products</th>
                        <th>Columns</th>
                    </tr>
                    <tr valign="top">
                        <th scope="row">3</th>
                        <th scope="row">Before products</th>
                        <th><input type="checkbox" name="woocommerce_before_shop_loop" value="10" <?php checked( 10 == get_option( 'woocommerce_before_shop_loop' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_before_shop_loop_rows" value="<?php echo esc_attr(get_option('woocommerce_before_shop_loop_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_before_shop_loop_columns" value="<?php echo esc_attr(get_option('woocommerce_before_shop_loop_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                    <tr valign="top">
                        <th>4</th>
                        <th scope="row">After products</th>
                        <th><input type="checkbox" name="woocommerce_after_shop_loop" value="10" <?php checked( 10 == get_option( 'woocommerce_after_shop_loop' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_after_shop_loop_rows" value="<?php echo esc_attr(get_option('woocommerce_after_shop_loop_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_after_shop_loop_columns" value="<?php echo esc_attr(get_option('woocommerce_after_shop_loop_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row" style="font-size: large">Shopping cart</th>
                    </tr>
                    <tr valign="top">
                        <th>ID</th>
                        <th>Box placement</th>
                        <th>Enabled</th>
                        <th>Products</th>
                        <th>Columns</th>
                    </tr>
                    <tr valign="top">
                        <th scope="row">5</th>
                        <th scope="row">Before cart</th>
                        <th><input type="checkbox" name="woocommerce_before_cart" value="10" <?php checked( 10 == get_option( 'woocommerce_before_cart' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_before_cart_rows" value="<?php echo esc_attr(get_option('woocommerce_before_cart_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_before_cart_columns" value="<?php echo esc_attr(get_option('woocommerce_before_cart_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row">6</th>
                        <th scope="row">After cart table</th>
                        <th><input type="checkbox" name="woocommerce_after_cart_table" value="10" <?php checked( 10 == get_option( 'woocommerce_after_cart_table' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_after_cart_table_rows" value="<?php echo esc_attr(get_option('woocommerce_after_cart_table_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_after_cart_table_columns" value="<?php echo esc_attr(get_option('woocommerce_after_cart_table_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row">7</th>
                        <th scope="row">After cart totals</th>
                        <th><input type="checkbox" name="woocommerce_after_cart_totals" value="10" <?php checked( 10 == get_option( 'woocommerce_after_cart_totals' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_after_cart_totals_rows" value="<?php echo esc_attr(get_option('woocommerce_after_cart_totals_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_after_cart_totals_columns" value="<?php echo esc_attr(get_option('woocommerce_after_cart_totals_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row">8</th>
                        <th scope="row">After cart</th>
                        <th><input type="checkbox" name="woocommerce_after_cart" value="10" <?php checked( 10 == get_option( 'woocommerce_after_cart' ) ); ?>"/></th>
                        <th scope="row"><input type="number" name="woocommerce_after_cart_rows" value="<?php echo esc_attr(get_option('woocommerce_after_cart_rows', $default = 2)); ?>" min="1" max="10" </th>
                        <th scope="row"><input type="number" name="woocommerce_after_cart_columns" value="<?php echo esc_attr(get_option('woocommerce_after_cart_columns', $default = 2)); ?>" min="1" max="10" </th>
                    </tr>
                </table>
                <?php
                // output save settings button
                submit_button('Confirm');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Function to sanitize the text field
     *
     * @param $data Input data that is to be sanitized
     * @return mixed Sanitized data
     */
    public function recommender_option_sanitizer($data)
    {
        return sanitize_text_field($data);
    }
}
