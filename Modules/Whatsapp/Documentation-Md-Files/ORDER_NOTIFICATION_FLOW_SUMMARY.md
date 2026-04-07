# WhatsApp Order Notification Flow - Complete Summary

## Overview
When an order is placed from POS (dine-in/delivery/pickup), the `NewOrderCreated` event is triggered, which activates the `SendOrderConfirmationListener`. This listener checks notification preferences and sends WhatsApp messages to different recipients based on order type.

---

## Event Flow

### 1. Order Creation from POS
- **Event**: `NewOrderCreated` is dispatched
- **Triggered in**:
  - `app/Livewire/Pos/Pos.php` (line 2078, 2208)
  - `app/Observers/OrderObserver.php` (line 44-45)

### 2. Listener Activation
- **Listener**: `SendOrderConfirmationListener`
- **Location**: `Modules/Whatsapp/Listeners/SendOrderConfirmationListener.php`
- **Handles**: `NewOrderCreated` event

---

## Notification Templates & Recipients by Order Type

### 📋 **DINE-IN Orders**

| Recipient | Template | Notification Type | Condition |
|-----------|----------|-------------------|-----------|
| **Customer** | `order_notification` | `order_confirmation` | ✅ Customer has phone number<br>✅ Preference enabled for `customer` |
| **Admin** | `new_order_alert` | `new_order_alert` | ✅ Admin users with phone numbers<br>✅ Preference enabled for `admin` |
| **Waiter** | `new_order_alert` | `new_order_alert` | ✅ Waiter assigned (`waiter_id` exists)<br>✅ Waiter has phone number<br>✅ Preference enabled for `staff` |
| **Kitchen** | `kitchen_notification` | `kitchen_notification` | ✅ Triggered separately via `KotUpdated` event<br>✅ Preference enabled for `staff` |

---

### 🚚 **DELIVERY Orders**

| Recipient | Template | Notification Type | Condition |
|-----------|----------|-------------------|-----------|
| **Customer** | `order_notification` | `order_confirmation` | ✅ Customer has phone number<br>✅ Preference enabled for `customer` |
| **Admin** | `new_order_alert` | `new_order_alert` | ✅ Admin users with phone numbers<br>✅ Preference enabled for `admin` |
| **Delivery Executive** | `new_order_alert` | `new_order_alert` | ✅ Order type is `delivery`<br>✅ Delivery executive assigned (`delivery_executive_id`)<br>✅ Executive has phone number<br>✅ Preference enabled for `delivery` |
| **Kitchen** | `kitchen_notification` | `kitchen_notification` | ✅ Triggered separately via `KotUpdated` event<br>✅ Preference enabled for `staff` |

---

### 📦 **PICKUP Orders**

| Recipient | Template | Notification Type | Condition |
|-----------|----------|-------------------|-----------|
| **Customer** | `order_notification` | `order_confirmation` | ✅ Customer has phone number<br>✅ Preference enabled for `customer` |
| **Admin** | `new_order_alert` | `new_order_alert` | ✅ Admin users with phone numbers<br>✅ Preference enabled for `admin` |
| **Kitchen** | `kitchen_notification` | `kitchen_notification` | ✅ Triggered separately via `KotUpdated` event<br>✅ Preference enabled for `staff` |

---

## Template Details

### 1. `order_notification` (Customer)
- **Consolidated Template**: Maps to `order_notification`
- **Variables**:
  - `{{1}}` - Customer name
  - `{{2}}` - Order number
  - `{{3}}` - Order type (Dine-in/Delivery/Pickup)
  - `{{4}}` - Total amount (with GST/taxes)
  - `{{5}}` - Estimated time
  - `{{6}}` - Restaurant name
  - `{{7}}` - Contact number

### 2. `new_order_alert` (Admin/Waiter/Delivery)
- **Consolidated Template**: Maps to `new_order_alert`
- **Variables**:
  - `{{1}}` - Recipient name (Admin/Waiter/Delivery Executive name)
  - `{{2}}` - Message context ("New")
  - `{{3}}` - Order number
  - `{{4}}` - Order type
  - `{{5}}` - Customer name
  - `{{6}}` - Amount (with GST/taxes)

### 3. `kitchen_notification` (Kitchen Staff)
- **Consolidated Template**: Maps to `kitchen_notification`
- **Triggered by**: `KotUpdated` event (separate listener)
- **Variables**: KOT details, items list with modifiers
- **Note**: Uses delayed job pattern (3-second delay) to collect all items

---

## Notification Preferences Check

The listener checks `whatsapp_notification_preferences` table for:
- `restaurant_id` matches
- `notification_type` matches
- `recipient_type` matches (`customer`, `admin`, `staff`, `delivery`)
- `is_enabled` = `true`

---

## Phone Number Format

All phone numbers are formatted as:
- **Format**: `phone_code + phone_number` (no `+` sign)
- **Example**: `919876543210` (for +91 9876543210)

