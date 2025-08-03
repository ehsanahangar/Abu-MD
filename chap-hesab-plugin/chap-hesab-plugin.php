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
 * Adds the main menu and sub-menu pages to the WordPress admin dashboard.
 */
function chap_hesab_add_admin_menu() {
    // Add main menu page (Dashboard)
    add_menu_page(
        'داشبورد حسابداری',
        'حسابداری چاپ',
        'manage_options',
        'chap-hesab-main',
        'chap_hesab_main_page_html',
        'dashicons-calculator',
        25
    );

    // Add sub-menu page for Products
    add_submenu_page(
        'chap-hesab-main',
        'مدیریت محصولات',
        'محصولات',
        'manage_options',
        'chap-hesab-products',
        'chap_hesab_products_page_html'
    );

    // Add sub-menu page for Invoices List
    add_submenu_page(
        'chap-hesab-main',
        'لیست فاکتورها',
        'فاکتورها',
        'manage_options',
        'chap-hesab-invoices',
        'chap_hesab_invoices_list_page_html'
    );

    // Add sub-menu page for adding a new Invoice
    add_submenu_page(
        'chap-hesab-main',
        'صدور فاکتور جدید',
        'صدور فاکتور',
        'manage_options',
        'chap-hesab-invoice-new',
        'chap_hesab_invoice_new_page_html'
    );
}
add_action( 'admin_menu', 'chap_hesab_add_admin_menu' );

/**
 * Renders the HTML for the Invoices List page.
 */
function chap_hesab_invoices_list_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>لیست فاکتورها</h1>
        <p>لیست تمام فاکتورهای صادر شده در اینجا نمایش داده خواهد شد.</p>
    </div>
    <?php
}

/**
 * Renders the HTML for the Add New Invoice page.
 * This will be a multi-step process. Step 1 is choosing the customer.
 */
