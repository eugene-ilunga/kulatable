# WhatsApp Business API Integration - Task Description

## 📋 Overview

Integrate WhatsApp Business API notifications into TableTrack to send automated notifications to customers, restaurant admins, staff, and delivery executives. This module will enable restaurants to send 29 different types of notifications via WhatsApp, improving customer engagement and operational efficiency.

## 🎯 Objectives

1. Create a modular WhatsApp notification system that integrates with existing TableTrack events
2. Provide admin panel for template management and WhatsApp Business API configuration
3. Support 29 predefined notification templates organized by recipient type
4. Allow restaurants to map system templates to their WhatsApp Business Portal templates
5. Ensure compliance with WhatsApp Business API policies and template requirements

## 📊 Scope

### In Scope
- WhatsApp Business API integration module
- 29 predefined notification templates
- Template management system (copy-paste JSON for WhatsApp Portal)
- Template mapping per restaurant
- Event listeners for existing TableTrack events
- Admin panel for configuration and template management
- Multi-language support for templates
- Notification logging and tracking

### Out of Scope (Future Enhancements)
- Two-way WhatsApp conversations
- WhatsApp chatbot integration
- Template approval status tracking via API
- Automated template creation via API

## 🏗️ Architecture

### Module Structure
```
Modules/WhatsAppNotifications/
├── Services/
│   ├── WhatsAppService.php (Main API service)
│   ├── WhatsAppTemplateService.php (Template management)
│   └── WhatsAppNotificationService.php (Notification sending)
├── Notifications/
│   ├── Customer/ (11 notification classes)
│   ├── Admin/ (5 notification classes)
│   ├── Staff/ (9 notification classes)
│   └── Delivery/ (3 notification classes)
├── Listeners/
│   ├── OrderEventListeners.php
│   ├── ReservationEventListeners.php
│   └── PaymentEventListeners.php
├── Entities/
│   ├── WhatsAppSetting.php
│   ├── WhatsAppTemplateMapping.php
│   └── WhatsAppTemplateDefinition.php
├── Database/
│   └── Migrations/
└── Resources/
    └── views/ (Admin panel UI)
```

## 🔄 Flow Diagram

### 1. Setup Flow (One-time per Restaurant)
```
Admin → WhatsApp Settings Page
  ↓
Enter WABA Credentials (WABA ID, Access Token, Phone Number ID)
  ↓
Save Configuration
  ↓
Go to Template Library
  ↓
View Template JSON for each notification type
  ↓
Copy Template JSON
  ↓
Go to WhatsApp Business Portal
  ↓
Create Template with same name (e.g., "order_confirmation")
  ↓
Wait for WhatsApp Approval (24 hours)
  ↓
Return to Template Mapping Page
  ↓
Map Notification Type → WhatsApp Template Name
  ↓
Activate Notification Type
```

### 2. Notification Sending Flow
```
System Event Triggered (e.g., NewOrderCreated)
  ↓
Event Listener Detected
  ↓
Check if WhatsApp enabled for restaurant
  ↓
Get Template Mapping for notification type
  ↓
Check if template is active
  ↓
Prepare Variables (order number, customer name, etc.)
  ↓
Call WhatsAppService.send()
  ↓
Send to WhatsApp Business API
  ↓
Log Notification (success/failure)
  ↓
Handle Errors (fallback to email or skip)
```

## 📦 Database Schema

### 1. whatsapp_settings
Stores WhatsApp Business API credentials per restaurant
```sql
- id (bigint, primary)
- restaurant_id (bigint, foreign key)
- waba_id (varchar) - WhatsApp Business Account ID
- access_token (text, encrypted) - API access token
- phone_number_id (varchar) - Phone number ID
- business_phone_number (varchar) - Business phone number
- is_enabled (boolean) - Enable/disable WhatsApp
- created_at, updated_at
```

### 2. whatsapp_template_mappings
Maps system notification types to restaurant's WhatsApp template names
```sql
- id (bigint, primary)
- restaurant_id (bigint, foreign key)
- notification_type (varchar) - e.g., 'order_confirmation'
- template_name (varchar) - Actual WhatsApp template name
- template_id (varchar, nullable) - WhatsApp template ID (reference)
- language_code (varchar) - Default 'en'
- is_active (boolean)
- created_at, updated_at
- UNIQUE(restaurant_id, notification_type, language_code)
```

### 3. whatsapp_template_definitions
Predefined template structures for copy-paste
```sql
- id (bigint, primary)
- notification_type (varchar, unique)
- template_name (varchar) - Standard name
- category (varchar) - customer/admin/staff/delivery
- description (text)
- template_json (text) - JSON for WhatsApp Portal
- sample_variables (json) - Sample data
- is_active (boolean)
- created_at, updated_at
```

