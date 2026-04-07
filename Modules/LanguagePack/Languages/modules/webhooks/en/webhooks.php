<?php

return [
    // General
    'module_name' => 'Webhooks',
    'webhooks' => 'Webhooks',
    'webhook' => 'Webhook',
    'system_webhooks' => 'System Webhooks',

    // Dashboard
    'overview' => 'Webhooks Overview',
    'super_admin_only' => 'Super Admin only: tenant-level status',
    'super_admin_visibility' => 'Super Admin visibility across tenants',
    'restaurant' => 'Restaurant',
    'deliveries' => 'Deliveries',
    'failed' => 'Failed',
    'pending' => 'Pending',
    'succeeded' => 'Succeeded',
    'recent_deliveries' => 'Recent Deliveries',
    'latest_for_tenant' => 'Latest :count for selected tenant',
    'latest_count' => 'Latest :count',
    'no_deliveries' => 'No deliveries yet.',

    // Table Headers
    'event' => 'Event',
    'status' => 'Status',
    'attempts' => 'Attempts',
    'response' => 'Response',
    'response_code' => 'Response Code',
    'duration_ms' => 'Duration (ms)',
    'created_at' => 'Created',
    'actions' => 'Actions',

    // Actions
    'view' => 'View',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'create' => 'Create',
    'test' => 'Test',
    'replay' => 'Replay',
    'send_test' => 'Send Test',
    'add_webhook' => 'Add Webhook',
    'create_webhook' => 'Create Webhook',
    'edit_webhook' => 'Edit Webhook',

    // Form Fields
    'name' => 'Name',
    'target_url' => 'Target URL',
    'secret' => 'Secret',
    'secret_hint' => 'Leave empty to auto-generate',
    'is_active' => 'Active',
    'max_attempts' => 'Max Attempts',
    'backoff_seconds' => 'Backoff (seconds)',
    'branch' => 'Branch',
    'all_branches' => 'All Branches',
    'subscribed_events' => 'Subscribed Events',
    'all_events' => 'All Events',
    'source_modules' => 'Source Modules',
    'all_modules' => 'All Modules',
    'redact_payload' => 'Redact Sensitive Data',
    'custom_headers' => 'Custom Headers',
    'description' => 'Description',

    // Delivery Detail
    'delivery_detail' => 'Delivery Detail',
    'payload' => 'Payload',
    'response_body' => 'Response Body',
    'error_message' => 'Error Message',
    'next_retry' => 'Next Retry',
    'idempotency_key' => 'Idempotency Key',

    // Routing Matrix
    'routing_matrix' => 'Routing Matrix',
    'routing_description' => 'Control which events can trigger webhooks for each package',
    'package' => 'Package',
    'allowed_events' => 'Allowed Events',
    'allowed_sources' => 'Allowed Sources',

    // Package Defaults
    'package_defaults' => 'Package Defaults',
    'package_defaults_description' => 'Set default webhook configurations for packages',
    'default_max_attempts' => 'Default Max Attempts',
    'default_backoff' => 'Default Backoff',

    // System Webhooks
    'system_webhooks_description' => 'Platform-wide webhooks that receive events from all tenants',
    'no_system_webhooks' => 'No system webhooks configured.',
    'add_system_webhook' => 'Add System Webhook',

    // Messages
    'webhook_created' => 'Webhook created successfully',
    'webhook_updated' => 'Webhook updated successfully',
    'webhook_deleted' => 'Webhook deleted successfully',
    'test_queued' => 'Test webhook queued',
    'replay_queued' => 'Replay queued',
    'confirm_delete' => 'Are you sure you want to delete this webhook?',
    'confirm_delete_message' => 'This action cannot be undone. All delivery logs will also be deleted.',

    // Status Labels
    'status_pending' => 'Pending',
    'status_succeeded' => 'Succeeded',
    'status_failed' => 'Failed',
    'status_disabled' => 'Disabled',

    // Events
    'event_order_created' => 'Order Created',
    'event_order_updated' => 'Order Updated',
    'event_order_cancelled' => 'Order Cancelled',
    'event_order_paid' => 'Order Paid',
    'event_reservation_received' => 'Reservation Received',
    'event_reservation_confirmed' => 'Reservation Confirmed',
    'event_payment_success' => 'Payment Success',
    'event_payment_failed' => 'Payment Failed',
    'event_restaurant_created' => 'Restaurant Created',
    'event_test' => 'Test Event',

    // Empty States
    'no_webhooks' => 'No webhooks configured',
    'no_webhooks_description' => 'Create your first webhook to start receiving real-time notifications.',
    'get_started' => 'Get Started',

    // Settings Menu
    'settings' => 'Settings',
    'integrations' => 'Integrations',

    // Routing Matrix & Package Defaults
    'routing_matrix_title' => 'Webhook Routing Matrix',
    'routing_matrix_desc' => 'Control which modules/events are allowed globally. Default is allowed.',
    'saving' => 'Saving...',
    'schema_version' => 'Schema v',
    'allowed' => 'Allowed',
    'blocked' => 'Blocked',
    'no_events_found' => 'No events found',

    'pkg_defaults_title' => 'Webhook Package Defaults',
    'pkg_defaults_desc' => 'Control default events and auto-provision per package.',
    'auto_provision' => 'Auto-provision webhook on tenant creation',
    'default_target_url' => 'Default Target URL',
    'default_secret' => 'Default Secret (optional)',
    'auto_generate_hint' => 'Auto-generate if empty',
    'rotate_interval' => 'Rotate Interval (days)',

    // Admin Webhook Manager
    'create_webhook_subtitle' => 'HTTPS recommended; scoped to this restaurant/branch',
    'reset' => 'Reset',
    'refresh' => 'Refresh',
    'configured_webhooks' => 'Configured Webhooks',
    'branch_optional' => 'Branch (optional)',
    'all_branches_hint' => 'All branches if empty',
    'leave_blank_auto' => 'Leave blank to auto-generate',
    'auto_generated' => 'Auto-generated if empty',
];
