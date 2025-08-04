import os
from datetime import datetime
from flask import Flask, render_template, request, redirect, url_for
from flask_sqlalchemy import SQLAlchemy
from flask_weasyprint import HTML, CSS

# اپلیکیشن Flask را مقداردهی اولیه کنید
app = Flask(__name__)
basedir = os.path.abspath(os.path.dirname(__file__))

# پیکربندی پایگاه داده
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///' + os.path.join(basedir, 'database.db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SECRET_KEY'] = 'your_super_secret_key'  # این کلید را در یک محیط واقعی تغییر دهید

db = SQLAlchemy(app)

# تعریف مدل‌های پایگاه داده

class User(db.Model):
    """مدل برای کاربران سیستم"""
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    password_hash = db.Column(db.String(120), nullable=False)
    is_admin = db.Column(db.Boolean, default=False)

class Customer(db.Model):
    """مدل برای مشتریان"""
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    phone = db.Column(db.String(20), nullable=True)
    address = db.Column(db.String(200), nullable=True)
    invoices = db.relationship('Invoice', backref='customer', lazy=True)

class Product(db.Model):
    """مدل برای محصولات و خدمات"""
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    description = db.Column(db.String(300), nullable=True)
    # این می‌تواند قیمت پایه باشد
    price = db.Column(db.Float, nullable=False)

class Invoice(db.Model):
    """مدل برای فاکتورها"""
    id = db.Column(db.Integer, primary_key=True)
    issue_date = db.Column(db.DateTime, nullable=False, default=db.func.current_timestamp())
    due_date = db.Column(db.DateTime, nullable=True)
    total_amount = db.Column(db.Float, nullable=False)
    status = db.Column(db.String(20), nullable=False, default='unpaid')  # unpaid, paid, partially_paid
    customer_id = db.Column(db.Integer, db.ForeignKey('customer.id'), nullable=False)
    tracking_code = db.Column(db.String(50), unique=True, nullable=True)
    payments = db.relationship('Payment', backref='invoice', lazy=True)

class InvoiceItem(db.Model):
    """جزئیات هر آیتم در فاکتور"""
    id = db.Column(db.Integer, primary_key=True)
    invoice_id = db.Column(db.Integer, db.ForeignKey('invoice.id'), nullable=False)
    product_id = db.Column(db.Integer, db.ForeignKey('product.id'), nullable=False)
    quantity = db.Column(db.Integer, nullable=False)
    unit_price = db.Column(db.Float, nullable=False)
    product = db.relationship('Product')

class Payment(db.Model):
    """مدل برای پرداخت‌ها"""
    id = db.Column(db.Integer, primary_key=True)
    invoice_id = db.Column(db.Integer, db.ForeignKey('invoice.id'), nullable=False)
    amount = db.Column(db.Float, nullable=False)
    payment_date = db.Column(db.DateTime, nullable=False, default=db.func.current_timestamp())
    payment_method = db.Column(db.String(50), nullable=False)  # e.g., 'cash', 'cheque'
    cheque_details = db.Column(db.String(200), nullable=True)

class Settings(db.Model):
    """مدل برای تنظیمات برنامه"""
    id = db.Column(db.Integer, primary_key=True)
    key = db.Column(db.String(50), unique=True, nullable=False)
    value = db.Column(db.String(200), nullable=False)

@app.route('/')
def index():
    return redirect(url_for('dashboard'))

@app.route('/dashboard')
def dashboard():
    customer_count = Customer.query.count()
    invoice_count = Invoice.query.count()
    product_count = Product.query.count()
    return render_template('dashboard.html',
                           customer_count=customer_count,
                           invoice_count=invoice_count,
                           product_count=product_count)

@app.route('/login')
def login():
    return render_template('login.html')

@app.route('/customers', methods=['GET', 'POST'])
def customers():
    if request.method == 'POST':
        name = request.form['name']
        phone = request.form['phone']
        address = request.form['address']
        new_customer = Customer(name=name, phone=phone, address=address)
        db.session.add(new_customer)
        db.session.commit()
        return redirect(url_for('customers'))

    all_customers = Customer.query.all()
    return render_template('customers.html', customers=all_customers)