---

## Important Notes

1. **Idempotency**: Each order is processed only once (15-minute cache window)
2. **KOT Notifications**: Sent separately via `SendKotNotificationListener` when KOT is created/updated
3. **Queue Processing**: Uses Laravel queue system (check `QUEUE_CONNECTION` in `.env`)
4. **Logging**: All notification attempts are logged in `storage/logs/laravel-*.log`

---

## Testing Checklist

Before placing a new order, verify:

- [ ] WhatsApp settings configured (`waba_id`, `phone_number_id`, `access_token`)
- [ ] Notification preferences enabled for:
  - [ ] Customer (`order_notification` → `customer`)
  - [ ] Admin (`new_order_alert` → `admin`)
  - [ ] Waiter (`new_order_alert` → `staff`) - for dine-in orders
  - [ ] Delivery (`new_order_alert` → `delivery`) - for delivery orders
  - [ ] Kitchen (`kitchen_notification` → `staff`)
- [ ] Users have valid phone numbers:
  - [ ] Customer phone number
  - [ ] Admin phone numbers
  - [ ] Waiter phone number (if assigned)
  - [ ] Delivery executive phone number (if assigned)
- [ ] Queue worker running (if `QUEUE_CONNECTION=database`)
- [ ] Templates approved in Meta Business Manager

---

## Quick Test Command

```bash
# Check notification preferences
php artisan tinker --execute="
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
\$prefs = WhatsAppNotificationPreference::where('is_enabled', true)
    ->where('restaurant_id', 1)
    ->get(['notification_type', 'recipient_type']);
foreach (\$prefs as \$p) {
    echo \$p->notification_type . ' → ' . \$p->recipient_type . PHP_EOL;
}
"

# Check queue status
php artisan queue:work --once

# Monitor logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "(WhatsApp|Order Confirmation)"
```

---

## Summary Table

| Order Type | Customer | Admin | Waiter | Delivery | Kitchen |
|------------|----------|-------|--------|----------|---------|
| **Dine-in** | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Delivery** | ✅ | ✅ | ❌ | ✅ | ✅ |
| **Pickup** | ✅ | ✅ | ❌ | ❌ | ✅ |

**Legend:**
- ✅ = Notification sent (if preferences enabled and phone numbers available)
- ❌ = Not applicable for this order type

---

**Last Updated**: 2025-12-10

---

## Bug Fixes Applied

### ✅ Fixed Waiter Notification Bug
- **Issue**: Waiter notification was checked but never sent
- **Fixed**: Added notification service call in `SendOrderConfirmationListener.php` (line 267-273)
- **Status**: ✅ Fixed and tested

---

## Verification Script

Run this before placing orders:

```bash
php artisan tinker --execute="
echo '=== NOTIFICATION SYSTEM CHECK ===' . PHP_EOL . PHP_EOL;

// 1. WhatsApp Settings
\$setting = \Modules\Whatsapp\Entities\WhatsAppSetting::where('is_enabled', true)->first();
if (\$setting && \$setting->isConfigured()) {
    echo '✅ WhatsApp Configured' . PHP_EOL;
} else {
    echo '❌ WhatsApp NOT Configured!' . PHP_EOL;
    exit;
}

// 2. Notification Preferences
\$prefs = \Modules\Whatsapp\Entities\WhatsAppNotificationPreference::where('restaurant_id', 1)->where('is_enabled', true)->get();
\$required = [
    ['order_notification', 'customer'],
    ['new_order_alert', 'admin'],
    ['new_order_alert', 'staff'],
    ['kitchen_notification', 'staff'],
];

echo PHP_EOL . 'Notification Preferences:' . PHP_EOL;
foreach (\$required as \$req) {
    \$found = \$prefs->where('notification_type', \$req[0])->where('recipient_type', \$req[1])->first();
    echo (\$found ? '✅' : '❌') . ' ' . \$req[0] . ' → ' . \$req[1] . PHP_EOL;
}

// 3. Users with Phone Numbers
echo PHP_EOL . 'Users Ready:' . PHP_EOL;
\$admins = \App\Models\User::withoutGlobalScope(\App\Scopes\BranchScope::class)
    ->role('Admin_1')->where('restaurant_id', 1)->whereNotNull('phone_number')->count();
echo '  Admins: ' . \$admins . PHP_EOL;

\$waiters = \App\Models\User::role('Waiter_1')->where('restaurant_id', 1)->whereNotNull('phone_number')->count();
echo '  Waiters: ' . \$waiters . PHP_EOL;

// 4. Queue Status
\$queueDriver = env('QUEUE_CONNECTION', 'sync');
echo PHP_EOL . 'Queue Driver: ' . \$queueDriver . PHP_EOL;
if (\$queueDriver === 'database') {
    echo '⚠️  Run: php artisan queue:work' . PHP_EOL;
}
"
```

