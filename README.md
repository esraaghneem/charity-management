# Charity Management System

A full-stack charity management system designed to organize and manage charitable activities through a centralized platform.

The system connects **donors, volunteers, beneficiaries, and administrators** while providing tools for managing charitable projects, sub-projects, donation campaigns, volunteer roles, support requests, donations, notifications, and administrative operations.

---

## Overview

The Charity Management System provides a centralized solution for managing the main operations of a charitable organization.

The platform supports different user activities, including:

* Browsing charitable projects and campaigns
* Making donations
* Managing donation carts and donor wallets
* Registering as a donor, volunteer, or beneficiary
* Joining volunteer roles
* Submitting support requests
* Receiving notifications
* Managing charitable projects and campaigns through an admin dashboard
* Monitoring activities through statistics and reports

The backend is implemented as a **Laravel REST API** with authentication and protected administrative routes.

---

## Key Features

### Authentication

* User registration and login
* Secure authentication using Laravel Sanctum
* User logout
* Authenticated profile access
* Separate administrator authentication
* Role-based access to protected administrative operations

### Charitable Projects

* Create charitable projects
* View available projects
* Search projects by name
* Manage project status
* Create and manage sub-projects
* View sub-projects related to a specific project
* Track completed projects and sub-projects

### Donation Campaigns

* Create donation campaigns
* Browse available campaigns
* View campaign details
* Update campaign information
* Activate or deactivate campaigns
* Track completed campaigns
* View campaign statistics

### Donations

* Donate directly to charitable causes
* Donate through the cart
* Manage donor wallet balance
* Recharge wallet
* Check wallet balance
* Manage donation-related operations

### Donation Cart

* Add items to the donation cart
* View cart contents
* Update cart items
* Remove items from the cart
* Complete donations from the cart

### Donor Management

* Donor registration
* Donor profile management
* Donor account deletion
* Search donors
* View donor information through the admin dashboard

### Volunteer Management

* Register as a volunteer
* View available volunteer roles
* Join volunteer roles
* Manage volunteer profile
* Delete volunteer account
* Search volunteers
* Review volunteer requests through the admin dashboard
* Accept or reject volunteer requests

### Beneficiary Management

* Register as a beneficiary
* Update beneficiary profile
* Submit support requests
* Delete support requests
* Delete beneficiary profile
* Review and manage support requests
* Confirm or reject support requests
* View accepted, pending, and rejected requests

### Notifications

* Send notifications to specific users
* Retrieve user notifications
* Retrieve unread notifications
* Mark all notifications as read
* Delete individual notifications
* Clear all notifications
* Update FCM device tokens

### Achievements

* Create achievements
* View all achievements
* View individual achievement details

### Multi-language Support

* Change application language
* Translation endpoint for application content

---

## Admin Dashboard

The system provides a protected administrative area for managing the main operations of the charity.

Administrators can manage:

* Donors
* Volunteers
* Beneficiaries
* Projects
* Sub-projects
* Donation campaigns
* Volunteer roles
* Support requests
* Administrative statistics
* Dashboard data
* System settings

The dashboard also provides search functionality for:

* Projects
* Sub-projects
* Campaigns
* Volunteer roles
* Donors
* Volunteers
* Beneficiaries

---

## Statistics

The system provides administrative statistics for monitoring the organization.

Available statistics include:

* General statistics
* Campaign statistics
* Completed projects
* Completed sub-projects
* Completed campaigns
* Completed volunteer roles
* Top donors
* Pending request counts

---

## Technology Stack

### Backend

* **PHP 8.2+**
* **Laravel 12**
* **Laravel Sanctum**
* **MySQL**
* **RESTful API**

### Supporting Technologies

* Firebase
* Firebase Cloud Messaging (FCM)
* Pusher
* Google Authentication
* Guzzle HTTP Client

### Frontend / Assets

* Vite
* JavaScript
* Axios
* Tailwind CSS

---

## Architecture

The project follows a Laravel-based MVC architecture.

```text
Client
   │
   ▼
REST API
   │
   ▼
Routes
   │
   ▼
Controllers
   │
   ▼
Models
   │
   ▼
Database
```

The application separates the main business areas into dedicated controllers, including:

```text
AuthController
ProjectController
SubProjectController
CampaignController
DonationController
CartController
BeneficiaryController
VolunteerController
DonorController
RoleController
NotificationController
AchievementController
WalletController
AdminAuthController
```

