# Error Handling Documentation

## Overview

This document describes the comprehensive error handling system implemented in the Multi-Tenant Laravel application. The system provides consistent, structured error responses across all API endpoints with proper logging and debugging capabilities.

## Error Response Format

All API errors follow a consistent JSON structure:

```json
{
    "success": false,
    "message": "Human-readable error message",
    "error": "ERROR_CODE",
    "status_code": 400,
    "timestamp": "2024-01-01T12:00:00.000000Z",
    "details": "Additional error details (local environment only)",
    "request_id": "optional-request-id",
    "meta": {
        "version": "1.0.0",
        "environment": "local"
    }
}
```

## Custom Exceptions

### JWT Exceptions

#### JwtException
- **Status Code**: 401
- **Error Codes**: 
  - `JWT_MISSING` - No authentication token provided
  - `JWT_EXPIRED` - Token has expired
  - `JWT_INVALID` - Invalid token format
  - `JWT_INVALID_SIGNATURE` - Invalid token signature
  - `JWT_MISSING_CLAIM` - Missing required claim

### Tenant Exceptions

#### TenantNotFoundException
- **Status Code**: 404
- **Error Code**: `TENANT_NOT_FOUND`
- **Usage**: When a tenant with the specified ID doesn't exist

#### TenantSuspendedException
- **Status Code**: 403
- **Error Code**: `TENANT_SUSPENDED`
- **Usage**: When a tenant exists but is suspended/inactive

#### TenantException
- **Status Code**: 500 (default)
- **Error Code**: `TENANT_ERROR`
- **Usage**: General tenant-related errors

### Database Exceptions

#### DatabaseConnectionException
- **Status Code**: 500
- **Error Code**: `DATABASE_CONNECTION_ERROR`
- **Usage**: When database connection fails

### File Upload Exceptions

#### FileUploadException
- **Status Code**: 422
- **Error Code**: `FILE_UPLOAD_ERROR`
- **Usage**: When file upload operations fail

### Provisioning Exceptions

#### ProvisioningException
- **Status Code**: 500
- **Error Code**: `PROVISIONING_ERROR`
- **Usage**: When tenant provisioning/deprovisioning fails

## Standard HTTP Exceptions

### Validation Errors
- **Status Code**: 422
- **Error Code**: `VALIDATION_ERROR`
- **Response**: Includes detailed validation errors

### Authentication Errors
- **Status Code**: 401
- **Error Code**: `AUTHENTICATION_REQUIRED`

### Rate Limiting
- **Status Code**: 429
- **Error Code**: `RATE_LIMIT_EXCEEDED`

### Not Found
- **Status Code**: 404
- **Error Code**: `RESOURCE_NOT_FOUND`

### Method Not Allowed
- **Status Code**: 405
- **Error Code**: `METHOD_NOT_ALLOWED`

### Access Denied
- **Status Code**: 403
- **Error Code**: `ACCESS_DENIED`

## Database Error Handling

The system handles specific MySQL error codes:

| Error Code | Description | HTTP Status | Error Code |
|------------|-------------|-------------|------------|
| 1045 | Access denied | 500 | `DATABASE_ACCESS_DENIED` |
| 1049 | Unknown database | 500 | `DATABASE_NOT_FOUND` |
| 2002 | Connection refused | 500 | `DATABASE_CONNECTION_REFUSED` |
| 1062 | Duplicate entry | 422 | `DUPLICATE_ENTRY` |
| 1452 | Foreign key constraint | 422 | `FOREIGN_KEY_CONSTRAINT` |
| 1146 | Table doesn't exist | 500 | `TABLE_NOT_FOUND` |

## Error Logging

### Automatic Logging
- All exceptions (except validation and authentication) are automatically logged
- Logs include: message, file, line, and full stack trace
- Logs are written to Laravel's default log files

### Log Levels
- **Error**: System errors, database failures, unexpected exceptions
- **Warning**: Non-critical issues that should be monitored
- **Info**: General application flow information

## Environment-Specific Behavior

### Local Environment
- Full error details are included in responses
- Stack traces are available in logs
- Debug information is exposed

### Production Environment
- Sensitive error details are hidden
- Generic error messages are returned
- Detailed logs are still recorded for debugging

## Usage Examples

### Throwing Custom Exceptions

```php
// In a service class
if (!$tenant) {
    throw new TenantNotFoundException("Tenant with ID {$tenantId} not found");
}

if ($tenant->status !== 'active') {
    throw new TenantSuspendedException("Tenant '{$tenant->name}' is suspended");
}

// In JWT service
if (!$token) {
    throw new JwtException('No authentication token provided', 401, 'JWT_MISSING');
}
```

### Handling Exceptions in Controllers

```php
try {
    $result = $this->service->performOperation();
    return response()->json(['success' => true, 'data' => $result]);
} catch (TenantNotFoundException $e) {
    throw $e; // Re-throw custom exceptions
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'Operation failed',
        'error' => 'OPERATION_FAILED',
        'details' => app()->environment('local') ? $e->getMessage() : null
    ], 500);
}
```

## Error Response Examples

### JWT Authentication Error
```json
{
    "success": false,
    "message": "Token has expired",
    "error": "JWT_EXPIRED",
    "status_code": 401,
    "timestamp": "2024-01-01T12:00:00.000000Z"
}
```

### Tenant Not Found
```json
{
    "success": false,
    "message": "Tenant not found",
    "error": "TENANT_NOT_FOUND",
    "status_code": 404,
    "details": "Tenant with ID 123 not found",
    "timestamp": "2024-01-01T12:00:00.000000Z"
}
```

### Validation Error
```json
{
    "success": false,
    "message": "Validation failed",
    "error": "VALIDATION_ERROR",
    "status_code": 422,
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    },
    "timestamp": "2024-01-01T12:00:00.000000Z"
}
```

### Database Error
```json
{
    "success": false,
    "message": "Database connection failed",
    "error": "DATABASE_CONNECTION_REFUSED",
    "status_code": 500,
    "details": "SQLSTATE[HY000] [2002] Connection refused",
    "timestamp": "2024-01-01T12:00:00.000000Z"
}
```

## Best Practices

1. **Use Specific Exceptions**: Always use the most specific exception type available
2. **Provide Context**: Include relevant context in error messages
3. **Log Appropriately**: Don't log validation errors or authentication failures
4. **Handle Gracefully**: Always provide meaningful error responses to clients
5. **Test Error Scenarios**: Ensure all error paths are tested
6. **Monitor Production**: Set up monitoring for error rates and patterns

## Testing Error Handling

### Unit Tests
```php
public function test_throws_tenant_not_found_exception()
{
    $this->expectException(TenantNotFoundException::class);
    $this->expectExceptionMessage('Tenant with ID 999 not found');
    
    $this->tenantManager->getTenant(999);
}
```

### Feature Tests
```php
public function test_returns_proper_error_for_invalid_tenant()
{
    $response = $this->postJson('/api/tenant/login', [
        'tenant_id' => 999,
        'email' => 'test@example.com',
        'password' => 'password'
    ]);
    
    $response->assertStatus(404)
             ->assertJson([
                 'success' => false,
                 'error' => 'TENANT_NOT_FOUND'
             ]);
}
```

## Monitoring and Alerting

### Recommended Monitoring
- Error rate by endpoint
- Error rate by error type
- Response time for error responses
- Database connection failures
- JWT token validation failures

### Alert Thresholds
- Error rate > 5% for any endpoint
- Database connection failures > 1% of requests
- JWT validation failures > 10% of authenticated requests
- Response time > 2 seconds for error responses
