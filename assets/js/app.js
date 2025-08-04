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
    function renderInvoiceDetailPage(invoice) { /* ... */ }

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
            default:
                loadCustomers();
        }
    }
    function loadCustomers() { /* ... */ }
    function loadProducts() { /* ... */ }
    function loadInvoices() { /* ... */ }
    function loadInvoiceDetail(invoiceId) { /* ... */ }
    function handleNewInvoiceClick() { /* ... */ }
    function handleNewInvoiceStep1(e) { /* ... */ }
    function handleSaveInvoice(e) { /* ... */ }
    function updateInvoiceGrandTotal() { /* ... */ }
    function handleAddInvoiceItem() { /* ... */ }
    function handleItemQuantityChange(e) { /* ... */ }

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
    $(document).on('click', '.view-invoice', function(e) { e.preventDefault(); loadInvoiceDetail($(this).data('id')); });
    $(document).on('click', '#back-to-invoices', () => navigate('invoices'));

})(jQuery);
