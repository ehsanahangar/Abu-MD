(function($) {
    'use strict';

    const appContainer = $('#chap-hesab-app-container');
    const apiUrl = chapHesabData.api_url;
    const nonce = chapHesabData.nonce;

    // --- TEMPLATES / RENDER FUNCTIONS ---

    function renderLayout() {
        const layout = `
            <div class="container-fluid py-4">
                <header class="d-flex justify-content-center py-3 mb-4 border-bottom">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link" data-target="customers">مشتریان</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="products">محصولات</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" data-target="invoices">فاکتورها (بزودی)</a></li>
                    </ul>
                </header>
                <main id="app-main-content">
                    <!-- Dynamic content will be loaded here -->
                </main>
            </div>
        `;
        appContainer.html(layout);
    }

    function renderCustomersPage(customers) {
        const customerRows = customers.map(c => `
            <tr>
                <td>${c.id}</td>
                <td>${c.name}</td>
                <td>${c.phone || ''}</td>
                <td>${c.email || ''}</td>
            </tr>`).join('');
        const pageHtml = `
            <div class="row"><div class="col-md-8">
            <div class="card"><div class="card-header">لیست مشتریان</div><div class="card-body">
            <table class="table table-striped"><thead><tr><th>ID</th><th>نام</th><th>تلفن</th><th>ایمیل</th></tr></thead><tbody>${customerRows}</tbody></table>
            </div></div></div><div class="col-md-4">
            <div class="card"><div class="card-header">افزودن مشتری</div><div class="card-body">
            <form id="customer-form"><div class="mb-3"><label class="form-label">نام</label><input type="text" name="name" class="form-control" required></div><div class="mb-3"><label class="form-label">تلفن</label><input type="text" name="phone" class="form-control"></div><div class="mb-3"><label class="form-label">ایمیل</label><input type="email" name="email" class="form-control"></div><div class="mb-3"><label class="form-label">آدرس</label><textarea name="address" class="form-control"></textarea></div><button type="submit" class="btn btn-primary">ذخیره</button></form>
            </div></div></div></div>`;
        $('#app-main-content').html(pageHtml);
    }

    function renderProductsPage(products) {
        const productRows = products.map(p => `
            <tr>
                <td>${p.id}</td>
                <td>${p.name}</td>
                <td>${Number(p.price).toLocaleString()} تومان</td>
                <td>
                    <button class="btn btn-sm btn-danger delete-product" data-id="${p.id}">حذف</button>
                </td>
            </tr>`).join('');
        const pageHtml = `
            <div class="row"><div class="col-md-8">
            <div class="card"><div class="card-header">لیست محصولات</div><div class="card-body">
            <table class="table table-striped"><thead><tr><th>ID</th><th>نام</th><th>قیمت</th><th>عملیات</th></tr></thead><tbody>${productRows}</tbody></table>
            </div></div></div><div class="col-md-4">
            <div class="card"><div class="card-header">افزودن محصول</div><div class="card-body">
            <form id="product-form"><div class="mb-3"><label class="form-label">نام محصول</label><input type="text" name="name" class="form-control" required></div><div class="mb-3"><label class="form-label">قیمت</label><input type="number" name="price" class="form-control" required></div><div class="mb-3"><label class="form-label">توضیحات</label><textarea name="description" class="form-control"></textarea></div><button type="submit" class="btn btn-primary">ذخیره</button></form>
            </div></div></div></div>`;
        $('#app-main-content').html(pageHtml);
    }

    // --- API & LOGIC FUNCTIONS ---

    function navigate(target) {
        $('.nav-link').removeClass('active');
        $(`[data-target="${target}"]`).addClass('active');
        if (target === 'customers') loadCustomers();
        if (target === 'products') loadProducts();
    }

    function loadCustomers() {
        $.ajax({ url: apiUrl + 'customers', method: 'GET', beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', nonce) })
         .done(response => renderCustomersPage(response));
    }

    function loadProducts() {
        $.ajax({ url: apiUrl + 'products', method: 'GET', beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', nonce) })
         .done(response => renderProductsPage(response));
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        const form = $(e.target);
        const endpoint = form.attr('id') === 'customer-form' ? 'customers' : 'products';
        let data = {};
        form.serializeArray().forEach(item => data[item.name] = item.value);

        $.ajax({
            url: apiUrl + endpoint,
            method: 'POST',
            beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', nonce),
            contentType: 'application/json',
            data: JSON.stringify(data)
        }).done(() => {
            form[0].reset();
            if (endpoint === 'customers') loadCustomers();
            if (endpoint === 'products') loadProducts();
        }).fail(err => alert('خطا: ' + err.responseJSON.message));
    }

    function handleDeleteProduct(e) {
        const id = $(e.target).data('id');
        if (confirm(`آیا از حذف محصول با شناسه ${id} مطمئن هستید؟`)) {
            $.ajax({
                url: `${apiUrl}products/${id}`,
                method: 'DELETE',
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', nonce)
            }).done(() => loadProducts())
              .fail(err => alert('خطا در حذف.'));
        }
    }

    // --- INITIALIZATION & EVENT BINDING ---

    renderLayout();
    navigate('customers'); // Default page

    $(document).on('click', '.nav-link', (e) => {
        e.preventDefault();
        navigate($(e.target).data('target'));
    });

    $(document).on('submit', '#customer-form, #product-form', handleFormSubmit);
    $(document).on('click', '.delete-product', handleDeleteProduct);

})(jQuery);