function chap_hesab_invoice_new_page_html() {
    global $wpdb;
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $customers_table_name = $wpdb->prefix . 'chap_hesab_customers';
    $step = isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1;
    $customer_id = isset( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : 0;

    ?>
    <div class="wrap">
        <h1>صدور فاکتور جدید</h1>
        <?php if ( $step === 1 ) : ?>
            <h2>مرحله ۱: انتخاب مشتری</h2>
            <?php
            $customers = $wpdb->get_results( "SELECT id, name FROM {$customers_table_name} ORDER BY name ASC" );
            if ( empty( $customers ) ) {
                echo '<div class="notice notice-warning"><p>هیچ مشتری ثبت نشده است. لطفاً ابتدا از <a href="?page=chap-hesab-main">صفحه اصلی</a> یک مشتری اضافه کنید.</p></div>';
                return;
            }
            ?>
            <form method="get" action="">
                <input type="hidden" name="page" value="chap-hesab-invoice-new" />
                <input type="hidden" name="step" value="2" />
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="customer_id">انتخاب مشتری</label></th>
                            <td>
                                <select name="customer_id" id="customer_id" required>
                                    <option value="">یک مشتری را انتخاب کنید...</option>
                                    <?php foreach ( $customers as $customer ) : ?>
                                        <option value="<?php echo esc_attr( $customer->id ); ?>"><?php echo esc_html( $customer->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( 'مرحله بعد (افزودن اقلام)' ); ?>
            </form>

        <?php elseif ( $step === 2 && $customer_id > 0 ) : ?>
            <h2>مرحله ۲: افزودن اقلام به فاکتور</h2>
            <p><strong>مشتری:</strong> <?php echo esc_html( $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$customers_table_name} WHERE id = %d", $customer_id ) ) ); ?></p>
            <p>بخش افزودن محصولات به فاکتور در مرحله بعدی پیاده‌سازی خواهد شد.</p>
            <a href="?page=chap-hesab-invoice-new" class="button">&larr; بازگشت به انتخاب مشتری</a>

        <?php else: ?>
             <p>مرحله نامعتبر است. لطفاً از اول شروع کنید.</p>
             <a href="?page=chap-hesab-invoice-new" class="button">شروع مجدد</a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Renders the HTML for the products management page (form and list).
 */
function chap_hesab_products_page_html() {
    global $wpdb;
    $products_table_name = $wpdb->prefix . 'chap_hesab_products';

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle form submission for adding a new product
    if ( isset( $_POST['submit_product'] ) && check_admin_referer( 'chap_hesab_add_product_nonce' ) ) {
        $name        = sanitize_text_field( $_POST['product_name'] );
        $description = sanitize_textarea_field( $_POST['product_description'] );
        $price       = preg_replace( '/[^0-9.]/', '', $_POST['product_price'] ); // Sanitize for decimal

        if ( ! empty( $name ) && is_numeric( $price ) ) {
            $result = $wpdb->insert(
                $products_table_name,
                [ 'name' => $name, 'description' => $description, 'price' => $price ],
                [ '%s', '%s', '%f' ]
            );
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>محصول جدید با موفقیت اضافه شد.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>خطایی در افزودن محصول رخ داد.</p></div>';
            }
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>نام محصول و قیمت باید معتبر باشند.</p></div>';
        }
    }

    // Fetch all products to display in the list
    $products = $wpdb->get_results( "SELECT * FROM $products_table_name ORDER BY id DESC" );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Form for adding new products -->
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <div class="postbox">
                            <h2 class="hndle"><span>افزودن محصول جدید</span></h2>
                            <div class="inside">
                                <form method="post" action="">
                                    <?php wp_nonce_field( 'chap_hesab_add_product_nonce' ); ?>
                                    <p>
                                        <label for="product_name">نام محصول</label>
                                        <input type="text" id="product_name" name="product_name" class="widefat" required/>
                                    </p>
                                    <p>
                                        <label for="product_price">قیمت (تومان)</label>
                                        <input type="text" id="product_price" name="product_price" class="widefat" required/>
                                    </p>
                                    <p>
                                        <label for="product_description">توضیحات</label>
                                        <textarea id="product_description" name="product_description" rows="4" class="widefat"></textarea>
                                    </p>
                                    <p class="submit">
                                        <input type="submit" name="submit_product" id="submit_product" class="button button-primary" value="افزودن محصول">
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- List of existing products -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h2 class="hndle"><span>لیست محصولات</span></h2>
                            <div class="inside">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">ID</th>
                                            <th>نام محصول</th>
                                            <th>قیمت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ( $products ) : ?>
                                            <?php foreach ( $products as $product ) : ?>
                                                <tr>
                                                    <td><?php echo esc_html( $product->id ); ?></td>
                                                    <td><strong><?php echo esc_html( $product->name ); ?></strong></td>
                                                    <td><?php echo esc_html( number_format_i18n( $product->price ) ); ?> تومان</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="3">هیچ محصولی یافت نشد.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br class="clear">
        </div>
    </div>
    <?php
}

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
 * Runs only when the plugin is activated to create all necessary database tables.
 */
function chap_hesab_plugin_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Table for customers
    $customers_table_name = $wpdb->prefix . 'chap_hesab_customers';
    $sql_customers = "CREATE TABLE $customers_table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        phone varchar(20) DEFAULT '' NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        address text,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_customers );

    // Table for products
    $products_table_name = $wpdb->prefix . 'chap_hesab_products';
    $sql_products = "CREATE TABLE $products_table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        price decimal(19, 2) DEFAULT 0.00 NOT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_products );

    // Table for invoices
    $invoices_table_name = $wpdb->prefix . 'chap_hesab_invoices';
    $sql_invoices = "CREATE TABLE $invoices_table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) unsigned NOT NULL,
        total_amount decimal(19, 2) NOT NULL,
        status varchar(20) NOT NULL,
        invoice_date datetime NOT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY customer_id (customer_id)
    ) $charset_collate;";
    dbDelta( $sql_invoices );

    // Table for invoice items
    $invoice_items_table_name = $wpdb->prefix . 'chap_hesab_invoice_items';
    $sql_invoice_items = "CREATE TABLE $invoice_items_table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        invoice_id bigint(20) unsigned NOT NULL,
        product_id bigint(20) unsigned NOT NULL,
        quantity int(11) NOT NULL,
        price decimal(19, 2) NOT NULL,
        PRIMARY KEY  (id),
        KEY invoice_id (invoice_id)
    ) $charset_collate;";
    dbDelta( $sql_invoice_items );
}
register_activation_hook( __FILE__, 'chap_hesab_plugin_activate' );
