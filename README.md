# Multi-Tenant SaaS CRM

A Laravel-based Multi-Tenant SaaS CRM system with custom JWT authentication and dynamic database switching.

## Features

- **Multi-Tenant Architecture**: Each tenant has its own isolated database
- **Custom JWT Authentication**: JWT tokens contain tenant information for dynamic database switching
- **Tenant Management**: Admin APIs for creating, managing, and suspending tenants
- **CRM Functionality**: Contacts, Deals, Activities management
- **Reporting**: Comprehensive reports for deals, contacts, and activities
- **Queue-based Provisioning**: Asynchronous tenant database provisioning

## Architecture

### Database Structure

- **System Database**: Manages tenants and system-wide data
- **Tenant Databases**: Each tenant has its own isolated database with:
  - Users (with roles: admin, manager, user)
  - Contacts
  - Deals (with status: open, won, lost)
  - Activities (calls, meetings, notes, emails)

### Authentication Flow

1. User logs in with `tenant_id`, `email`, and `password`
2. System switches to tenant database
3. User credentials are validated
4. JWT token is issued containing:
   - `sub`: User ID
   - `tenant_id`: Tenant ID
   - `role`: User role
   - `exp`: Expiration time

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 5.7+ or MySQL 8.0+
- Laravel 11

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd multi-tenant-crm
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   Update `.env` file:
   ```env
   # System Database (for tenants management)
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=multi_tenant_system
   DB_USERNAME=root
   DB_PASSWORD=your_password

   # Tenant Database (template - will be dynamically configured)
   TENANT_DB_CONNECTION=mysql
   TENANT_DB_HOST=127.0.0.1
   TENANT_DB_PORT=3306
   TENANT_DB_DATABASE=tenant_template
   TENANT_DB_USERNAME=root
   TENANT_DB_PASSWORD=your_password
   ```

5. **Create MySQL databases**
   ```sql
   CREATE DATABASE multi_tenant_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE tenant_template CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

6. **Run migrations**
   ```bash
   php artisan migrate --path=database/migrations/system
   ```

7. **Seed demo data**
   ```bash
   php artisan tenants:seed-demo
   ```

## Usage

### 1. Create a Tenant

```bash
curl -X POST http://localhost:8000/api/admin/tenants \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ACME Corporation",
    "slug": "acme",
    "db_name": "tenant_acme",
    "db_user": "acme_user",
    "db_pass": "acme_password"
  }'
```

### 2. Provision Tenant Database

```bash
php artisan tenant:provision 1
php artisan queue:work
```

### 3. Login to Tenant

```bash
curl -X POST http://localhost:8000/api/tenant/login \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "email": "admin@tenant.com",
    "password": "password"
  }'
```

### 4. Use Tenant APIs

```bash
# Get contacts
curl -X GET http://localhost:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Create a contact
curl -X POST http://localhost:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "company": "Example Corp"
  }'

# Get deals report
curl -X GET http://localhost:8000/api/tenant/reports/deals \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## API Endpoints

### Admin APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/tenants` | List all tenants |
| POST | `/api/admin/tenants` | Create new tenant |
| GET | `/api/admin/tenants/{id}` | Get tenant details |
| PUT | `/api/admin/tenants/{id}` | Update tenant |
| DELETE | `/api/admin/tenants/{id}` | Delete tenant |
| PATCH | `/api/admin/tenants/{id}/suspend` | Suspend tenant |
| PATCH | `/api/admin/tenants/{id}/activate` | Activate tenant |

### Tenant APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/tenant/login` | Login user |
| GET | `/api/tenant/me` | Get current user info |
| POST | `/api/tenant/logout` | Logout user |
| GET | `/api/tenant/contacts` | List contacts |
| POST | `/api/tenant/contacts` | Create contact |
| GET | `/api/tenant/contacts/{id}` | Get contact |
| PUT | `/api/tenant/contacts/{id}` | Update contact |
| DELETE | `/api/tenant/contacts/{id}` | Delete contact |
| GET | `/api/tenant/deals` | List deals |
| POST | `/api/tenant/deals` | Create deal |
| GET | `/api/tenant/deals/{id}` | Get deal |
| PUT | `/api/tenant/deals/{id}` | Update deal |
| DELETE | `/api/tenant/deals/{id}` | Delete deal |
| PATCH | `/api/tenant/deals/{id}/won` | Mark deal as won |
| PATCH | `/api/tenant/deals/{id}/lost` | Mark deal as lost |
| GET | `/api/tenant/reports/deals` | Get deals report |
| GET | `/api/tenant/reports/contacts` | Get contacts report |
| GET | `/api/tenant/reports/activities` | Get activities report |

## Commands

```bash
# Seed demo tenants
php artisan tenants:seed-demo

# Provision a tenant
php artisan tenant:provision {tenant_id}

# Process queue jobs
php artisan queue:work
```

## Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=TenantTest
```

## Security Features

- **JWT Authentication**: Secure token-based authentication
- **Tenant Isolation**: Complete data isolation between tenants
- **Role-based Access**: User roles (admin, manager, user)
- **Token Validation**: Automatic tenant status checking
- **Suspension Handling**: Immediate access revocation for suspended tenants

## Development

### Project Structure

```
app/
├── Auth/
│   ├── JwtTenantGuard.php
│   └── JwtUserProvider.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   └── TenantController.php
│   │   └── Tenant/
│   │       ├── AuthController.php
│   │       ├── ContactController.php
│   │       ├── DealController.php
│   │       └── ReportController.php
│   └── Middleware/
│       └── EnsureTenantJwtIsValid.php
├── Models/
│   ├── System/
│   │   └── Tenant.php
│   └── Tenant/
│       ├── User.php
│       ├── Contact.php
│       ├── Deal.php
│       └── Activity.php
├── Services/
│   ├── JwtService.php
│   ├── TenantManager.php
│   └── TenantProvisioner.php
└── Jobs/
    └── ProvisionTenant.php
```

### Key Components

- **TenantManager**: Handles dynamic database switching
- **JwtService**: JWT token encoding/decoding
- **TenantProvisioner**: Creates and manages tenant databases
- **EnsureTenantJwtIsValid**: Middleware for JWT validation and tenant checking

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This project is licensed under the MIT License.