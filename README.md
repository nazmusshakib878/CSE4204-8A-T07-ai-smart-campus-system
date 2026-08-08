# AI Smart Campus System

Academic management and student-success platform for Northern University of Business and Technology, Khulna. The system uses a React frontend, Laravel API, MySQL, role-based authorization, academic monitoring, notices, and optional OpenAI assistance.

## AI features

- Student AI Assistant: Gemini-powered, student-only guidance based on trusted academic context assembled by Laravel.
- AI Academic Risk Analysis: OpenAI-powered, structured decision support for authorized faculty and administrators.

Both integrations follow this secure flow: React -> Laravel authorization -> trusted database context -> AI provider -> backend response validation -> React.

~~~dotenv
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.6-flash
GEMINI_TIMEOUT=30

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_TIMEOUT=30
~~~

Add these values only to "backend/.env" locally or to protected production environment settings. Never commit or expose an API key in frontend code, documentation, screenshots, or environment examples. See the [AI integration guide](documentation/AI_INTEGRATION_GUIDE.md) for architecture, security, and verification details.
## Repository structure

```text
ai-smart-campus-system/
|-- frontend/       React and Vite application
|-- backend/        Laravel API, migrations, tests, and seeders
|-- database/       Backup and recovery documentation
|-- documentation/  API, deployment, ER diagram, Postman, and reports
|-- screenshots/    Reviewed application screenshots
`-- README.md
```

## Quick start

```powershell
cd backend
Copy-Item .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan db:seed
php artisan serve
```

```powershell
cd frontend
npm install
npm run dev
```

Demo password: `Demo@12345`. See [deployment guide](documentation/DEPLOYMENT.md) for production instructions.

## Verification

```powershell
cd backend
php artisan test
php artisan uploads:cleanup --dry-run
```

```powershell
cd frontend
npm test
npm run lint
npm run build
npm run test:e2e
```

## Documentation

- [API reference](documentation/API.md)
- [Deployment guide](documentation/DEPLOYMENT.md)
- [ER diagram](documentation/ER_DIAGRAM.md)
- [Postman collection](documentation/postman/CSE4204-8A-T07_APICollection.postman_collection.json)
- [Screenshot index](screenshots/README.md)
- [Database backup notes](database/README.md)

## Team

| Role | Name | Student ID |
| --- | --- | --- |
| Team Leader / Database | Md. Nazmus Shakib | 11220320852 |
| AI | Samira Akter Mitu | 11220320858 |
| Frontend | Tanvin Sadik Dhrubo | 11220320860 |
| Backend | Khan Waziur Rahman | 11220320861 |

Course: CSE 4204 Mobile Computing Lab, Section 8A.
