{{-- Alpine component for the task board: modal state, sub-task fetches and
     native drag-and-drop persistence. Registered defensively so it works
     whether or not Alpine (bundled with Livewire) has booted yet. --}}
<script>
(function () {
    const blank = () => ({
        mode: 'create', id: null, action: '', method: 'POST',
        title: '', description: '', status: 'todo', priority: 'normal',
        due_date: '', assignee_id: '', labels: [], items: [],
    });

    const factory = (config) => ({
        config,
        view: 'board',
        taskOpen: false,
        labelOpen: false,
        projectOpen: false,
        form: blank(),

        init() {
            const saved = localStorage.getItem('tasks_view_' + this.config.projectId);
            if (saved === 'list' || saved === 'board') this.view = saved;
            this.$watch('view', (v) => localStorage.setItem('tasks_view_' + this.config.projectId, v));

            const requested = new URLSearchParams(window.location.search).get('task');
            if (requested && this.config.tasksById[requested]) {
                this.$nextTick(() => this.openEdit(requested));
            }
            this.$nextTick(() => this.initDragDrop());
        },

        openCreate(status) {
            this.form = blank();
            this.form.status = status || 'todo';
            this.form.action = this.config.storeUrl;
            this.form.method = 'POST';
            this.taskOpen = true;
        },

        openEdit(id) {
            const t = this.config.tasksById[String(id)];
            if (!t) return;
            this.form = {
                mode: 'edit',
                id: t.id,
                action: this.config.taskBase + '/' + t.id,
                method: 'PATCH',
                title: t.title || '',
                description: t.description || '',
                status: t.status,
                priority: t.priority,
                due_date: t.due_date || '',
                assignee_id: t.assignee_id ? String(t.assignee_id) : '',
                labels: (t.label_ids || []).map(String),
                items: (t.items || []).map((i) => ({ id: i.id, title: i.title, is_done: !!i.is_done })),
            };
            this.taskOpen = true;
        },

        openProjectEdit() {
            this.projectOpen = true;
        },

        // ── Sub-tasks (live, via fetch) ──────────────────────────────
        request(url, method, body) {
            return fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrf,
                },
                body: body ? JSON.stringify(body) : undefined,
            });
        },

        addItem(title) {
            title = (title || '').trim();
            if (!title || this.form.mode !== 'edit') return;
            this.request(this.config.taskBase + '/' + this.form.id + '/items', 'POST', { title: title })
                .then((r) => r.ok ? r.json() : Promise.reject(r))
                .then((item) => {
                    this.form.items.push({ id: item.id, title: item.title, is_done: !!item.is_done });
                    if (this.$refs.newItem) this.$refs.newItem.value = '';
                    this.syncCardSubtasks();
                })
                .catch(() => {});
        },

        toggleItem(item) {
            this.request(this.config.taskBase + '/items/' + item.id + '/toggle', 'POST')
                .then((r) => r.ok ? r.json() : Promise.reject(r))
                .then((res) => { item.is_done = !!res.is_done; this.syncCardSubtasks(); })
                .catch(() => {});
        },

        deleteItem(item) {
            this.request(this.config.taskBase + '/items/' + item.id, 'DELETE')
                .then((r) => {
                    if (!r.ok) return Promise.reject(r);
                    this.form.items = this.form.items.filter((i) => i.id !== item.id);
                    this.syncCardSubtasks();
                })
                .catch(() => {});
        },

        // Mirror sub-task progress onto the in-memory map + the board card badge.
        syncCardSubtasks() {
            const cached = this.config.tasksById[String(this.form.id)];
            if (cached) cached.items = this.form.items.map((i) => ({ id: i.id, title: i.title, is_done: i.is_done }));

            const total = this.form.items.length;
            const done = this.form.items.filter((i) => i.is_done).length;
            const card = this.$root.querySelector('[data-task-card][data-task-id="' + this.form.id + '"]');
            if (!card) return;
            const wrap = card.querySelector('[data-subtask-wrap]');
            const prog = card.querySelector('[data-subtask-progress]');
            if (prog) prog.textContent = done + '/' + total;
            if (wrap) wrap.classList.toggle('hidden', total === 0);
        },

        // ── Drag & drop ──────────────────────────────────────────────
        initDragDrop() {
            const self = this;
            let dragEl = null;

            this.$root.querySelectorAll('[data-task-card]').forEach((card) => {
                card.addEventListener('dragstart', (e) => {
                    dragEl = card;
                    e.dataTransfer.effectAllowed = 'move';
                    setTimeout(() => card.classList.add('opacity-40'), 0);
                });
                card.addEventListener('dragend', () => {
                    card.classList.remove('opacity-40');
                    dragEl = null;
                });
            });

            this.$root.querySelectorAll('[data-board-column]').forEach((col) => {
                const list = col.querySelector('[data-column-list]');
                col.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    col.classList.add('ring-2', 'ring-brand-300');
                });
                col.addEventListener('dragleave', (e) => {
                    if (!col.contains(e.relatedTarget)) col.classList.remove('ring-2', 'ring-brand-300');
                });
                col.addEventListener('drop', (e) => {
                    e.preventDefault();
                    col.classList.remove('ring-2', 'ring-brand-300');
                    if (!dragEl) return;
                    const after = self.dragAfter(list, e.clientY);
                    if (after == null) list.appendChild(dragEl); else list.insertBefore(dragEl, after);

                    const status = col.getAttribute('data-status');
                    const id = dragEl.getAttribute('data-task-id');
                    const order = Array.from(list.querySelectorAll('[data-task-card]'))
                        .map((c) => parseInt(c.getAttribute('data-task-id'), 10));

                    self.applyCardStatus(dragEl, status);
                    self.persistMove(id, status, order);
                    self.refreshCounts();
                });
            });
        },

        dragAfter(list, y) {
            const cards = Array.from(list.querySelectorAll('[data-task-card]:not(.opacity-40)'));
            return cards.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
        },

        applyCardStatus(card, status) {
            card.setAttribute('data-status', status);
            const cached = this.config.tasksById[card.getAttribute('data-task-id')];
            if (cached) cached.status = status;

            const done = status === 'done';
            const check = card.querySelector('[data-card-check]');
            const title = card.querySelector('[data-card-title]');
            if (check) check.classList.toggle('hidden', !done);
            if (title) {
                title.classList.toggle('line-through', done);
                title.classList.toggle('text-slate-400', done);
                title.classList.toggle('text-slate-800', !done);
            }
        },

        persistMove(id, status, order) {
            this.request(this.config.taskBase + '/' + id + '/move', 'POST', { status: status, order: order })
                .then((r) => { if (!r.ok) location.reload(); })
                .catch(() => location.reload());
        },

        refreshCounts() {
            this.$root.querySelectorAll('[data-board-column]').forEach((col) => {
                const n = col.querySelectorAll('[data-task-card]').length;
                const badge = col.querySelector('[data-count]');
                if (badge) badge.textContent = n;
                const empty = col.querySelector('[data-empty]');
                if (empty) empty.style.display = n === 0 ? '' : 'none';
            });
        },
    });

    const register = (Alpine) => Alpine.data('taskBoard', factory);
    if (window.Alpine) {
        register(window.Alpine);
    } else {
        document.addEventListener('alpine:init', () => register(window.Alpine));
    }
})();
</script>
