# AI Smart Campus System

AI Smart Campus System is an academic management and student-success platform for Northern University of Business and Technology Khulna. It combines a React frontend, a Laravel API, MySQL-backed academic data, and AI-assisted campus workflows.

## Repository structure

```text
ai-smart-campus-system/
├── frontend/       React and Vite application
├── backend/        Laravel API application
├── database/       Database notes and portable exports
├── documentation/  Reports, diagrams, source documents, and API collections
├── screenshots/    Application screenshots
└── README.md
```

Laravel migrations remain in `backend/database/migrations`, where Laravel expects them. The root `database` directory is reserved for database-level documentation, SQL exports, and portable schema artifacts.

## Technology stack

- Frontend: React, Vite, Bootstrap, Material UI
- Backend: Laravel 12, Laravel Sanctum
- Database: MySQL
- AI integrations: Gemini API, OpenAI API, or Hugging Face

## Prerequisites

- Node.js and npm
- PHP 8.2 or newer
- Composer
- MySQL, such as the MySQL service included with XAMPP

## Frontend setup

```bash
cd frontend
npm install
npm run dev
```

The Vite development server normally starts at `http://localhost:5173`.

## Backend setup

```bash
cd backend
composer install
```

Create the local environment file:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Set the MySQL connection values in `backend/.env`, then run:

```bash
php artisan migrate
php artisan serve
```

The Laravel development server normally starts at `http://127.0.0.1:8000`.

## Verification

```bash
cd frontend
npm run build
npm run lint
```

```bash
cd backend
php artisan test
```

## Documentation

- `documentation/deliverables` contains submitted weekly reports.
- `documentation/source-files` contains editable/source versions.
- `documentation/diagrams` contains system and database diagrams.
- `documentation/postman` contains API collection material.

## Team

| Role | Name | Student ID |
| --- | --- | --- |
| Team Leader / Database | Md. Nazmus Shakib | 11220320852 |
| AI | Samira Akter Mitu | 11220320858 |
| Frontend | Tanvin Sadik Dhrubo | 11220320860 |
| Backend | Khan Waziur Rahman | 11220320861 |

Course: CSE 4204 (Mobile Computing Lab), Section 8A.
