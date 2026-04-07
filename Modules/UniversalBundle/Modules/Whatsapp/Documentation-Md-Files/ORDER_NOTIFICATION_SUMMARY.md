# WhatsApp Order Notification Summary

## ✅ System Status: READY

All notification templates are configured and enabled. System is ready for testing.

---

## 📋 Notification Flow by Order Type

### 🍽️ **DINE-IN Orders**

**Event**: `NewOrderCreated` → `SendOrderConfirmationListener`

| Recipient | Template | When Sent | Condition |
|-----------|----------|-----------|-----------|
| **Customer** | `order_notification` | ✅ Immediately | Customer has phone number |
| **Admin** | `new_order_alert` | ✅ Immediately | All admin users with phone numbers |
| **Waiter** | `new_order_alert` | ✅ Immediately | Waiter assigned + has phone number |
| **Kitchen** | `kitchen_notification` | ✅ After 3s delay | KOT created (via `KotUpdated` event) |

**Total Notifications**: 4 (Customer + Admin + Waiter + Kitchen)

---

### 🚚 **DELIVERY Orders**

**Event**: `NewOrderCreated` → `SendOrderConfirmationListener`

| Recipient | Template | When Sent | Condition |
|-----------|----------|-----------|-----------|
| **Customer** | `order_notification` | ✅ Immediately | Customer has phone number |
| **Admin** | `new_order_alert` | ✅ Immediately | All admin users with phone numbers |
| **Delivery Executive** | `new_order_alert` | ✅ Immediately | Executive assigned + has phone number |
| **Kitchen** | `kitchen_notification` | ✅ After 3s delay | KOT created (via `KotUpdated` event) |

**Total Notifications**: 4 (Customer + Admin + Delivery + Kitchen)

---

### 📦 **PICKUP Orders**

**Event**: `NewOrderCreated` → `SendOrderConfirmationListener`

| Recipient | Template | When Sent | Condition |
|-----------|----------|-----------|-----------|
| **Customer** | `order_notification` | ✅ Immediately | Customer has phone number |
| **Admin** | `new_order_alert` | ✅ Immediately | All admin users with phone numbers |
| **Kitchen** | `kitchen_notification` | ✅ After 3s delay | KOT created (via `KotUpdated` event) |

**Total Notifications**: 3 (Customer + Admin + Kitchen)

---

## 🔧 Template Details

### 1. `order_notification` (Customer)
- **Consolidated Template**: `order_notification`
- **Variables**:
  - `{{1}}` - Customer name
  - `{{2}}` - Order number
  - `{{3}}` - Order type (Dine-in/Delivery/Pickup)
  - `{{4}}` - Total amount (with GST/taxes)
  - `{{5}}` - Estimated time
  - `{{6}}` - Restaurant name
  - `{{7}}` - Contact number

### 2. `new_order_alert` (Admin/Waiter/Delivery)
- **Consolidated Template**: `new_order_alert`
- **Variables**:
  - `{{1}}` - Recipient name
  - `{{2}}` - Message context ("New")
  - `{{3}}` - Order number
  - `{{4}}` - Order type
  - `{{5}}` - Customer name
  - `{{6}}` - Amount (with GST/taxes)

### 3. `kitchen_notification` (Kitchen Staff)
- **Consolidated Template**: `kitchen_notification`
- **Triggered by**: `KotUpdated` event → `SendKotNotificationListener` → `SendKotNotificationJob` (3s delay)
- **Variables**: KOT number, items list with modifiers in format: `Item Name xQty (Modifier1, Modifier2)`

---

## ✅ Bug Fixes Applied

### Fixed Waiter Notification Bug
- **Issue**: Waiter notification was checked but never sent
- **Fixed**: Added notification service call in `SendOrderConfirmationListener.php` (line 267-273)
- **Status**: ✅ Fixed

---

## 🧪 Pre-Testing Checklist

Before placing a new order, verify:

- [x] ✅ WhatsApp settings configured
- [x] ✅ Customer notification enabled (`order_notification` → `customer`)
- [x] ✅ Admin notification enabled (`new_order_alert` → `admin`)
- [x] ✅ Waiter notification enabled (`new_order_alert` → `staff`)
- [x] ✅ Kitchen notification enabled (`kitchen_notification` → `staff`)
- [x] ✅ Admin users have phone numbers (1 admin ready)
- [x] ✅ Waiter users have phone numbers (1 waiter ready)
- [ ] ⚠️ Queue worker running (if `QUEUE_CONNECTION=database`)

---

## 🚀 Quick Verification Command

```bash
php artisan tinker --execute="
\$setting = \Modules\Whatsapp\Entities\WhatsAppSetting::where('is_enabled', true)->first();
echo (\$setting && \$setting->isConfigured() ? '✅' : '❌') . ' WhatsApp Configured' . PHP_EOL;

\$prefs = \Modules\Whatsapp\Entities\WhatsAppNotificationPreference::where('restaurant_id', 1)->where('is_enabled', true)->get();
\$required = [['order_notification', 'customer'], ['new_order_alert', 'admin'], ['new_order_alert', 'staff'], ['kitchen_notification', 'staff']];
foreach (\$required as \$req) {
    \$found = \$prefs->where('notification_type', \$req[0])->where('recipient_type', \$req[1])->first();
    echo (\$found ? '✅' : '❌') . ' ' . \$req[0] . ' → ' . \$req[1] . PHP_EOL;
}
"
```

---

## 📊 Summary Table

| Order Type | Customer | Admin | Waiter | Delivery | Kitchen | Total |
|------------|----------|-------|--------|----------|---------|-------|
| **Dine-in** | ✅ | ✅ | ✅ | ❌ | ✅ | **4** |
| **Delivery** | ✅ | ✅ | ❌ | ✅ | ✅ | **4** |
| **Pickup** | ✅ | ✅ | ❌ | ❌ | ✅ | **3** |

---

## 📝 Important Notes

1. **Queue Processing**: If using `database` queue driver, ensure queue worker is running:
   ```bash
   php artisan queue:work
   ```

2. **KOT Notifications**: Sent separately via delayed job (3-second delay) to collect all items

3. **Idempotency**: Each order is processed only once (15-minute cache window)

4. **Logging**: All notifications are logged in `storage/logs/laravel-*.log`

5. **Phone Format**: All phone numbers formatted as `phone_code + phone_number` (no `+` sign)

---

**Last Updated**: 2025-12-10
**Status**: ✅ Ready for Testing

