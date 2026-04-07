@extends('layouts.guest')

@section('content')

@livewire('loyalty.shop.shop-customer-loyalty-account', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch])

@livewire('customer.signup', ['restaurant' => $restaurant])

@endsection
