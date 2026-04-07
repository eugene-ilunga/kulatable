# ApplicationIntegration Module Documentation

## Overview

**ApplicationIntegration** is a unified API layer that exposes POS and customer-facing functionality for external or companion applications (Flutter, mobile, web).

It wraps existing Laravel domain logic into stable, token-based endpoints and provides essential supporting utilities, including:

- Branch scoping and enforcement
- Menus and items
- Orders and payments
- Reservations and table management
- Customers and staff
- Device tokens
- In-app and broadcast notifications

The module integrates seamlessly without requiring changes to the core application.

---

## Module Objectives

- Provide a stable, well-documented API surface for POS and customer applications.
- Enforce restaurant and branch context for all data access.
- Expose the complete ordering lifecycle:
  
  **menus → items / variations / modifiers → carts → orders → payments**

- Support operational workflows:
  - Table management
  - Reservations
  - Order status updates
  - Delivery platforms
  - Staff (waiters / delivery)
  - Taxes and extra charges
  - Order types
  - Customer management

- Enable real-time and in-app notifications using:
  - Pusher / Laravel Broadcast
  - Stored notifications
  - Device token registration

- Keep the integration fully self-contained with no modifications required in the main application.

---

## Value Proposition

- A single API contract for all POS and customer-facing applications.
- Secure multi-branch enforcement ensuring users only access their assigned branch data.
- Faster application development with ready-made endpoints for menus, items, orders, payments, staff, customers, taxes, charges, and reservations.
- Real-time user experience via broadcast notifications, in-app APIs, and push notification support.
- Full compatibility with existing Laravel models and business logic.

---

## Quick Start Guide

### 1. Authentication

**Login Endpoint**
```
POST /api/application-integration/auth/login
```

Returns a **Bearer Token**.

