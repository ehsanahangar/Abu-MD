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

    // Add sub-menu page for Check Management
    add_submenu_page(
        'chap-hesab-main',
        'مدیریت چک‌ها',
        'مدیریت چک‌ها',
        'manage_options',
        'chap-hesab-checks',
        'chap_hesab_checks_page_html'
    );

    // Add sub-menu page for Reports
    add_submenu_page(
        'chap-hesab-main',
        'گزارش‌ها',
        'گزارش‌ها',
        'manage_options',
        'chap-hesab-reports',
        'chap_hesab_reports_page_html'
    );

// Add sub-menu page for Settings
add_submenu_page(
    'chap-hesab-main',
    'تنظیمات',
    'تنظیمات',
    'manage_options',
    'chap-hesab-settings',
    'chap_hesab_settings_page_html'
);
}
add_action( 'admin_menu', 'chap_hesab_add_admin_menu' );

/**
 * Renders the Reports page, routing to the correct report view.
 */
function chap_hesab_reports_page_html() {
    global $wpdb;
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $customers_table = $wpdb->prefix . 'chap_hesab_customers';
    $selected_customer_id = isset( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : 0;
    ?>
    <div class="wrap">
        <h1>گزارش کاردکس مشتری</h1>
        <form method="get" action="">
            <input type="hidden" name="page" value="chap-hesab-reports">
            <label for="customer_id_select">یک مشتری را برای مشاهده گزارش انتخاب کنید:</label>
            <select name="customer_id" id="customer_id_select">
                <option value="">-- انتخاب مشتری --</option>
                <?php
                $customers = $wpdb->get_results( "SELECT id, name FROM $customers_table ORDER BY name ASC" );
                foreach ( $customers as $customer ) {
                    printf(
                        '<option value="%d" %s>%s</option>',
                        esc_attr( $customer->id ),
                        selected( $selected_customer_id, $customer->id, false ),
                        esc_html( $customer->name )
                    );
                }
                ?>
            </select>
            <input type="submit" value="نمایش گزارش" class="button">
        </form>
        <hr>
        <?php
        if ( $selected_customer_id > 0 ) {
            $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
            $payments_table = $wpdb->prefix . 'chap_hesab_payments';

            $customer_invoices = $wpdb->get_results($wpdb->prepare("SELECT id, invoice_date as date, total_amount FROM $invoices_table WHERE customer_id = %d", $selected_customer_id));
            $customer_payments = $wpdb->get_results($wpdb->prepare("SELECT payment_date as date, amount, notes FROM $payments_table WHERE invoice_id IN (SELECT id FROM $invoices_table WHERE customer_id = %d)", $selected_customer_id));

            $transactions = [];
            foreach ($customer_invoices as $invoice) {
                $transactions[] = ['date' => $invoice->date, 'description' => 'فاکتور شماره ' . $invoice->id, 'debit' => $invoice->total_amount, 'credit' => 0];
            }
            foreach ($customer_payments as $payment) {
                $transactions[] = ['date' => $payment->date, 'description' => 'پرداخت' . ($payment->notes ? ' - ' . $payment->notes : ''), 'debit' => 0, 'credit' => $payment->amount];
            }

            usort($transactions, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            ?>
            <h2>کاردکس حساب مشتری: <?php echo esc_html($wpdb->get_var($wpdb->prepare("SELECT name FROM $customers_table WHERE id = %d", $selected_customer_id))); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>شرح</th>
                        <th>بدهکار</th>
                        <th>بستانکار</th>
                        <th>مانده</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $balance = 0;
                    if (empty($transactions)) {
                        echo '<tr><td colspan="5">هیچ تراکنشی برای این مشتری یافت نشد.</td></tr>';
                    } else {
                        foreach ($transactions as $tx) {
                            $balance = $balance + $tx['debit'] - $tx['credit'];
                            ?>
                            <tr>
                                <td><?php echo esc_html(date('Y/m/d', strtotime($tx['date']))); ?></td>
                                <td><?php echo esc_html($tx['description']); ?></td>
                                <td><?php echo $tx['debit'] > 0 ? number_format($tx['debit'], 0) . ' تومان' : '-'; ?></td>
                                <td><?php echo $tx['credit'] > 0 ? number_format($tx['credit'], 0) . ' تومان' : '-'; ?></td>
                                <td><?php echo number_format($balance, 0); ?> تومان</td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" style="text-align:left;">مانده نهایی:</th>
                        <th><?php echo number_format($balance, 0); ?> تومان</th>
                    </tr>
                </tfoot>
            </table>
            <?php
        }
        ?>
    </div>
    <?php
}

// -- REST API ENDPOINTS --

function chap_hesab_api_permission_check() {
    return current_user_can( 'manage_options' );
}

function chap_hesab_register_api_routes() {
    $namespace = 'chap-hesab/v1';

    register_rest_route( $namespace, '/customers', array(
        'methods' => 'GET',
        'callback' => 'chap_hesab_api_get_customers',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );

    register_rest_route( $namespace, '/customers', array(
        'methods' => 'POST',
        'callback' => 'chap_hesab_api_create_customer',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );

    // Product Routes
    register_rest_route( $namespace, '/products', array(
        'methods' => 'GET',
        'callback' => 'chap_hesab_api_get_products',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
    register_rest_route( $namespace, '/products', array(
        'methods' => 'POST',
        'callback' => 'chap_hesab_api_create_or_update_product',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
    register_rest_route( $namespace, '/products/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'chap_hesab_api_delete_product',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );

    // Invoice Routes
    register_rest_route( $namespace, '/invoices', array(
        'methods' => 'GET',
        'callback' => 'chap_hesab_api_get_invoices',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
    register_rest_route( $namespace, '/invoices/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'chap_hesab_api_get_single_invoice',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
    register_rest_route( $namespace, '/invoices', array(
        'methods' => 'POST',
        'callback' => 'chap_hesab_api_create_invoice',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );

    // Check Routes
    register_rest_route( $namespace, '/checks', array(
        'methods' => 'GET',
        'callback' => 'chap_hesab_api_get_checks',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
    register_rest_route( $namespace, '/checks', array(
        'methods' => 'POST',
        'callback' => 'chap_hesab_api_create_check',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
    register_rest_route( $namespace, '/checks/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'chap_hesab_api_update_check_status',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );

    // Reports Route
    register_rest_route( $namespace, '/reports/customer/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'chap_hesab_api_get_customer_report',
        'permission_callback' => 'chap_hesab_api_permission_check',
    ) );
}
add_action( 'rest_api_init', 'chap_hesab_register_api_routes' );

function chap_hesab_api_get_customers() {
    global $wpdb;
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';
    $results = $wpdb->get_results( "SELECT * FROM $customers_table ORDER BY id DESC" );
    return new WP_REST_Response( $results, 200 );
}

function chap_hesab_api_create_customer( WP_REST_Request $request ) {
    global $wpdb;
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';
    $params = $request->get_json_params();

    $name = sanitize_text_field( $params['name'] );
    if ( empty( $name ) ) {
        return new WP_Error( 'no_name', 'نام مشتری اجباری است.', array( 'status' => 400 ) );
    }

    $wpdb->insert( $customers_table, [
        'name'    => $name,
        'email'   => sanitize_email( $params['email'] ),
        'phone'   => sanitize_text_field( $params['phone'] ),
        'address' => sanitize_textarea_field( $params['address'] ),
    ] );
    $new_id = $wpdb->insert_id;

    return new WP_REST_Response( ['id' => $new_id, 'message' => 'مشتری با موفقیت ایجاد شد.'], 201 );
}

function chap_hesab_api_get_products() {
    global $wpdb;
    $products_table = $wpdb->prefix . 'chap_hesab_products';
    $results = $wpdb->get_results( "SELECT * FROM $products_table ORDER BY id DESC" );
    return new WP_REST_Response( $results, 200 );
}

function chap_hesab_api_create_or_update_product( WP_REST_Request $request ) {
    global $wpdb;
    $products_table = $wpdb->prefix . 'chap_hesab_products';
    $params = $request->get_json_params();

    $data = [
        'name'        => sanitize_text_field( $params['name'] ),
        'description' => sanitize_textarea_field( $params['description'] ),
        'price'       => floatval( $params['price'] ),
    ];
    $product_id = isset( $params['id'] ) ? intval( $params['id'] ) : 0;

    if ( empty( $data['name'] ) ) {
        return new WP_Error( 'no_name', 'نام محصول اجباری است.', array( 'status' => 400 ) );
    }

    if ( $product_id > 0 ) {
        $wpdb->update( $products_table, $data, ['id' => $product_id] );
        return new WP_REST_Response( ['id' => $product_id, 'message' => 'محصول به‌روزرسانی شد.'], 200 );
    } else {
        $wpdb->insert( $products_table, $data );
        return new WP_REST_Response( ['id' => $wpdb->insert_id, 'message' => 'محصول جدید ایجاد شد.'], 201 );
    }
}

function chap_hesab_api_delete_product( WP_REST_Request $request ) {
    global $wpdb;
    $products_table = $wpdb->prefix . 'chap_hesab_products';
    $product_id = intval( $request['id'] );

    if ( $product_id <= 0 ) {
        return new WP_Error( 'invalid_id', 'شناسه محصول نامعتبر است.', array( 'status' => 400 ) );
    }

    $wpdb->delete( $products_table, array( 'id' => $product_id ) );
    return new WP_REST_Response( ['message' => 'محصول حذف شد.'], 200 );
}

function chap_hesab_api_get_invoices() {
    global $wpdb;
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';

    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT i.id, i.total_amount, i.status, i.invoice_date, c.name as customer_name
         FROM %i AS i
         LEFT JOIN %i AS c ON i.customer_id = c.id
         ORDER BY i.id DESC",
        $invoices_table, $customers_table
    ) );
    return new WP_REST_Response( $results, 200 );
}

function chap_hesab_api_get_single_invoice( WP_REST_Request $request ) {
    global $wpdb;
    $invoice_id = intval( $request['id'] );

    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';
    $invoice_items_table = $wpdb->prefix . 'chap_hesab_invoice_items';
    $products_table = $wpdb->prefix . 'chap_hesab_products';

    $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT i.*, c.name as customer_name, c.address as customer_address, c.phone as customer_phone FROM %i AS i LEFT JOIN %i AS c ON i.customer_id = c.id WHERE i.id = %d", $invoices_table, $customers_table, $invoice_id ) );

    if ( ! $invoice ) {
        return new WP_Error( 'not_found', 'فاکتور یافت نشد.', array( 'status' => 404 ) );
    }

    // Rename 'total_amount' to 'total' to match front-end expectations
    $invoice->total = $invoice->total_amount;
    unset($invoice->total_amount);

    // Rename 'invoice_date' to 'date_created'
    $invoice->date_created = $invoice->invoice_date;
    unset($invoice->invoice_date);

    $items = $wpdb->get_results( $wpdb->prepare( "SELECT ii.quantity, ii.price, p.name as product_name FROM %i AS ii LEFT JOIN %i AS p ON ii.product_id = p.id WHERE ii.invoice_id = %d", $invoice_items_table, $products_table, $invoice_id ) );

    $invoice->items = $items;

    return new WP_REST_Response( $invoice, 200 );
}

function chap_hesab_api_create_invoice( WP_REST_Request $request ) {
    global $wpdb;
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $invoice_items_table = $wpdb->prefix . 'chap_hesab_invoice_items';

    $params = $request->get_json_params();
    $customer_id = isset( $params['customer_id'] ) ? intval( $params['customer_id'] ) : 0;
    $invoice_items = isset( $params['items'] ) ? $params['items'] : [];

    if ( empty($customer_id) || empty($invoice_items) ) {
        return new WP_Error( 'bad_request', 'اطلاعات فاکتور ناقص است.', array( 'status' => 400 ) );
    }

    $total_amount = 0;
    foreach( $invoice_items as $item ) {
        $total_amount += floatval( $item['price'] ) * intval( $item['quantity'] );
    }

    $wpdb->insert( $invoices_table, [
        'customer_id'  => $customer_id,
        'total_amount' => $total_amount,
        'invoice_date' => current_time( 'mysql' ),
    ], [ '%d', '%f', '%s' ] );
    $invoice_id = $wpdb->insert_id;

    if ( ! $invoice_id ) {
        return new WP_Error( 'db_error', 'خطا در ذخیره فاکتور.', array( 'status' => 500 ) );
    }

    foreach ( $invoice_items as $item ) {
        $wpdb->insert( $invoice_items_table, [
            'invoice_id' => $invoice_id,
            'product_id' => intval( $item['product_id'] ),
            'quantity'   => intval( $item['quantity'] ),
            'price'      => floatval( $item['price'] ),
        ], [ '%d', '%d', '%d', '%f' ] );
    }

    return new WP_REST_Response( ['id' => $invoice_id, 'message' => 'فاکتور با موفقیت ایجاد شد.'], 201 );
}

function chap_hesab_api_get_checks() {
    global $wpdb;
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';

    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.id, p.amount, p.due_date, p.check_status, p.notes, c.name as customer_name, p.invoice_id
         FROM %i AS p
         LEFT JOIN %i AS i ON p.invoice_id = i.id
         LEFT JOIN %i AS c ON i.customer_id = c.id
         WHERE p.payment_method = %s
         ORDER BY p.due_date ASC",
        $payments_table, $invoices_table, $customers_table, 'چک'
    ) );
    return new WP_REST_Response( $results, 200 );
}

function chap_hesab_api_create_check( WP_REST_Request $request ) {
    global $wpdb;
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';
    $params = $request->get_json_params();

    $invoice_id = intval($params['invoice_id']);
    $amount = floatval($params['amount']);
    $due_date = sanitize_text_field($params['due_date']);
    $notes = sanitize_text_field($params['notes']);

    if ( empty($invoice_id) || empty($amount) || empty($due_date) ) {
        return new WP_Error( 'bad_request', 'اطلاعات چک ناقص است.', array( 'status' => 400 ) );
    }

    $wpdb->insert( $payments_table, [
        'invoice_id' => $invoice_id,
        'amount' => $amount,
        'payment_date' => current_time('mysql'),
        'payment_method' => 'چک',
        'due_date' => $due_date,
        'notes' => $notes,
        'check_status' => 'pending'
    ], [ '%d', '%f', '%s', '%s', '%s', '%s', '%s' ] );

    $new_id = $wpdb->insert_id;
    return new WP_REST_Response( ['id' => $new_id, 'message' => 'چک با موفقیت ثبت شد.'], 201 );
}

function chap_hesab_api_update_check_status( WP_REST_Request $request ) {
    global $wpdb;
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';
    $payment_id = intval( $request['id'] );
    $params = $request->get_json_params();
    $new_status = sanitize_text_field( $params['status'] );

    if ( ! in_array( $new_status, ['pending', 'cleared', 'bounced'] ) ) {
        return new WP_Error( 'bad_request', 'وضعیت چک نامعتبر است.', array( 'status' => 400 ) );
    }

    // This logic is simplified for the API. The more complex logic from the admin handler can be added if needed.
    $wpdb->update( $payments_table, ['check_status' => $new_status], ['id' => $payment_id] );

    return new WP_REST_Response( ['message' => 'وضعیت چک به‌روزرسانی شد.'], 200 );
}

function chap_hesab_api_get_customer_report( WP_REST_Request $request ) {
    global $wpdb;
    $customer_id = intval( $request['id'] );

    $customers_table = $wpdb->prefix . 'chap_hesab_customers';
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';

    $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $customers_table WHERE id = %d", $customer_id ) );
    if ( ! $customer ) {
        return new WP_Error( 'not_found', 'مشتری یافت نشد.', array( 'status' => 404 ) );
    }

    $customer_invoices = $wpdb->get_results( $wpdb->prepare("SELECT id, invoice_date as date, total_amount FROM $invoices_table WHERE customer_id = %d", $customer_id) );
    // Only include non-check and cleared check payments in the report
    $customer_payments = $wpdb->get_results( $wpdb->prepare("SELECT p.payment_date as date, p.amount, p.notes, p.payment_method FROM %i p JOIN %i i ON p.invoice_id = i.id WHERE i.customer_id = %d AND (p.payment_method != 'چک' OR p.check_status = 'cleared')", $payments_table, $invoices_table, $customer_id) );

    $transactions = [];
    foreach ($customer_invoices as $invoice) {
        $transactions[] = ['date' => $invoice->date, 'type' => 'invoice', 'description' => 'فاکتور شماره ' . $invoice->id, 'debit' => $invoice->total_amount, 'credit' => 0];
    }
    foreach ($customer_payments as $payment) {
        $description = 'پرداخت' . ($payment->payment_method ? ' (' . $payment->payment_method . ')' : '') . ($payment->notes ? ' - ' . $payment->notes : '');
        $transactions[] = ['date' => $payment->date, 'type' => 'payment', 'description' => $description, 'debit' => 0, 'credit' => $payment->amount];
    }

    // Sort transactions by date
    usort($transactions, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    $report = [
        'customer' => $customer,
        'transactions' => $transactions,
    ];

    return new WP_REST_Response( $report, 200 );
}

/**
 * Renders the Check Management page.
 */
function chap_hesab_checks_page_html() {
    global $wpdb;
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';

    // Fetch all check payments
    $checks_list = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.*, c.name as customer_name
         FROM %i AS p
         LEFT JOIN %i AS i ON p.invoice_id = i.id
         LEFT JOIN %i AS c ON i.customer_id = c.id
         WHERE p.payment_method = %s
         ORDER BY p.due_date ASC",
        $payments_table, $invoices_table, $customers_table, 'چک'
    ) );
    ?>
    <div class="wrap">
        <h1>مدیریت چک‌ها</h1>

        <?php
        if ( isset( $_GET['message'] ) && $_GET['message'] === 'check_success' ) {
            echo '<div class="notice notice-success is-dismissible"><p>وضعیت چک با موفقیت به‌روزرسانی شد.</p></div>';
        }
        if ( isset( $_GET['message'] ) && $_GET['message'] === 'check_error' ) {
            echo '<div class="notice notice-error is-dismissible"><p>خطا در به‌روزرسانی وضعیت چک.</p></div>';
        }
        ?>

        <p>در این صفحه می‌توانید وضعیت چک‌های دریافتی را مدیریت کنید.</p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>مشتری</th>
                    <th>مبلغ</th>
                    <th>تاریخ سررسید</th>
                    <th>شماره چک/یادداشت</th>
                    <th style="width: 25%;">تغییر وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $checks_list ) ) : ?>
                    <?php foreach ( $checks_list as $check ) : ?>
                        <tr>
                            <td><?php echo esc_html( $check->customer_name ); ?><br><small>مربوط به فاکتور <a href="?page=chap-hesab-invoices&action=view&id=<?php echo esc_attr($check->invoice_id); ?>">#<?php echo esc_html($check->invoice_id); ?></a></small></td>
                            <td><?php echo number_format($check->amount, 0); ?> تومان</td>
                            <td><?php echo esc_html($check->due_date); ?></td>
                            <td><?php echo esc_html($check->notes); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="chap_hesab_update_check_status">
                                    <input type="hidden" name="payment_id" value="<?php echo esc_attr($check->id); ?>">
                                    <?php wp_nonce_field('chap_hesab_update_check_nonce'); ?>
                                    <select name="check_status">
                                        <option value="pending" <?php selected($check->check_status, 'pending'); ?>>در انتظار</option>
                                        <option value="cleared" <?php selected($check->check_status, 'cleared'); ?>>پاس شده</option>
                                        <option value="bounced" <?php selected($check->check_status, 'bounced'); ?>>برگشتی</option>
                                    </select>
                                    <button type="submit" class="button button-small">ذخیره</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">هیچ چکی ثبت نشده است.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

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

    // Get settings
    $options = get_option('chap_hesab_options');
    $company_info = [
        'name' => !empty($options['company_name']) ? $options['company_name'] : 'اشک قلم',
        'phone' => !empty($options['company_phone']) ? $options['company_phone'] : '۰۹۱۵۳۱۰۸۷۶۳',
        'email' => !empty($options['company_email']) ? $options['company_email'] : 'ehsanahangar2010@gmail.com',
    ];
    $quotes_text = !empty($options['invoice_quotes']) ? $options['invoice_quotes'] : "آنکه می‌اندیشد، به هدف می‌رسد. - امام علی (ع)";
    $quotes = array_filter(array_map('trim', explode("\n", $quotes_text)));
    $random_quote = !empty($quotes) ? $quotes[array_rand($quotes)] : '';

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
                            <tr class="check-details" style="display: none;">
                                <th><label for="due_date">تاریخ سررسید چک</label></th>
                                <td><input type="date" id="due_date" name="due_date" class="regular-text"></td>
                            </tr>
                            <tr class="check-details" style="display: none;">
                                <th><label for="payment_notes">یادداشت (شماره چک و...)</label></th>
                                <td><textarea id="payment_notes" name="payment_notes" rows="3" class="large-text"></textarea></td>
                            </tr>
                            <tr class="non-check-details">
                                <th><label for="payment_notes_general">یادداشت</label></th>
                                <td><textarea id="payment_notes_general" name="payment_notes_general" rows="3" class="large-text"></textarea></td>
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
    $invoice_items_table = $wpdb->prefix . 'chap_hesab_invoice_items';
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';
    $customers_table = $wpdb->prefix . 'chap_hesab_customers';

    // Handle Delete Action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'chap_delete_invoice_' . $_GET['id'] ) ) {
            $invoice_id_to_delete = intval($_GET['id']);
            // Cascading delete
            $wpdb->delete( $invoice_items_table, array( 'invoice_id' => $invoice_id_to_delete ) );
            $wpdb->delete( $payments_table, array( 'invoice_id' => $invoice_id_to_delete ) );
            $wpdb->delete( $invoices_table, array( 'id' => $invoice_id_to_delete ) );
            echo '<div class="notice notice-success is-dismissible"><p>فاکتور و تمام داده‌های مرتبط با آن حذف شد.</p></div>';
        }
    }

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
                            <td><?php echo number_format( $invoice->total_amount, 0 ); ?> تومان</td>
                            <td><?php echo esc_html( date( 'Y-m-d', strtotime( $invoice->invoice_date ) ) ); ?></td>
                            <td><span class="badge badge-<?php echo esc_attr( $invoice->status ); ?>"><?php echo esc_html( $invoice->status ); ?></span></td>
                            <td>
                                <a href="?page=chap-hesab-invoices&action=view&id=<?php echo esc_attr( $invoice->id ); ?>">مشاهده</a> |
                                <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=chap-hesab-invoices&action=delete&id=' . $invoice->id), 'chap_delete_invoice_' . $invoice->id); ?>" style="color: #a00;" onclick="return confirm('آیا از حذف این فاکتور مطمئن هستید؟ این عمل غیرقابل بازگشت است.')">حذف</a>
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
    // Router for edit/delete/list views
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
        chap_hesab_render_edit_product_page( intval( $_GET['id'] ) );
    } else {
        chap_hesab_render_products_list_page();
    }
}

function chap_hesab_render_edit_product_page( $product_id ) {
    global $wpdb;
    $products_table_name = $wpdb->prefix . 'chap_hesab_products';
    $product = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $products_table_name WHERE id = %d", $product_id ) );

    if ( ! $product ) {
        echo '<div class="wrap"><h1>محصول یافت نشد.</h1></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>ویرایش محصول</h1>
        <form method="post" action="<?php echo admin_url( 'admin.php?page=chap-hesab-products' ); ?>">
            <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>" />
            <?php wp_nonce_field( 'chap_add_edit_product_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="product_name">نام محصول</label></th>
                    <td><input type="text" id="product_name" name="product_name" class="regular-text" value="<?php echo esc_attr( $product->name ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="product_price">قیمت (تومان)</label></th>
                    <td><input type="number" step="any" id="product_price" name="product_price" class="regular-text" value="<?php echo esc_attr( $product->price ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="product_description">توضیحات</label></th>
                    <td><textarea id="product_description" name="product_description" rows="4" class="large-text"><?php echo esc_textarea( $product->description ); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button( 'ذخیره تغییرات' ); ?>
        </form>
    </div>
    <?php
}

function chap_hesab_render_products_list_page() {
    global $wpdb;
    $products_table_name = $wpdb->prefix . 'chap_hesab_products';

    // Handle Add/Edit/Delete Actions
    if ( isset( $_POST['submit'] ) && check_admin_referer( 'chap_add_edit_product_nonce' ) ) {
        $data = [
            'name'        => sanitize_text_field( $_POST['product_name'] ),
            'description' => sanitize_textarea_field( $_POST['product_description'] ),
            'price'       => floatval( $_POST['product_price'] ),
        ];
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        if ( $product_id > 0 ) {
            $wpdb->update( $products_table_name, $data, ['id' => $product_id] );
            echo '<div class="notice notice-success is-dismissible"><p>محصول با موفقیت به‌روزرسانی شد.</p></div>';
        }
    } elseif ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'chap_delete_product_' . $_GET['id'] ) ) {
            $wpdb->delete( $products_table_name, array( 'id' => intval( $_GET['id'] ) ) );
            echo '<div class="notice notice-success is-dismissible"><p>محصول با موفقیت حذف شد.</p></div>';
        }
    }

    $products = $wpdb->get_results( "SELECT * FROM $products_table_name ORDER BY id DESC" );
    ?>
    <div class="wrap">
        <h1>مدیریت محصولات</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th>نام محصول</th>
                    <th>قیمت</th>
                    <th style="width: 20%;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $products ) : foreach ( $products as $product ) : ?>
                    <tr>
                        <td><?php echo esc_html( $product->id ); ?></td>
                        <td><strong><?php echo esc_html( $product->name ); ?></strong></td>
                        <td><?php echo esc_html( number_format( $product->price, 0 ) ); ?> تومان</td>
                        <td>
                            <a href="?page=chap-hesab-products&action=edit&id=<?php echo esc_attr($product->id); ?>">ویرایش</a> |
                            <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=chap-hesab-products&action=delete&id=' . $product->id), 'chap_delete_product_' . $product->id); ?>" style="color: #a00;" onclick="return confirm('آیا از حذف این محصول مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="4">هیچ محصولی یافت نشد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Renders the HTML for the main plugin page, including the customer form.
 */
function chap_hesab_main_page_html() {
    // Router for main page (customers)
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
        chap_hesab_render_edit_customer_page( intval( $_GET['id'] ) );
    } else {
        chap_hesab_render_customers_list_page();
    }
}

function chap_hesab_render_edit_customer_page($customer_id) {
    global $wpdb;
    $customers_table_name = $wpdb->prefix . 'chap_hesab_customers';
    $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $customers_table_name WHERE id = %d", $customer_id ) );

    if ( ! $customer ) {
        echo '<div class="wrap"><h1>مشتری یافت نشد.</h1></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>ویرایش مشتری</h1>
        <form method="post" action="<?php echo admin_url( 'admin.php?page=chap-hesab-main' ); ?>">
            <input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>" />
            <?php wp_nonce_field( 'chap_add_edit_customer_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="customer_name">نام و نام خانوادگی</label></th>
                    <td><input type="text" id="customer_name" name="customer_name" class="regular-text" value="<?php echo esc_attr( $customer->name ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="customer_phone">تلفن</label></th>
                    <td><input type="text" id="customer_phone" name="customer_phone" class="regular-text" value="<?php echo esc_attr( $customer->phone ); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="customer_email">ایمیل</label></th>
                    <td><input type="email" id="customer_email" name="customer_email" class="regular-text" value="<?php echo esc_attr( $customer->email ); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="customer_address">آدرس</label></th>
                    <td><textarea id="customer_address" name="customer_address" rows="3" class="large-text"><?php echo esc_textarea( $customer->address ); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button( 'ذخیره تغییرات' ); ?>
        </form>
    </div>
    <?php
}

function chap_hesab_render_customers_list_page() {
    global $wpdb;
    $customers_table_name = $wpdb->prefix . 'chap_hesab_customers';

    // Handle Add/Edit
    if ( isset( $_POST['submit'] ) && check_admin_referer( 'chap_add_edit_customer_nonce' ) ) {
        $data = [
            'name'    => sanitize_text_field( $_POST['customer_name'] ),
            'email'   => sanitize_email( $_POST['customer_email'] ),
            'phone'   => sanitize_text_field( $_POST['customer_phone'] ),
            'address' => sanitize_textarea_field( $_POST['customer_address'] ),
        ];
        $customer_id = isset( $_POST['customer_id'] ) ? intval( $_POST['customer_id'] ) : 0;

        if ( ! empty( $data['name'] ) ) {
            if ( $customer_id > 0 ) {
                $wpdb->update( $customers_table_name, $data, ['id' => $customer_id] );
                echo '<div class="notice notice-success is-dismissible"><p>مشتری با موفقیت به‌روزرسانی شد.</p></div>';
            } else {
                $wpdb->insert( $customers_table_name, $data );
                echo '<div class="notice notice-success is-dismissible"><p>مشتری جدید اضافه شد.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>نام مشتری نمی‌تواند خالی باشد.</p></div>';
        }
    }

    // Handle Delete
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'chap_delete_customer_' . $_GET['id'] ) ) {
            $customer_id_to_delete = intval($_GET['id']);
            // ... (cascading delete logic will be added here)
            $wpdb->delete( $customers_table_name, array( 'id' => $customer_id_to_delete ) );
            echo '<div class="notice notice-success is-dismissible"><p>مشتری حذف شد. (منطق حذف فاکتورها در مرحله بعد اضافه میشود)</p></div>';
        }
    }

    $customers = $wpdb->get_results( "SELECT * FROM $customers_table_name ORDER BY id DESC" );
    ?>
    <div class="wrap">
        <h1>مدیریت مشتریان</h1>
        <div id="poststuff">
             <div id="post-body" class="metabox-holder columns-1">
                <div class="postbox-container">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h2 class="hndle"><span>افزودن/ویرایش مشتری</span></h2>
                            <div class="inside">
                                <form method="post" action="">
                                    <?php wp_nonce_field( 'chap_add_edit_customer_nonce' ); ?>
                                    <p><label>نام:</label><br><input type="text" name="customer_name" class="widefat" required/></p>
                                    <p><label>تلفن:</label><br><input type="text" name="customer_phone" class="widefat"/></p>
                                    <p><label>ایمیل:</label><br><input type="email" name="customer_email" class="widefat"/></p>
                                    <p><label>آدرس:</label><br><textarea name="customer_address" rows="3" class="widefat"></textarea></p>
                                    <p class="submit"><input type="submit" name="submit" class="button button-primary" value="ذخیره مشتری"></p>
                                </form>
                            </div>
                        </div>
                        <div class="postbox">
                            <h2 class="hndle"><span>لیست مشتریان</span></h2>
                            <div class="inside">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead><tr><th>نام</th><th>تلفن</th><th>ایمیل</th><th style="width: 20%;">عملیات</th></tr></thead>
                                    <tbody>
                                        <?php if ( $customers ) : foreach ( $customers as $customer ) : ?>
                                            <tr>
                                                <td><strong><?php echo esc_html( $customer->name ); ?></strong></td>
                                                <td><?php echo esc_html( $customer->phone ); ?></td>
                                                <td><?php echo esc_html( $customer->email ); ?></td>
                                                <td>
                                                    <a href="?page=chap-hesab-main&action=edit&id=<?php echo esc_attr($customer->id); ?>">ویرایش</a> |
                                                    <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=chap-hesab-main&action=delete&id=' . $customer->id), 'chap_delete_customer_' . $customer->id); ?>" style="color: #a00;" onclick="return confirm('آیا از حذف این مشتری مطمئن هستید؟')">حذف</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; else : ?>
                                            <tr><td colspan="4">هیچ مشتری یافت نشد.</td></tr>
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
        payment_date date NOT NULL,
        notes text,
        due_date date DEFAULT NULL,
        check_status varchar(20) DEFAULT NULL,
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

/**
 * Handles the submission of the check status update form.
 */
function chap_hesab_update_check_status_handler() {
    // Security checks
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'chap_hesab_update_check_nonce' ) ) {
        wp_die( 'Nonce verification failed!' );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    global $wpdb;
    $payments_table = $wpdb->prefix . 'chap_hesab_payments';
    $invoices_table = $wpdb->prefix . 'chap_hesab_invoices';

    // Sanitize data
    $payment_id   = isset( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
    $new_check_status = isset( $_POST['check_status'] ) ? sanitize_text_field( $_POST['check_status'] ) : '';
    $redirect_url = admin_url( 'admin.php?page=chap-hesab-checks' );

    if ( $payment_id <= 0 || ! in_array( $new_check_status, ['pending', 'cleared', 'bounced'] ) ) {
        wp_redirect( $redirect_url . '&message=check_error' );
        exit;
    }

    // Get the payment record BEFORE updating
    $payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $payments_table, $payment_id ) );
    if ( ! $payment ) {
        wp_redirect( $redirect_url . '&message=check_error' );
        exit;
    }
    $old_check_status = $payment->check_status;

    // Update the check status in the payments table
    $wpdb->update( $payments_table, ['check_status' => $new_check_status], ['id' => $payment_id], ['%s'], ['%d'] );

    // If the status changed to or from 'cleared', we need to update the parent invoice
    if ( $new_check_status !== $old_check_status && ($new_check_status === 'cleared' || $old_check_status === 'cleared') ) {
        $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT total_amount, amount_paid FROM %i WHERE id = %d", $invoices_table, $payment->invoice_id ) );

        $new_amount_paid = $invoice->amount_paid;
        if ($new_check_status === 'cleared') {
            $new_amount_paid += $payment->amount; // Add payment amount
        } elseif ($old_check_status === 'cleared') {
            $new_amount_paid -= $payment->amount; // Subtract payment amount
        }

        // Determine new invoice status
        $new_invoice_status = 'unpaid';
        if ( $new_amount_paid >= $invoice->total_amount ) {
            $new_invoice_status = 'paid';
            $new_amount_paid = $invoice->total_amount;
        } elseif ($new_amount_paid > 0) {
            $new_invoice_status = 'partially-paid';
        }

        $wpdb->update(
            $invoices_table,
            [ 'amount_paid' => $new_amount_paid, 'status' => $new_invoice_status ],
            [ 'id' => $payment->invoice_id ],
            [ '%f', '%s' ],
            [ '%d' ]
        );
    }

    wp_redirect( $redirect_url . '&message=check_success' );
    exit;
}
add_action( 'admin_post_chap_hesab_update_check_status', 'chap_hesab_update_check_status_handler' );

// -- FRONT-END APP INFRASTRUCTURE --

/**
 * Enqueues scripts and styles for the front-end app.
 */
function chap_hesab_enqueue_frontend_assets() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'chap_hesab_app' ) ) {
        // Enqueue Fonts & Icons
        wp_enqueue_style( 'vazirmatn-font', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css' );
        wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' );

        // Enqueue Bootstrap CSS
        wp_enqueue_style( 'bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' );
        // Enqueue custom app CSS
        wp_enqueue_style( 'chap-hesab-app-css', plugin_dir_url( __FILE__ ) . 'assets/css/app.css', array(), '1.0.1' );

        // Enqueue Bootstrap JS
        wp_enqueue_script( 'bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true );
        // Enqueue JsBarcode for barcode generation
        wp_enqueue_script( 'jsbarcode', 'https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js', array(), '3.11.5', true );
        // Enqueue Bootstrap JS
        wp_enqueue_script( 'bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true );
        // Enqueue custom app JS
        wp_enqueue_script( 'chap-hesab-app-js', plugin_dir_url( __FILE__ ) . 'assets/js/app.js', array( 'jquery', 'bootstrap-js' ), '1.0.0', true );

        // Pass data to our script
        wp_localize_script( 'chap-hesab-app-js', 'chapHesabData', array(
            'api_url' => esc_url_raw( rest_url( 'chap-hesab/v1/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' )
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'chap_hesab_enqueue_frontend_assets' );

/**
 * Renders the root element for the front-end app via a shortcode.
 */
function chap_hesab_app_shortcode_handler() {
    // Check if user has permission
    if ( ! current_user_can( 'manage_options' ) ) {
        return '<p>شما اجازه دسترسی به این بخش را ندارید.</p>';
    }
    // Return the root div for the JS app to mount on
    return '<div id="chap-hesab-app-container">درحال بارگذاری اپلیکیشن حسابداری...</div>';
}
add_shortcode( 'chap_hesab_app', 'chap_hesab_app_shortcode_handler' );

// -- SETTINGS API --

function chap_hesab_register_settings() {
    register_setting( 'chap_hesab_settings_group', 'chap_hesab_options' );

    add_settings_section( 'chap_hesab_company_section', 'اطلاعات شرکت', null, 'chap-hesab-settings' );
    add_settings_field( 'company_name', 'نام شرکت', 'chap_hesab_render_text_field', 'chap-hesab-settings', 'chap_hesab_company_section', ['name' => 'company_name'] );
    add_settings_field( 'company_phone', 'تلفن', 'chap_hesab_render_text_field', 'chap-hesab-settings', 'chap_hesab_company_section', ['name' => 'company_phone'] );
    add_settings_field( 'company_email', 'ایمیل', 'chap_hesab_render_text_field', 'chap-hesab-settings', 'chap_hesab_company_section', ['name' => 'company_email'] );

    add_settings_section( 'chap_hesab_invoice_section', 'تنظیمات فاکتور', null, 'chap-hesab-settings' );
    add_settings_field( 'invoice_quotes', 'جملات قصار (هر جمله در یک خط)', 'chap_hesab_render_textarea_field', 'chap-hesab-settings', 'chap_hesab_invoice_section', ['name' => 'invoice_quotes'] );
}
add_action( 'admin_init', 'chap_hesab_register_settings' );

function chap_hesab_render_text_field( $args ) {
    $options = get_option( 'chap_hesab_options' );
    $value = isset( $options[$args['name']] ) ? esc_attr( $options[$args['name']] ) : '';
    echo "<input type='text' name='chap_hesab_options[{$args['name']}]' value='{$value}' class='regular-text' />";
}

function chap_hesab_render_textarea_field( $args ) {
    $options = get_option( 'chap_hesab_options' );
    $value = isset( $options[$args['name']] ) ? esc_textarea( $options[$args['name']] ) : '';
    echo "<textarea name='chap_hesab_options[{$args['name']}]' rows='5' class='large-text'>{$value}</textarea>";
}

function chap_hesab_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>تنظیمات پلاگین حسابداری</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'chap_hesab_settings_group' );
            do_settings_sections( 'chap-hesab-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
