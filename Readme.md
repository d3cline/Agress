![Agress Logo](logo.png)

# Agress

_A minimalist, API-first e-commerce platform designed to get out of your way, letting you focus on delivering a clean, simple shopping experience._

Agress is an open-source e-commerce platform that strips out the bloat, avoids plugin overload, and emphasizes performance with a single-template setup. Built with **PHP**, **MySQL**, and a **Tailwind/DaisyUI** frontend, Agress delivers a streamlined experience on the front end and powerful, customizable workflows on the back end.

---

## Key Features

- **Simplicity-First Design**: A single template structure without excess.
- **API-Driven Architecture**: REST API for easy frontend integration and custom workflows.
- **Multi-Platform Ready**: Supports static HTML, ActivityPub, Gopher, and more.
- **Direct Payment Gateway Integration**: Plugin-based setup for Stripe, Square, Auth.net, and more.
- **Customizable Cart & Checkout**: Flexible currency, cart totals, and billing configurations.
- **No App Store Dependence**: Everything you need, right out of the box—no reliance on third-party app ecosystems.

---

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/agress.git
   cd agress
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Environment setup**:
   - Configure your `.env` file with database, Signal-cli, and SMTP settings.
   - Add API keys for payment gateways as needed.

4. **Run Migrations**:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Serve the application**:
   ```bash
   php -S localhost:8000 -t public
   ```

---

## Usage Examples

### Add a New Product
```bash
curl -X POST 'http://localhost:8000/product' \
-H 'Authorization: Bearer YOUR_API_TOKEN' \
-F 'name=Standard USD Product' \
-F 'price=19.99' \
-F 'currency=USD' \
-F 'description=Standard USD price' \
-F 'image=@/path/to/image.webp'
```

### Update an Existing Product
```bash
curl -X POST 'http://localhost:8000/product/1' \
-H 'Authorization: Bearer YOUR_API_TOKEN' \
-F '_method=PATCH' \
-F 'name=Updated Product Name' \
-F 'price=24.99' \
-F 'currency=USD' \
-F 'description=Updated description for the product' \
-F 'image=@/path/to/image.webp'
```

### Delete a Product
```bash
curl -X DELETE 'http://localhost:8000/product/2' \
-H 'Authorization: Bearer YOUR_API_TOKEN'
```

---

## Signal-CLI Setup

Agress supports Signal for secure messaging. Start the Signal-CLI daemon:

```bash
./signal-cli -u +YOUR_PHONE_NUMBER daemon --socket
```

To check if a user is registered on Signal:
```bash
echo '{"jsonrpc": "2.0", "method": "getUserStatus", "params": {"recipient": ["+123456789"]}, "id": 1}' | socat - UNIX-CONNECT:/run/user/1000/signal-cli/socket
```

Example response:
```json
{
  "jsonrpc": "2.0",
  "result": [
    {
      "recipient": "+123456789",
      "number": "+123456789",
      "uuid": "46684565-1036-4a57-9fa6-bbed18a4b021",
      "isRegistered": true
    }
  ],
  "id": 1
}
```

---

## Additional Tools and Dependencies

- **Mail Support**: Install these packages for email support:
  ```bash
  composer require illuminate/mail
  composer require swiftmailer/swiftmailer
  composer require guzzlehttp/guzzle
  ```

- **CAPTCHA Integration**: Set up hCaptcha:
  - Register and manage your keys at [hCaptcha Dashboard](https://dashboard.hcaptcha.com/).

---

## Development

To contribute, clone the repository, make your changes, and submit a pull request. Ensure that all code adheres to PSR-12 standards and passes all tests.

### Testing

Run unit tests with:
```bash
php artisan test
```

---

## Roadmap

- **Multi-Language Support**: Enable multi-language options for global shops.
- **Enhanced Analytics**: Integrate lightweight analytics for insights.
- **Discount Codes**: Built-in support for promotions and coupons.

---

## License

Agress is licensed under the MIT License.

---

Let’s build something incredible with **Agress** and redefine e-commerce, together!
