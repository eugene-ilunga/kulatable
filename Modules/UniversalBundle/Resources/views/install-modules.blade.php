@if(count(getUniversalBundleAvailableForInstallModules()) > 0)
<div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-4">
    <!-- Card Header -->
    <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold mb-1 text-gray-900 dark:text-gray-100">
            @lang('universalbundle::app.installBundleModules')
        </h4>
        <p class="text-xs text-gray-600 dark:text-gray-400">
            {{ count(getUniversalBundleAvailableForInstallModules()) }} new module(s) available for installation. Click the install button to add them to your system.
        </p>
    </div>

    <!-- Modules List -->
    <ul class="space-y-0" id="files-list">
        @foreach (getUniversalBundleAvailableForInstallModules() as $index => $module)
        <li class="border-b border-gray-100 dark:border-gray-700 last:border-b-0 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded px-2 -mx-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 flex-1 min-w-0">
                    <span class="font-medium text-gray-600 dark:text-gray-400 text-sm flex-shrink-0">{{ $index + 1 }}.</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ $module }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                <i class="fas fa-circle text-green-500 dark:text-green-400 text-[6px] mr-1"></i>
                                New Version
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Included in Universal Bundle • Ready to install</p>
                    </div>
                </div>

                <button type="button"
                        class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white px-3 py-1.5 text-xs font-medium rounded transition-colors duration-200 installUniversalBundleModule flex items-center gap-1.5 whitespace-nowrap flex-shrink-0"
                        data-module="{{ $module }}">
                    <i class="fa fa-download"></i>
                    <span>@lang('modules.update.install')</span>
                </button>
            </div>
        </li>

        @endforeach
    </ul>
</div>

<script>
document.body.addEventListener('click', function(event) {
    if (event.target.closest('.installUniversalBundleModule')) {
        const button = event.target.closest('.installUniversalBundleModule');
        const module = button.getAttribute('data-module');

        let alertMessage = `@lang('universalbundle::app.installModuleConfirm', ['module' => ':module'])`;
        alertMessage = alertMessage.replace(':module', module);

        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: alertMessage,
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('app.yes')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium mr-3 transition-colors duration-200',
                cancelButton: 'bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const url = "{{ route('install-universal-bundle-module') }}";
                const token = "{{ csrf_token() }}";

                // Disable button to prevent double clicks
                button.disabled = true;
                button.classList.add('opacity-50', 'cursor-not-allowed');

                // Show loading indicator
                Swal.fire({
                    title: 'Installing ' + module + '...',
                    text: 'Please wait, this may take a moment.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        module: module
                    })
                })
                .then(response => response.json())
                .then(response => {
                    if (response.status === 'success') {
                        // Second API call
                        return fetch("{{ route('add-universal-module-purchase-code') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                module: module
                            })
                        });
                    } else {
                        throw new Error(response.message || 'Installation failed');
                    }
                })
                .then(response => response.json())
                .then(response => {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Module installed successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        throw new Error(response.message || 'Failed to add purchase code');
                    }
                })
                .catch(error => {
                    // Re-enable button on error
                    button.disabled = false;
                    button.classList.remove('opacity-50', 'cursor-not-allowed');

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'An error occurred'
                    });
                });
            }
        });
    }
});

</script>
@endif
