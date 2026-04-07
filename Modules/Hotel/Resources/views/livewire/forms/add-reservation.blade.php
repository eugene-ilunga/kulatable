<div>
    <div>
        <div id="add-reservation-form" wire:key="add-reservation-form">
            
            @csrf
            <div class="flex flex-col lg:flex-row gap-6">
                {{-- Main form --}}
                <div class="flex-1 space-y-6">
                    {{-- Guests section - Multiple guests --}}
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('hotel::modules.reservation.guestDetails') }}</span>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-600 space-y-4" wire:key="reservation-single-guest-add">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-label :value="__('hotel::modules.guest.firstName')" required />
                                        <x-input type="text" wire:model.live="guests.0.first_name" class="block mt-1 w-full" />
                                        <x-input-error for="guests.0.first_name" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-label :value="__('hotel::modules.guest.lastName')" />
                                        <x-input type="text" wire:model.live="guests.0.last_name" class="block mt-1 w-full" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-label :value="__('hotel::modules.guest.email')" />
                                        <x-input type="email" wire:model.live="guests.0.email" class="block mt-1 w-full" />
                                        <x-input-error for="guests.0.email" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-label :value="__('hotel::modules.guest.phone')" required />
                                        <x-input type="text" wire:model.live="guests.0.phone" class="block mt-1 w-full" />
                                        <x-input-error for="guests.0.phone" class="mt-2" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-label :value="__('hotel::modules.guest.idType')" required />
                                        <x-select wire:model.live="guests.0.id_type" class="block mt-1 w-full">
                                            <option value="passport">{{ __('hotel::modules.guest.passport') }}</option>
                                            <option value="aadhaar">{{ __('hotel::modules.guest.aadhaar') }}</option>
                                            <option value="driving_license">{{ __('hotel::modules.guest.drivingLicense') }}</option>
                                            <option value="national_id">{{ __('hotel::modules.guest.nationalId') }}</option>
                                            <option value="other">{{ __('hotel::modules.guest.other') }}</option>
                                        </x-select>
                                    </div>
                                    <div>
                                        <x-label :value="__('hotel::modules.guest.idNumber')" required />
                                        <x-input type="text" wire:model.live="guests.0.id_number" class="block mt-1 w-full" />
                                        <x-input-error for="guests.0.id_number" class="mt-2" />
                                    </div>
                                </div>
                                <div>
                                    <x-label :value="__('hotel::modules.guest.idProof')" />
                                    <input type="file" wire:model.live="guests.0.id_proof_file"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                                        class="block mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-skin-base file:text-white hover:file:opacity-90">
                                    @if(isset($guests[0]['id_proof_file']) && is_object($guests[0]['id_proof_file']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $guests[0]['id_proof_file']->getClientOriginalName() }}</p>
                                    @endif
                                    <x-input-error for="guests.0.id_proof_file" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stay details --}}
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('hotel::modules.checkIn.stayDetails') }}</span>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-label for="check_in_date" value="{{ __('hotel::modules.reservation.checkInDate') }}" required />
                                    <x-input id="check_in_date" type="date" wire:model.live="check_in_date" class="block mt-1 w-full" />
                                    <x-input-error for="check_in_date" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="check_out_date" value="{{ __('hotel::modules.reservation.checkOutDate') }}" required />
                                    <x-input id="check_out_date" type="date" wire:model.live="check_out_date" class="block mt-1 w-full" />
                                    <x-input-error for="check_out_date" class="mt-2" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-label for="check_in_time" value="{{ __('hotel::modules.reservation.checkInTime') }}" />
                                    <x-input id="check_in_time" type="time" wire:model.live="check_in_time" class="block mt-1 w-full" />
                                </div>
                                <div>
                                    <x-label for="check_out_time" value="{{ __('hotel::modules.reservation.checkOutTime') }}" />
                                    <x-input id="check_out_time" type="time" wire:model.live="check_out_time" class="block mt-1 w-full" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-label for="adults" value="{{ __('hotel::modules.reservation.adults') }}" required />
                                    <x-input id="adults" type="number" min="1" wire:model.live="adults" class="block mt-1 w-full" />
                                </div>
                                <div>
                                    <x-label for="children" value="{{ __('hotel::modules.reservation.children') }}" />
                                    <x-input id="children" type="number" min="0" wire:model.live="children" class="block mt-1 w-full" />
                                </div>
                            </div>
                            <div>
                                <x-label for="rate_plan_id" value="{{ __('hotel::modules.reservation.ratePlan') }}" />
                                <x-select id="rate_plan_id" wire:model.live="rate_plan_id" class="block mt-1 w-full">
                                    <option value="">{{ __('hotel::modules.reservation.selectRatePlan') }}</option>
                                    @foreach($ratePlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->type->label() }})</option>
                                    @endforeach
                                </x-select>
                            </div>
                        </div>
                    </div>

                    {{-- Travel info --}}
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('hotel::modules.reservation.travelInfo') }}</span>
                        </div>
                        <div class="p-4 space-y-4">
                            <div>
                                <x-label for="reason_for_trip" value="{{ __('hotel::modules.reservation.reasonForTrip') }}" />
                                <textarea id="reason_for_trip" wire:model.live="reason_for_trip" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <x-label for="place_of_origin" value="{{ __('hotel::modules.guest.placeOfOrigin') }}" />
                                    <x-input id="place_of_origin" type="text" wire:model.live="place_of_origin" class="block mt-1 w-full" />
                                </div>
                                <div>
                                    <x-label for="vehicle_registration_number" value="{{ __('hotel::modules.guest.vehicleRegistrationNumber') }}" />
                                    <x-input id="vehicle_registration_number" type="text" wire:model.live="vehicle_registration_number" class="block mt-1 w-full" />
                                </div>
                                <div>
                                    <x-label for="means_of_transport" value="{{ __('hotel::modules.guest.meansOfTransport') }}" />
                                    <x-select id="means_of_transport" wire:model.live="means_of_transport" class="block mt-1 w-full">
                                        <option value="">{{ __('hotel::modules.guest.selectMeansOfTransport') }}</option>
                                        <option value="car">{{ __('hotel::modules.guest.car') }}</option>
                                        <option value="bus">{{ __('hotel::modules.guest.bus') }}</option>
                                        <option value="plane">{{ __('hotel::modules.guest.plane') }}</option>
                                        <option value="train">{{ __('hotel::modules.guest.train') }}</option>
                                        <option value="motorcycle">{{ __('hotel::modules.guest.motorcycle') }}</option>
                                        <option value="other">{{ __('hotel::modules.guest.other') }}</option>
                                    </x-select>
                                </div>
                                <div>
                                    <x-label for="final_destination" value="{{ __('hotel::modules.reservation.finalDestination') }}" />
                                    <x-input id="final_destination" type="text" wire:model.live="final_destination" class="block mt-1 w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Rooms --}}
                    @if(count($availableRooms) > 0)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('hotel::modules.reservation.selectRooms') }}</span>
                        </div>
                        <div class="p-4 space-y-2 max-h-64 overflow-y-auto">
                            @foreach($availableRooms as $roomTypeId => $roomData)
                            <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-600 rounded-lg" wire:key="room-{{ $roomTypeId }}">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $roomData['room_type_name'] }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('hotel::modules.reservation.available') }}: {{ $roomData['available'] }} | {{ __('hotel::modules.reservation.rate') }}: {{ currency_format($roomData['rate']) }}/{{ __('hotel::modules.reservation.night') }}
                                    </div>
                                </div>
                                    <div class="flex items-center gap-2">
                                        <input type="number" wire:model.live="selectedRooms.{{ $roomTypeId }}.quantity" wire:key="selectedRooms.{{ $roomTypeId }}.quantity" min="0" max="{{ $roomData['available'] }}" class="w-20 border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-md shadow-sm" placeholder="Qty" />
                                        <input type="number" wire:model.live="selectedRooms.{{ $roomTypeId }}.rate" wire:key="selectedRooms.{{ $roomTypeId }}.rate" step="0.01" min="0" class="w-32 border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-md shadow-sm" placeholder="{{ __('hotel::modules.reservation.rate') }}" />
                                    </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-800">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ __('hotel::modules.reservation.selectDatesMessage') }}</p>
                    </div>
                    @endif

                    {{-- Extras --}}
                    @if(count($extraServices) > 0)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('hotel::modules.reservation.extras') }}</span>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach($extraServices as $extra)
                            <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-600 rounded-lg" wire:key="reservation-extra-add-{{ $extra->id }}">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $extra->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ currency_format($extra->price) }}</div>
                                </div>
                                    <div class="flex items-center gap-2">
                                    <input type="number" wire:model.live.debounce.250ms="selectedExtras.{{ $extra->id }}.quantity" min="0" class="w-20 border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-md shadow-sm" placeholder="Qty" />
                                    <input type="number" wire:model.live.debounce.250ms="selectedExtras.{{ $extra->id }}.unit_price" step="0.01" min="0" class="w-28 border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-md shadow-sm" placeholder="Price" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div>
                        <x-label for="special_requests" value="{{ __('hotel::modules.reservation.specialRequests') }}" />
                        <textarea id="special_requests" wire:model.live="special_requests" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                    </div>
                </div>

                {{-- Sidebar - Pricing summary --}}
                <div class="lg:w-80 xl:w-96 shrink-0">
                    <div class="sticky top-4 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('hotel::modules.reservation.bookingSummary') }}</span>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.roomsPrice') }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currency_format($roomsTotal) }}</span>
                            </div>
                            @if($extrasTotal > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.extrasPrice') }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currency_format($extrasTotal) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-sm pt-2 border-t border-dashed border-gray-200 dark:border-gray-600">
                                <span class="text-gray-600 dark:text-gray-300 font-medium">{{ __('hotel::modules.reservation.subtotal') }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currency_format($subTotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.discount') }}</span>
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model.live="apply_discount" class="rounded" />
                                </div>
                            </div>
                            @if($apply_discount)
                            <div wire:key="reservation-add-discount-controls">
                            <div class="flex justify-between text-sm items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400"></span>
                                <div class="flex items-center gap-2 flex-wrap justify-end">
                                    <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                                        <input type="radio" wire:model.live="discount_type" value="percentage" class="rounded" />
                                        {{ __('hotel::modules.reservation.percentage') }}
                                    </label>
                                    <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                                        <input type="radio" wire:model.live="discount_type" value="fixed" class="rounded" />
                                        {{ __('hotel::modules.reservation.fixedAmount') }}
                                    </label>
                                    <x-input type="number" wire:model.live="discount_value" step="0.01" min="0" class="w-24 !py-1 !text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500" />
                                </div>
                            </div>
                            </div>
                            @endif
                            @if($apply_discount && $discount_value > 0)
                            <div wire:key="reservation-add-discount-amount">
                            <div class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                                <span>{{ __('hotel::modules.reservation.discountAmount') }}</span>
                                <span class="text-gray-900 dark:text-gray-100">- {{ currency_format($discountAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.amountAfterDiscount') }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currency_format($amountAfterDiscount) }}</span>
                            </div>
                            </div>
                            @endif
                            <div>
                                <x-label for="tax_id" value="{{ __('hotel::modules.reservation.bookingTax') }}" class="!text-sm" />
                                <x-select id="tax_id" wire:model.live="tax_id" class="block mt-1 w-full">
                                    <option value="">{{ __('hotel::modules.reservation.noTax') }}</option>
                                    @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </x-select>
                            </div>
                            @if($tax_id)
                            <div wire:key="reservation-add-tax-amount">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.taxAmount') }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ currency_format($taxAmount) }}</span>
                            </div>
                            </div>
                            @endif
                            <div class="flex justify-between text-base font-bold pt-3 border-t border-gray-200 dark:border-gray-600">
                                <span class="text-gray-900 dark:text-gray-100">{{ __('hotel::modules.reservation.total') }}</span>
                                <span class="text-skin-base dark:text-skin-base">{{ currency_format($totalAmount) }}</span>
                            </div>
                            <div>
                                <x-label for="advance_paid" value="{{ __('hotel::modules.reservation.advancePaid') }}" class="!text-sm" />
                                <x-input id="advance_paid" type="number" step="0.01" min="0" wire:model.live="advance_paid" class="block mt-1 w-full" />
                            </div>
                            @if((float) $advance_paid > 0)
                            <div wire:key="reservation-add-advance-method">
                            <div>
                                <x-label for="advance_payment_method" value="{{ __('hotel::modules.checkOut.paymentMethod') }}" class="!text-sm" />
                                <x-select id="advance_payment_method" wire:model.live="advance_payment_method" class="block mt-1 w-full">
                                    <option value="">Select</option>
                                    <option value="cash">{{ __('hotel::modules.checkOut.cash') }}</option>
                                    <option value="card">{{ __('hotel::modules.checkOut.card') }}</option>
                                    <option value="upi">{{ __('hotel::modules.checkOut.upi') }}</option>
                                    <option value="bank_transfer">{{ __('hotel::modules.checkOut.bankTransfer') }}</option>
                                    <option value="other">{{ __('hotel::modules.checkOut.other') }}</option>
                                </x-select>
                                <x-input-error for="advance_payment_method" class="mt-2" />
                            </div>
                            </div>
                            @endif
                            <div class="flex justify-between text-sm pt-1">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.remainingAtCheckout') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ currency_format($remainingBalance) }}</span>
                            </div>
                            <div>
                                <x-label for="status" value="{{ __('hotel::modules.reservation.status') }}" required class="!text-sm" />
                                <x-select id="status" wire:model.live="status" class="block mt-1 w-full">
                                    @foreach($statuses as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                    @endforeach
                                </x-select>
                            </div>
                            <div class="flex flex-col gap-2 pt-4">
                                <x-button type="button" wire:click.prevent="submitForm" wire:loading.attr="disabled" class="w-full">{{ __('hotel::modules.reservation.createReservation') }}</x-button>
                    
                                <a href="{{ route('hotel.reservations.index') }}" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                    {{ __('app.cancel') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
