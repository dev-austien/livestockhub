# LivestuChub REST API

PHP REST API backend for the LivestuChub livestock management system.

## Setup

1. Copy the `livestuchub-api/` folder into your web server root (e.g. `htdocs/api/`).
2. Import the SQL schema into your MariaDB/MySQL database.
3. Edit **`config/database.php`** with your DB credentials.
4. Edit **`config/config.php`** — change `JWT_SECRET` to a strong random string.
5. Ensure `mod_rewrite` is enabled in Apache.
6. Make sure `uploads/livestock/` is writable: `chmod 755 uploads/livestock/`

---

## Authentication

All protected routes require a **Bearer token** in the `Authorization` header:

```
Authorization: Bearer <token>
```

Tokens are obtained from `POST /api/auth/login` and expire after **24 hours**.

---

## Endpoints

### Auth
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/login` | ❌ | Login with email + password |
| POST | `/api/auth/register` | ❌ | Register new user |
| GET | `/api/auth/me` | ✅ | Get current user profile |

**Login body:**
```json
{ "email": "user@example.com", "password": "secret" }
```

**Register body:**
```json
{
  "email": "farmer@example.com",
  "password": "secret123",
  "first_name": "Juan",
  "last_name": "dela Cruz",
  "middle_name": "Santos",
  "username": "juan",
  "phone": "09171234567",
  "role": "Farmer",
  "farm_name": "Juan's Farm"
}
```

---

### Users
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/users` | Admin=all, Others=self | List users |
| GET | `/api/users/{id}` | Admin/Self | Get single user |
| PUT | `/api/users/{id}` | Admin/Self | Update user |
| DELETE | `/api/users/{id}` | Admin | Delete user |
| PATCH | `/api/users/{id}/status` | Admin | Update user status |

---

### Farmers
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/farmers` | All | List farmers |
| GET | `/api/farmers/{id}` | All | Get farmer |
| PUT | `/api/farmers/{id}` | Owner/Admin | Update farm name |
| GET | `/api/farmers/{id}/contacts` | All | List contacts |
| POST | `/api/farmers/{id}/contacts` | Owner/Admin | Add contact |
| DELETE | `/api/farmers/{id}/contacts/{contact_id}` | Owner/Admin | Delete contact |

---

### Categories
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/categories` | Public | List categories |
| GET | `/api/categories/{id}` | Public | Get category |
| POST | `/api/categories` | Admin | Create category |
| PUT | `/api/categories/{id}` | Admin | Update category |
| DELETE | `/api/categories/{id}` | Admin | Delete category |

---

### Breeds
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/breeds` | Public | List all breeds |
| GET | `/api/breeds?category_id=1` | Public | Filter by category |
| GET | `/api/breeds/{id}` | Public | Get breed |
| POST | `/api/breeds` | Admin | Create breed |
| PUT | `/api/breeds/{id}` | Admin | Update breed |
| DELETE | `/api/breeds/{id}` | Admin | Delete breed |

---

### Locations
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/locations` | All | List locations |
| GET | `/api/locations/{id}` | All | Get location |
| POST | `/api/locations` | Farmer/Admin | Create location |
| PUT | `/api/locations/{id}` | Owner/Admin | Update location |
| DELETE | `/api/locations/{id}` | Owner/Admin | Delete location |

---

### Livestock
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/livestock` | All | List livestock (filterable) |
| GET | `/api/livestock/{id}` | All | Get livestock detail |
| POST | `/api/livestock` | Farmer/Admin | Add livestock |
| PUT | `/api/livestock/{id}` | Owner/Admin | Update livestock |
| DELETE | `/api/livestock/{id}` | Owner/Admin | Delete livestock |
| GET | `/api/livestock/{id}/weights` | All | Weight history |
| POST | `/api/livestock/{id}/weights` | Owner/Admin | Log new weight |
| DELETE | `/api/livestock/{id}/weights/{weight_id}` | Owner/Admin | Delete weight record |

**Livestock GET filters (query params):**
- `farmer_id`, `category_id`, `breed_id`, `location_id`
- `sale_status` — `Available` / `Reserved` / `Sold`
- `gender` — `Male` / `Female`
- `search` — searches tag_number and description

**POST/PUT livestock body:**
```json
{
  "tag_number": "TAG-001",
  "category_id": 3,
  "breed_id": 9,
  "location_id": 1,
  "gender": "Male",
  "health_status": "Healthy",
  "date_of_birth": "2025-01-15",
  "sale_status": "Available",
  "price": 8500.00,
  "description": "Well-fed fattener",
  "current_weight": 95.5
}
```
Use `multipart/form-data` to include a `livestock_image` file.

---

### Orders
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/orders` | All | List orders (scoped by role) |
| GET | `/api/orders/{id}` | Owner | Get order |
| POST | `/api/orders` | Buyer/Farmer | Place order |
| PATCH | `/api/orders/{id}/status` | Farmer/Admin | Update order status |
| DELETE | `/api/orders/{id}` | Buyer(pending)/Admin | Delete order |

**Place order body:**
```json
{
  "livestock_id": 3,
  "order_type": "Buy",
  "quantity": 1
}
```
`order_type`: `"Buy"` or `"Reserve"` (reserves for 3 days)

**Update status body:**
```json
{ "status": "Confirmed" }
```
Status flow: `Pending → Confirmed → Completed` or `→ Cancelled`

---

### Transactions
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/transactions` | All | List transactions (scoped) |
| GET | `/api/transactions/{id}` | Owner | Get transaction |
| POST | `/api/transactions` | Farmer/Admin | Record payment |
| PUT | `/api/transactions/{id}` | Admin | Update transaction |
| DELETE | `/api/transactions/{id}` | Admin | Delete transaction |

**Record payment body:**
```json
{
  "order_id": 1,
  "payment_method": "GCash",
  "payment_status": "Paid",
  "total_amount": 8500.00
}
```
Payment methods: `Cash`, `Bank Transfer`, `GCash`, `Maya`, `Credit Card`, `Other`

When `payment_status = "Paid"`: order is automatically set to `Completed` and livestock to `Sold`.

---

## Response Format

All responses follow this shape:

```json
{
  "success": true,
  "message": "Success",
  "data": { ... }
}
```

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request / Validation error |
| 401 | Unauthorized (no/invalid token) |
| 403 | Forbidden (insufficient role) |
| 404 | Not Found |
| 409 | Conflict (e.g. duplicate email) |
| 422 | Unprocessable (business logic error) |
| 500 | Server Error |

---

## Role Summary

| Feature | Admin | Farmer | Buyer |
|---------|-------|--------|-------|
| Manage users | ✅ | ❌ | ❌ |
| Manage categories/breeds | ✅ | ❌ | ❌ |
| Add/edit livestock | ✅ | Own only | ❌ |
| View livestock | ✅ | Own only | ✅ |
| Place orders | ❌ | ✅ | ✅ |
| Confirm/complete orders | ✅ | Own livestock | ❌ |
| Cancel orders | ✅ | ✅ | Own pending |
| Record transactions | ✅ | Own livestock | ❌ |
