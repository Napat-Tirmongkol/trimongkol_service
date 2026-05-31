{{-- SweetAlert2 integration — confirm dialogs + toast notifications.
     Include before </body> in every layout. --}}

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    (() => {
        const ready = (fn) => document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);
        const waitForSwal = (cb) => window.Swal ? cb() : setTimeout(() => waitForSwal(cb), 30);

        // Toast helper — short, top-right notifications for flash messages.
        window.flashToast = ({ message, type = 'success', timer = 3500 }) => {
            if (! message) return;
            waitForSwal(() => Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer,
                timerProgressBar: true,
            }));
        };

        // Modal confirm — intercept form submits with [data-confirm].
        // Usage: <form data-confirm="Are you sure?" data-confirm-danger="1" ...>
        ready(() => {
            document.addEventListener('submit', async (e) => {
                const form = e.target;
                if (! (form instanceof HTMLFormElement)) return;
                if (! form.dataset.confirm) return;
                if (form.dataset.confirmed === '1') return; // re-submit after user confirms

                e.preventDefault();

                const isDanger = form.dataset.confirmDanger === '1';

                await new Promise(waitForSwal);
                const result = await Swal.fire({
                    title: form.dataset.confirmTitle || (isDanger ? @json(__('app.common.confirm_danger_title')) : @json(__('app.common.confirm_title'))),
                    text: form.dataset.confirm,
                    icon: isDanger ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmYes || @json(__('app.common.confirm')),
                    cancelButtonText: form.dataset.confirmNo || @json(__('app.common.cancel')),
                    confirmButtonColor: isDanger ? '#e11d48' : @json(config('brand.color')),
                    cancelButtonColor: '#94a3b8',
                    reverseButtons: true,
                });

                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    // Re-submit, including the originally clicked submitter so its name/value is included.
                    if (e.submitter) {
                        e.submitter.click();
                    } else {
                        form.submit();
                    }
                }
            });
        });
    })();
</script>

@if (session('status'))
    <script>
        window.addEventListener('DOMContentLoaded', () => flashToast({ message: @json(session('status')), type: 'success' }));
    </script>
@endif

@if (session('error'))
    <script>
        window.addEventListener('DOMContentLoaded', () => flashToast({ message: @json(session('error')), type: 'error' }));
    </script>
@endif
