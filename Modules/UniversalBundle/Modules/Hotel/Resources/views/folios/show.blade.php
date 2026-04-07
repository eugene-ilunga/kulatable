@extends('layouts.app')

@section('content')

@livewire('hotel::folio-view', ['stayId' => $stayId])

@endsection