@app.route('/products', methods=['GET', 'POST'])
def products():
    if request.method == 'POST':
        name = request.form['name']
        price = request.form['price']
        description = request.form['description']
        new_product = Product(name=name, price=float(price), description=description)
        db.session.add(new_product)
        db.session.commit()
        return redirect(url_for('products'))

    all_products = Product.query.all()
    return render_template('products.html', products=all_products)


@app.route('/invoices')
def invoices():
    all_invoices = Invoice.query.order_by(Invoice.issue_date.desc()).all()
    return render_template('invoices.html', invoices=all_invoices)


@app.route('/invoice/<int:invoice_id>')
def view_invoice(invoice_id):
    invoice = Invoice.query.get_or_404(invoice_id)
    invoice_items = InvoiceItem.query.filter_by(invoice_id=invoice.id).all()
    return render_template('view_invoice.html', invoice=invoice, invoice_items=invoice_items)


@app.route('/invoice/<int:invoice_id>/add_payment', methods=['POST'])
def add_payment(invoice_id):
    invoice = Invoice.query.get_or_404(invoice_id)

    amount = request.form.get('amount')
    payment_date_str = request.form.get('payment_date')
    payment_method = request.form.get('payment_method')
    cheque_details = request.form.get('cheque_details')

    # Create new payment
    new_payment = Payment(
        invoice_id=invoice.id,
        amount=float(amount),
        payment_date=datetime.strptime(payment_date_str, '%Y-%m-%d'),
        payment_method=payment_method,
        cheque_details=cheque_details
    )
    db.session.add(new_payment)

    # Update invoice status
    total_paid = sum(p.amount for p in invoice.payments) + float(amount)
    if total_paid >= invoice.total_amount:
        invoice.status = 'paid'
    else:
        invoice.status = 'partially_paid'

    db.session.commit()

    return redirect(url_for('view_invoice', invoice_id=invoice.id))


@app.route('/invoice/<int:invoice_id>/pdf')
def invoice_pdf(invoice_id):
    invoice = Invoice.query.get_or_404(invoice_id)
    # Eagerly load related items to avoid separate queries in the template
    invoice_items = InvoiceItem.query.filter_by(invoice_id=invoice.id).all()

    html = render_template('invoice_pdf.html', invoice=invoice, items=invoice_items)

    # The 'invoice_pdf.html' template should have a specific font for Persian.
    # If not, WeasyPrint might not render the characters correctly.
    # I have added font-family: 'DejaVu Sans' in the template which is a common fallback.

    return HTML(string=html).write_pdf()


@app.route('/create-invoice', methods=['GET', 'POST'])
def create_invoice():
    if request.method == 'POST':
        customer_id = request.form.get('customer_id')
        issue_date_str = request.form.get('issue_date')
        due_date_str = request.form.get('due_date')

        # Convert date strings to datetime objects
        issue_date = datetime.strptime(issue_date_str, '%Y-%m-%d')
        due_date = datetime.strptime(due_date_str, '%Y-%m-%d') if due_date_str else None

        new_invoice = Invoice(
            customer_id=customer_id,
            issue_date=issue_date,
            due_date=due_date,
            total_amount=0  # Will be calculated later
        )
        db.session.add(new_invoice)
        db.session.flush()  # Flush to get the new_invoice.id

        total_invoice_amount = 0
        items_data = {}
        for key, value in request.form.items():
            if key.startswith('items['):
                parts = key.replace(']', '').split('[')
                index = int(parts[1])
                field = parts[2]
                if index not in items_data:
                    items_data[index] = {}
                items_data[index][field] = value

        for index, item_data in items_data.items():
            product_id = item_data['product_id']
            quantity = int(item_data['quantity'])
            unit_price = float(item_data['unit_price'])

            total_invoice_amount += quantity * unit_price

            invoice_item = InvoiceItem(
                invoice_id=new_invoice.id,
                product_id=product_id,
                quantity=quantity,
                unit_price=unit_price
            )
            db.session.add(invoice_item)

        new_invoice.total_amount = total_invoice_amount
        db.session.commit()

        # For now, redirect to dashboard. Later, maybe to the new invoice's page.
        return redirect(url_for('dashboard'))

    # For GET request
    all_customers = Customer.query.all()
    all_products = Product.query.all()
    return render_template('create_invoice.html', customers=all_customers, products=all_products)

# ایجاد پایگاه داده
with app.app_context():
    db.create_all()

if __name__ == '__main__':
    app.run(debug=True)
