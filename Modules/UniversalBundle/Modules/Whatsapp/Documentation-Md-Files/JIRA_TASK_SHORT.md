# WhatsApp Business API Integration

## Description

Integrate WhatsApp Business API notifications into TableTrack to send automated notifications to customers, restaurant admins, staff, and delivery executives. This module will enable restaurants to send 29 different types of notifications via WhatsApp.

## Objectives

* Create modular WhatsApp notification system integrated with existing TableTrack events
* Provide admin panel for template management and WhatsApp Business API configuration
* Support 29 predefined notification templates organized by recipient type
* Allow restaurants to map system templates to their WhatsApp Business Portal templates
* Ensure compliance with WhatsApp Business API policies

## Key Features

### 1. Template Management System
* 29 predefined notification templates (Customer: 11, Admin: 5, Staff: 9, Delivery: 3, Automated: 1)
* Template JSON export for copy-paste to WhatsApp Business Portal
* Template mapping per restaurant (notification type → WhatsApp template name)
* Enable/disable individual notification types

### 2. WhatsApp Configuration
* WABA credentials management (WABA ID, Access Token, Phone Number ID)
* Encrypted storage of API credentials
* Connection testing functionality
* Enable/disable WhatsApp notifications per restaurant

### 3. Notification System
* Event-driven notifications (hooks into existing events)
* Automatic variable preparation and template rendering
* Notification logging and tracking
* Error handling with fallback mechanisms

### 4. Admin Panel
* WhatsApp Settings page (credentials configuration)
* Template Library page (view and copy template JSONs)
* Template Mapping page (map notification types to templates)
* Notification Logs page (view and filter sent notifications)

## Database Schema

### whatsapp_settings
* restaurant_id, waba_id, access_token (encrypted), phone_number_id, business_phone_number, is_enabled

### whatsapp_template_mappings
* restaurant_id, notification_type, template_name, template_id, language_code, is_active

### whatsapp_template_definitions
* notification_type, template_name, category, description, template_json, sample_variables

### whatsapp_notification_logs
* restaurant_id, notification_type, recipient_phone, template_name, variables, status, whatsapp_message_id, error_message

## Implementation Flow

### Setup Flow
1. Admin enters WABA credentials
2. Views template library and copies template JSON
3. Creates template on WhatsApp Business Portal (same name)
4. Waits for WhatsApp approval (24 hours)
5. Maps notification type to template name in system
6. Activates notification type

### Notification Flow
1. System event triggered (e.g., NewOrderCreated)
2. Event listener checks if WhatsApp enabled
3. Gets template mapping for notification type
4. Prepares variables
5. Sends via WhatsApp Business API
6. Logs notification status

## Notification Templates (29 Total)

### Customer (11)
order_confirmation, order_status_update, order_cancelled, order_bill_invoice, payment_confirmation, payment_reminder, reservation_confirmation, reservation_reminder, reservation_status_update, waiter_request_acknowledgment, table_ready_notification

### Admin (5)
new_order_alert, sales_report, low_inventory_alert, subscription_expiry_reminder, new_restaurant_signup

### Staff (9)
new_kot_notification, order_modification_alert, order_cancellation_alert, waiter_request, table_assignment, order_ready_to_serve, payment_request_alert, table_status_change, daily_operations_summary

### Delivery (3)
delivery_assignment, order_ready_for_pickup, delivery_completion_confirmation

### Automated (1)
reservation_followup

## Technical Requirements

* Module structure following existing pattern (Modules/WhatsAppNotifications/)
* Services: WhatsAppService, WhatsAppTemplateService, WhatsAppNotificationService
* 29 notification classes organized by category
* Event listeners for Order, Reservation, Payment, Waiter events
* Admin panel UI with 4 main pages
* Database migrations and models
* Template definitions seeder
* Error handling and logging
* Multi-language support

## Acceptance Criteria

* [ ] Restaurant admin can configure WhatsApp Business API credentials
* [ ] Admin can view all 29 template JSONs for copy-paste
* [ ] Admin can map notification types to WhatsApp template names
* [ ] Admin can enable/disable individual notification types
* [ ] System sends notifications when events are triggered
* [ ] All notifications are logged with status
* [ ] Failed notifications handled gracefully
* [ ] Template names follow WhatsApp naming conventions
* [ ] Multi-language support for templates
* [ ] All code tested and documented

## Important Notes

* Templates must be approved by WhatsApp (24 hours) - handle pending templates gracefully
* All restaurants can use same template names (each has own WABA)
* Implement fallback mechanism (log errors, optionally fallback to email)
* Use WhatsApp Business API test environment during development
* Implement queuing for bulk notifications (rate limits)
* Ensure customer opt-in compliance

## Dependencies

* WhatsApp Business API account (restaurant must have)
* WhatsApp Business Portal access
* Existing event system
* Restaurant model and settings
* Permission system
* Module system

## Estimated Timeline

16-20 days (can be broken into subtasks for parallel development)