---

## Authentication & Security

The application uses **Laravel Sanctum** to protect authenticated API endpoints.

Protected operations include:

* User logout
* Profile access
* Beneficiary operations
* Volunteer profile operations
* Donor profile operations
* Notifications
* Donation and wallet operations
* Administrative operations

Administrative endpoints are additionally protected using authentication and admin authorization middleware.

---

## API Modules

The API is organized around the main system modules.

### Public API

```text
POST   /register
POST   /login

GET    /projects
POST   /projects
POST   /search

GET    /getC
GET    /getCam/{id}
POST   /campaign

POST   /cart
GET    /cart/{cartId}
POST   /updat
DELETE /remove
```

### User Operations

```text
POST   /register-donor
POST   /register-as-volunteer
POST   /register-as-beneficiary

POST   /support-request
PUT    /update-profile

GET    /profile
POST   /logout
```

### Volunteer Operations

```text
GET    /roles
POST   /roles
GET    /roles/{id}
PUT    /roles/{id}
DELETE /roles/{id}

POST   /reqes/{id}
PUT    /volunteer/profile
DELETE /volunteer/account
```

### Notifications

```text
POST   /update-fcm-token
POST   /notify-user
GET    /notifications
GET    /notifications/unread
POST   /notifications/mark-all-read
DELETE /notifications/clear
DELETE /notifications/{id}
```

### Donor & Wallet Operations

```text
PUT    /donor/profile
DELETE /donor/account

POST   /donate
POST   /wallet/recharge
GET    /wallet/balance
POST   /dontCar/{id}
```

### Admin Operations

Administrative routes are protected and provide management functionality for:

```text
Donors
Volunteers
Beneficiaries
Projects
Sub-projects
Campaigns
Volunteer roles
Support requests
Statistics
Dashboard
Settings
```

---

## Project Structure

```text
charity-management/
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   └── ...
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── public/
│
├── resources/
│
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
│
├── storage/
│
├── tests/
│
├── .env.example
├── artisan
├── composer.json
├── package.json
├── vite.config.js
└── README.md
```

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/esraaghneem/charity-management.git
```

### 2. Navigate to the project

```bash
cd charity-management
```

### 3. Install PHP dependencies

```bash
composer install
```

### 4. Install frontend dependencies

```bash
npm install
```

### 5. Create the environment file

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 6. Generate the application key

```bash
php artisan key:generate
```

### 7. Configure the database

Update the `.env` file with your database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=charity_management
DB_USERNAME=root
DB_PASSWORD=
```

### 8. Run migrations

```bash
php artisan migrate
```

### 9. Create the storage link

```bash
php artisan storage:link
```

### 10. Build frontend assets

```bash
npm run build
```

### 11. Start the Laravel server

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

---

## Development

For frontend asset development:

```bash
npm run dev
```

For the Laravel application:

```bash
php artisan serve
```

---

## Environment Configuration

The application may require configuration for external services such as:

* Firebase
* Firebase Cloud Messaging
* Pusher
* Database connection
* Application URL

These values should be configured through the `.env` file.

Never commit sensitive credentials or environment secrets to the repository.

---

## API Authentication

Authenticated requests use Laravel Sanctum.

After successful login, the client can use the returned authentication token when accessing protected endpoints.

Example:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

---

## Main System Roles

The platform includes different types of users and administrative operations:

```text
User
├── Donor
├── Volunteer
└── Beneficiary

Administrator
└── System Management
```

Each type interacts with the platform according to its available operations.

---

## Project Goals

The main goals of the system are to:

* Centralize charity management
* Simplify donation operations
* Improve donor and volunteer management
* Organize beneficiary support requests
* Provide structured project and campaign management
* Improve communication through notifications
* Provide administrators with centralized monitoring and statistics

---

## Future Improvements

Potential future improvements include:

* Advanced reporting and analytics
* Expanded payment gateway integration
* Improved notification management
* More detailed donor dashboards
* Advanced campaign analytics
* Mobile application integration
* Improved automated testing
* Enhanced role and permission management

---

## Author

**Esraa Ghneem**

Backend Developer

GitHub: [@esraaghneem](https://github.com/esraaghneem)

---

## License

This project is developed for educational and project purposes.
