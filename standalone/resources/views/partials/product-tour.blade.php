{{-- Product tour (Driver.js via CDN — no npm dep, same approach as SweetAlert).
     window.startTour(steps) drives a spotlight walkthrough on the real UI;
     window.maybeAutoTour(key, steps) runs it once per browser. Each page
     defines its own steps + a "How to use" button. Steps whose element is
     missing are skipped automatically, so a tour adapts to page state. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js" defer></script>
<script>
    (() => {
        if (window.startTour) return; // include-once guard

        const ready = (cb) => (window.driver && window.driver.js) ? cb() : setTimeout(() => ready(cb), 40);

        window.startTour = (steps) => ready(() => {
            const usable = (steps || []).filter((s) => !s.el || document.querySelector(s.el));
            if (! usable.length) return;
            window.driver.js.driver({
                showProgress: true,
                allowClose: true,
                overlayColor: 'rgba(15, 23, 42, 0.7)',
                nextBtnText: @json(__('app.tour.next')),
                prevBtnText: @json(__('app.tour.prev')),
                doneBtnText: @json(__('app.tour.done')),
                progressText: @json(__('app.tour.progress')),
                steps: usable.map((s) => ({
                    element: s.el || undefined,
                    popover: {
                        title: s.title,
                        description: s.text,
                        side: s.side || 'bottom',
                        align: s.align || 'center',
                    },
                })),
            }).drive();
        });

        window.maybeAutoTour = (key, steps) => {
            try {
                if (localStorage.getItem('tour:' + key) === '1') return;
                localStorage.setItem('tour:' + key, '1');
            } catch (e) { /* localStorage unavailable — just skip auto-start */ }
            setTimeout(() => window.startTour(steps), 700);
        };
    })();
</script>
