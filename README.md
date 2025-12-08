# Simple Campaign Manager

A modern, feature-rich email campaign management system built with Laravel and React, designed to help businesses create, manage, and track email marketing campaigns efficiently.

## Table of Contents
- [Installation](#installation)
- [Technology Stack](#technology-stack)
- [Features](#features)
- [Use Cases](#use-cases)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [API Endpoints](#api-endpoints)
- [Database Schema](#database-schema)
- [Contributing](#contributing)

## Installation

### Prerequisites
- PHP 8.2 or higher
- Node.js 18 or higher
- Composer
- MySQL or Sqlite database
- Redis (recommended for queue processing)

### Step-by-Step Installation

1. **Clone the repository**
   `bash
   git clone https://github.com/AmadulHaque/simple-campaign-manager.git
   cd simple-campaign-manager
   `

2. **Install PHP dependencies**
   `bash
   composer install
   `

3. **Install JavaScript dependencies**
   `bash
   npm install
   `

4. **Environment Configuration**
   `bash
   cp .env.example .env
   php artisan key:generate
   `

5. **Configure your .env file**
   `env
   APP_NAME=SimpleCampaignManager
   APP_ENV=local
   APP_KEY=your_generated_key
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=simple_campaign_manager
   DB_USERNAME=root
   DB_PASSWORD=

   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_mailtrap_username
   MAIL_PASSWORD=your_mailtrap_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="hello@example.com"
   MAIL_FROM_NAME="\"

   QUEUE_CONNECTION=database
   `

6. **Database Setup**
   `bash
   php artisan migrate
   php artisan db:seed
   `

7. **Build frontend assets**
   `bash
   npm run build
   `

8. **Start the development server**
   ```
   php artisan serve
   ```

9. **Process the queue (in a separate terminal)**
   ```
   php artisan queue:work
   ```

## Technology Stack

### Backend
- **PHP 8.2+** - Core programming language
- **Laravel 12** - PHP framework
- **MySQL/PostgreSQL** - Database
- **Redis** - Caching and queue processing
- **Laravel Fortify** - Authentication
- **Laravel Inertia** - Frontend-backend bridge

### Frontend
- **React 19.2** - JavaScript library for building user interfaces
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS 4** - Utility-first CSS framework
- **Radix UI** - Headless UI components
- **Vite** - Build tool and development server
- **Inertia.js** - Modern monolithic SPA framework

### Development Tools
- **Composer** - PHP dependency manager
- **NPM** - JavaScript package manager
- **ESLint** - JavaScript linting
- **Prettier** - Code formatting
- **Pint** - PHP code formatting

## Features

### Core Features
- **Campaign Management**
  - Create, edit, and delete email campaigns
  - Rich text email composer
  - Campaign scheduling
  - Draft, scheduled, and sent campaign states

- **Contact Management**
  - Add, edit, and manage contacts
  - Contact status management (active, inactive)
  - Bulk contact operations
  - Contact segmentation

- **Email Sending**
  - Queue-based email sending
  - SMTP integration
  - Email template system
  - Personalization support

- **Analytics & Reporting**
  - Real-time campaign statistics
  - Delivery tracking (sent, failed, pending)
  - Open and click tracking
  - Success rate calculations

### Advanced Features
- **User Authentication**
  - Laravel Fortify integration
  - Two-factor authentication support
  - User management
  - Role-based access control ready

- **Queue Processing**
  - Asynchronous email sending
  - Job retry mechanisms
  - Failed job handling
  - Performance optimization

- **Modern UI/UX**
  - Responsive design
  - Dark/light theme support
  - Real-time updates
  - Interactive dashboards

## Use Cases

### Business Scenarios

1. **Email Marketing Campaigns**
   - Send promotional emails to customers
   - Newsletter distribution
   - Product announcements
   - Seasonal offers and discounts

2. **Transactional Emails**
   - Order confirmations
   - Password reset emails
   - Account notifications
   - Welcome emails

3. **Customer Engagement**
   - Onboarding sequences
   - Re-engagement campaigns
   - Customer feedback requests
   - Event invitations

4. **Internal Communications**
   - Company announcements
   - Team notifications
   - HR communications
   - Project updates

### Target Users

- **Small to Medium Businesses**
  - Marketing teams
  - Sales departments
  - Customer support teams

- **Agencies**
  - Digital marketing agencies
  - PR agencies
  - Web development agencies

- **Individuals**
  - Freelancers
  - Content creators
  - Small business owners

## Getting Started

### Quick Start

1. **Create a Campaign**
   - Navigate to the campaigns page
   - Click "Create Campaign"
   - Fill in campaign details (name, subject, content)
   - Select recipients from your contact list
   - Save as draft or schedule for sending

2. **Manage Contacts**
   - Go to the contacts section
   - Add individual contacts or import bulk contacts
   - Organize contacts by status (active/inactive)
   - Assign contacts to campaigns

3. **Send Campaigns**
   - From the campaign details page, click "Send Campaign"
   - Monitor sending progress in real-time
   - View delivery statistics and analytics

### Development Workflow

1. **Setup Development Environment**
   `ash
   # Install dependencies
   composer install
   npm install

   # Configure environment
   cp .env.example .env
   php artisan key:generate

   # Run migrations
   php artisan migrate

   # Start development servers
   npm run dev
   php artisan serve
   `

2. **Code Quality**
   `ash
   # Format PHP code
   ./vendor/bin/pint

   # Format and lint JavaScript
   npm run format
   npm run lint

   # Run tests
   php artisan test
   `

**Simple Campaign Manager** - Built with  using Laravel and React
