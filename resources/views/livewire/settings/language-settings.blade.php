<div class="px-4 my-4 space-y-4">
    <div class="flex flex-wrap gap-2">
        <x-secondary-link href="{{ url('/translations') }}" target="_blank">
            @lang('modules.settings.manageTranslations')
        </x-secondary-link>

        @includeIf('languagepack::publish-all-button')

        <x-button type="button" wire:click="refreshLanguages">
            Refresh
        </x-button>
    </div>

    <div class="p-4 rounded-lg border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200 text-sm">
        Les langues sont détectées depuis les fichiers présents dans <code>lang/</code>.
    </div>

    <div class="overflow-x-auto shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                        @lang('modules.language.languageName')
                    </th>
                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                        @lang('modules.language.languageCode')
                    </th>
                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                        @lang('modules.language.rtl')
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                @forelse ($languageSettings as $item)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white flex items-center gap-2 ltr:text-left rtl:text-right">
                            <img class="h-4 w-4 rounded-full border border-gray-200" src="{{ $item->flagUrl }}" alt="{{ $item->language_code }}">
                            {{ locale_label($item->language_code) }}
                        </td>
                        <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white ltr:text-left rtl:text-right">
                            {{ $item->language_code }}
                        </td>
                        <td class="py-2.5 px-4 ltr:text-left rtl:text-right">
                            @if ($item->is_rtl)
                                <span class="bg-green-100 uppercase text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                    @lang('app.yes')
                                </span>
                            @else
                                <span class="bg-gray-100 uppercase text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-900 dark:text-gray-300">
                                    @lang('app.no')
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-4 px-4 text-sm text-gray-500 dark:text-gray-400">
                            No language files detected.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    @includeIf('languagepack::script')
@endpush
