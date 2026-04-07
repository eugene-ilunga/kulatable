<?php

return [
    'settings_title' => 'MultiPOS',
    'help_text' => 'Manage and configure your POS machines',

    'info' => [
        'registration_title' => 'Machine Registration',
        'registration_text' => "When users access the POS page without a registered device, they'll be prompted to register it. New registrations appear as \"Pending\" and require approval.",
        'status_title' => 'Status Guide',
        'status_active' => 'Active',
        'status_pending' => 'Pending',
        'status_declined' => 'Declined',
        'status_active_text' => 'Approved and can process orders',
        'status_pending_text' => 'Awaiting approval',
        'status_declined_text' => 'Access denied, please contact administrator',
    ],

    'table' => [
        'registered_for_branch' => 'Registered Machines for Branch: :branch',
        'alias' => 'Alias',
        'machine_id' => 'Machine ID',
        'status' => 'Status',
        'last_seen' => 'Last Seen',
        'registered' => 'Registered',
        'actions' => 'Actions',
        'no_alias' => 'No alias',
        'never' => 'Never',
        'no_machines' => 'No machines registered',
        'no_machines_hint' => 'Devices will appear here when users register them from the POS page.',
    ],

    'actions' => [
        'approve' => 'Approve',
        'decline' => 'Decline',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    'usage' => [
        'title' => 'POS Machine Usages (:monthYear)',
        'no_data' => 'No POS machines found for this restaurant in :monthYear',
        'headers' => [
            'no' => 'No.',
            'alias' => 'Machine Alias',
            'branch' => 'Branch',
            'machine_id' => 'Machine ID',
            'status' => 'Status',
            'orders_current_month' => 'Orders (Current Month)',
            'revenue_current_month' => 'Revenue (Current Month)',
            'last_seen' => 'Last Seen',
        ],
    ],

    'machines' => [
        'title' => 'POS Machines Management',
        'pending_approvals' => 'Pending Approvals (:count)',
        'filters' => [
            'all' => 'All Machines',
            'active' => 'Active',
            'pending' => 'Pending',
            'declined' => 'Declined',
        ],
        'table' => [
            'alias' => 'Alias',
            'machine_id' => 'Machine ID',
            'status' => 'Status',
            'last_seen' => 'Last Seen',
            'created' => 'Created',
            'actions' => 'Actions',
            'no_alias' => 'No alias',
        ],
        'buttons' => [
            'view' => 'View',
            'approve' => 'Approve',
            'edit' => 'Edit',
            'decline' => 'Decline',
        ],
        'empty' => [
            'title' => 'No machines registered',
            'hint' => 'Devices will appear here once they are registered from the POS page.',
        ],
        'modal' => [
            'title' => 'Machine Details',
            'statistics' => 'Statistics',
            'total_orders' => "Total Orders",
            'today_orders' => "Today's Orders",
        ],
        'confirm' => [
            'approve' => 'Approve this POS machine?',
            'decline' => 'Disable this POS machine? It will need to re-register.',
        ],
    ],
    'reports' => [
        'title' => 'POS Machine Report',
        'filter' => 'Filter',
        'export_csv' => 'Export CSV',
        'customDateRange' => 'Custom Date Range',
        'pos_only' => 'POS Wise Only',
        'pos_machine' => 'POS Machine',
        'cards' => [
            'total_machines' => 'Total Machines',
            'total_orders' => 'Total Orders',
            'net_sales' => 'Net Sales',
            'avg_order_value' => 'Avg Order Value',
        ],
        'table' => [
            'title' => 'Machine Performance',
            'pos_machine' => 'POS Machine',
            'orders' => 'Orders',
            'net_sales' => 'Net Sales',
            'avg_order' => 'Avg Order',
            'cash_sales' => 'Cash Sales',
            'card_upi_sales' => 'Card/UPI Sales',
            'refunds' => 'Refunds',
        ],
        'empty' => [
            'title' => 'No data available',
            'hint' => 'Try selecting a different date range.',
        ],
    ],

    // POS Registration and Status Messages
    'registration' => [
        'declined' => [
            'title' => 'Registration Declined',
            'message' => 'Your POS registration request has been declined. Please contact your administrator for assistance.',
            'check_status' => 'Check Status Again',
        ],
        'pending' => [
            'title' => 'Registration Pending',
            'message' => 'Waiting for admin approval. POS access is currently blocked until your device is approved.',
            'refresh_status' => 'Refresh Status',
            'approve_this_machine' => 'Approve This Machine',
            'go_to_settings' => 'Go to Settings',
        ],
        'active' => [
            'label' => 'POS:',
        ],
        'limit_reached' => [
            'title' => 'POS Machine Limit Reached',
            'message' => 'POS machine limit reached for this branch (limit: :limit). Please contact your administrator.',
            'what_can_you_do' => 'What can you do?',
            'hint' => 'Contact your administrator to increase the POS machine limit or remove inactive machines.',
        ],
        'form' => [
            'title' => 'Register This Device as a POS',
            'description' => 'To use the POS system, this device must be registered. All orders will be linked to this device.',
            'select_branch' => 'Select Branch',
            'select_branch_placeholder' => 'Choose a branch...',
            'select_branch_error' => 'Please select a branch',
            'device_name' => 'Device Name (Optional)',
            'device_name_placeholder' => 'e.g., Counter 1, Bar POS, Kiosk 1',
            'device_name_hint' => 'Give this device a friendly name for easy identification.',
            'what_happens_next' => 'What happens next?',
            'what_happens_next_text' => 'After registration, you\'ll need admin approval before the device becomes active.',
            'register_button' => 'Register Device',
            'cancel_button' => 'Cancel & Go Back',
            'go_to_dashboard' => 'Go to Dashboard',
        ],
        'device' => 'Device',
        'already_registered' => 'This device is already registered as :alias.',
        'limit_reached_error' => 'POS machine limit reached for this branch (limit: :limit). Please contact your administrator.',
        'registered_success' => 'POS device registered and activated successfully as :alias (:public_id)',
        'registered_pending' => 'Your registration request has been submitted. Waiting for admin approval. You will be able to use the POS once approved.',
    ],

    // Settings Component Messages
    'settings' => [
        'machine_not_pending' => 'Machine is not pending approval',
        'user_not_found' => 'User not found',
        'machine_approved' => 'Machine approved successfully',
        'machine_already_declined' => 'Machine is already declined',
        'machine_declined' => 'Machine declined successfully',
        'alias_required' => 'Alias is required',
        'machine_updated' => 'Machine updated successfully',
        'machine_deleted' => 'Machine deleted successfully',
        'delete_machine_title' => 'Delete POS Machine?',
        'delete_machine_message' => 'Are you sure you want to delete this POS machine? This action cannot be undone.',
    ],

    // JavaScript Messages
    'js' => [
        'network_error' => 'Network response was not ok',
        'error_checking_limit' => 'Error checking branch limit',
        'limit_reached_message' => 'POS machine limit reached for this branch (limit: :limit). Please contact your administrator.',
        'error_loading_report' => 'Error loading report data. Please try again.',
        'edit_coming_soon' => 'Edit functionality coming soon',
        'unnamed' => 'Unnamed',
    ],

    // Dashboard/Index Page
    'dashboard' => [
        'title' => 'MultiPOS Management',
        'manage_terminals' => 'Manage Terminals',
        'settings' => 'Settings',
        'pos_machines_branch' => 'POS Machines (Branch)',
        'unlimited' => 'Unlimited',
        'active_limit' => '(Active / Limit)',
        'pending_label' => 'Pending:',
        'total_terminals' => 'Total Terminals',
        'active_orders' => 'Active Orders',
        'todays_revenue' => 'Today\'s Revenue',
        'total_tables' => 'Total Tables',
        'quick_actions' => 'Quick Actions',
        'pos_terminal' => 'POS Terminal',
        'take_orders' => 'Take orders',
        'configure_pos_terminals' => 'Configure POS terminals',
        'configure_multipos' => 'Configure MultiPOS',
        'branch_label' => 'Branch:',
        'used_label' => 'Used:',
    ],

    // Claim/Registration Page
    'claim' => [
        'title' => 'Register This Device as a POS',
        'description' => 'This will register your current device to track orders and cash register sessions.',
        'branch_label' => 'Branch',
        'branch_required' => 'Branch',
        'select_branch' => 'Select a branch',
        'device_alias_label' => 'Device Alias (Optional)',
        'device_alias_placeholder' => 'e.g., Counter 1, Bar POS, Kiosk 1',
        'device_alias_hint' => 'Give this device a friendly name for easy identification.',
        'what_happens_next_title' => 'What happens next?',
        'what_happens_next_message' => 'After registration, this device will be tracked for all orders and transactions. Your admin may need to approve this device before it becomes active.',
        'cancel' => 'Cancel',
        'register_device' => 'Register Device',
        'select_branch_alert' => 'Please select a branch',
    ],

    // Terminals Page
    'terminals' => [
        'title' => 'POS Terminals Management',
        'pos_terminals' => 'POS Terminals',
        'add_terminal' => 'Add Terminal',
        'back_to_dashboard' => 'Back to Dashboard',
        'edit_terminal' => 'Edit Terminal',
        'delete_terminal' => 'Delete',
        'no_terminals' => 'No terminals',
        'get_started' => 'Get started by creating a new POS terminal.',
        'terminal_name' => 'Terminal Name',
        'type' => 'Type',
        'active' => 'Active',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'delete_confirm' => 'Are you sure you want to delete this terminal?',
        'table' => [
            'name' => 'Name',
            'type' => 'Type',
            'printer' => 'Printer',
            'status' => 'Status',
            'default' => 'Default',
            'actions' => 'Actions',
        ],
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'type_options' => [
            'food' => 'Food',
            'beverage' => 'Beverage',
            'general' => 'General',
        ],
        'no_printer' => 'No Printer',
        'default' => 'Default',
    ],

    // Package Settings
    'package' => [
        'multiposLimit' => 'MultiPOS Machine Limit',
        'multiposLimitInfo' => 'This is a branch-wise limit. Enter -1 for unlimited POS machines per branch. Leave blank or set a positive number to set a limit per branch.',
    ],

    // Notifications
    'notifications' => [
        'pos_request' => [
            'subject' => 'New POS Machine Registration Request',
            'push_title' => 'POS Registration Request',
            'push_message' => ':alias has requested POS access for :branch branch',
            'text1' => 'A new POS machine registration request has been submitted.',
            'text2' => 'Please review and approve or decline the request:',
            'text3' => 'You can manage all POS machine requests from the MultiPOS settings page.',
            'action' => 'Review Request',
        ],
    ],
];
