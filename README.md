# OLX Plus

OLX Plus is a PHP and MySQL classifieds marketplace focused on local listings and safer buyer-seller interactions. Users can browse and filter ads, contact sellers, save listings, and manage ads from an account dashboard. The project also includes moderation tools and an admin area.

## Features

- Account registration, login, password reset, and profile management
- Classified listings with categories, photos, pricing, condition, and location
- Search and filters for category, location, price, condition, and delivery type
- Buyer-seller messaging, saved listings, and notifications
- Mutual deal receipts and transaction reviews
- Admin tools for listings, users, reports, and marketplace settings
- English and Urdu language support
- Optional Groq-powered chatbot

## Requirements

- PHP 7.4 or newer
- MySQL or MariaDB, with a version that supports the SQL used in the migration files
- PHP PDO MySQL extension
- PHP cURL extension for the chatbot and other external API features
- Apache, Nginx, or PHP's built-in development server

## Local Setup

1. Clone the repository and open its project directory.
2. Create a MySQL database and import `database/schema.sql`. The included setup script can create the default `olx_marketplace` database and load the base schema:

   ```powershell
   php setup_db.php
   ```

   The script currently uses the local MySQL defaults `localhost`, user `root`, and an empty password. Update the connection values in `setup_db.php` and `includes/db.php` if your local database differs.

3. Apply the migrations in order using a database client such as phpMyAdmin:

   ```text
   database/migration_v2_part1.sql
   database/migration_v2_part2.sql
   database/migration_v3_locations.sql
   ```

   These migrations add marketplace fields, transaction and review tables, and seeded country/city data used by the app.

4. Start the PHP development server from the project root:

   ```powershell
   php -S 127.0.0.1:8000
   ```

5. Visit <http://127.0.0.1:8000>.

For Apache or Nginx, configure the project root as the document root and ensure PHP requests are handled by your PHP runtime.

## Optional Chatbot

The chatbot uses the Groq API. Set `GROQ_API_KEY` in the PHP process environment; the endpoint returns a configuration error when the variable is missing.

For a temporary PowerShell development session:

```powershell
$env:GROQ_API_KEY = "your-groq-api-key"
php -S 127.0.0.1:8000
```

Do not commit API keys. If a key has previously been committed or shared, revoke it with its provider and create a replacement.

## Project Structure

```text
admin/       Admin pages and admin authentication
ajax/        JSON endpoints and asynchronous actions
assets/      Stylesheets and runtime-uploaded media
database/    Base schema and SQL migrations
includes/    Database, authentication, security, language, and layout helpers
*.php        Public pages and application entry points
```

## Notes

- User-uploaded files are stored under `assets/uploads/` at runtime; back up this directory if you need to preserve listing images and avatars.
- Password reset delivery and AI integrations may require additional service configuration for your deployment.
- This project is a classifieds marketplace; buyers and sellers should independently verify listings and agree on safe transaction arrangements.
