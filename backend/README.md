# TaskHive Backend

## Tech Stack

- **Laravel** - PHP web framework
- **PHP** - Server-side programming language
- **MySQL** - Database management system

## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- Docker and Docker Compose (for MySQL database)

### Installation

1. **Start MySQL Database (Docker):**
   ```bash
   # From the project root
   docker-compose up -d
   ```
   
   This starts a MySQL 8.0 container with:
   - Database: `taskhive`
   - Username: `taskhive`
   - Password: `root`
   - Port: `3306`

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure environment:
   - Copy `.env.example` to `.env` (if not already done)
   - Update database credentials in `.env`:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=taskhive
     DB_USERNAME=taskhive
     DB_PASSWORD=root
     ```
   
   **Note:** If using Docker Compose, the database is automatically created.

4. Generate application key (if not already done):
   ```bash
   php artisan key:generate
   ```

### Running the Backend

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Project Structure

This is a Laravel API project configured for building RESTful APIs. The project structure follows Laravel conventions:

- `app/` - Application core code
- `routes/` - API routes
- `database/` - Migrations and seeders
- `config/` - Configuration files
