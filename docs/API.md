# API Documentation

Base URL: `http://localhost:8000/api`

## Authentication

All protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

Tokens are obtained via the login or register endpoints.

---

## Auth Endpoints

### Register

Create a new user account.

```
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201 Created):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2024-01-15T10:30:00.000000Z"
    },
    "token": "1|abc123..."
}
```

**Errors:**
- `422` - Validation error (email taken, password mismatch)

---

### Login

Authenticate and receive an access token.

```
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2024-01-15T10:30:00.000000Z"
    },
    "token": "2|xyz789..."
}
```

**Errors:**
- `422` - Invalid credentials

---

### Logout

Revoke the current access token.

```
POST /api/auth/logout
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "message": "Successfully logged out"
}
```

---

### Forgot Password

Request a password reset link.

```
POST /api/auth/forgot-password
```

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Response (200 OK):**
```json
{
    "message": "Password reset link sent to your email."
}
```

---

### Reset Password

Reset password using token from email.

```
POST /api/auth/reset-password
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "token": "reset-token-from-email",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response (200 OK):**
```json
{
    "message": "Password has been reset successfully."
}
```

---

### Get Current User

Get the authenticated user's profile.

```
GET /api/user
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2024-01-15T10:30:00.000000Z",
        "created_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

---

## Clients

### List Clients

Get all clients for the authenticated user.

```
GET /api/clients
```

**Headers:** Authorization required

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search by name, email, or company |
| `per_page` | integer | Items per page (default: 15) |
| `page` | integer | Page number |

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "John Smith",
            "email": "john@techcorp.com",
            "phone": "+1 555-123-4567",
            "company": "TechCorp",
            "address": "123 Main St",
            "notes": "Preferred client",
            "invoices_count": 5,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ],
    "links": { ... },
    "meta": { ... }
}
```

---

### Create Client

```
POST /api/clients
```

**Headers:** Authorization required

**Request Body:**
```json
{
    "name": "John Smith",
    "email": "john@techcorp.com",
    "phone": "+1 555-123-4567",
    "company": "TechCorp",
    "address": "123 Main St",
    "notes": "Preferred client"
}
```

**Required fields:** `name`

**Response (201 Created):**
```json
{
    "data": {
        "id": 1,
        "name": "John Smith",
        "email": "john@techcorp.com",
        ...
    }
}
```

---

### Get Client

```
GET /api/clients/{id}
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "name": "John Smith",
        ...
    }
}
```

**Errors:**
- `403` - Forbidden (not your client)
- `404` - Not found

---

### Update Client

```
PUT /api/clients/{id}
```

**Headers:** Authorization required

**Request Body:** Same as create (all fields optional)

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "name": "Updated Name",
        ...
    }
}
```

---

### Delete Client

```
DELETE /api/clients/{id}
```

**Headers:** Authorization required

**Response (204 No Content)**

---

## Products

### List Products

```
GET /api/products
```

**Headers:** Authorization required

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search by name or description |
| `per_page` | integer | Items per page (default: 15) |

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Web Development",
            "description": "Custom web development",
            "price": 150.00,
            "unit": "hour",
            "unit_label": "Hour",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ],
    "links": { ... },
    "meta": { ... }
}
```

---

### Create Product

```
POST /api/products
```

**Headers:** Authorization required

**Request Body:**
```json
{
    "name": "Web Development",
    "description": "Custom web development services",
    "price": 150.00,
    "unit": "hour"
}
```

**Required fields:** `name`, `price`, `unit`

**Unit values:** `hour`, `piece`, `service`

**Response (201 Created):**
```json
{
    "data": {
        "id": 1,
        "name": "Web Development",
        ...
    }
}
```

---

### Get Product

```
GET /api/products/{id}
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "name": "Web Development",
        ...
    }
}
```

---

### Update Product

```
PUT /api/products/{id}
```

**Headers:** Authorization required

**Request Body:** Same as create

**Response (200 OK)**

---

### Delete Product

```
DELETE /api/products/{id}
```

**Headers:** Authorization required

**Response (204 No Content)**

---

## Invoices

### List Invoices

```
GET /api/invoices
```

**Headers:** Authorization required

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status (draft, sent, paid, overdue) |
| `client_id` | integer | Filter by client |
| `per_page` | integer | Items per page (default: 15) |

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "invoice_number": "INV-0001",
            "client_id": 1,
            "client": { ... },
            "subtotal": 1500.00,
            "tax_percent": 10.00,
            "discount": 50.00,
            "total": 1600.00,
            "status": "sent",
            "status_label": "Sent",
            "status_color": "blue",
            "due_date": "2024-02-15",
            "notes": "Thank you for your business",
            "items": [ ... ],
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ],
    "links": { ... },
    "meta": { ... }
}
```

---

### Create Invoice

```
POST /api/invoices
```

