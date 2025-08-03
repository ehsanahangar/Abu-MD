jQuery(document).ready(function($) {
    // Function to format numbers with commas, specific to Persian/Farsi
    function formatNumber(num) {
        return new Intl.NumberFormat('fa-IR').format(num);
    }

    // Add item to invoice table
    $('#add-invoice-item-btn').on('click', function() {
        var productSelect = $('#product-select');
        var selectedOption = productSelect.find('option:selected');
        var productId = selectedOption.val();
        var productName = selectedOption.text();
        var productPrice = selectedOption.data('price');
        var quantity = $('#item-quantity').val();

        if (!productId || !quantity || quantity < 1) {
            alert('لطفاً یک محصول و تعداد معتبر را انتخاب کنید.');
            return;
        }

        var lineTotal = productPrice * quantity;

        var newRow = `
            <tr class="invoice-item-row">
                <td>
                    ${productName}
                    <input type="hidden" name="invoice_items[product_id][]" value="${productId}">
                    <input type="hidden" name="invoice_items[price][]" value="${productPrice}">
                </td>
                <td>
                    <input type="number" name="invoice_items[quantity][]" value="${quantity}" min="1" class="item-quantity" style="width: 100%;">
                </td>
                <td class="item-price">${formatNumber(productPrice)} تومان</td>
                <td class="item-line-total">${formatNumber(lineTotal)} تومان</td>
                <td><button type="button" class="button button-link-delete remove-invoice-item">&times;</button></td>
            </tr>
        `;

        $('#invoice-items-body').append(newRow);

        // Reset inputs
        productSelect.val('').trigger('change');
        $('#item-quantity').val('1');

        // Trigger update of totals
        updateGrandTotal();
    });

    // Remove item from invoice table
    $('#invoice-items-body').on('click', '.remove-invoice-item', function() {
        $(this).closest('tr').remove();
        updateGrandTotal();
    });

    // Update totals when quantity changes
    $('#invoice-items-body').on('change', '.item-quantity', function() {
        var row = $(this).closest('tr');
        var quantity = $(this).val();
        var price = parseFloat(row.find('input[name="invoice_items[price][]"]').val());
        var lineTotal = quantity * price;
        row.find('.item-line-total').text(formatNumber(lineTotal) + ' تومان');
        updateGrandTotal();
    });

    // Function to calculate and update the grand total
    function updateGrandTotal() {
        var grandTotal = 0;
        $('.invoice-item-row').each(function() {
            var row = $(this);
            var quantity = parseFloat(row.find('.item-quantity').val());
            var price = parseFloat(row.find('input[name="invoice_items[price][]"]').val());
            grandTotal += quantity * price;
        });
        $('#invoice-grand-total').text(formatNumber(grandTotal) + ' تومان');
    }
});
