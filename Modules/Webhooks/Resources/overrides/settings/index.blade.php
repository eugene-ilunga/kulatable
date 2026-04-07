@push('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('vendor/trix/trix.css') }}">
<script type="text/javascript" src="{{ asset('vendor/trix/trix.umd.min.js') }}"></script>

@endpush

@component('layouts.app')
    @slot('slot')
        @livewire('settings.master')
    @endslot
@endcomponent

@push('scripts')
<script>
    function form() {
        var element = document.querySelector("trix-editor");

        return {
            save() {
                let that = this;
                let myPromise = new Promise(function(myResolve, myReject) {

                    Livewire.dispatch('alpine-save', {description: that.$refs.description.value});

                    setTimeout(() => {
                        myResolve(); // when successful
                    }, 100);

                });

                // "Consuming Code" (Must wait for a fulfilled Promise)
                myPromise.then(
                    function (value) {
                        that.$wire.call('submitForm');
                    }
                );
            }
        }
    }

</script>
@endpush
