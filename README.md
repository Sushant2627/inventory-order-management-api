# Inventory & Order Management API

A RESTful API for managing products, inventory stock, customers, and customer orders. The application is built using Laravel and MySQL, with transactional order processing and automated feature tests.

## Features

* Product management

  * Create products
  * View all products
  * View a single product
  * Update product details
  * Delete products
  * Add stock

* Customer management

  * Create customers
  * View all customers
  * View a single customer
  * Update customer details
  * Delete customers

* Order management

  * Create orders with one or more products
  * Automatically calculate order totals
  * Automatically deduct product stock
  * Prevent orders when stock is insufficient
  * View all orders
  * View a single order
  * Cancel orders
  * Automatically restore stock when an order is cancelled
  * Prevent an order from being cancelled more than once

* Database transactions

  * Order creation and stock deduction are handled inside a database transaction.
  * Stock updates use row-level locking to help prevent inconsistent inventory during concurrent requests.

* Automated testing

  * Product API tests
  * Order creation tests
  * Insufficient stock tests
  * Order cancellation tests
  * Double-cancellation tests

## Tech Stack

* PHP 8.4
* Laravel 13
* MySQL 8
* REST API
* PHPUnit / Laravel Feature Testing
* Postman
* Git & GitHub

## Requirements

Make sure the following are installed:

* PHP 8.2 or higher
* Composer
* MySQL 8 or compatible version
* Git
* Postman (optional, for API testing)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Sushant2627/inventory-order-management-api.git
cd inventory-order-management-api
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure environment

Copy the example environment file:

```bash
copy .env.example .env
```

For Linux/macOS:

```bash
cp .env.example .env
```

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_api
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

### 4. Create the database

Create a MySQL database named:

```text
inventory_api
```

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Run migrations

```bash
php artisan migrate
```

### 7. Start the development server

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## API Endpoints

Base URL:

```text
http://127.0.0.1:8000/api
```

### Products

| Method    | Endpoint               | Description      |
| --------- | ---------------------- | ---------------- |
| GET       | `/products`            | Get all products |
| POST      | `/products`            | Create a product |
| GET       | `/products/{id}`       | Get a product    |
| PUT/PATCH | `/products/{id}`       | Update a product |
| DELETE    | `/products/{id}`       | Delete a product |
| POST      | `/products/{id}/stock` | Add stock        |

### Customers

| Method    | Endpoint          | Description       |
| --------- | ----------------- | ----------------- |
| GET       | `/customers`      | Get all customers |
| POST      | `/customers`      | Create a customer |
| GET       | `/customers/{id}` | Get a customer    |
| PUT/PATCH | `/customers/{id}` | Update a customer |
| DELETE    | `/customers/{id}` | Delete a customer |

### Orders

| Method | Endpoint              | Description     |
| ------ | --------------------- | --------------- |
| GET    | `/orders`             | Get all orders  |
| POST   | `/orders`             | Create an order |
| GET    | `/orders/{id}`        | Get an order    |
| POST   | `/orders/{id}/cancel` | Cancel an order |

## Request Examples

### Create Product

`POST /api/products`

```json
{
    "name": "Dell Laptop",
    "sku": "LAP-001",
    "price": 52000,
    "stock": 15
}
```

### Add Stock

`POST /api/products/1/stock`

```json
{
    "quantity": 5
}
```

### Create Customer

`POST /api/customers`

```json
{
    "name": "Rahul Sharma",
    "email": "rahul@example.com"
}
```

### Create Order

`POST /api/orders`

```json
{
    "customer_id": 1,
    "products": [
        {
            "product_id": 1,
            "quantity": 2
        },
        {
            "product_id": 2,
            "quantity": 3
        }
    ]
}
```

The API automatically:

1. Validates the customer and products.
2. Checks available stock.
3. Calculates each item's subtotal.
4. Calculates the total order amount.
5. Deducts stock from the products.
6. Creates the order and order items.
7. Returns the created order.

### Cancel Order

`POST /api/orders/1/cancel`

When an order is cancelled:

