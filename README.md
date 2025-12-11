# 🔌 Python API Demo – WordPress Plugin (Full OOP Integration)

A fully object-oriented WordPress plugin that connects to an external Python FastAPI backend, fetches product data, displays it using a shortcode, and allows adding new products through the WordPress admin.

This plugin is meant as a clean, well-structured example of integrating WordPress with a remote API using modern coding standards.

## 📦 Features

✔ **Fetch all products (GET /products)**
- Displays all available products from the Python API.

✔ **Fetch single product by ID (GET /product/{id})**
- You can fetch and display one specific product using: `[python_product id="10"]`

✔ **Add new products (POST /product/add)**
- Admin-side form allows adding new products to the API.

✔ **Shortcodes included**
- Display all products: `[python_products]`
- Limit products: `[python_products limit="5"]`
- Display a single product: `[python_product id="7"]`

✔ **Admin settings page**
- Located at: Settings → Python API Demo
- Allows configuration of:
  - API Base URL
  - API Key
  - Enable/Disable caching
  - Cache duration
  - Add product form (POST request)

✔ **Optional caching (WordPress transients)**
- Reduces API calls
- Cached for X seconds (configurable)
- Cache automatically clears on new product POST

✔ **Full OOP architecture**
- The plugin uses a loader class and clean separation of concerns.

## 📁 Folder Structure

```
python-api-demo/
├── python-api-demo.php              # Main plugin loader
├── includes/
│   ├── class-plugin-loader.php      # Initializes plugin classes
│   ├── class-api-client.php         # Performs GET/POST requests to Python API
│   ├── class-admin-page.php         # Settings page (API URL, token, caching)
│   ├── class-products-shortcode.php # Shortcode: all products + limit
│   ├── class-product-shortcode.php  # Shortcode: single product by ID
│   ├── class-product-form.php       # Add product via POST to API
│   └── helpers.php                  # Sanitization, utility helpers
├── assets/
│   └── admin.css
└── README.md
```

## 🛠 Installation

1. Download the plugin or clone the repository.

2. Upload to your WordPress installation under: `wp-content/plugins/`

3. Activate the plugin.

4. Open: **Settings → Python API Demo**

5. Configure:
   - API Base URL (e.g `http://127.0.0.1:8000/`)
   - API Key
   - Cache settings

6. Add one of the shortcodes to your page/post.

## 🧩 Shortcodes

### 1️⃣ Display ALL products

```
[python_products]
```

With limit:
```
[python_products limit="3"]
```

### 2️⃣ Display a SINGLE product

```
[python_product id="10"]
```

If product does not exist → shows a styled "Product not found" message.

## 🔗 API Communication

All API requests use:

**Headers:**
```
X-API-KEY: YOUR_API_KEY
Content-Type: application/json
```

### 📥 GET all products

```
GET {API_URL}/products
```

PHP example (inside `class-api-client.php`):

```php
$response = wp_remote_get(
    trailingslashit( $base_url ) . 'products',
    [ 'headers' => $this->get_headers() ]
);
```

### 📥 GET single product

```
GET {API_URL}/product/{id}
```

Shortcode example:

```
[python_product id="5"]
```

### 📤 POST add product

Endpoint:

```
POST {API_URL}/product/add
```

Sent JSON structure:

```json
{
    "name": "Keyboard Pro",
    "price": 89.99,
    "image": "https://example.com/kb.jpg",
    "stock": 10
}
```

PHP example:

```php
$response = wp_remote_post(
    trailingslashit( $base_url ) . 'product/add',
    [
        'headers' => $this->get_headers(),
        'body'    => json_encode( $data ),
    ]
);
```

## 🧱 Admin Settings Page

Located in: **⚙ Settings → Python API Demo**

It contains:

✔ **API Base URL**

✔ **API Key**

✔ **Caching Options**
- enable / disable
- cache lifetime (seconds)

✔ **Add New Product Form**

Fields:
- Name
- Price
- Image URL
- In Stock (1/0)

The form sends POST request directly to Python API and clears cache.

## ⚙ Internal Plugin Architecture

### `class-plugin-loader.php`
- Initializes all components in correct order.

### `class-api-client.php`
- Handles communication with Python API:
  - GET all products
  - GET single product
  - POST new product
  - Caching layer
  - Headers & error handling

### `class-products-shortcode.php`
- Renders list of products via shortcode.

### `class-product-shortcode.php`
- Renders a single product by ID.

### `class-admin-page.php`
- Creates settings page and stores options.

### `class-product-form.php`
- Handles:
  - form UI
  - nonce validation
  - POST request wrapper
  - success/error messages

## 🧹 Security

The plugin uses strict WP security standards:

✔ `wp_nonce_field()`

✔ `sanitize_text_field()`

✔ `sanitize_url()`

✔ `esc_html()` and `esc_attr()` when printing

✔ API key stored in WordPress options

✔ No direct file access

## 🪫 Troubleshooting

### ❌ "No products available"

- Wrong API URL
- API is down
- API key missing
- Cache contains stale results

### ❌ "Invalid API Key"

- Token mismatch
- Missing header

### ❌ "Product not found"

- Incorrect ID
- Product removed

### ❌ API error: Array

Happens when:
- Python server returns non-JSON
- App crashed or returns HTML error
- Port conflict (already in use)

## 🤝 Contributing

Pull requests are welcome. Follow:

- WordPress Coding Standards
- OOP architecture
- Proper escaping & sanitization

## 📜 License

MIT License. You are free to use, modify and distribute.
