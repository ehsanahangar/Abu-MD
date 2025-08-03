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

// Include helper files
require_once plugin_dir_path( __FILE__ ) . 'includes/persian-helpers.php';

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
 * Renders the correct page based on action (list vs view single).
 */
function chap_hesab_invoices_list_page_html() {
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && isset( $_GET['id'] ) ) {
        chap_hesab_render_single_invoice_page( intval( $_GET['id'] ) );
    } else {
        chap_hesab_render_invoices_table_page();
    }
}

/**
 * Renders the single invoice view.
 */
function chap_hesab_render_single_invoice_page( $invoice_id ) {
    global $wpdb;
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';
    $invoice_items_table = $wpdb->prefix . 'chap_hesab_invoice_items';
    $products_table = $wpdb->prefix . 'chap_hesab_products';

    $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT i.*, c.name as customer_name, c.address as customer_address, c.phone as customer_phone FROM %i AS i LEFT JOIN %i AS c ON i.customer_id = c.id WHERE i.id = %d", $invoices_table, $customers_table, $invoice_id ) );
    $items = $wpdb->get_results( $wpdb->prepare( "SELECT ii.quantity, ii.price, p.name as product_name FROM %i AS ii LEFT JOIN %i AS p ON ii.product_id = p.id WHERE ii.invoice_id = %d", $invoice_items_table, $products_table, $invoice_id ) );

    if ( ! $invoice ) {
        echo '<div class="wrap"><h1>فاکتور یافت نشد</h1><p>فاکتور مورد نظر وجود ندارد.</p><a href="?page=chap-hesab-invoices" class="button">بازگشت</a></div>';
        return;
    }

    // Helper data
    $company_info = ['name' => 'اشک قلم', 'phone' => '۰۹۱۵۳۱۰۸۷۶۳', 'email' => 'ehsanahangar2010@gmail.com'];
    $quotes = ["آنکه می‌اندیشد، به هدف می‌رسد. - امام علی (ع)", "دانش، میراثی گرانبهاست. - امام علی (ع)", "هنر، کلید فهم زندگی است. - سهراب سپهری"];
    $random_quote = $quotes[array_rand($quotes)];
    list($g_y, $g_m, $g_d) = explode('-', date('Y-m-d', strtotime($invoice->invoice_date)));
    $jalali_date = chap_hesab_gregorian_to_jalali($g_y, $g_m, $g_d);

    ?>
    <style>
        #invoice-wrapper { background: #fff; border: 1px solid #e5e5e5; padding: 40px; max-width: 800px; margin: 20px auto; }
        #invoice-wrapper table { width: 100%; }
        #invoice-wrapper .invoice-top { margin-bottom: 40px; }
        #invoice-wrapper .invoice-top h2 { font-size: 28px; margin: 0; }
        #invoice-wrapper .company-details, #invoice-wrapper .customer-details { font-size: 14px; line-height: 1.6; }
        #invoice-wrapper .invoice-top table td { vertical-align: top; }
        #invoice-wrapper .invoice-items-table th, #invoice-wrapper .invoice-items-table td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        #invoice-wrapper .invoice-items-table th { background: #f9f9f9; }
        #invoice-wrapper .invoice-footer { margin-top: 40px; text-align: center; color: #777; }
        @media print { body { background: #fff; } #wpadminbar, #adminmenumain, .wrap > h1, .wrap > .button { display: none; } #invoice-wrapper { box-shadow: none; border: none; margin: 0; max-width: 100%;} }
    </style>
    <div class="wrap">
        <h1>مشاهده فاکتور #<?php echo chap_hesab_to_persian_digits($invoice->id); ?> <a href="#" class="page-title-action" onclick="window.print(); return false;">چاپ</a></h1>

        <?php
        if ( isset( $_GET['message'] ) && $_GET['message'] === 'payment_success' ) {
            echo '<div class="notice notice-success is-dismissible"><p>پرداخت با موفقیت ثبت شد.</p></div>';
        }
        if ( isset( $_GET['message'] ) && $_GET['message'] === 'payment_error' ) {
            echo '<div class="notice notice-error is-dismissible"><p>خطا در ثبت پرداخت. لطفاً مبلغ معتبر وارد کنید.</p></div>';
        }
        ?>

        <div id="invoice-wrapper">
            <div class="invoice-top">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="company-details">
                            <h2><?php echo esc_html($company_info['name']); ?></h2>
                            <p>تلفن: <?php echo chap_hesab_to_persian_digits($company_info['phone']); ?><br>
                               ایمیل: <?php echo esc_html($company_info['email']); ?></p>
                        </td>
                        <td style="text-align: left; vertical-align: top;">
                            <h3>فاکتور فروش</h3>
                            <p><strong>شماره فاکتور:</strong> <?php echo chap_hesab_to_persian_digits($invoice->id); ?><br>
                               <strong>تاریخ صدور:</strong> <?php echo chap_hesab_to_persian_digits($jalali_date); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="customer-details">
                             <strong>صورتحساب برای:</strong><br>
                             <?php echo esc_html($invoice->customer_name); ?><br>
                             <?php if($invoice->customer_address) echo esc_html($invoice->customer_address) . '<br>'; ?>
                             تلفن: <?php echo chap_hesab_to_persian_digits($invoice->customer_phone); ?>
                        </td>
                        <td style="text-align: left; vertical-align: bottom;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=<?php echo urlencode(admin_url('admin.php?page=chap-hesab-invoices&action=view&id=' . $invoice->id)); ?>" alt="QR Code">
                        </td>
                    </tr>
                </table>
            </div>

            <table class="invoice-items-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>شرح محصول/خدمات</th>
                        <th style="width: 15%;">تعداد</th>
                        <th style="width: 25%;">قیمت واحد (تومان)</th>
                        <th style="width: 25%;">مبلغ کل (تومان)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item):
                        $line_total = $item->price * $item->quantity;
                    ?>
                    <tr>
                        <td><?php echo esc_html($item->product_name); ?></td>
                        <td><?php echo chap_hesab_to_persian_digits($item->quantity); ?></td>
                        <td><?php echo chap_hesab_to_persian_digits(number_format($item->price, 0)); ?></td>
                        <td><?php echo chap_hesab_to_persian_digits(number_format($line_total, 0)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                 <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        <td style="text-align: left; font-weight: bold;">جمع کل:</td>
                        <td style="font-weight: bold;"><?php echo chap_hesab_to_persian_digits(number_format($invoice->total_amount, 0)); ?> تومان</td>
                    </tr>
                </tfoot>
            </table>
            <div class="invoice-footer">
                <p><i><?php echo esc_html($random_quote); ?></i></p>
            </div>
        </div>

        <div id="payment-wrapper" style="margin-top: 40px;">
            <div class="postbox">
                <h2 class="hndle"><span>وضعیت پرداخت</span></h2>
                <div class="inside">
                    <p><strong>مبلغ کل:</strong> <?php echo chap_hesab_to_persian_digits(number_format($invoice->total_amount, 0)); ?> تومان</p>
                    <p><strong>پرداخت شده:</strong> <?php echo chap_hesab_to_persian_digits(number_format($invoice->amount_paid, 0)); ?> تومان</p>
                    <p><strong>مانده:</strong> <?php echo chap_hesab_to_persian_digits(number_format($invoice->total_amount - $invoice->amount_paid, 0)); ?> تومان</p>
                    <hr>
                    <?php if ( ($invoice->total_amount - $invoice->amount_paid) > 0) : ?>
                    <h3>ثبت پرداخت جدید</h3>
                    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                        <input type="hidden" name="action" value="chap_hesab_add_payment">
                        <input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice_id); ?>">
                        <?php wp_nonce_field( 'chap_hesab_add_payment_nonce' ); ?>

                        <table class="form-table">
                            <tr>
                                <th><label for="payment_amount">مبلغ پرداخت</label></th>
                                <td><input type="number" id="payment_amount" name="payment_amount" class="regular-text" required> تومان</td>
                            </tr>
                            <tr>
                                <th><label for="payment_date">تاریخ پرداخت</label></th>
                                <td><input type="date" id="payment_date" name="payment_date" class="regular-text" value="<?php echo date('Y-m-d'); ?>" required></td>
                            </tr>
                             <tr>
                                <th><label for="payment_method">روش پرداخت</label></th>
                                <td>
                                    <select id="payment_method" name="payment_method">
                                        <option value="نقدی">نقدی</option>
                                        <option value="کارت‌خوان">کارت‌خوان</option>
                                        <option value="چک">چک</option>
                                        <option value="انتقال بانکی">انتقال بانکی</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="payment_notes">یادداشت (شماره چک و...)</label></th>
                                <td><textarea id="payment_notes" name="payment_notes" rows="3" class="large-text"></textarea></td>
                            </tr>
                        </table>
                        <?php submit_button('ثبت پرداخت'); ?>
                    </form>
                    <?php else: ?>
                    <p><strong>این فاکتور به طور کامل پرداخت شده است.</strong></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
    <?php
}

/**
 * Renders the page with the list of all invoices.
 */
function chap_hesab_render_invoices_table_page() {
    global $wpdb;
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';

    $invoices_list = $wpdb->get_results( $wpdb->prepare(
        "SELECT i.id, i.total_amount, i.status, i.invoice_date, c.name as customer_name
         FROM %i AS i
         LEFT JOIN %i AS c ON i.customer_id = c.id
         ORDER BY i.id DESC",
        $invoices_table, $customers_table
    ) );
    ?>
    <div class="wrap">
        <h1>لیست فاکتورها <a href="<?php echo admin_url('admin.php?page=chap-hesab-invoice-new'); ?>" class="page-title-action">صدور فاکتور جدید</a></h1>

        <?php
        if ( isset( $_GET['message'] ) && $_GET['message'] === 'success' ) {
            echo '<div class="notice notice-success is-dismissible"><p>فاکتور جدید با موفقیت ذخیره شد.</p></div>';
        }
        ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:10%;">شماره فاکتور</th>
                    <th>مشتری</th>
                    <th>مبلغ کل</th>
                    <th>تاریخ صدور</th>
                    <th style="width:10%;">وضعیت</th>
                    <th style="width:15%;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $invoices_list ) ) : ?>
                    <?php foreach ( $invoices_list as $invoice ) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html( $invoice->id ); ?></strong></td>
                            <td><?php echo esc_html( $invoice->customer_name ); ?></td>
                            <td><?php echo number_format( $invoice->total_amount, 2 ); ?> تومان</td>
                            <td><?php echo esc_html( date( 'Y-m-d', strtotime( $invoice->invoice_date ) ) ); ?></td>
                            <td><span class="badge badge-<?php echo esc_attr( $invoice->status ); ?>"><?php echo esc_html( $invoice->status ); ?></span></td>
                            <td>
                                <a href="?page=chap-hesab-invoices&action=view&id=<?php echo esc_attr( $invoice->id ); ?>">مشاهده</a> |
                                <a href="#" style="color: #a00;">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">هیچ فاکتوری یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
    $products_table_name = $wpdb->prefix . 'chap_hesab_products';
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
                                <select name="customer_id" id="customer_id" class="wc-customer-search" required>
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

        <?php elseif ( $step === 2 && $customer_id > 0 ) :
            $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$customers_table_name} WHERE id = %d", $customer_id ) );
            $products = $wpdb->get_results( "SELECT id, name, price FROM {$products_table_name} ORDER BY name ASC" );
            ?>
            <form id="invoice-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <input type="hidden" name="action" value="chap_hesab_save_invoice" />
                <input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer_id ); ?>" />
                <?php wp_nonce_field( 'chap_hesab_save_invoice_nonce' ); ?>

                <h2>مرحله ۲: افزودن اقلام برای مشتری: <?php echo esc_html( $customer->name ); ?></h2>

                <div id="invoice-items-wrapper">
                    <table class="wp-list-table widefat fixed striped" id="invoice-items-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">محصول</th>
                                <th style="width: 15%;">تعداد</th>
                                <th style="width: 20%;">قیمت واحد</th>
                                <th style="width: 20%;">جمع کل</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="invoice-items-body">
                            <!-- Rows will be added here by JavaScript -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" style="text-align:left;">جمع کل فاکتور:</th>
                                <td id="invoice-grand-total">۰ تومان</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="add-item-wrapper" style="margin-top: 20px;">
                    <select id="product-select" style="width: 300px;">
                        <option value="">-- یک محصول را انتخاب کنید --</option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product->id ); ?>" data-price="<?php echo esc_attr( $product->price ); ?>"><?php echo esc_html( $product->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="item-quantity" value="1" min="1" style="width: 80px;" />
                    <button type="button" class="button" id="add-invoice-item-btn">افزودن به فاکتور</button>
                </div>

                <p class="submit" style="margin-top: 20px;">
                    <input type="submit" name="save_invoice" id="save_invoice" class="button button-primary button-large" value="ذخیره فاکتور">
                    <a href="?page=chap-hesab-invoice-new" class="button" style="margin-right: 10px;">&larr; بازگشت به انتخاب مشتری</a>
                </p>
            </form>
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
        amount_paid decimal(19, 2) NOT NULL DEFAULT 0.00,
        status varchar(20) NOT NULL DEFAULT 'unpaid',
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

    // Table for payments
    $payments_table_name = $wpdb->prefix . 'chap_hesab_payments';
    $sql_payments = "CREATE TABLE $payments_table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        invoice_id bigint(20) unsigned NOT NULL,
        amount decimal(19, 2) NOT NULL,
        payment_method varchar(50) NOT NULL,
        payment_date datetime NOT NULL,
        notes text,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY invoice_id (invoice_id)
    ) $charset_collate;";
    dbDelta( $sql_payments );
}
register_activation_hook( __FILE__, 'chap_hesab_plugin_activate' );

