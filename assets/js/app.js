(function($) {
    'use strict';

    const appContainer = $('#chap-hesab-app-container');
    const apiUrl = chapHesabData.api_url;
    const nonce = chapHesabData.nonce;
    let customersCache = [];
    let productsCache = [];
    let lastLoadedInvoice = null;

    // --- RENDER FUNCTIONS ---
    function renderLayout() {
        const layout = `
            <div class="container-fluid py-4">
                <header class="d-flex justify-content-center py-3 mb-4 border-bottom">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link" data-target="customers"><i class="bi bi-people-fill"></i> مشتریان</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="products"><i class="bi bi-box-seam-fill"></i> محصولات</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="invoices"><i class="bi bi-receipt-cutoff"></i> فاکتورها</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="checks"><i class="bi bi-bank"></i> مدیریت چک‌ها</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="reports"><i class="bi bi-graph-up"></i> گزارش‌ها</a></li>
                    </ul>
                </header>
                <main id="app-main-content">
                    <div id="loading-spinner" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>در حال بارگذاری...</p>
                    </div>
                </main>
            </div>`;
        appContainer.html(layout);
    }
    function renderCustomersPage(customers) { /* ... */ }
    function renderProductsPage(products) { /* ... */ }
    function renderInvoicesPage(invoices) { /* ... */ }
    function renderNewInvoiceStep1() { /* ... */ }
    function renderNewInvoiceStep2(customerId) { /* ... */ }
    function renderChecksPage(checks) {
        const getStatusBadge = (status) => {
            switch (status) {
                case 'pending': return '<span class="badge bg-warning text-dark">در انتظار</span>';
                case 'cleared': return '<span class="badge bg-success">پاس شده</span>';
                case 'bounced': return '<span class="badge bg-danger">برگشتی</span>';
                default: return '<span class="badge bg-secondary">نامشخص</span>';
            }
        };

        let checksHtml = checks.map(check => `
            <tr>
                <td>${check.customer_name}</td>
                <td>${Number(check.amount).toLocaleString('fa-IR')} تومان</td>
                <td>${new Date(check.due_date).toLocaleDateString('fa-IR')}</td>
                <td>${check.notes || ''}</td>
                <td>${getStatusBadge(check.check_status)}</td>
                <td>
                    <select class="form-select form-select-sm" data-id="${check.id}" onchange="updateCheckStatus(this)">
                        <option value="pending" ${check.check_status === 'pending' ? 'selected' : ''}>در انتظار</option>
                        <option value="cleared" ${check.check_status === 'cleared' ? 'selected' : ''}>پاس شده</option>
                        <option value="bounced" ${check.check_status === 'bounced' ? 'selected' : ''}>برگشتی</option>
                    </select>
                </td>
            </tr>
        `).join('');

        const content = `
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-bank"></i> مدیریت چک‌ها</h3>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>مشتری</th>
                                <th>مبلغ</th>
                                <th>تاریخ سررسید</th>
                                <th>یادداشت (شماره چک)</th>
                                <th>وضعیت</th>
                                <th>تغییر وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${checksHtml.length > 0 ? checksHtml : '<tr><td colspan="6" class="text-center">هیچ چکی یافت نشد.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        $('#app-main-content').html(content);
    }

    function renderReportsPage() {
        // We need customers to populate the dropdown
        if (customersCache.length === 0) {
            // Fetch customers first, then render the page
            $.ajax({
                url: `${apiUrl}customers`,
                method: 'GET',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
                success: function(response) {
                    customersCache = response;
                    renderReportsPage(); // Call again now that cache is populated
                },
                error: function() { $('#app-main-content').html('<div class="alert alert-danger">خطا در بارگذاری مشتریان برای گزارش.</div>'); }
            });
            return;
        }

        let customerOptions = customersCache.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        const content = `
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-graph-up"></i> گزارش کاردکس مشتری</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer-report-select" class="form-label">مشتری را انتخاب کنید:</label>
                            <select id="customer-report-select" class="form-select">
                                <option value="">-- انتخاب کنید --</option>
                                ${customerOptions}
                            </select>
                        </div>
                    </div>
                    <div id="report-content"></div>
                </div>
            </div>
        `;
        $('#app-main-content').html(content);
    }
                case 'cleared': return '<span class="badge bg-success">پاس شده</span>';
                case 'bounced': return '<span class="badge bg-danger">برگشتی</span>';
                default: return '<span class="badge bg-secondary">نامشخص</span>';
            }
        };

        let checksHtml = checks.map(check => `
            <tr>
                <td>${check.customer_name}</td>
                <td>${Number(check.amount).toLocaleString('fa-IR')} تومان</td>
                <td>${new Date(check.due_date).toLocaleDateString('fa-IR')}</td>
                <td>${check.notes || ''}</td>
                <td>${getStatusBadge(check.check_status)}</td>
                <td>
                    <select class="form-select form-select-sm" data-id="${check.id}" onchange="updateCheckStatus(this)">
                        <option value="pending" ${check.check_status === 'pending' ? 'selected' : ''}>در انتظار</option>
                        <option value="cleared" ${check.check_status === 'cleared' ? 'selected' : ''}>پاس شده</option>
                        <option value="bounced" ${check.check_status === 'bounced' ? 'selected' : ''}>برگشتی</option>
                    </select>
                </td>
            </tr>
        `).join('');

        const content = `
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-bank"></i> مدیریت چک‌ها</h3>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>مشتری</th>
                                <th>مبلغ</th>
                                <th>تاریخ سررسید</th>
                                <th>یادداشت (شماره چک)</th>
                                <th>وضعیت</th>
                                <th>تغییر وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${checksHtml.length > 0 ? checksHtml : '<tr><td colspan="6" class="text-center">هیچ چکی یافت نشد.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        $('#app-main-content').html(content);
    }
    function renderInvoiceDetailPage(invoice) {
        let itemsHtml = invoice.items.map(item => `
            <tr>
                <td>${item.product_name}</td>
                <td>${item.quantity}</td>
                <td>${Number(item.price).toLocaleString('fa-IR')} تومان</td>
                <td>${(item.quantity * item.price).toLocaleString('fa-IR')} تومان</td>
            </tr>
        `).join('');

        const content = `
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>جزئیات فاکتور #${invoice.id}</h3>
                    <div>
                        <div class="btn-group">
                            <button class="btn btn-primary" id="print-invoice"><i class="bi bi-printer-fill"></i> چاپ</button>
                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="share-whatsapp"><i class="bi bi-whatsapp"></i> واتس‌اپ</a></li>
                                <li><a class="dropdown-item" href="#" id="share-telegram"><i class="bi bi-telegram"></i> تلگرام</a></li>
                                <li><a class="dropdown-item" href="#" id="share-email"><i class="bi bi-envelope-fill"></i> ایمیل</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-secondary ms-2" id="back-to-invoices">بازگشت به لیست</button>
                    </div>
                </div>
                <div class="card-body" id="invoice-to-print" data-invoice-id="${invoice.id}">
                    <div class="invoice-header text-center mb-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <svg id="barcode"></svg>
                            </div>
                            <div class="col">
                                <h1>فاکتور فروش</h1>
                                <p>شماره فاکتور: ${invoice.id}</p>
                                <p>تاریخ: ${new Date(invoice.date_created).toLocaleDateString('fa-IR')}</p>
                            </div>
                            <div class="col">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=${encodeURIComponent(chapHesabData.admin_url + '?page=chap-hesab-invoices&action=view&id=' + invoice.id)}" alt="QR Code">
                            </div>
                        </div>
                    </div>
                    <div class="row invoice-customer-info">
                        <div class="col">
                            <h5>مشخصات مشتری</h5>
                            <p><strong>نام:</strong> ${invoice.customer_name}</p>
                            <p><strong>تلفن:</strong> ${invoice.customer_phone || 'وارد نشده'}</p>
                            <p><strong>آدرس:</strong> ${invoice.customer_address || 'وارد نشده'}</p>
                        </div>
                    </div>
                    <hr>
                    <h4>اقلام فاکتور</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>محصول</th>
                                <th>تعداد</th>
                                <th>قیمت واحد</th>
                                <th>جمع کل</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">جمع کل:</td>
                                <td class="fw-bold">${Number(invoice.total).toLocaleString('fa-IR')} تومان</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        `;
        $('#app-main-content').html(content);

        // Generate Barcode
        JsBarcode("#barcode", invoice.id, {
            format: "CODE128",
            displayValue: false,
            height: 50,
            margin: 0
        });
    }

    // --- LOGIC / API ---
    function navigate(target) {
        $('.nav-link').removeClass('active');
        $(`.nav-link[data-target="${target}"]`).addClass('active');

        switch (target) {
            case 'customers':
                loadCustomers();
                break;
            case 'products':
                loadProducts();
                break;
            case 'invoices':
                loadInvoices();
                break;
            case 'checks':
                loadChecks();
                break;
            default:
                loadCustomers();
        }
    }
    function loadCustomers() {
        $('#loading-spinner').show();
        $('#app-main-content').children().not('#loading-spinner').hide();
        $.ajax({
            url: `${apiUrl}customers`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                customersCache = response;
                renderCustomersPage(response);
            },
            error: function() {
                $('#app-main-content').html('<div class="alert alert-danger">خطا در بارگذاری مشتریان.</div>');
            },
            complete: function() {
                $('#loading-spinner').hide();
                $('#app-main-content').children().show();
            }
        });
    }

    function loadCustomerReport(customerId) {
        if (!customerId) {
            $('#report-content').html('');
            return;
        }
        $('#report-content').html('<div class="text-center"><div class="spinner-border" role="status"></div><p>در حال تولید گزارش...</p></div>');

        $.ajax({
            url: `${apiUrl}reports/customer/${customerId}`,
            method: 'GET',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
            success: function(response) {
                let balance = 0;
                let rowsHtml = response.transactions.map(tx => {
                    balance += tx.debit - tx.credit;
                    return `
                        <tr>
                            <td>${new Date(tx.date).toLocaleDateString('fa-IR')}</td>
                            <td>${tx.description}</td>
                            <td>${tx.debit > 0 ? Number(tx.debit).toLocaleString('fa-IR') : '-'}</td>
                            <td>${tx.credit > 0 ? Number(tx.credit).toLocaleString('fa-IR') : '-'}</td>
                            <td>${Number(balance).toLocaleString('fa-IR')}</td>
                        </tr>
                    `;
                }).join('');

                const reportHtml = `
                    <h4 class="mt-4">کاردکس حساب: ${response.customer.name}</h4>
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>تاریخ</th>
                                <th>شرح</th>
                                <th>بدهکار (تومان)</th>
                                <th>بستانکار (تومان)</th>
                                <th>مانده (تومان)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-start">مانده نهایی:</th>
                                <th>${Number(balance).toLocaleString('fa-IR')} تومان</th>
                            </tr>
                        </tfoot>
                    </table>
                `;
                $('#report-content').html(reportHtml);
            },
            error: function() {
                $('#report-content').html('<div class="alert alert-danger">خطا در تولید گزارش.</div>');
            }
        });
    }
    function loadProducts() {
        $('#loading-spinner').show();
        $('#app-main-content').children().not('#loading-spinner').hide();
        $.ajax({
            url: `${apiUrl}products`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                productsCache = response;
                renderProductsPage(response);
            },
            error: function() {
                $('#app-main-content').html('<div class="alert alert-danger">خطا در بارگذاری محصولات.</div>');
            },
            complete: function() {
                $('#loading-spinner').hide();
                $('#app-main-content').children().show();
            }
        });
    }
    function loadInvoices() {
        $('#loading-spinner').show();
        $('#app-main-content').children().not('#loading-spinner').hide();
        $.ajax({
            url: `${apiUrl}invoices`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                renderInvoicesPage(response);
            },
            error: function() {
                $('#app-main-content').html('<div class="alert alert-danger">خطا در بارگذاری فاکتورها.</div>');
            },
            complete: function() {
                $('#loading-spinner').hide();
                $('#app-main-content').children().show();
            }
        });
    }

    function loadChecks() {
        $('#loading-spinner').show();
        $('#app-main-content').children().not('#loading-spinner').hide();
        $.ajax({
            url: `${apiUrl}checks`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                renderChecksPage(response);
            },
            error: function() {
                $('#app-main-content').html('<div class="alert alert-danger">خطا در بارگذاری چک‌ها.</div>');
            },
            complete: function() {
                $('#loading-spinner').hide();
                $('#app-main-content').children().show();
            }
        });
    }

    function updateCheckStatus(selectElement) {
        const checkId = $(selectElement).data('id');
        const newStatus = $(selectElement).val();

        $.ajax({
            url: `${apiUrl}checks/${checkId}`,
            method: 'PUT',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            contentType: 'application/json',
            data: JSON.stringify({ status: newStatus }),
            success: function() {
                // Refresh the list to show the updated status badge
                loadChecks();
            },
            error: function() {
                alert('خطا در به‌روزرسانی وضعیت چک.');
            }
        });
    }
    function handleNewInvoiceClick() { /* ... */ }
    function handleNewInvoiceStep1(e) { /* ... */ }
    function handleSaveInvoice(e) { /* ... */ }

    function loadInvoiceDetail(invoiceId) {
        $('#loading-spinner').show();
        $('#app-main-content').children().not('#loading-spinner').hide();
        $.ajax({
            url: `${apiUrl}invoices/${invoiceId}`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                lastLoadedInvoice = response; // Store the loaded invoice data
                renderInvoiceDetailPage(response);
            },
            error: function() {
                $('#app-main-content').html('<div class="alert alert-danger">خطا در دریافت اطلاعات فاکتور.</div>');
            },
            complete: function() {
                $('#loading-spinner').hide();
                $('#app-main-content').children().show();
            }
        });
    }

    function updateInvoiceGrandTotal() {
        let grandTotal = 0;
        $('#invoice-items-body tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const price = parseFloat($(this).data('price')) || 0;
            grandTotal += quantity * price;
        });
        $('#invoice-grand-total').text(grandTotal.toLocaleString('fa-IR') + ' تومان');
    }

    function handleAddInvoiceItem() {
        const productSelect = $('#product-select');
        const selected = productSelect.find('option:selected');
        const productId = selected.val();
        const productName = selected.text();
        const productPrice = selected.data('price');
        const quantity = $('#item-quantity').val();

        if (!productId || !quantity || quantity < 1) return;

        const newRow = `
            <tr data-product-id="${productId}" data-price="${productPrice}">
                <td>${productName}</td>
                <td><input type="number" class="form-control form-control-sm item-quantity" value="${quantity}" min="1"></td>
                <td>${Number(productPrice).toLocaleString('fa-IR')}</td>
                <td class="line-total">${(productPrice * quantity).toLocaleString('fa-IR')}</td>
                <td><button type="button" class="btn btn-sm btn-danger remove-invoice-item">&times;</button></td>
            </tr>`;
        $('#invoice-items-body').append(newRow);
        updateInvoiceGrandTotal();
    }

    function handleItemQuantityChange(e) {
        const row = $(e.target).closest('tr');
        const quantity = $(e.target).val();
        const price = row.data('price');
        const lineTotal = quantity * price;
        row.find('.line-total').text(lineTotal.toLocaleString('fa-IR'));
        updateInvoiceGrandTotal();
    }

    // --- INIT & BINDING ---
    renderLayout();
    navigate('customers');
    $(document).on('click', '.nav-link', e => { e.preventDefault(); navigate($(e.target).data('target')); });
    $(document).on('click', '#btn-new-invoice', handleNewInvoiceClick);
    $(document).on('click', '#cancel-new-invoice', () => navigate('invoices'));
    $(document).on('submit', '#new-invoice-step1-form', handleNewInvoiceStep1);
    $(document).on('submit', '#new-invoice-form', handleSaveInvoice);
    $(document).on('click', '#add-invoice-item-btn', handleAddInvoiceItem);
    $(document).on('click', '.remove-invoice-item', function() { $(this).closest('tr').remove(); updateInvoiceGrandTotal(); });
    $(document).on('change', '.item-quantity', handleItemQuantityChange);
    $(document).on('click', '.view-invoice', function(e) {
        e.preventDefault();
        const invoiceId = $(this).data('id');
        loadInvoiceDetail(invoiceId);
    });
    $(document).on('click', '#back-to-invoices', () => navigate('invoices'));
    $(document).on('click', '#print-invoice', function() {
        const printContents = document.getElementById('invoice-to-print').innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        // Re-initialize the app after printing
        renderLayout();
        loadInvoiceDetail($('#invoice-to-print').data('invoice-id'));
    });
    $(document).on('change', '#customer-report-select', function() {
        const customerId = $(this).val();
        loadCustomerReport(customerId);
    });

    // Share functionality
    function getShareMessage(invoice) {
        const invoiceUrl = chapHesabData.admin_url + '?page=chap-hesab-invoices&action=view&id=' + invoice.id;
        const message = `
سلام،
فاکتور شماره ${invoice.id} به مبلغ ${Number(invoice.total).toLocaleString('fa-IR')} تومان صادر گردید.
جهت مشاهده جزئیات، از لینک زیر استفاده نمایید:
${invoiceUrl}
        `;
        return encodeURIComponent(message.trim());
    }

    $(document).on('click', '#share-whatsapp', function(e) {
        e.preventDefault();
        const message = getShareMessage(lastLoadedInvoice);
        window.open(`https://api.whatsapp.com/send?text=${message}`, '_blank');
    });

    $(document).on('click', '#share-telegram', function(e) {
        e.preventDefault();
        const message = getShareMessage(lastLoadedInvoice);
        window.open(`https://t.me/share/url?url=&text=${message}`, '_blank');
    });

    $(document).on('click', '#share-email', function(e) {
        e.preventDefault();
        const message = getShareMessage(lastLoadedInvoice);
        const subject = `فاکتور شماره ${lastLoadedInvoice.id}`;
        window.open(`mailto:?subject=${encodeURIComponent(subject)}&body=${message}`, '_blank');
    });

})(jQuery);