* The order status changes to `cancelled`.
* The ordered quantities are returned to product stock.
* The operation is performed inside a database transaction.
* A cancelled order cannot be cancelled again.

## Order Processing Logic

Order creation uses a database transaction to ensure that related operations succeed or fail together.

For each product:

```text
Available Stock >= Requested Quantity
        |
        v
Calculate Subtotal
        |
        v
Deduct Stock
        |
        v
Create Order
        |
        v
Create Order Items
```

If any product does not have sufficient stock, the transaction is rolled back and no order is created.

## Database Structure

### Products

Stores product and inventory information.

Main fields:

* `id`
* `name`
* `sku`
* `price`
* `stock`
* `created_at`
* `updated_at`

### Customers

Stores customer information.

Main fields:

* `id`
* `name`
* `email`
* `created_at`
* `updated_at`

### Orders

Stores customer orders.

Main fields:

* `id`
* `customer_id`
* `total_amount`
* `status`
* `created_at`
* `updated_at`

Order status values:

```text
pending
completed
cancelled
```

### Order Items

Stores individual products belonging to an order.

Main fields:

* `id`
* `order_id`
* `product_id`
* `quantity`
* `price`
* `subtotal`
* `created_at`
* `updated_at`

## Relationships

```text
Customer
   |
   | 1:N
   v
Orders
   |
   | 1:N
   v
Order Items
   |
   | N:1
   v
Products
```

* A customer can have many orders.
* An order belongs to one customer.
* An order contains many order items.
* An order item belongs to one product.
* A product can appear in many order items.

## Validation

The API validates incoming requests before modifying the database.

Examples:

* Product name is required.
* SKU is required and unique.
* Product price must be numeric and non-negative.
* Stock must be a non-negative integer.
* Customer email must be valid and unique.
* Order customer must exist.
* Ordered products must exist.
* Order quantity must be at least `1`.

## Error Handling

Example insufficient stock response:

```json
{
    "success": false,
    "message": "Insufficient stock for product: Dell Laptop"
}
```

HTTP status:

```text
422 Unprocessable Entity
```

Example duplicate cancellation response:

```json
{
    "success": false,
    "message": "Order is already cancelled."
}
```

## Testing

The project includes automated Laravel feature tests.

Run the complete test suite:

```bash
php artisan test
```

Current test coverage includes:

* Product creation
* Stock updates
* Order creation
* Automatic stock deduction
* Insufficient stock handling
* Order cancellation
* Stock restoration after cancellation
* Prevention of duplicate order cancellation

The complete test suite currently passes:

```text
8 tests
23 assertions
```

## Manual API Testing with Postman

Start the Laravel server:

```bash
php artisan serve
```

Then use the following base URL in Postman:

```text
http://127.0.0.1:8000/api
```

Recommended testing sequence:

1. Create a product.
2. Create a customer.
3. Create an order.
4. Verify product stock was deducted.
5. Try creating an order with insufficient stock.
6. Verify the order was rejected and stock remained unchanged.
7. Cancel an existing order.
8. Verify the order status changed to `cancelled`.
9. Verify product stock was restored.
10. Try cancelling the same order again.

## Project Structure

```text
inventory-order-management-api/
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── CustomerController.php
│   │           ├── OrderController.php
│   │           └── ProductController.php
│   │
│   └── Models/
│       ├── Customer.php
│       ├── Order.php
│       ├── OrderItem.php
│       └── Product.php
│
├── database/
│   └── migrations/
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── tests/
│   ├── Feature/
│   │   ├── OrderApiTest.php
│   │   └── ProductApiTest.php
│   └── Unit/
│
├── .env.example
├── composer.json
├── phpunit.xml
└── README.md
```

## Notes

* This project is API-only and does not include a frontend.
* Authentication was not required for the assessment specification.
* Product stock is managed as a non-negative integer.
* Product prices are stored as decimal values.
* Order item prices are stored at the time of purchase so historical order data remains consistent if a product price changes later.

## Author

Sushant Sawant
PHP / Laravel Developer | B.Tech in Computer Science & Engineering

Email: sushantsawant2627@gmail.com
LinkedIn: https://www.linkedin.com/in/sushant-sawant27/