**Headers:** Authorization required

**Request Body:**
```json
{
    "client_id": 1,
    "tax_percent": 10,
    "discount": 50,
    "due_date": "2024-02-15",
    "notes": "Thank you for your business",
    "items": [
        {
            "product_id": 1,
            "description": "Web Development",
            "quantity": 10,
            "price": 150.00
        },
        {
            "description": "Custom service",
            "quantity": 5,
            "price": 100.00
        }
    ]
}
```

**Required fields:** `client_id`, `items` (at least 1)

**Item required fields:** `description`, `quantity`, `price`

**Note:** `invoice_number` is auto-generated (INV-0001, INV-0002, etc.)

**Response (201 Created):**
```json
{
    "data": {
        "id": 1,
        "invoice_number": "INV-0001",
        "subtotal": 2000.00,
        "tax_percent": 10.00,
        "discount": 50.00,
        "total": 2150.00,
        "status": "draft",
        ...
    }
}
```

---

### Get Invoice

```
GET /api/invoices/{id}
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "invoice_number": "INV-0001",
        "client": {
            "id": 1,
            "name": "John Smith",
            ...
        },
        "items": [
            {
                "id": 1,
                "description": "Web Development",
                "quantity": 10,
                "price": 150.00,
                "total": 1500.00
            }
        ],
        ...
    }
}
```

---

### Update Invoice

```
PUT /api/invoices/{id}
```

**Headers:** Authorization required

**Request Body:** Same as create

**Note:** Updates replace all existing items with new ones

**Response (200 OK)**

---

### Delete Invoice

```
DELETE /api/invoices/{id}
```

**Headers:** Authorization required

**Response (204 No Content)**

---

### Update Invoice Status

```
PATCH /api/invoices/{id}/status
```

**Headers:** Authorization required

**Request Body:**
```json
{
    "status": "paid"
}
```

**Status values:** `draft`, `sent`, `paid`, `overdue`

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "status": "paid",
        "status_label": "Paid",
        "status_color": "green",
        ...
    }
}
```

---

### Get PDF Download URL

Get a signed URL for downloading the invoice PDF.

```
GET /api/invoices/{id}/pdf-url
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "url": "http://localhost:8000/api/invoices/1/pdf/download?signature=abc123..."
}
```

**Note:** The URL is valid for 5 minutes and can be opened directly in a browser.

---

### Send Invoice Email

Send the invoice to the client via email.

```
POST /api/invoices/{id}/send
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "message": "Invoice sent successfully"
}
```

**Note:** This also updates the invoice status to "sent".

---

## Dashboard

### Get Dashboard Statistics

```
GET /api/dashboard
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "stats": {
        "total_invoices": 25,
        "paid_total": 15000.00,
        "unpaid_total": 5000.00,
        "overdue_total": 2000.00,
        "total_clients": 10,
        "total_products": 15,
        "status_counts": {
            "draft": 3,
            "sent": 5,
            "paid": 15,
            "overdue": 2
        }
    },
    "recent_invoices": [
        {
            "id": 25,
            "invoice_number": "INV-0025",
            "client": { ... },
            "total": 1500.00,
            "status": "sent",
            ...
        }
    ]
}
```

---

## Company Settings

### Get Settings

```
GET /api/settings
```

**Headers:** Authorization required

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "company_name": "Acme Corp",
        "logo": "logos/abc123.png",
        "logo_url": "http://localhost:8000/storage/logos/abc123.png",
        "address": "123 Main St",
        "phone": "+1 555-123-4567",
        "email": "billing@acme.com",
        "bank_details": "Bank: First National\nAccount: 1234567890",
        "default_currency": "USD",
        "default_tax_percent": 10.00,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

---

### Update Settings

```
PUT /api/settings
```

**Headers:** Authorization required

**Request Body:**
```json
{
    "company_name": "Acme Corp",
    "address": "123 Main St",
    "phone": "+1 555-123-4567",
    "email": "billing@acme.com",
    "bank_details": "Bank: First National\nAccount: 1234567890",
    "default_currency": "USD",
    "default_tax_percent": 10.00
}
```

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "company_name": "Acme Corp",
        ...
    }
}
```

---

### Upload Logo

```
POST /api/settings/logo
```

**Headers:** Authorization required

**Content-Type:** `multipart/form-data`

**Request Body:**
| Field | Type | Description |
|-------|------|-------------|
| `logo` | file | Image file (jpg, png, gif, svg). Max 2MB |

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "logo": "logos/abc123.png",
        "logo_url": "http://localhost:8000/storage/logos/abc123.png",
        ...
    }
}
```

---

## Error Responses

### Validation Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### Unauthorized (401)

```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
    "message": "This action is unauthorized."
}
```

### Not Found (404)

```json
{
    "message": "No query results for model [App\\Models\\Invoice] 999"
}
```
