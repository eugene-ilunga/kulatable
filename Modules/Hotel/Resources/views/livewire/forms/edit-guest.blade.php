<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="first_name" value="{{ __('hotel::modules.guest.firstName') }}" required />
                    <x-input id="first_name" class="block mt-1 w-full" type="text" autofocus wire:model='first_name' />
                    <x-input-error for="first_name" class="mt-2" />
                </div>

                <div>
                    <x-label for="last_name" value="{{ __('hotel::modules.guest.lastName') }}" />
                    <x-input id="last_name" class="block mt-1 w-full" type="text" wire:model='last_name' />
                    <x-input-error for="last_name" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="email" value="{{ __('hotel::modules.guest.email') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" wire:model='email' />
                    <x-input-error for="email" class="mt-2" />
                </div>

                <div>
                    <x-label for="phone" value="{{ __('hotel::modules.guest.phone') }}" required />
                    <x-input id="phone" class="block mt-1 w-full" type="text" wire:model='phone' />
                    <x-input-error for="phone" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="customer_id" value="{{ __('hotel::modules.guest.linkCustomerOptional') }}" />
                <x-select class="mt-1 block w-full" wire:model='customer_id'>
                    <option value="">{{ __('hotel::modules.guest.none') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email ?? $customer->phone }})</option>
                    @endforeach
                </x-select>
                <x-input-error for="customer_id" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="id_type" value="{{ __('hotel::modules.guest.idType') }}" required />
                    <x-select class="mt-1 block w-full" wire:model='id_type'>
                        <option value="">{{ __('hotel::modules.guest.selectIdType') }}</option>
                        <option value="passport">{{ __('hotel::modules.guest.passport') }}</option>
                        <option value="aadhaar">{{ __('hotel::modules.guest.aadhaar') }}</option>
                        <option value="driving_license">{{ __('hotel::modules.guest.drivingLicense') }}</option>
                        <option value="national_id">{{ __('hotel::modules.guest.nationalId') }}</option>
                        <option value="other">{{ __('hotel::modules.guest.other') }}</option>
                    </x-select>
                    <x-input-error for="id_type" class="mt-2" />
                </div>

                <div>
                    <x-label for="id_number" value="{{ __('hotel::modules.guest.idNumber') }}" required />
                    <x-input id="id_number" class="block mt-1 w-full" type="text" wire:model='id_number' />
                    <x-input-error for="id_number" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="edit_id_proof_file" value="{{ __('hotel::modules.guest.idProof') }}" />
                <input type="file" id="edit_id_proof_file" wire:model="id_proof_file"
                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                    class="block mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-skin-base file:text-white hover:file:opacity-90 dark:file:bg-skin-base dark:file:text-white">
                @if($id_proof_file)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.fileChosen') }}: {{ $id_proof_file->getClientOriginalName() }}</p>
                @elseif($guest->id_proof_file ?? null)
                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('hotel::modules.guest.currentFile') }}: {{ basename($guest->id_proof_file) }}</p>
                    <a href="{{ asset_url_local_s3('guest-id-proof/' . $guest->id_proof_file) }}" target="_blank" class="text-xs text-skin-base hover:underline">{{ __('hotel::modules.guest.viewFile') }}</a>
                @endif
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('hotel::modules.guest.idProofHint') }}</p>
                <x-input-error for="id_proof_file" class="mt-2" />
            </div>

            <div>
                <x-label for="address" value="{{ __('hotel::modules.guest.address') }}" />
                <textarea id="address" wire:model="address" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                <x-input-error for="address" class="mt-2" />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <x-label for="city" value="{{ __('hotel::modules.guest.city') }}" />
                    <x-input id="city" class="block mt-1 w-full" type="text" wire:model='city' />
                    <x-input-error for="city" class="mt-2" />
                </div>

                <div>
                    <x-label for="state" value="{{ __('hotel::modules.guest.state') }}" />
                    <x-input id="state" class="block mt-1 w-full" type="text" wire:model='state' />
                    <x-input-error for="state" class="mt-2" />
                </div>

                <div>
                    <x-label for="country" value="{{ __('hotel::modules.guest.country') }}" />
                    <x-input id="country" class="block mt-1 w-full" type="text" wire:model='country' />
                    <x-input-error for="country" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="notes" value="{{ __('hotel::modules.guest.notes') }}" />
                <textarea id="notes" wire:model="notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                <x-input-error for="notes" class="mt-2" />
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button wire:loading.attr="disabled">@lang('app.update')</x-button>
            <x-button-cancel wire:click="$dispatch('hideEditGuest')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>
