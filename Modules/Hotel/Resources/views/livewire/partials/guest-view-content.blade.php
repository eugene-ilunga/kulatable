@php
    $idTypeLabels = [
        'passport' => __('hotel::modules.guest.passport'),
        'aadhaar' => __('hotel::modules.guest.aadhaar'),
        'driving_license' => __('hotel::modules.guest.drivingLicense'),
        'national_id' => __('hotel::modules.guest.nationalId'),
        'other' => __('hotel::modules.guest.other'),
    ];
    $idTypeLabel = $guest->id_type
        ? ($idTypeLabels[$guest->id_type] ?? ucfirst(str_replace('_', ' ', $guest->id_type)))
        : null;
    $initials = collect(explode(' ', $guest->full_name))
        ->map(fn ($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
    $docPath = $guest->id_proof_file;
    $docUrl = $docPath ? asset_url_local_s3('guest-id-proof/' . $docPath) : null;
    $docExt = $docPath ? strtolower(pathinfo($docPath, PATHINFO_EXTENSION)) : '';
    $isPdf = $docExt === 'pdf';
    $dobFormatted = $guest->date_of_birth ? $guest->date_of_birth->format(config('app.date_format', 'Y-m-d')) : null;
    $hasAddress = collect([$guest->address, $guest->city, $guest->state, $guest->country, $guest->postal_code])
        ->filter(fn ($v) => $v !== null && $v !== '')
        ->isNotEmpty();
@endphp     

<div class="space-y-4 text-left">
    {{-- Identity header: light, compact (PMS / travel-app style) --}}
    <div class="rounded-xl border border-gray-200/90 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="p-4 sm:p-5">
            <div class="flex gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-semibold text-slate-700 ring-1 ring-slate-200/80 dark:from-slate-700 dark:to-slate-800 dark:text-slate-200 dark:ring-slate-600">
                    {{ $initials ?: '?' }}
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold leading-tight text-gray-900 dark:text-white">{{ $guest->full_name }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.guestProfileSubtitle') }}</p>
                    @if($guest->customer)
                        <p class="mt-2 inline-flex max-w-full items-center gap-1.5 truncate rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700/80 dark:text-gray-200">
                            <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                            <span class="truncate">{{ __('hotel::modules.guest.linkedCustomer') }}: {{ $guest->customer->name }}</span>
                        </p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if(filled($guest->phone))
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs text-gray-700 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2z" /></svg>
                                {{ $guest->phone }}
                            </span>
                        @endif
                        @if(filled($guest->email))
                            <span class="inline-flex min-w-0 max-w-full items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs text-gray-700 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                                <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                <span class="truncate">{{ $guest->email }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Single definition list: neutral, scannable --}}
        <div class="border-t border-gray-100 dark:border-gray-700/80">
            <p class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 sm:px-5">{{ __('hotel::modules.guest.profileDetails') }}</p>
            <dl class="divide-y divide-gray-100 dark:divide-gray-700/80">
                @foreach([
                    [__('hotel::modules.guest.email'), $guest->email],
                    [__('hotel::modules.guest.phone'), $guest->phone],
                    [__('hotel::modules.guest.idType'), $idTypeLabel],
                    [__('hotel::modules.guest.idNumber'), $guest->id_number],
                ] as [$label, $val])
                    <div class="grid grid-cols-1 gap-1 px-4 py-2.5 sm:grid-cols-[minmax(0,8rem)_1fr] sm:items-baseline sm:gap-6 sm:px-5">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 break-words">{{ ($val !== null && $val !== '') ? $val : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    {{-- ID: compact preview + actions (no oversized image) --}}
    <div class="rounded-xl border border-gray-200/90 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-700/80 sm:px-5">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.guest.idVerification') }}</h4>
            @if($docUrl)
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $docUrl }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        {{ __('hotel::modules.guest.openDocument') }}
                    </a>
                    <a href="{{ $docUrl }}" download="{{ basename($docPath) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-skin-base px-2.5 py-1.5 text-xs font-medium text-white shadow-sm transition hover:opacity-95">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        {{ __('hotel::modules.guest.downloadDocument') }}
                    </a>
                </div>
            @endif
        </div>
        <div class="p-4 sm:p-5">
            @if($docUrl)
                @if($isPdf)
                    <div class="flex items-start gap-3 rounded-lg border border-dashed border-gray-200 bg-gray-50/80 p-3 dark:border-gray-600 dark:bg-gray-900/30">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-600">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ basename($docPath) }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.pdfPreviewHint') }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                        <div class="mx-auto w-full max-w-[220px] shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-900/40 sm:mx-0">
                            <img src="{{ $docUrl }}" alt="{{ __('hotel::modules.guest.idProof') }}" class="max-h-36 w-full object-contain object-center" loading="lazy" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 sm:pt-1">{{ basename($docPath) }}</p>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-200 py-8 text-center dark:border-gray-600">
                    <svg class="mb-2 h-9 w-9 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.noIdDocument') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Address: only full card when there is data; otherwise one line --}}
    @if($hasAddress)
        <div class="rounded-xl border border-gray-200/90 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-700/80 dark:text-white sm:px-5">{{ __('hotel::modules.guest.sectionAddress') }}</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/80">
                @if(filled($guest->address))
                    <div class="px-4 py-3 sm:px-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.address') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $guest->address }}</p>
                    </div>
                @endif
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:gap-6 sm:p-5">
                    @foreach([
                        [__('hotel::modules.guest.city'), $guest->city],
                        [__('hotel::modules.guest.state'), $guest->state],
                        [__('hotel::modules.guest.country'), $guest->country],
                        [__('hotel::modules.guest.postalCode'), $guest->postal_code],
                    ] as [$label, $val])
                        @if(filled($val))
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                <p class="mt-0.5 text-sm text-gray-900 dark:text-gray-100">{{ $val }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <p class="px-1 text-xs text-gray-400 dark:text-gray-500">{{ __('hotel::modules.guest.noAddressOnFile') }}</p>
    @endif

    @if($guest->notes)
        <div class="rounded-xl border-l-4 border-skin-base bg-gray-50/90 py-3 pl-4 pr-4 dark:bg-gray-900/40">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.notes') }}</p>
            <p class="mt-1.5 text-sm leading-relaxed text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $guest->notes }}</p>
        </div>
    @endif
</div>