/**
 * Enqueues the necessary admin scripts.
 */
function chap_hesab_load_admin_scripts( $hook ) {
    // Only load the script on our plugin's 'add new invoice' page
    if ( 'toplevel_page_chap-hesab-main' !== $hook && 'حسابداری-چاپ_page_chap-hesab-invoice-new' !== $hook && strpos($hook, 'chap-hesab-invoice-new') === false) {
        // A more robust check might be needed if the hook name is complex
        if(!isset($_GET['page']) || $_GET['page'] !== 'chap-hesab-invoice-new') {
            return;
        }
    }

    wp_enqueue_script(
        'chap-hesab-invoice-script',
        plugin_dir_url( __FILE__ ) . 'assets/js/invoice.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'chap_hesab_load_admin_scripts' );

/**
 * Handles the submission of the new invoice form.
 */
function chap_hesab_save_invoice_handler() {
    // Security checks
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'chap_hesab_save_invoice_nonce' ) ) {
        wp_die( 'Nonce verification failed!' );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    global $wpdb;
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $invoice_items_table = $wpdb->prefix . 'chap_hesab_invoice_items';

    // Sanitize and validate data
    $customer_id = isset( $_POST['customer_id'] ) ? intval( $_POST['customer_id'] ) : 0;
    $invoice_items = isset( $_POST['invoice_items'] ) ? $_POST['invoice_items'] : array();

    if ( empty( $customer_id ) || empty( $invoice_items ) || empty( $invoice_items['product_id'] ) ) {
        wp_redirect( admin_url( 'admin.php?page=chap-hesab-invoice-new&message=error' ) );
        exit;
    }

    // Server-side calculation of total
    $total_amount = 0;
    $items_to_insert = [];
    for ( $i = 0; $i < count( $invoice_items['product_id'] ); $i++ ) {
        $product_id = intval( $invoice_items['product_id'][ $i ] );
        $quantity   = intval( $invoice_items['quantity'][ $i ] );
        $price      = floatval( $invoice_items['price'][ $i ] );

        if ($quantity > 0 && $price >= 0) {
            $total_amount += $quantity * $price;
            $items_to_insert[] = [
                'product_id' => $product_id,
                'quantity'   => $quantity,
                'price'      => $price,
            ];
        }
    }

    // Insert main invoice record
    $invoice_data = [
        'customer_id'  => $customer_id,
        'total_amount' => $total_amount,
        'invoice_date' => current_time( 'mysql' ),
        // status and amount_paid will use their DB defaults ('unpaid', 0.00)
    ];
    $wpdb->insert( $invoices_table, $invoice_data, [ '%d', '%f', '%s' ] );
    $invoice_id = $wpdb->insert_id;

    if ( $invoice_id ) {
        // Insert invoice items
        foreach ( $items_to_insert as $item ) {
            $wpdb->insert(
                $invoice_items_table,
                [
                    'invoice_id' => $invoice_id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ],
                [ '%d', '%d', '%d', '%f' ]
            );
        }
        // Redirect on success
        wp_redirect( admin_url( 'admin.php?page=chap-hesab-invoices&message=success' ) );
    } else {
        // Redirect on failure
        wp_redirect( admin_url( 'admin.php?page=chap-hesab-invoice-new&message=error' ) );
    }
    exit;
}
add_action( 'admin_post_chap_hesab_save_invoice', 'chap_hesab_save_invoice_handler' );

/**
 * Handles the submission of the add payment form.
 */
function chap_hesab_add_payment_handler() {
    // Security checks
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'chap_hesab_add_payment_nonce' ) ) {
        wp_die( 'Nonce verification failed!' );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    global $wpdb;
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';

    // Sanitize data
    $invoice_id     = isset( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : 0;
    $amount         = isset( $_POST['payment_amount'] ) ? floatval( $_POST['payment_amount'] ) : 0;
    $payment_date   = isset( $_POST['payment_date'] ) ? sanitize_text_field( $_POST['payment_date'] ) : current_time( 'Y-m-d' );
    $payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';
    $notes          = isset( $_POST['payment_notes'] ) ? sanitize_textarea_field( $_POST['payment_notes'] ) : '';

    $redirect_url = admin_url( 'admin.php?page=chap-hesab-invoices&action=view&id=' . $invoice_id );

    if ( $invoice_id <= 0 || $amount <= 0 ) {
        wp_redirect( $redirect_url . '&message=payment_error' );
        exit;
    }

    // Insert payment record
    $wpdb->insert(
        $payments_table,
        [
            'invoice_id'     => $invoice_id,
            'amount'         => $amount,
            'payment_date'   => $payment_date,
            'payment_method' => $payment_method,
            'notes'          => $notes,
        ],
        [ '%d', '%f', '%s', '%s', '%s' ]
    );

    // Update the invoice amount_paid and status
    $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT total_amount, amount_paid FROM %i WHERE id = %d", $invoices_table, $invoice_id ) );
    $new_amount_paid = $invoice->amount_paid + $amount;

    $new_status = 'partially-paid';
    if ( $new_amount_paid >= $invoice->total_amount ) {
        $new_status = 'paid';
        $new_amount_paid = $invoice->total_amount; // Prevent overpaying
    }

    $wpdb->update(
        $invoices_table,
        [ 'amount_paid' => $new_amount_paid, 'status' => $new_status ],
        [ 'id' => $invoice_id ],
        [ '%f', '%s' ],
        [ '%d' ]
    );

    wp_redirect( $redirect_url . '&message=payment_success' );
    exit;
}
add_action( 'admin_post_chap_hesab_add_payment', 'chap_hesab_add_payment_handler' );
