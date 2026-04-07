# WhatsApp Notifications - Finalized Template List

## Template Consolidation Strategy
- Similar notifications with different statuses/values → Single template with variables
- Different contexts/recipients → Separate templates
- Different urgency levels → Separate templates

---

## 1. CUSTOMER-FACING NOTIFICATIONS

### Order Notifications

#### Template 1: `order_confirmation`
**Purpose:** New order confirmation when customer places an order
**Variables:**
- Customer name
- Order number
- Order type (dine-in/takeout/delivery)
- Total amount
- Estimated time
- Restaurant name
- Restaurant contact

**Used for:**
- New order confirmation (dine-in/takeout/delivery)

---

#### Template 2: `order_status_update`
**Purpose:** Single template for all order status changes
**Variables:**
- Customer name
- Order number
- Status (confirmed/preparing/ready_for_pickup/out_for_delivery/delivered)
- Status message (dynamic based on status)
- Estimated time (if applicable)
- Delivery executive name & phone (if out_for_delivery)
- Restaurant name

**Used for:**
- Order confirmed
- Order preparing
- Order ready for pickup
- Order out for delivery (with delivery executive details)
- Order delivered

**Note:** One template handles all status updates with dynamic messaging

---

#### Template 3: `order_cancelled`
**Purpose:** Order cancellation notification
**Variables:**
- Customer name
- Order number
- Cancellation reason
- Refund status (if applicable)
- Restaurant name
- Restaurant contact

**Used for:**
- Order cancellation (by customer or restaurant)

---

#### Template 4: `order_bill_invoice`
**Purpose:** Send bill/invoice after order completion
**Variables:**
- Customer name
- Order number
- Bill amount
- Payment method
- Bill link/QR code
- Restaurant name
- Restaurant contact

**Used for:**
- Order bill/invoice after order completion

---

### Payment Notifications

#### Template 5: `payment_confirmation`
**Purpose:** Payment confirmation after successful payment
**Variables:**
- Customer name
- Order number
- Payment amount
- Payment method
- Transaction ID
- Payment date/time
- Restaurant name

**Used for:**
- Payment confirmation after successful payment

---

#### Template 6: `payment_reminder`
**Purpose:** Reminder for pending/due payments
**Variables:**
- Customer name
- Order number
- Due amount
- Due date
- Payment link
- Restaurant name
- Restaurant contact

**Used for:**
- Payment reminder for pending/due payments

---

### Reservation Notifications

#### Template 7: `reservation_confirmation`
**Purpose:** Reservation confirmation when table is booked
**Variables:**
- Customer name
- Reservation date
- Reservation time
- Number of guests
- Table number (if assigned)
- Restaurant name
- Restaurant contact
- Reservation ID

**Used for:**
- Reservation confirmation when table is booked

---

#### Template 8: `reservation_reminder`
**Purpose:** Reminder before reservation time
**Variables:**
- Customer name
- Reservation date
- Reservation time
- Number of guests
- Time until reservation
- Restaurant name
- Restaurant contact
- Reservation ID

**Used for:**
- Reservation reminder (1-2 hours before)
- Today's reservation reminder (morning reminder for same-day)

---

#### Template 9: `reservation_status_update`
**Purpose:** Single template for reservation status changes
**Variables:**
- Customer name
- Reservation ID
- Status (confirmed/cancelled/modified)
- Status message (dynamic based on status)
- New date/time (if modified)
- Cancellation reason (if cancelled)
- Restaurant name
- Restaurant contact

**Used for:**
- Reservation confirmed
- Reservation cancelled
- Reservation modified

---

### Service Notifications

#### Template 10: `waiter_request_acknowledgment`
**Purpose:** Acknowledge waiter request from customer
**Variables:**
- Customer name
- Table number
- Request time
- Estimated wait time
- Restaurant name

**Used for:**
- Waiter request acknowledgment

---

#### Template 11: `table_ready_notification`
**Purpose:** Notify when reserved table becomes available
**Variables:**
- Customer name
- Table number
- Reservation time
- Restaurant name
- Restaurant contact
- Arrival instructions

**Used for:**
- Table ready notification

---

## 2. RESTAURANT ADMIN/OWNER NOTIFICATIONS

### Business Operations

#### Template 12: `new_order_alert`
**Purpose:** Alert admin when new order is placed
**Variables:**
- Order number
- Order type (dine-in/takeout/delivery)
- Customer name
- Total amount
- Order items summary
- Order time
- Branch name (if multi-branch)

**Used for:**
- New order alert when new order is placed

---

#### Template 13: `sales_report`
**Purpose:** Single template for all sales reports (daily/weekly/monthly)
**Variables:**
- Report period (daily/weekly/monthly)
- Report date/date range
- Total sales
- Total orders
- Average order value
- Top selling items
- Comparison with previous period
- Restaurant name

**Used for:**
- Daily sales summary (end-of-day report)
- Weekly sales report
- Monthly sales report

---

#### Template 14: `low_inventory_alert`
**Purpose:** Alert when inventory items are running low
**Variables:**
- Item name(s)
- Current stock
- Minimum threshold
- Branch name (if applicable)
- Alert time

**Used for:**
- Low inventory alerts

---

#### Template 15: `subscription_expiry_reminder`
**Purpose:** Reminder before subscription expires
**Variables:**
- Restaurant name
- Current plan
- Expiry date
- Days remaining
- Renewal link
- Support contact

