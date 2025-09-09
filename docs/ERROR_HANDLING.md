# Error Handling Documentation

## Overview

This document describes the comprehensive error handling system implemented in the Multi-Tenant CRM application.

## Error Handling Architecture

### 1. Custom Exception Classes

#### TenantException
- **Purpose**: Handle tenant-specific errors
- **Status Codes**: 400, 422, 500
- **Error Codes**: TENANT_ERROR, DUPLICATE_SLUG

```php
throw new TenantException('A tenant with this slug already exists', 422, 'DUPLICATE_SLUG');
```

#### DatabaseConnectionException
- **Purpose**: Handle database connection and operation errors
- **Status Codes**: 500
- **Error Codes**: DATABASE_CONNECTION_ERROR

```php
throw new DatabaseConnectionException('Invalid database credentials provided');
```

#### FileUploadException
- **Purpose**: Handle file upload errors
- **Status Codes**: 422
- **Error Codes**: FILE_UPLOAD_ERROR

```php
throw new FileUploadException('File size exceeds 10MB limit');
```

### 2. Exception Handler

The main `Handler` class in `app/Exceptions/Handler.php` handles all exceptions and provides consistent JSON responses for API endpoints.

#### Database Error Handling

| Error Code | Description | HTTP Status | Response |
|------------|-------------|-------------|----------|
| 1045 | Access denied | 500 | Invalid database credentials |
| 1049 | Unknown database | 500 | Database does not exist |
| 2002 | Connection refused | 500 | Cannot connect to database server |
| 1062 | Duplicate entry | 422 | Record already exists |
| 1452 | Foreign key constraint | 422 | Referenced record does not exist |
| 1146 | Table doesn't exist | 500 | Required database table is missing |

### 3. API Error Middleware

The `ApiErrorHandler` middleware provides additional error handling for API routes:

```php
// Applied to all API routes
$middleware->api(prepend: [
    \App\Http\Middleware\ApiErrorHandler::class,
]);
```

### 4. Database Connection Service

The `DatabaseConnectionService` provides methods for testing and managing database connections:

#### Methods:
- `testConnection(array $credentials)`: Test database connection
- `createDatabase(array $credentials)`: Create database if it doesn't exist
- `createDatabaseUser(array $credentials)`: Create database user
- `getConnectionStatus()`: Get current database status

## Error Response Format

All API errors follow a consistent format:

```json
{
  "message": "Human-readable error message",
  "error": "ERROR_CODE",
  "details": "Additional error details (in local environment only)",
  "status_code": 422
}
```

### Example Error Responses

#### Database Connection Error
```json
{
  "message": "Database connection failed",
  "error": "DATABASE_CONNECTION_ERROR",
  "details": "Invalid database credentials provided"
}
```

#### Validation Error
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

#### File Upload Error
```json
{
  "message": "File upload failed",
  "error": "FILE_UPLOAD_ERROR",
  "details": "File size exceeds 10MB limit"
}
```

#### Tenant Error
```json
{
  "message": "A tenant with this slug already exists",
  "error": "DUPLICATE_SLUG",
  "status_code": 422
}
```

## Error Logging

All errors are logged using Laravel's logging system:

```php
Log::error('Failed to create tenant: ' . $e->getMessage());
```

Logs include:
- Error message
- Stack trace
- Request context
- User information (if available)

## Testing Error Scenarios

### Database Connection Errors

1. **Invalid Credentials**:
   ```bash
   curl -X POST http://localhost:8000/api/admin/tenants \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","slug":"test","db_name":"test","db_user":"invalid","db_pass":"invalid"}'
   ```

2. **Database Status Check**:
   ```bash
   curl -X GET http://localhost:8000/api/admin/database/status
   ```

### File Upload Errors

1. **File Too Large**:
   ```bash
   curl -X POST http://localhost:8000/api/tenant/files/upload \
     -F "file=@large_file.pdf" \
     -F "type=document"
   ```

2. **Invalid File Type**:
   ```bash
   curl -X POST http://localhost:8000/api/tenant/files/upload \
     -F "file=@script.exe" \
     -F "type=document"
   ```

### Validation Errors

1. **Missing Required Fields**:
   ```bash
   curl -X POST http://localhost:8000/api/admin/tenants \
     -H "Content-Type: application/json" \
     -d '{"name":"Test"}'
   ```

2. **Invalid Data Format**:
   ```bash
   curl -X POST http://localhost:8000/api/tenant/contacts \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","email":"invalid-email"}'
   ```

## Error Handling Best Practices

### 1. Always Use Try-Catch Blocks
```php
try {
    $tenant = Tenant::create($request->validated());
} catch (QueryException $e) {
    Log::error('Failed to create tenant: ' . $e->getMessage());
    throw new DatabaseConnectionException('Unable to create tenant');
}
```

### 2. Log Errors with Context
```php
Log::error('Database connection test failed', [
    'error' => $e->getMessage(),
    'code' => $e->getCode(),
    'credentials' => $credentials // Don't log passwords in production
]);
```

### 3. Provide Meaningful Error Messages
```php
// Good
throw new DatabaseConnectionException('Invalid database credentials provided');

// Bad
throw new DatabaseConnectionException('Database error');
```

### 4. Handle Specific Error Codes
```php
switch ($e->getCode()) {
    case 1045: // Access denied
        throw new DatabaseConnectionException('Invalid database credentials');
    case 1049: // Unknown database
        throw new DatabaseConnectionException('Database does not exist');
    default:
        throw new DatabaseConnectionException('Database connection failed');
}
```

### 5. Don't Expose Sensitive Information
```php
// In production, don't expose detailed error messages
'details' => app()->environment('local') ? $e->getMessage() : null
```

## Environment-Specific Error Handling

### Local Environment
- Full error details exposed
- Stack traces included
- Detailed logging

### Production Environment
- Generic error messages
- No stack traces
- Minimal sensitive information

## Monitoring and Alerting

### Error Metrics to Monitor:
- Database connection failures
- File upload failures
- Validation errors
- Authentication failures

### Recommended Alerts:
- High error rate (>5% of requests)
- Database connection failures
- File upload failures
- Authentication failures

## Troubleshooting Common Issues

### 1. Database Connection Issues
- Check MySQL server status
- Verify credentials
- Check network connectivity
- Review MySQL error logs

### 2. File Upload Issues
- Check file size limits
- Verify file type restrictions
- Check storage permissions
- Review disk space

### 3. Validation Issues
- Check request data format
- Verify validation rules
- Review required fields
- Check data types

## Conclusion

The error handling system provides:
- Consistent error responses
- Comprehensive logging
- Environment-specific error details
- Custom exception handling
- Database connection management
- File upload validation

This ensures a robust and user-friendly API experience while maintaining security and debugging capabilities.
