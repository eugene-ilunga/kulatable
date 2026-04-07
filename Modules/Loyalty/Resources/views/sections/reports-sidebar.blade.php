@livewire('sidebar-dropdown-menu', [
    'name' => __('loyalty::app.loyaltyReportsTitle'),
    'link' => route('loyalty.reports.index'),
    'active' => request()->routeIs('loyalty.reports.index')
])