**Used for:**
- Subscription expiry reminder

---

### System Alerts

#### Template 16: `new_restaurant_signup`
**Purpose:** Notify superadmin when new restaurant registers
**Variables:**
- Restaurant name
- Owner name
- Email
- Phone
- Signup date
- Plan selected
- Approval link (if needed)

**Used for:**
- New restaurant signup notification

---

## 3. STAFF NOTIFICATIONS

### Kitchen Staff (Chefs)

#### Template 17: `new_kot_notification`
**Purpose:** Notify kitchen when new KOT is created
**Variables:**
- KOT number
- Order number
- Table number (if dine-in)
- Order type
- Items list with quantities
- Special instructions
- Priority (if applicable)
- Order time

**Used for:**
- New KOT (Kitchen Order Ticket) notification

---

#### Template 18: `order_modification_alert`
**Purpose:** Alert kitchen when order is modified
**Variables:**
- KOT number
- Order number
- Modification type (item added/removed/modified)
- Changes details
- Updated items list
- Modification time

**Used for:**
- Order modification alert

---

#### Template 19: `order_cancellation_alert`
**Purpose:** Alert kitchen when order is cancelled
**Variables:**
- KOT number
- Order number
- Cancellation reason
- Cancelled items
- Cancellation time

**Used for:**
- Order cancellation alert (for kitchen)

---

### Waiters

#### Template 20: `waiter_request`
**Purpose:** Notify waiter when customer requests service
**Variables:**
- Table number
- Customer name (if available)
- Request type (general/assistance/bill/etc.)
- Request time
- Branch name

**Used for:**
- New waiter request

---

#### Template 21: `table_assignment`
**Purpose:** Notify waiter when assigned to a table
**Variables:**
- Waiter name
- Table number
- Customer name
- Number of guests
- Assignment time
- Special notes

**Used for:**
- Table assignment notification

---

#### Template 22: `order_ready_to_serve`
**Purpose:** Notify waiter when order is ready to serve
**Variables:**
- Waiter name
- Table number
- Order number
- Items ready
- Ready time
- Special instructions

**Used for:**
- Order ready to serve notification

---

#### Template 23: `payment_request_alert`
**Purpose:** Alert waiter when customer requests bill
**Variables:**
- Waiter name
- Table number
- Order number
- Customer name
- Request time

**Used for:**
- Payment request alert

---

#### Template 24: `table_status_change`
**Purpose:** Notify waiter about table status changes
**Variables:**
- Table number
- Previous status
- New status
- Change time
- Notes

**Used for:**
- Table status change notifications

---

### Managers/Branch Heads

#### Template 25: `daily_operations_summary`
**Purpose:** End-of-day operations summary for managers
**Variables:**
- Branch name
- Date
- Total orders
- Total revenue
- Staff on duty
- Peak hours
- Issues/alerts summary
- Next day preview

**Used for:**
- Daily operations summary (end-of-day)

---

## 4. DELIVERY EXECUTIVE NOTIFICATIONS

#### Template 26: `delivery_assignment`
**Purpose:** Notify delivery executive when assigned a delivery
**Variables:**
- Delivery executive name
- Order number
- Customer name
- Customer phone
- Pickup address
- Delivery address
- Order items
- Total amount
- Estimated delivery time
- Special instructions

**Used for:**
- New delivery assignment

---

#### Template 27: `order_ready_for_pickup`
**Purpose:** Notify delivery executive when order is ready for pickup
**Variables:**
- Delivery executive name
- Order number
- Pickup address
- Ready time
- Customer name
- Delivery address
- Contact number

**Used for:**
- Order ready for pickup

---

#### Template 28: `delivery_completion_confirmation`
**Purpose:** Confirm successful delivery completion
**Variables:**
- Delivery executive name
- Order number
- Customer name
- Delivery time
- Delivery address
- Payment status
- Next delivery (if available)

**Used for:**
- Delivery completion confirmation

---

## 5. AUTOMATED REMINDER NOTIFICATIONS

#### Template 29: `reservation_followup`
**Purpose:** Follow-up after reservation completion
**Variables:**
- Customer name
- Reservation date
- Restaurant name
- Feedback link
- Discount offer (optional)
- Thank you message

**Used for:**
- Reservation follow-up after completion

---

## SUMMARY

### Total Templates: 29

**By Category:**
- Customer Notifications: 11 templates
- Admin Notifications: 5 templates
- Staff Notifications: 9 templates
- Delivery Notifications: 3 templates
- Automated Reminders: 1 template

**Key Consolidations:**
1. ✅ Order status updates → 1 template (`order_status_update`)
2. ✅ Reservation status updates → 1 template (`reservation_status_update`)
3. ✅ Sales reports (daily/weekly/monthly) → 1 template (`sales_report`)
4. ✅ Reservation reminders (1-2hr before + same-day) → 1 template (`reservation_reminder`)

**Template Naming Convention:**
- All lowercase
- Underscore separated
- Descriptive and clear
- Matches WhatsApp Business API requirements

---

## IMPLEMENTATION NOTES

1. **Template Variables:** Each template should have clearly defined variables that can be dynamically replaced
2. **Multi-language Support:** Each template should support multiple languages (en, hi, etc.)
3. **Template Status:** Track approval status (pending/approved/rejected) per restaurant
4. **Fallback Mechanism:** If template not approved, fallback to email or skip notification
5. **Testing:** Provide test mode to verify templates before going live

