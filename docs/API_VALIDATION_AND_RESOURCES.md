# API Validation and Resources Documentation

## Overview

This document describes the validation rules and JSON resource formatting implemented in the Multi-Tenant CRM system.

## Form Request Validation

### Admin APIs

#### CreateTenantRequest
- **name**: required, string, max 255 characters
- **slug**: required, string, max 255 characters, unique, regex: `^[a-z0-9-]+$`
- **db_name**: required, string, max 255 characters, regex: `^[a-zA-Z0-9_]+$`
- **db_user**: required, string, max 255 characters, regex: `^[a-zA-Z0-9_]+$`
- **db_pass**: required, string, min 8 characters, max 255 characters

#### UpdateTenantRequest
- **name**: sometimes, string, max 255 characters
- **slug**: sometimes, string, max 255 characters, unique (except current), regex: `^[a-z0-9-]+$`
- **db_name**: sometimes, string, max 255 characters, regex: `^[a-zA-Z0-9_]+$`
- **db_user**: sometimes, string, max 255 characters, regex: `^[a-zA-Z0-9_]+$`
- **db_pass**: sometimes, string, min 8 characters, max 255 characters
- **status**: sometimes, enum: active, suspended

### Tenant APIs

#### LoginRequest
- **tenant_id**: required, integer, min 1
- **email**: required, email, max 255 characters
- **password**: required, string, min 6 characters

#### CreateContactRequest
- **name**: required, string, max 255 characters
- **email**: nullable, email, max 255 characters
- **phone**: nullable, string, max 20 characters
- **company**: nullable, string, max 255 characters
- **notes**: nullable, string, max 1000 characters

#### UpdateContactRequest
- **name**: sometimes, string, max 255 characters
- **email**: nullable, email, max 255 characters
- **phone**: nullable, string, max 20 characters
- **company**: nullable, string, max 255 characters
- **notes**: nullable, string, max 1000 characters

#### CreateDealRequest
- **title**: required, string, max 255 characters
- **amount**: required, numeric, min 0
- **contact_id**: required, exists in contacts table
- **assigned_to**: required, exists in users table
- **description**: nullable, string, max 1000 characters
- **status**: sometimes, enum: open, won, lost

#### UpdateDealRequest
- **title**: sometimes, string, max 255 characters
- **amount**: sometimes, numeric, min 0
- **contact_id**: sometimes, exists in contacts table
- **assigned_to**: sometimes, exists in users table
- **description**: nullable, string, max 1000 characters
- **status**: sometimes, enum: open, won, lost

#### FileUploadRequest
- **file**: required, file, max 10MB, mimes: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, csv
- **type**: sometimes, string, enum: avatar, document, attachment
- **description**: nullable, string, max 500 characters

## API Resources

### Admin Resources

#### TenantResource
```json
{
  "id": 1,
  "name": "ACME Corporation",
  "slug": "acme",
  "status": "active",
  "db_name": "tenant_acme",
  "db_user": "acme_user",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z",
  "provisioned_at": "2024-01-01T00:00:00.000000Z"
}
```

### Tenant Resources

#### UserResource
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "admin",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### ContactResource
```json
{
  "id": 1,
  "name": "Jane Smith",
  "email": "jane@example.com",
  "phone": "+1234567890",
  "company": "Example Corp",
  "notes": "Potential customer",
  "created_by": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin"
  },
  "deals_count": 2,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### DealResource
```json
{
  "id": 1,
  "title": "Website Development",
  "amount": 5000.00,
  "status": "open",
  "description": "Custom website development project",
  "contact": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com"
  },
  "assigned_user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin"
  },
  "activities": [
    {
      "id": 1,
      "type": "call",
      "subject": "Initial consultation",
      "description": "Discussed project requirements"
    }
  ],
  "closed_at": null,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### ActivityResource
```json
{
  "id": 1,
  "type": "call",
  "subject": "Initial consultation call",
  "description": "Discussed project requirements",
  "happened_at": "2024-01-01T00:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin"
  },
  "contact": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com"
  },
  "deal": {
    "id": 1,
    "title": "Website Development",
    "amount": 5000.00
  },
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### ReportResource
```json
{
  "summary": {
    "total_deals": 10,
    "open_deals": 5,
    "won_deals": 3,
    "lost_deals": 2,
    "total_amount": 50000.00,
    "won_amount": 30000.00,
    "open_amount": 15000.00,
    "lost_amount": 5000.00
  },
  "by_status": [
    {
      "status": "open",
      "count": 5,
      "total_amount": 15000.00
    }
  ],
  "by_month": [
    {
      "month": "2024-01",
      "count": 3,
      "total_amount": 10000.00
    }
  ],
  "recent_contacts": [
    {
      "id": 1,
      "name": "Jane Smith",
      "email": "jane@example.com"
    }
  ]
}
```

## File Upload API

### Upload File
**POST** `/api/tenant/files/upload`

**Request:**
- Content-Type: `multipart/form-data`
- **file**: File to upload (required)
- **type**: File type - avatar, document, attachment (optional)
- **description**: File description (optional)

**Response:**
```json
{
  "data": {
    "id": "uuid",
    "filename": "document.pdf",
    "path": "documents/uuid.pdf",
    "url": "http://localhost:8000/storage/documents/uuid.pdf",
    "type": "document",
    "size": 1024000,
    "mime_type": "application/pdf",
    "description": "Project proposal",
    "uploaded_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "File uploaded successfully"
}
```

### List Files
**GET** `/api/tenant/files`

### Get File Info
**GET** `/api/tenant/files/{fileId}`

### Delete File
**DELETE** `/api/tenant/files/{fileId}`

## Error Responses

### Validation Errors
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

### File Upload Errors
```json
{
  "message": "Validation failed",
  "errors": {
    "file": ["The file field is required."],
    "file.mimes": ["The file must be a file of type: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, csv."]
  }
}
```

## Benefits

1. **Consistent Validation**: All API endpoints use standardized validation rules
2. **Structured Responses**: JSON responses are consistently formatted using resources
3. **File Security**: File uploads are validated for type, size, and content
4. **Error Handling**: Clear error messages for validation failures
5. **Type Safety**: Strong typing for all request parameters
6. **Documentation**: Self-documenting API with clear validation rules
