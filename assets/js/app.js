(function($) {
    'use strict';

    const appContainer = $('#chap-hesab-app-container');
    const apiUrl = chapHesabData.api_url;
    const nonce = chapHesabData.nonce;
    let customersCache = [];
    let productsCache = [];

    // --- RENDER FUNCTIONS ---
    function renderLayout() {
        const layout = `
            <div class="container-fluid py-4">
                <header class="d-flex justify-content-center py-3 mb-4 border-bottom">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link" data-target="customers">مشتریان</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="products">محصولات</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="invoices">فاکتورها</a></li>
                    </ul>
                </header>
                <main id="app-main-content"></main>
            </div>`;
        appContainer.html(layout);
    }
    function renderCustomersPage(customers) { /* ... */ }
    function renderProductsPage(products) { /* ... */ }
    function renderInvoicesPage(invoices) { /* ... */ }
    function renderNewInvoiceStep1() { /* ... */ }
    function renderNewInvoiceStep2(customerId) { /* ... */ }
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
                        <button class="btn btn-primary" id="print-invoice">چاپ</button>
                        <button class="btn btn-secondary" id="back-to-invoices">بازگشت به لیست</button>
                    </div>
                </div>
                <div class="card-body" id="invoice-to-print">
                    <div class="invoice-header text-center mb-4">
                        <h1>فاکتور فروش</h1>
                        <p>شماره فاکتور: ${invoice.id}</p>
                        <p>تاریخ: ${new Date(invoice.date_created).toLocaleDateString('fa-IR')}</p>
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
                    <p><strong>مشتری:</strong> ${invoice.customer_name}</p>
                    <p><strong>تاریخ صدور:</strong> ${new Date(invoice.date_created).toLocaleDateString('fa-IR')}</p>
                    <p><strong>مبلغ کل:</strong> ${Number(invoice.total).toLocaleString('fa-IR')} تومان</p>
                    <p><strong>وضعیت:</strong> ${invoice.status}</p>
                    <hr>
                    <h4>اقلام فاکتور</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>محصول</th>
                                <th>تعداد</th>
                                <th>قیمت واحد</th>
                                <th>جمع</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        $('#app-main-content').html(content);
    }

    // --- LOGIC / API ---
    function navigate(target) { /* ... */ }
    function loadCustomers() { /* ... */ }
    function loadProducts() { /* ... */ }
    function loadInvoices() { /* ... */ }
    function handleNewInvoiceClick() { /* ... */ }
    function handleNewInvoiceStep1(e) { /* ... */ }
    function handleSaveInvoice(e) { /* ... */ }

    function loadInvoiceDetail(invoiceId) {
        $.ajax({
            url: `${apiUrl}invoices/${invoiceId}`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                renderInvoiceDetailPage(response);
            },
            error: function() {
                alert('خطا در دریافت اطلاعات فاکتور.');
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

})(jQuery);