### 4. whatsapp_notification_logs
Track all sent notifications
```sql
- id (bigint, primary)
- restaurant_id (bigint, foreign key)
- notification_type (varchar)
- recipient_phone (varchar)
- template_name (varchar)
- variables (json) - Sent variables
- status (enum) - sent/failed/pending
- whatsapp_message_id (varchar, nullable)
- error_message (text, nullable)
- sent_at (timestamp)
- created_at, updated_at
```

## 📝 Notification Templates (29 Total)

### Customer Notifications (11)
1. `order_confirmation` - New order confirmation
2. `order_status_update` - All order status changes (confirmed/preparing/ready/delivered)
3. `order_cancelled` - Order cancellation
4. `order_bill_invoice` - Bill/invoice after completion
5. `payment_confirmation` - Payment success confirmation
6. `payment_reminder` - Payment due reminder
7. `reservation_confirmation` - Table booking confirmation
8. `reservation_reminder` - Pre-reservation reminder
9. `reservation_status_update` - Reservation status changes
10. `waiter_request_acknowledgment` - Waiter request acknowledgment
11. `table_ready_notification` - Table ready notification

### Admin Notifications (5)
12. `new_order_alert` - New order alert
13. `sales_report` - Daily/weekly/monthly sales reports
14. `low_inventory_alert` - Low stock alert
15. `subscription_expiry_reminder` - Subscription expiry reminder
16. `new_restaurant_signup` - New restaurant signup (superadmin)

### Staff Notifications (9)
17. `new_kot_notification` - New KOT for kitchen
18. `order_modification_alert` - Order modification alert
19. `order_cancellation_alert` - Order cancellation (kitchen)
20. `waiter_request` - Waiter request notification
21. `table_assignment` - Table assignment to waiter
22. `order_ready_to_serve` - Order ready to serve
23. `payment_request_alert` - Payment request alert
24. `table_status_change` - Table status change
25. `daily_operations_summary` - Daily summary for managers

### Delivery Notifications (3)
26. `delivery_assignment` - Delivery assignment
27. `order_ready_for_pickup` - Order ready for pickup
28. `delivery_completion_confirmation` - Delivery completion

### Automated Reminders (1)
29. `reservation_followup` - Post-reservation follow-up

## 🔧 Implementation Steps

### Phase 1: Module Setup
1. Create WhatsAppNotifications module structure
2. Create database migrations
3. Create model classes (WhatsAppSetting, WhatsAppTemplateMapping, WhatsAppTemplateDefinition, WhatsAppNotificationLog)
4. Create service provider and register module

### Phase 2: Core Services
1. Create WhatsAppService.php - Main API integration
   - Send message method
   - Handle API responses
   - Error handling and retry logic
2. Create WhatsAppTemplateService.php
   - Get template JSON for copy-paste
   - Get restaurant template mapping
   - Validate template names
3. Create WhatsAppNotificationService.php
   - Send notification wrapper
   - Variable preparation
   - Logging

### Phase 3: Notification Classes
1. Create 29 notification classes organized by folder:
   - Customer/ (11 classes)
   - Admin/ (5 classes)
   - Staff/ (9 classes)
   - Delivery/ (3 classes)
   - Automated/ (1 class)
2. Each class should:
   - Extend base notification class
   - Define template name
   - Prepare variables
   - Handle recipient phone number

### Phase 4: Event Listeners
1. Create event listeners for:
   - Order events (NewOrderCreated, OrderUpdated, OrderCancelled, OrderSuccessEvent)
   - Reservation events (ReservationReceived, ReservationConfirmationSent, TodayReservationCreatedEvent)
   - Payment events (SendOrderBillEvent)
   - Waiter events (ActiveWaiterRequestCreatedEvent, NotifyWaiter)
2. Register listeners in EventServiceProvider

### Phase 5: Admin Panel UI
1. WhatsApp Settings Page
   - WABA credentials form
   - Enable/disable toggle
   - Test connection button
2. Template Library Page
   - List all 29 templates
   - Show template JSON (copy button)
   - Show sample variables
   - Instructions for WhatsApp Portal
3. Template Mapping Page
   - Map notification types to template names
   - Enable/disable per notification type
   - Test notification button
4. Notification Logs Page
   - View sent notifications
   - Filter by date, type, status
   - Resend failed notifications

