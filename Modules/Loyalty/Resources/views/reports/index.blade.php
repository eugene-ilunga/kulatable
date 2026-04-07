@extends('layouts.app')

@section('content')
<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-0">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    @lang('loyalty::app.loyaltyReportsTitle')
                </h1>
            </div>
        </div>
    </div>

    <div class="p-4" x-data="{tab:'overview'}">
        <div class="border-b dark:border-gray-700">
            <nav class="-mb-px flex flex-wrap gap-2" aria-label="Tabs">
                <button @click="tab='overview'" :class="tab==='overview' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">
                    @lang('loyalty::app.loyaltyOverviewReport')
                </button>
                <button @click="tab='ledger'" :class="tab==='ledger' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">
                    @lang('loyalty::app.pointsLedgerReport')
                </button>
                <button @click="tab='redemption'" :class="tab==='redemption' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">
                    @lang('loyalty::app.redemptionReport')
                </button>
                <button @click="tab='stamps'" :class="tab==='stamps' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">
                    @lang('loyalty::app.stampPerformanceReport')
                </button>
                <button @click="tab='liability'" :class="tab==='liability' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">
                    @lang('loyalty::app.loyaltyLiabilityReport')
                </button>
            </nav>
        </div>
        <div class="space-y-6 mt-6">
            <div x-show="tab==='overview'">
                @livewire('loyalty::reports.loyalty-overview')
            </div>
            <div x-show="tab==='ledger'">
                @livewire('loyalty::reports.points-ledger')
            </div>
            <div x-show="tab==='redemption'">
                @livewire('loyalty::reports.redemption-report')
            </div>
            <div x-show="tab==='stamps'">
                @livewire('loyalty::reports.stamp-performance')
            </div>
            <div x-show="tab==='liability'">
                @livewire('loyalty::reports.loyalty-liability')
            </div>
        </div>
    </div>

</div>
@endsection