**Required Headers (for all requests):**
```
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

---

### 2. Branch Context

- Tokens are scoped to the user’s restaurant and branch.
- To switch branches:
```
POST /platform/switch-branch
```
(Include the desired branch in the request body.)

---

### 3. Catalog (POS)

**Menus & Categories**
- `GET /pos/menus`
- `GET /pos/categories`

**Items & Modifiers**
- `GET /pos/items`
- `GET /pos/items/category/{id}`
- `GET /pos/items/menu/{id}`
- `GET /pos/items/{id}/variations`
- `GET /pos/items/{id}/modifier-groups`

**Order Configuration**
- `GET /pos/order-types`
- `GET /pos/extra-charges/{orderType}`
- `GET /pos/delivery-platforms`
- `GET /pos/actions`
- `GET /pos/get-order-number`

---

### 4. Tables & Reservations

- List tables:
```
GET /pos/tables
```

- Force unlock a table:
```
POST /pos/tables/{tableId}/unlock
```

- Reservations:
```
GET /pos/reservations/today
GET /pos/reservations
POST /pos/reservations
POST /pos/reservations/{id}/status
```

---

### 5. Orders (POS)

**Create / Submit Order**
```
POST /pos/orders
```
(Include items, order_type, table_id, pax, charges, etc.)

**Retrieve Orders**
```
GET /pos/orders
GET /pos/orders/{id}
```

**Update Status**
```
POST /pos/orders/{id}/status
```

**Payment**
```
POST /pos/orders/{id}/pay
```

Request Body:
```json
{
  "amount": 20,
  "method": "cash|card|wallet|other"
}
```

Notes:
- Does not modify the `order_status` enum.
- Marks the order as paid and frees the table if dine-in.
- Returns `404` if the order does not belong to the active branch.

---

### 6. Customers

- List / Search:
```
GET /pos/customers
```

- Create / Update:
```
POST /pos/customers
```

- Phone country codes:
```
GET /pos/phone-codes
```

Global customer endpoints are also available under `/customer`.

---

### 7. Staff, Waiters & Roles

- List by role:
```
GET /pos/waiters?role=delivery
```

Optional:
```
include_permissions=true
```

- Platform endpoints:
```
GET /platform/roles
GET /platform/staff
GET /platform/permissions
```

---

### 8. Taxes, Charges, Restaurants & Branches

- Taxes:
```
GET /pos/taxes
```

- Extra charges:
```
GET /pos/extra-charges/{orderType}
```

- Restaurants:
```
GET /pos/restaurants
```

- Branches:
```
GET /pos/branches
```

---

## Typical POS Payment Flow

1. Create order via `POST /pos/orders`.
2. Collect payment externally (cash, card, or gateway).
3. Confirm payment using `POST /pos/orders/{id}/pay`.
4. Refresh orders and tables after success.

---

## Notifications & Push

### Register Device Token
```
POST /pos/notifications/register-token
```

```json
{
  "token": "<fcm_or_apns>",
  "platform": "ios|android|web",
  "device_id": "optional"
}
```

### In-App Notifications

- List:
```
GET /pos/notifications
```

- Mark as read:
```
POST /pos/notifications/{id}/read
```

- Send test (debug):
```
POST /pos/notifications/test
```

### Broadcast (Pusher / Laravel Echo)

- Channel:
```
private-App.Models.User.{userId}
```

- Event:
```
BroadcastNotificationCreated
```

Payload includes `title`, `body`, and `data`.

---

## Pusher Configuration & Status

### 📌 Important
Pusher endpoints are **system-wide** and accessible to **ALL authenticated users** (superadmin, admin, staff). These settings are configured once by superadmin and shared across the entire system. No POS module requirement.

### Get All Pusher Settings
```
GET /api/application-integration/pusher/settings
```

Returns complete Pusher configuration including both broadcast and beams settings. **Accessible to all authenticated users.**

**Response:**
```json
{
  "data": {
    "id": 1,
    "beamer_status": true,
    "instance_id": "instance_123",
    "beam_secret": "secret_123",
    "pusher_broadcast": true,
    "pusher_app_id": "app_id_123",
    "pusher_key": "key_123",
    "pusher_secret": "secret_123",
    "pusher_cluster": "mt1",
    "is_enabled_pusher_broadcast": true
  }
}
```

---

### Get Pusher Broadcast Settings
```
GET /api/application-integration/pusher/broadcast-settings
```

Returns settings for real-time page updates (Laravel Broadcasting).

**Response:**
```json
{
  "data": {
    "pusher_broadcast": true,
    "pusher_app_id": "app_id_123",
    "pusher_key": "key_123",
    "pusher_cluster": "mt1",
    "is_enabled": true
  }
}
```

---

### Get Pusher Beams Settings
```
GET /api/application-integration/pusher/beams-settings
```

Returns settings for browser push notifications.

**Response:**
```json
{
  "data": {
    "beamer_status": true,
    "instance_id": "instance_123",
    "beam_secret": "secret_123",
    "is_enabled": true
  }
}
```

---

### Check Pusher Status
```
GET /api/application-integration/pusher/status
```

Quick check to see if Pusher broadcast and/or Beams are enabled.

**Response:**
```json
{
  "pusher_broadcast_enabled": true,
  "pusher_beams_enabled": true,
  "any_pusher_enabled": true
}
```

---

### Authorize Pusher Channel
```
POST /api/application-integration/pusher/authorize-channel
```

Authorize a private or presence channel for the authenticated user.

**Request Body:**
```json
{
  "channel_name": "orders.1",
  "socket_id": "socket_123.456"
}
```

**Response:**
```json
{
  "auth": "auth_signature_here"
}
```

---

### Get Presence Channel Members
```
GET /api/application-integration/pusher/presence/{channel}/members
```

Get list of users currently connected to a presence channel.

**Example:**
```
GET /api/application-integration/pusher/presence/orders.presence.1/members
```

**Response:**
```json
{
  "members": [
    {
      "id": "user_1",
      "info": {
        "name": "John Doe"
      }
    },
    {
      "id": "user_2",
      "info": {
        "name": "Jane Smith"
      }
    }
  ],
  "count": 2
}
```

---

### Pusher Configuration Notes

**Broadcast Channels:**
- Use for real-time page updates
- Requires: `pusher_app_id`, `pusher_key`, `pusher_secret`, `pusher_cluster`
- Channel format: `private-App.Models.User.{userId}`, `orders.{orderId}`, etc.
- Event types: `BroadcastNotificationCreated`, `OrderStatusUpdated`, etc.

**Beams (Push Notifications):**
- Use for browser push notifications
- Requires: `instance_id`, `beam_secret`
- Works with device tokens registered via `/notifications/register-token`

**Channel Authorization:**
- Private channels require authentication
- Presence channels track connected users
- Use `/pusher/authorize-channel` to get auth signature

---

## Device Token Storage

- Table name: `ai_device_tokens`
- Fields:
  - `user_id`
  - `restaurant_id`
  - `branch_id`
  - `platform`
  - `device_id`
  - `token`

---

## Error Handling

- Always include:
```
Accept: application/json
```

- `401` – Missing or invalid token
- `403 / 404` – Branch scoping violation or resource not found
- `500` – Unexpected server error

Payments must always be handled via the `/pay` endpoint.

---

## Testing Examples

### Login
```bash
curl -X POST "https://yourdomain.com/api/application-integration/auth/login" \
-H "Content-Type: application/json" \
-d '{"email":"<user>","password":"<password>"}'
```

### Pay Order
```bash
curl -X POST "https://yourdomain.com/api/application-integration/pos/orders/62/pay" \
-H "Authorization: Bearer <token>" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-d '{"amount":20,"method":"cash"}'
```

### Register Device Token
```bash
curl -X POST "https://yourdomain.com/api/application-integration/pos/notifications/register-token" \
-H "Authorization: Bearer <token>" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-d '{"token":"<fcm>","platform":"android","device_id":"device-123"}'
```

### Get Pusher Status
```bash
curl -X GET "https://yourdomain.com/api/application-integration/pusher/status" \
-H "Authorization: Bearer <token>" \
-H "Accept: application/json"
```

### Get Pusher Broadcast Settings
```bash
curl -X GET "https://yourdomain.com/api/application-integration/pusher/broadcast-settings" \
-H "Authorization: Bearer <token>" \
-H "Accept: application/json"
```

### Authorize Pusher Channel
```bash
curl -X POST "https://yourdomain.com/api/application-integration/pusher/authorize-channel" \
-H "Authorization: Bearer <token>" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-d '{"channel_name":"orders.1","socket_id":"socket_123.456"}'
```

### Get Presence Channel Members
```bash
curl -X GET "https://yourdomain.com/api/application-integration/pusher/presence/orders.presence.1/members" \
-H "Authorization: Bearer <token>" \
-H "Accept: application/json"
```

---

## Deployment Notes

- Run module migrations to create the `ai_device_tokens` table.
- No core application changes are required.
- Clear caches if needed:
```
php artisan optimize:clear
```

---

**This document is the official reference for integrating the ApplicationIntegration module into any POS or customer-facing application.**