### Phase 6: Template Definitions
1. Create seeder for 29 template definitions
2. Each template should have:
   - Template JSON structure
   - Sample variables
   - Description
   - Category

### Phase 7: Integration
1. Hook into existing events
2. Add settings to restaurant settings menu
3. Add permissions for WhatsApp module
4. Add module to package/module system

### Phase 8: Testing
1. Unit tests for services
2. Integration tests for event listeners
3. Manual testing of all 29 notification types
4. Test error handling and fallbacks

## ✅ Acceptance Criteria

### Functional Requirements
- [ ] Restaurant admin can configure WhatsApp Business API credentials
- [ ] Admin can view all 29 template JSONs for copy-paste
- [ ] Admin can map notification types to WhatsApp template names
- [ ] Admin can enable/disable individual notification types
- [ ] System sends notifications when events are triggered
- [ ] All notifications are logged with status
- [ ] Failed notifications are handled gracefully
- [ ] Template names follow WhatsApp naming conventions (lowercase, underscores)
- [ ] Multi-language support for templates

### Technical Requirements
- [ ] Module follows existing module structure pattern
- [ ] All database migrations are created
- [ ] Services are properly tested
- [ ] Event listeners are properly registered
- [ ] Error handling and logging implemented
- [ ] API credentials are encrypted in database
- [ ] Code follows Laravel best practices
- [ ] Documentation is complete

### UI/UX Requirements
- [ ] Settings page is intuitive and easy to use
- [ ] Template library shows clear instructions
- [ ] Template mapping is straightforward
- [ ] Notification logs are searchable and filterable
- [ ] Error messages are user-friendly

## 🔐 Security Considerations

1. **API Credentials**: Encrypt access tokens in database
2. **Phone Numbers**: Validate and sanitize phone numbers
3. **Rate Limiting**: Implement rate limiting for API calls
4. **Error Handling**: Don't expose sensitive errors to users
5. **Permissions**: Add proper permission checks for WhatsApp settings

## 📚 Dependencies

### External
- WhatsApp Business API account (restaurant must have)
- WhatsApp Business Portal access (for template creation)
- Guzzle HTTP client (already in project)

### Internal
- Existing event system (NewOrderCreated, etc.)
- Restaurant model and settings
- Permission system
- Module system

## 🚨 Important Notes

1. **Template Approval**: Templates must be approved by WhatsApp (24 hours). System should handle pending templates gracefully.

2. **Template Naming**: All restaurants can use same template names (e.g., "order_confirmation") since each has their own WABA. No conflicts.

3. **Fallback Mechanism**: If WhatsApp fails, system should:
   - Log the error
   - Optionally fallback to email (if configured)
   - Not break the main flow

4. **Testing**: Use WhatsApp Business API test environment during development.

5. **Rate Limits**: WhatsApp has rate limits. Implement queuing for bulk notifications.

6. **Opt-in**: Ensure customers have opted in to receive WhatsApp messages (compliance).

## 📖 Documentation Required

1. Admin guide for setting up WhatsApp Business API
2. Template creation guide (step-by-step)
3. Template mapping guide
4. Troubleshooting guide
5. API integration documentation

## 🎯 Success Metrics

- All 29 notification types implemented and tested
- Admin can successfully configure and use WhatsApp notifications
- Notifications are sent successfully with >95% success rate
- Template management is user-friendly
- Zero breaking changes to existing functionality

## 📅 Estimated Timeline

- Phase 1-2: 2-3 days (Module setup + Core services)
- Phase 3: 3-4 days (29 notification classes)
- Phase 4: 2 days (Event listeners)
- Phase 5: 4-5 days (Admin panel UI)
- Phase 6: 1 day (Template definitions)
- Phase 7: 2 days (Integration)
- Phase 8: 2-3 days (Testing)
- **Total: 16-20 days**

---

## 📋 Template JSON Example

```json
{
  "name": "order_confirmation",
  "language": "en",
  "category": "UTILITY",
  "components": [
    {
      "type": "HEADER",
      "format": "TEXT",
      "text": "Order Confirmed"
    },
    {
      "type": "BODY",
      "text": "Hello {{1}}, your order #{{2}} has been confirmed! Order type: {{3}}. Total amount: {{4}}. Estimated time: {{5}}. Thank you for choosing {{6}}!"
    },
    {
      "type": "FOOTER",
      "text": "For queries, contact {{7}}"
    }
  ]
}
```

**Variables:**
1. Customer name
2. Order number
3. Order type
4. Total amount
5. Estimated time
6. Restaurant name
7. Restaurant contact

---

*This task should be broken down into subtasks for better tracking and parallel development.*

