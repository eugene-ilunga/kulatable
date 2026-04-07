# Meta WhatsApp Business API - Template Creation Guide

## 🎯 Important: Variable Rules

**CRITICAL**: Meta WhatsApp API has specific rules for variables:
- **Header variables**: Start from `{{1}}` (separate scope, max 1 variable if TEXT format)
- **Body variables**: Start from `{{1}}` (separate scope, independent from header)
- **Body variable limit**: **Maximum 6 variables allowed** in the body
- **Footer**: **NO VARIABLES ALLOWED** - Must be static text only

## 📋 Prerequisites

1. **Meta Business Account**: You need a Meta Business account
2. **WhatsApp Business Account (WABA)**: Set up in Meta Business Manager
3. **Developer Access**: Access to WhatsApp Manager in Meta Business Manager
4. **Phone Number**: Verified phone number for testing

## 🔗 Access Template Manager

1. Go to [Meta Business Manager](https://business.facebook.com)
2. Navigate to **WhatsApp Manager** → **Message Templates**
3. Click **"Create Template"** or **"New Template"**

## 📝 Template Creation Process

For each template, follow these steps:

**Step 1: Name**
- Use the exact name from the template definition (e.g., `reservation_notification`)

**Step 2: Category**
- Select appropriate category (UTILITY, MARKETING, or AUTHENTICATION)
- Most templates use UTILITY category

**Step 3: Language**
- Select your language (usually English)

**Step 4: Header**
- Add Header component as specified in the template
- If Header has variables, add variable descriptions (e.g., `{{1}}` = "Header text")

**Step 5: Body**
- Add Body component with the exact text from the template
- Add variable descriptions for each `{{1}}`, `{{2}}`, etc.
- Remember: Body variables start from `{{1}}` (separate scope from Header)

**Step 6: Footer**
- Add Footer component with static text only
- **Important**: Footer cannot have variables - must be static text

**Step 7: Buttons (Optional)**
- If the template includes buttons, add them here
- Select button type (usually "URL Button")
- Enter button text (e.g., "View Booking", "View Order")
- Enter URL with variable placeholder (e.g., `https://yourdomain.com/restaurant/my-bookings/{{1}}`)
- Add variable description for the button URL variable (e.g., "Restaurant hash/slug")
- Add example URL (e.g., `https://yourdomain.com/restaurant/my-bookings/demo-restaurant`)
- **Note**: Replace `yourdomain.com` with your actual website domain

## 🎯 Template List (10 Consolidated Templates)

### 1. Order Notification
**Template Name**: `order_notifications`  
**Category**: UTILITY  
**Replaces**: `order_confirmation`, `order_status_update`, `order_cancelled`, `order_bill_invoice`

**Components**:
- **Header**: TEXT - `Order Alert` (Static text, no variables)
- **Body**: 
  ```
  Hello {{1}}, 

  We would like to inform you that {{2}}

  Order number #{{3}}.

  Order details: {{4}}.

  Additional information: {{5}}.
- **Footer**: (Optional static text)
- **Button**: URL Button - "View Order" with dynamic order ID parameter {{1}}

  Thank you for your order.
  ```
  - Variable descriptions:
    1. Customer name
    2. Main message (e.g., "your order has been confirmed", "order status updated to", "order has been cancelled", "your bill is ready")
    3. Order number
    4. Details line 1 (Order type/Status/Reason/Amount)
    5. Details line 2 (Estimated time/Additional info/Refund status/Payment method)
- **Footer**: `Thank you for choosing us!` (Static text, no variables)
- **Buttons**: 
  - **Type**: URL
  - **Text**: `View Order`
  - **URL**: `https://yourdomain.com/order/{{1}}` (Replace `yourdomain.com` with your actual website domain. The `{{1}}` will be replaced with order number when sending)
  - **Example**: `https://yourdomain.com/order/12345` (This is what Meta will show as an example. Replace `yourdomain.com` with your actual domain)

---

### 2. Payment Notification
**Template Name**: `payment_notification`  
**Category**: UTILITY  
**Replaces**: `payment_confirmation`, `payment_reminder`

**Components**:
- **Header**: TEXT - `Payment Notification` (Static text, no variables)
- **Body**: 
  ```
  Hello {{1}},

  {{2}} for order: #{{3}} has been successfully received.

  Order type: {{4}},

  Customer name: {{5}},

  Total amount: {{6}}.

  Thank you for choosing our services!
  ```
  - Variable descriptions:
    1. Customer name
    2. Message type (Payment/Pending payment)
    3. Order number
    4. Order type
    5. Customer name
    6. Total amount
- **Footer**: `Thank you!` (Static text, no variables)
- **Buttons**: 
  - **Type**: URL Button
  - **Text**: `View Order`
  - **URL**: `https://yourdomain.com/order/{{1}}`
    - Variable description: "Order number"
    - Example: `https://yourdomain.com/order/123`

---

### 3. Reservation Notification
**Template Name**: `reservation_notification`  
**Category**: UTILITY  
**Replaces**: `reservation_confirmation`, `reservation_reminder`, `reservation_status_update`, `reservation_followup`

**Components**:
- **Header**: TEXT - `Status: {{1}}`
  - **Important**: Header must have static text before the variable. Do NOT start with just `{{1}}`.
  - Variable description: "Header text (Reservation Confirmed/Reminder/Update/Thank You)"
- **Body**: 
  ```
  Hello {{1}}, we are pleased to confirm that {{2}} for a party of {{3}} guests. Your reservation has been scheduled for the date of {{4}} at the time of {{5}}. Here are some additional important details regarding your reservation: {{6}}. We are excited to welcome you and look forward to providing you with an excellent dining experience!
  ```
  - Variable descriptions:
    1. Customer name
    2. Message type (your reservation is confirmed/reminder: your reservation is on/your reservation status/thank you for visiting)
    3. Number of guests
    4. Date
    5. Time
    6. Additional details (Table number/Status/Time until reservation/Feedback link/Restaurant name - combined)
- **Footer**: `We look forward to serving you!` (Static text, no variables)
- **Buttons**: 
  - **Type**: URL Button
  - **Text**: `View Booking`
  - **URL**: `https://yourdomain.com/restaurant/my-bookings/{{1}}` (Replace `yourdomain.com` with your actual website domain. The `{{1}}` will be replaced with restaurant hash/slug when sending)
    - Variable description: "Restaurant hash/slug"
    - Example: `https://yourdomain.com/restaurant/my-bookings/demo-restaurant` (Replace `yourdomain.com` with your actual domain)

---

### 4. New Order Alert
**Template Name**: `new_order_alert`  
**Category**: UTILITY  
**Replaces**: `new_order_alert` (for admin/customer/staff)

**Components**:
- **Header**: TEXT - `New Order` (Static text, no variables)
- **Body**: 
  ```
  Hello {{1}},

  {{2}} order with order number {{3}} has been successfully received.

  The order type: {{4}}.

  Customer name: {{5}}.

  Amount for this order is {{6}}.

  Thank you for choosing our services!
  ```
  - Variable descriptions:
    1. Recipient name (Admin name/Customer name/Staff name)
    2. Message context (New/Your)
    3. Order number
    4. Order type
    5. Customer name (or "You" for customer)
    6. Amount
- **Footer**: `Thank you!` (Static text, no variables)
- **Buttons**: 
  - **Type**: URL Button
  - **Text**: `View Order`
  - **URL**: `https://yourdomain.com/order/{{1}}`
    - Variable description: "Order number"
    - Example: `https://yourdomain.com/order/123`

---

### 5. Delivery Notification
**Template Name**: `delivery_notification`  
**Category**: UTILITY  
**Replaces**: `delivery_assignment`, `order_ready_for_pickup`, `delivery_completion_confirmation`

**Components**:
- **Header**: TEXT - `Status: {{1}}`
  - **Important**: Header must have static text before the variable. Do NOT start with just `{{1}}`.
  - Variable description: "Header text (New Delivery/Ready for Pickup/Delivery Completed)"
- **Body**: 
  ```
  Hello {{1}}, we are notifying you that {{2}} for the order number {{3}}. The customer name for this delivery is {{4}}, the customer phone number is {{5}}, and here are the complete delivery details including address and amount: {{6}}. Please proceed with the delivery process as soon as possible. We appreciate your prompt service and thank you for your dedication!
  ```
  - Variable descriptions:
    1. Recipient name (Delivery executive/Customer)
    2. Message type (new delivery assigned/order is ready for pickup/delivery completed successfully)
    3. Order number
    4. Customer name
    5. Customer phone
    6. Address and Amount (combined)
- **Footer**: `Thank you!` (Static text, no variables)

---

### 6. Kitchen Notification
**Template Name**: `kitchen_notification`  
**Category**: UTILITY  
**Replaces**: `new_kot_notification`, `order_ready_to_serve`, `order_modification_alert`

**Components**:
- **Header**: TEXT - `Status: {{1}}`
  - **Important**: Header must have static text before the variable. Do NOT start with just `{{1}}`.
  - Variable description: "Header text (New KOT/Order Ready/Order Modified)"
- **Body**: 
  ```
  Hello {{1}}, we have received {{2}} with reference number {{3}} for the order number {{4}}. The table number assigned for this order is {{5}}, the order type for this transaction is {{6}}, and here is the complete list of items that need to be prepared: {{7}}. Please prepare all items accordingly and ensure timely service. We appreciate your hard work and thank you for your attention to detail!
  ```
  - Variable descriptions:
    1. Staff name (Chef/Waiter)
    2. Notification type (New KOT/Order ready to serve/Order has been modified)
    3. KOT number or Order number
    4. Order number
    5. Table number
    6. Order type, Items list and Time (combined)
- **Footer**: `Thank you!` (Static text, no variables)

---

### 7. Staff Notification
**Template Name**: `staff_notification`  
**Category**: UTILITY  
**Replaces**: `payment_request_alert`, `table_assignment`, `table_status_change`, `waiter_request_acknowledgment`, `notify_waiter`

**Components**:
- **Header**: TEXT - `Status: {{1}}`
  - **Important**: Header must have static text before the variable. Do NOT start with just `{{1}}`.
  - Variable description: "Header text (Payment Request/Table Assigned/Table Status/Waiter Request)"
- **Body**: 
  ```
  Hello {{1}}, we are sending you this notification regarding {{2}} for {{3}}. Here is the important detail: {{4}}. Please take necessary action. Thank you!
  ```
  - Variable descriptions:
    1. Staff name
    2. Notification type (payment requested/table assigned/table status changed/waiter request received)
    3. Target (table number/reservation number)
    4. Details (single detail)
- **Footer**: `Thank you!` (Static text, no variables)

---

### 8. Sales Report
**Template Name**: `sales_report`  
**Category**: UTILITY  
**Replaces**: `daily_sales_report`, `weekly_sales_report`, `monthly_sales_report`

**Components**:
- **Header**: TEXT - `Status: {{1}}`
  - **Important**: Header must have static text before the variable. Do NOT start with just `{{1}}`.
  - Variable description: "Header text (Daily/Weekly/Monthly Sales Report)"
- **Body**: 
  ```
  Here is your comprehensive {{1}} Sales Report for the reporting period of {{2}}. The total number of orders processed during this period is {{3}}, the total revenue generated is {{4}}, the net revenue after all deductions is {{5}}, and here are the combined tax and discount details: {{6}}. This report has been generated successfully and is ready for your review and analysis!
  ```
  - Variable descriptions:
    1. Period type (Daily/Weekly/Monthly)
    2. Period (Date/Date Range/Month)
    3. Total orders
    4. Total revenue
    5. Net revenue
    6. Tax and Discount (combined)
- **Footer**: `Generated automatically` (Static text, no variables)

---

### 9. Operations Summary
**Template Name**: `operations_summary`  
**Category**: UTILITY

**Components**:
- **Header**: TEXT - `Daily Summary` (Static text, no variables)
- **Body**: 
  ```
  Here is the daily operations summary for branch {{1}} on the date of {{2}}. The total number of orders processed today is {{3}}, the total revenue generated for today is {{4}}, the total number of reservations handled today is {{5}}, and here are the combined staff on duty and peak hours information: {{6}}. The end of day summary has been completed successfully and is ready for review!
  ```
  - Variable descriptions:
    1. Branch name
    2. Date
    3. Total orders
    4. Total revenue
    5. Total reservations
    6. Staff on duty and Peak hours (combined)
- **Footer**: `End of day summary` (Static text, no variables)

---

### 10. Inventory Alert
**Template Name**: `inventory_alert`  
**Category**: UTILITY

**Components**:
- **Header**: TEXT - `Low Stock Alert` (Static text, no variables)
- **Body**: 
  ```
  We are sending you this important low stock alert notification. There are currently {{1}} items that have fallen below the minimum threshold level. Here is the complete list of items that need immediate attention: {{2}}. This alert is for restaurant location: {{3}}. Please take immediate action to restock these items as soon as possible to avoid any service disruptions. Thank you for your prompt attention to this matter!
  ```
  - Variable descriptions:
    1. Item count
    2. Item names
    3. Restaurant name
- **Footer**: `Please restock soon` (Static text, no variables)

---

## ✅ Template Approval Process

1. **Submit for Review**: After creating each template, submit it for Meta's review
2. **Review Time**: Usually takes 24-48 hours
3. **Approval Status**: Check status in Meta Business Manager
4. **Rejection**: If rejected, Meta will provide feedback - fix and resubmit

## 📝 Variable Description Tips

When adding variable descriptions in Meta:
- Be clear and specific
- Use examples when helpful
- Keep descriptions concise
- Match the description to the actual data being sent

## 🚨 Common Mistakes to Avoid

1. ❌ **Using variables in Footer** - Footer must be static text only
2. ❌ **Starting Body variables from {{2}}** - Body variables must start from {{1}}
3. ❌ **Mixing variable scopes** - Header and Body have separate scopes
4. ❌ **Wrong template name** - Use exact names as specified
5. ❌ **Wrong category** - Use UTILITY for all templates
6. ❌ **Too many variables for message length** - Ensure there's enough descriptive text between variables
7. ❌ **Variables at start or end** - Variables cannot be at the very beginning or end of the body message

## 🎯 Next Steps

After creating all 10 templates:
1. Wait for approval (24-48 hours)
2. Once approved, templates will be available in your WhatsApp Business account
3. Map templates in the admin panel
4. Test notifications to ensure they work correctly
