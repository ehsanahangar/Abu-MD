<?php
/**
 * Plugin Name: Chap Hesab Accounting
 * Description: A custom accounting plugin for the printing and packaging industry.
 * Version: 0.1.0
 * Author: Jules
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
 * Renders the HTML for the main plugin page.
 */
function chap_hesab_main_page_html() {
    // Double-check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php echo esc_html__( 'به صفحه اصلی پلاگین حسابداری خوش آمدید. قابلیت‌ها به زودی در اینجا اضافه خواهند شد.', 'chap-hesab' ); ?></p>
    </div>
    <?php
}
