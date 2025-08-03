(function($) {
    'use strict';

    const appContainer = $('#chap-hesab-app-container');
    const apiUrl = chapHesabData.api_url;
    const nonce = chapHesabData.nonce;

    function renderLayout() {
        const layout = `
            <div class="container-fluid">
                <header class="d-flex justify-content-center py-3 mb-4 border-bottom">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active" id="nav-customers">مشتریان</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" id="nav-products">محصولات</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" id="nav-invoices">فاکتورها</a></li>
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
        const customerRows = customers.map(customer => `
            <tr>
                <td>${customer.id}</td>
                <td>${customer.name}</td>
                <td>${customer.phone || ''}</td>
                <td>${customer.email || ''}</td>
            </tr>
        `).join('');

        const pageHtml = `
            <h2>مدیریت مشتریان</h2>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">لیست مشتریان</div>
                        <div class="card-body">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr><th>ID</th><th>نام</th><th>تلفن</th><th>ایمیل</th></tr>
                                </thead>
                                <tbody id="customer-list-body">
                                    ${customerRows}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">افزودن مشتری جدید</div>
                        <div class="card-body">
                            <form id="add-customer-form">
                                <div class="mb-3">
                                    <label for="customer-name" class="form-label">نام</label>
                                    <input type="text" class="form-control" id="customer-name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="customer-phone" class="form-label">تلفن</label>
                                    <input type="text" class="form-control" id="customer-phone">
                                </div>
                                <div class="mb-3">
                                    <label for="customer-email" class="form-label">ایمیل</label>
                                    <input type="email" class="form-control" id="customer-email">
                                </div>
                                <div class="mb-3">
                                    <label for="customer-address" class="form-label">آدرس</label>
                                    <textarea class="form-control" id="customer-address" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">ذخیره</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#app-main-content').html(pageHtml);
    }

    function loadCustomers() {
        $.ajax({
            url: apiUrl + 'customers',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            }
        }).done(function(response) {
            renderCustomersPage(response);
        });
    }

    function handleAddCustomer(e) {
        e.preventDefault();
        const data = {
            name: $('#customer-name').val(),
            phone: $('#customer-phone').val(),
            email: $('#customer-email').val(),
            address: $('#customer-address').val(),
        };

        $.ajax({
            url: apiUrl + 'customers',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            contentType: 'application/json',
            data: JSON.stringify(data)
        }).done(function() {
            $('#add-customer-form')[0].reset();
            loadCustomers();
        }).fail(function(response) {
            alert('خطا: ' + response.responseJSON.message);
        });
    }

    // Initial Load
    renderLayout();
    loadCustomers();

    // Event Delegation for form submission
    $(document).on('submit', '#add-customer-form', handleAddCustomer);

})(jQuery);
