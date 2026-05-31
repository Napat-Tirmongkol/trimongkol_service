<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-slate-900">{{ __('app.profile.delete_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('app.profile.delete_desc') }}</p>
    </header>

    {{-- Confirmation is handled by SweetAlert2 (centred, always on top) to match
         the rest of the app — the old Breeze Alpine <x-modal> rendered in the
         wrong place. The password is collected in the dialog and posted via a
         hidden field. --}}
    <form id="delete-account-form" method="post" action="{{ route('profile.destroy') }}">
        @csrf
        @method('delete')
        <input type="hidden" name="password" id="delete-account-password">
        <button type="button" id="delete-account-btn"
                class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
            {{ __('app.profile.delete_button') }}
        </button>
        @error('password', 'userDeletion')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </form>

    <script>
        (function () {
            const form = document.getElementById('delete-account-form');
            const btn = document.getElementById('delete-account-btn');
            const pwField = document.getElementById('delete-account-password');
            if (! form || ! btn) return;

            const waitForSwal = (cb) => window.Swal ? cb() : setTimeout(() => waitForSwal(cb), 30);

            function promptDelete(errorText) {
                waitForSwal(async () => {
                    const result = await Swal.fire({
                        title: @json(__('app.profile.delete_confirm_title')),
                        html: @json(__('app.profile.delete_confirm_text'))
                            + (errorText ? '<div style="color:#e11d48;margin-top:.5rem;font-size:.875rem">' + errorText + '</div>' : ''),
                        icon: 'warning',
                        input: 'password',
                        inputPlaceholder: @json(__('app.profile.delete_password_placeholder')),
                        inputAttributes: { autocomplete: 'current-password' },
                        showCancelButton: true,
                        confirmButtonText: @json(__('app.profile.delete_button')),
                        cancelButtonText: @json(__('app.common.cancel')),
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#94a3b8',
                        reverseButtons: true,
                        inputValidator: (v) => (! v ? @json(__('app.profile.delete_password_required')) : undefined),
                    });
                    if (result.isConfirmed) {
                        pwField.value = result.value;
                        form.submit();
                    }
                });
            }

            btn.addEventListener('click', () => promptDelete(null));

            @if ($errors->userDeletion->isNotEmpty())
                document.addEventListener('DOMContentLoaded', () => promptDelete(@json($errors->userDeletion->first('password'))));
            @endif
        })();
    </script>
</section>
