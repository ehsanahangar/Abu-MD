<?php
/**
 * Plugin Name: Chap Hesab Accounting
 * Description: A custom accounting plugin for the printing and packaging industry.
 * Version: 0.1.0
 * Author: Ashk Ghalam
 */

// Security check: Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the main menu page to the WordPress admin dashboard.
 */
function chap_hesab_add_admin_menu() {
    add_menu_page(
        'حسابداری چاپ',          // Page Title (in browser tab)
        'حسابداری چاپ',          // Menu Title (in dashboard)
        'manage_options',        // Capability required to see this menu
        'chap-hesab-main',       // Menu Slug (unique identifier)
        'chap_hesab_main_page_html', // Function that renders the page
        'dashicons-calculator',  // Icon
        25                       // Position in the menu
    );
}
add_action( 'admin_menu', 'chap_hesab_add_admin_menu' );

/**
 * Renders the HTML for the main plugin page, including the customer form.
 */
function chap_hesab_main_page_html() {
    global $wpdb;
    $customers_table_name = $wpdb->prefix . 'chap_hesab_customers';

    // Check if user has the required capability
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle form submission for adding a new customer
    if ( isset( $_POST['submit_customer'] ) && check_admin_referer( 'chap_hesab_add_customer_nonce' ) ) {
        $name    = sanitize_text_field( $_POST['customer_name'] );
        $email   = sanitize_email( $_POST['customer_email'] );
        $phone   = sanitize_text_field( $_POST['customer_phone'] );
        $address = sanitize_textarea_field( $_POST['customer_address'] );

        if ( ! empty( $name ) ) {
            $result = $wpdb->insert(
                $customers_table_name,
                [
                    'name'    => $name,
                    'email'   => $email,
                    'phone'   => $phone,
                    'address' => $address,
                ],
                [ '%s', '%s', '%s', '%s' ]
            );
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>مشتری جدید با موفقیت اضافه شد.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>خطایی در افزودن مشتری رخ داد.</p></div>';
            }
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>نام مشتری نمی‌تواند خالی باشد.</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <div class="postbox">
                            <h2 class="hndle"><span>افزودن مشتری جدید</span></h2>
                            <div class="inside">
                                <form method="post" action="">
                                    <?php wp_nonce_field( 'chap_hesab_add_customer_nonce' ); ?>
                                    <table class="form-table">
                                        <tr valign="top">
                                            <th scope="row"><label for="customer_name">نام و نام خانوادگی</label></th>
                                            <td><input type="text" id="customer_name" name="customer_name" class="regular-text" required/></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><label for="customer_phone">تلفن</label></th>
                                            <td><input type="text" id="customer_phone" name="customer_phone" class="regular-text"/></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><label for="customer_email">ایمیل</label></th>
                                            <td><input type="email" id="customer_email" name="customer_email" class="regular-text"/></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><label for="customer_address">آدرس</label></th>
                                            <td><textarea id="customer_address" name="customer_address" rows="4" class="large-text"></textarea></td>
                                        </tr>
                                    </table>
                                    <p class="submit">
                                        <input type="submit" name="submit_customer" id="submit_customer" class="button button-primary" value="افزودن مشتری">
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Runs only when the plugin is activated to create necessary database tables.
 */
function chap_hesab_plugin_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $customers_table_name = $wpdb->prefix . 'chap_hesab_customers';

    $sql = "CREATE TABLE $customers_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        phone varchar(20) DEFAULT '' NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        address text,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'chap_hesab_plugin_activate' );
