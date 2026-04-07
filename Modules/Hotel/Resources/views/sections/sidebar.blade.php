@if(in_array('Hotel', restaurant_modules()) && (user_can('Show Hotel Front Desk') || user_can('Show Hotel Rooms') || user_can('Show Hotel Reservations') || user_can('Show Hotel Guests') || user_can('Show Hotel Stays') || user_can('Show Hotel Housekeeping') || user_can('Show Hotel Banquet') || user_can('Show Hotel Room Service')))
<x-sidebar-dropdown-menu :name='__("hotel::modules.menu.hotel")' isAddon="true" icon='hotel' customIcon='<svg class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" fill="currentColor" width="24" height="24" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <g> <path d="M448,0c-27.872,0-51.562,17.808-60.346,42.667H21.333C9.551,42.667,0,52.218,0,64c0,11.782,9.551,21.333,21.333,21.333 h64V128H42.667c-11.782,0-21.333,9.551-21.333,21.333v341.333c0,11.782,9.551,21.333,21.333,21.333H384 c11.782,0,21.333-9.551,21.333-21.333V149.333c0-11.782-9.551-21.333-21.333-21.333h-42.667V85.333h46.321 C396.438,110.192,420.128,128,448,128c35.355,0,64-28.645,64-64S483.355,0,448,0z M362.667,469.333H64V170.667h298.667V469.333z M298.667,128H128V85.333h170.667V128z M448,85.333c-11.791,0-21.333-9.542-21.333-21.333S436.209,42.667,448,42.667 S469.333,52.209,469.333,64S459.791,85.333,448,85.333z"></path> <path d="M277.333,213.333c-11.782,0-21.333,9.551-21.333,21.333v64h-85.333v-64c0-11.782-9.551-21.333-21.333-21.333 c-11.782,0-21.333,9.551-21.333,21.333v170.667c0,11.782,9.551,21.333,21.333,21.333c11.782,0,21.333-9.551,21.333-21.333v-64 H256v64c0,11.782,9.551,21.333,21.333,21.333c11.782,0,21.333-9.551,21.333-21.333V234.667 C298.667,222.885,289.115,213.333,277.333,213.333z"></path> </g> </g> </g> </g>
  </svg>' :active='request()->routeIs(["hotel.*"])'>

    @if(user_can('Show Hotel Front Desk'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.frontDeskDashboard'), 'link' => route('hotel.front-desk.dashboard'), 'active' => request()->routeIs('hotel.front-desk.*')])
    @endif
   
    @if(user_can('Show Hotel Room Types'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.roomTypes'), 'link' => route('hotel.room-types.index'), 'active' => request()->routeIs('hotel.room-types.*')])
    @endif

    @if(user_can('Show Hotel Rooms'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.rooms'), 'link' => route('hotel.rooms.index'), 'active' => request()->routeIs('hotel.rooms.index')])
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.roomStatusBoard'), 'link' => route('hotel.rooms.status-board'), 'active' => request()->routeIs('hotel.rooms.status-board')])
    @endif
    
    @if(user_can('Show Hotel Guests'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.guests'), 'link' => route('hotel.guests.index'), 'active' => request()->routeIs('hotel.guests.*')])
    @endif
    
    @if(user_can('Show Hotel Reservations'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.reservations'), 'link' => route('hotel.reservations.index'), 'active' => request()->routeIs('hotel.reservations.*')])
    @endif
    
    @if(user_can('Check In Hotel Guest'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.checkIn'), 'link' => route('hotel.check-in.index'), 'active' => request()->routeIs('hotel.check-in.*')])
    @endif
    
    @if(user_can('Check Out Hotel Guest'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.checkOut'), 'link' => route('hotel.check-out.index'), 'active' => request()->routeIs('hotel.check-out.*')])
    @endif
    
    @if(user_can('Show Hotel Rate Plans'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.ratePlans'), 'link' => route('hotel.rate-plans.index'), 'active' => request()->routeIs('hotel.rate-plans.*')])
    @endif
    
    @if(user_can('Show Hotel Housekeeping'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.housekeeping'), 'link' => route('hotel.housekeeping.index'), 'active' => request()->routeIs('hotel.housekeeping.*')])
    @endif
    
    @if(user_can('Show Hotel Room Service'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.roomService'), 'link' => route('hotel.room-service.index'), 'active' => request()->routeIs('hotel.room-service.*')])
    @endif

    @if(user_can('Show Hotel Stays'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.stays'), 'link' => route('hotel.stays.index'), 'active' => request()->routeIs('hotel.stays.*')])
    @endif
    
    @if(user_can('Show Hotel Banquet'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.menu.banquetEvents'), 'link' => route('hotel.banquet.index'), 'active' => request()->routeIs('hotel.banquet.*')])
    @endif

    @if(user_can('Show Hotel Reservations'))
    @livewire('sidebar-dropdown-menu', ['name' => __('hotel::modules.settings.hotelSettings'), 'link' => route('hotel.settings.index'), 'active' => request()->routeIs('hotel.settings.index')])
    @endif

</x-sidebar-dropdown-menu>
@endif
