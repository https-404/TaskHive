# TaskHive

TaskHive is a full-stack task and project management application built with modern web technologies.

## Project Overview

TaskHive provides a comprehensive solution for managing tasks and projects, featuring a clean separation between backend API and frontend SPA.

## Folder Structure

```
TaskHive/
├── backend/    # Laravel API backend
└── frontend/   # Vue 3 SPA frontend
```

### Backend (`/backend`)

The backend is a Laravel API project that handles:
- RESTful API endpoints
- Database operations
- Business logic
- Authentication and authorization

**Tech Stack:** Laravel, PHP, MySQL

### Frontend (`/frontend`)

The frontend is a Vue 3 Single Page Application that provides:
- User interface
- Client-side routing
- State management
- API integration

**Tech Stack:** Vue 3, Vite, Tailwind CSS, Vuex, Axios, Vue Router

## Getting Started

### Prerequisites

**Backend:**
- PHP >= 8.2
- Composer
- Docker and Docker Compose (for MySQL database)

**Frontend:**
- Node.js >= 16
- npm or yarn

### Setup Instructions

#### 0. Database Setup (Docker)

Start the MySQL database using Docker Compose:

```bash
docker-compose up -d
```

This will start a MySQL 8.0 container with the following configuration:
- Database: `taskhive`
- Username: `taskhive`
- Password: `root`
- Root Password: `root`
- Port: `3306`

The database will be available at `localhost:3306`

#### 1. Backend Setup

```bash
cd backend
composer install
```

Configure your `.env` file with MySQL database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskhive
DB_USERNAME=taskhive
DB_PASSWORD=root
```

**Note:** If using Docker Compose for MySQL, the database is automatically created. No manual database creation needed.

Start the backend server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

#### 2. Frontend Setup

```bash
cd frontend
npm install
```

Start the development server:
```bash
npm run dev
```

The frontend will be available at `http://localhost:5173` (or the next available port)

## Development

### Running All Services

You'll need to run the database, backend, and frontend:

**Terminal 1 - Database (if not already running):**
```bash
docker-compose up -d
```

**Terminal 2 - Backend:**
```bash
cd backend
php artisan serve
```

**Terminal 3 - Frontend:**
```bash
cd frontend
npm run dev
```

### Docker Commands

- Start MySQL: `docker-compose up -d`
- Stop MySQL: `docker-compose down`
- View logs: `docker-compose logs -f mysql`
- Restart MySQL: `docker-compose restart mysql`

## Project Status

This is a ready-to-code repository with all dependencies installed and folder structures in place. You can start building features immediately!

## Documentation

- [Backend README](./backend/README.md) - Backend-specific documentation
- [Frontend README](./frontend/README.md) - Frontend-specific documentation

