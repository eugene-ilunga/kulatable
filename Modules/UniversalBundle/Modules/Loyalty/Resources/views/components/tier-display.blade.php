@props(['currentTier', 'nextTier', 'pointsToNextTier', 'tierProgress', 'availableLoyaltyPoints'])

@if($currentTier)
    <div class="mt-4 p-4 rounded-lg shadow-sm border-2" style="background: linear-gradient(135deg, {{ $currentTier->color }}10 0%, {{ $currentTier->color }}20 100%); border-color: {{ $currentTier->color }}30;">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg" style="background-color: {{ $currentTier->color }};">
                    {{ strtoupper(substr($currentTier->name, 0, 1)) }}
                </div>
                
                <div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('loyalty::app.currentTier') }}</div>
                    <div class="text-xl font-bold" style="color: {{ $currentTier->color }};">{{ $currentTier->name }}</div>
                </div>
            </div>
            @if($nextTier)
                <div class="text-right">
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('loyalty::app.nextTier') }}</div>
                    <div class="text-sm font-semibold" style="color: {{ $nextTier->color }};">{{ $nextTier->name }}</div>
                </div>
            @else
                <div class="text-right">
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('loyalty::app.highestTier') }}</div>
                </div>
            @endif
        </div>
        
        @if($nextTier && $pointsToNextTier !== null)
        <div class="mt-3">
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1.5">
                <span>{{ __('loyalty::app.tierProgress') }}</span>
                <span class="font-semibold">{{ number_format($tierProgress, 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                <div class="h-2.5 rounded-full transition-all duration-300" style="width: {{ $tierProgress }}%; background-color: {{ $currentTier->color }};"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs">
                <span class="text-gray-600 dark:text-gray-400">
                    {{ number_format($availableLoyaltyPoints ?? 0) }} {{ __('loyalty::app.points') }}
                </span>
                @if($pointsToNextTier > 0)
                    <span class="font-medium" style="color: {{ $nextTier->color }};">
                        {{ __('loyalty::app.pointsToNextTier') }}: {{ number_format($pointsToNextTier) }}
                    </span>
                @endif
            </div>
        </div>
        @elseif(!$nextTier)
        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
            {{ __('loyalty::app.highestTierAchieved') }} - {{ number_format($availableLoyaltyPoints ?? 0) }} {{ __('loyalty::app.points') }}
        </div>
        @endif
    </div>
@endif
