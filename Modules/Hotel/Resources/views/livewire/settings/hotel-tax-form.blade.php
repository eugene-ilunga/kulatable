<form wire:submit="save" class="space-y-4">
    <div>
        <x-label for="tax_name" value="{{ __('hotel::modules.settings.taxName') }}" required />
        <x-input id="tax_name" type="text" wire:model="name" class="block mt-1 w-full" />
        <x-input-error for="name" class="mt-2" />
    </div>
    <div>
        <x-label for="tax_rate" value="{{ __('hotel::modules.settings.taxRate') }} (%)" required />
        <x-input id="tax_rate" type="number" wire:model="rate" step="0.01" min="0" max="100" class="block mt-1 w-full" />
        <x-input-error for="rate" class="mt-2" />
    </div>
    <div class="flex items-center gap-2">
        <x-checkbox id="tax_active" wire:model="is_active" />
        <x-label for="tax_active" value="{{ __('hotel::modules.settings.active') }}" class="!mb-0" />
    </div>
    <div class="flex justify-end gap-2 pt-2">
        <x-secondary-button type="button" wire:click="$dispatch('hotelTaxSaved')">{{ __('app.cancel') }}</x-secondary-button>
        <x-button type="submit">{{ __('app.save') }}</x-button>
    </div>
</form>
