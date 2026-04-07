<form wire:submit="save" class="space-y-4">
    <div>
        <x-label for="extra_name" value="{{ __('hotel::modules.settings.extraName') }}" required />
        <x-input id="extra_name" type="text" wire:model="name" class="block mt-1 w-full" />
        <x-input-error for="name" class="mt-2" />
    </div>
    <div>
        <x-label for="extra_price" value="{{ __('hotel::modules.settings.extraPrice') }}" required />
        <x-input id="extra_price" type="number" wire:model="price" step="0.01" min="0" class="block mt-1 w-full" />
        <x-input-error for="price" class="mt-2" />
    </div>
    <div class="flex items-center gap-2">
        <x-checkbox id="extra_active" wire:model="is_active" />
        <x-label for="extra_active" value="{{ __('hotel::modules.settings.active') }}" class="!mb-0" />
    </div>
    <div class="flex justify-end gap-2 pt-2">
        <x-secondary-button type="button" wire:click="$dispatch('hotelExtraSaved')">{{ __('app.cancel') }}</x-secondary-button>
        <x-button type="submit">{{ __('app.save') }}</x-button>
    </div>
</form>
