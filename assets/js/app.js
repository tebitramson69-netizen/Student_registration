/* ===========================================================================
   Student Registry - front-end behaviour.

   Everything here is an enhancement. The pages render complete HTML and every
   form and link works on its own, so the system stays usable when this file
   fails to load or JavaScript is switched off.

   Student values are written with textContent, never innerHTML, so a name
   containing markup cannot become markup.
   ======================================================================== */

(() => {
    'use strict';

    /* -------------------------------------------------------------- helpers */

    const $  = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    const el = (tag, props = {}, children = []) => {
        const node = Object.assign(document.createElement(tag), props);
        for (const child of [].concat(children)) {
            node.append(child);
        }
        return node;
    };

    const debounce = (fn, wait) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), wait);
        };
    };

    /* --------------------------------------------------------------- theme */

    const THEME_KEY = 'sr.theme';

    const applyTheme = (theme) => {
        if (theme) {
            document.documentElement.dataset.theme = theme;
        } else {
            delete document.documentElement.dataset.theme;
        }
        const icon = $('[data-theme-icon]');
        if (icon) {
            const dark = theme === 'dark'
                || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            icon.textContent = dark ? '☀' : '☽';
        }
    };

    const initTheme = () => {
        let stored = null;
        try {
            stored = localStorage.getItem(THEME_KEY);
        } catch {
            // Private browsing can make localStorage throw; the OS preference
            // still applies, so there is nothing to recover from.
        }
        applyTheme(stored);

        const toggle = $('[data-theme-toggle]');
        if (!toggle) return;

        toggle.addEventListener('click', () => {
            const isDark = document.documentElement.dataset.theme === 'dark'
                || (!document.documentElement.dataset.theme
                    && window.matchMedia('(prefers-color-scheme: dark)').matches);
            const next = isDark ? 'light' : 'dark';
            applyTheme(next);
            try {
                localStorage.setItem(THEME_KEY, next);
            } catch { /* nothing to do */ }
        });
    };

    /* -------------------------------------------------------------- toasts */

    const toast = (message, type = 'info') => {
        const host = $('[data-toasts]');
        if (!host) return;

        const node = el('div', { className: `toast toast--${type}`, role: 'status' }, [
            el('span', { textContent: type === 'error' ? '⚠' : '✓', ariaHidden: 'true' }),
            el('span', { textContent: message })
        ]);

        host.append(node);
        setTimeout(() => {
            node.classList.add('is-leaving');
            node.addEventListener('transitionend', () => node.remove(), { once: true });
            setTimeout(() => node.remove(), 400);
        }, 4000);
    };

    /* ------------------------------------------------- confirmation dialog */

    /**
     * Replaces window.confirm() for any form carrying data-confirm. The real
     * form is still what submits, so the CSRF token and the POST method are
     * untouched.
     */
    const initConfirm = () => {
        const dialog = $('[data-confirm-dialog]');

        // Where <dialog> is unsupported, guard the same forms with the native
        // confirm. The markup carries no inline onsubmit, because an inline
        // handler would force 'unsafe-inline' into the Content-Security-Policy.
        if (!dialog || typeof dialog.showModal !== 'function') {
            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (form instanceof HTMLFormElement
                    && form.hasAttribute('data-confirm')
                    && !window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
            return;
        }

        let pendingForm = null;

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;

            event.preventDefault();
            pendingForm = form;

            $('[data-confirm-title]', dialog).textContent =
                form.dataset.confirmTitle || 'Are you sure?';
            $('[data-confirm-body]', dialog).textContent = form.dataset.confirm;
            $('[data-confirm-accept]', dialog).textContent =
                form.dataset.confirmAccept || 'Confirm';

            dialog.showModal();
            $('[data-confirm-cancel]', dialog).focus();
        });

        $('[data-confirm-cancel]', dialog).addEventListener('click', () => {
            pendingForm = null;
            dialog.close();
        });

        $('[data-confirm-accept]', dialog).addEventListener('click', () => {
            const form = pendingForm;
            pendingForm = null;
            dialog.close();
            if (form) {
                // removeAttribute so the submit event is not intercepted again.
                form.removeAttribute('data-confirm');
                form.submit();
            }
        });
    };

    /* ---------------------------------------------------- form validation */

    /**
     * Mirrors validation.php so mistakes are caught before a round trip. The
     * server runs the same rules again and is the one that decides.
     */
    const RULES = {
        first_name: {
            label: 'First name',
            max: 100,
            pattern: /^[\p{L}\p{M}' .-]+$/u,
            patternMessage: 'Only letters, spaces, apostrophes, hyphens and full stops.'
        },
        last_name: {
            label: 'Last name',
            max: 100,
            pattern: /^[\p{L}\p{M}' .-]+$/u,
            patternMessage: 'Only letters, spaces, apostrophes, hyphens and full stops.'
        },
        email: {
            label: 'Email',
            max: 255,
            pattern: /^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/,
            patternMessage: 'Enter a valid email address.'
        },
        telephone: {
            label: 'Telephone',
            max: 30,
            pattern: /^\+?[0-9 ()-]{6,}$/,
            patternMessage: 'Digits only, optionally starting with +.',
            extra: (value) => ((value.match(/[0-9]/g) || []).length < 6
                ? 'Telephone must contain at least 6 digits.'
                : '')
        }
    };

    const fieldError = (name, rawValue) => {
        const rule  = RULES[name];
        const value = rawValue.trim().replace(/\s+/g, ' ');

        if (value === '') return `${rule.label} is required.`;
        if (value.length > rule.max) return `${rule.label} cannot be longer than ${rule.max} characters.`;
        if (!rule.pattern.test(value)) return rule.patternMessage;
        if (rule.extra) return rule.extra(value);
        return '';
    };

    const initValidation = () => {
        const form = $('[data-validate]');
        if (!form) return;

        // The browser's own bubbles would compete with the inline messages.
        form.setAttribute('novalidate', 'novalidate');

        const show = (input, message) => {
            const slot = $(`[data-error-for="${input.name}"]`, form);
            if (slot) slot.textContent = message;
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
            return message === '';
        };

        const inputs = $$('input[name]', form).filter((input) => RULES[input.name]);

        for (const input of inputs) {
            // Validate on blur, then live once the field has been touched, so a
            // half-typed address is not called invalid on the third keystroke.
            input.addEventListener('blur', () => {
                input.dataset.touched = 'true';
                show(input, fieldError(input.name, input.value));
            });
            input.addEventListener('input', () => {
                if (input.dataset.touched === 'true') {
                    show(input, fieldError(input.name, input.value));
                }
            });
        }

        form.addEventListener('submit', (event) => {
            let firstInvalid = null;

            for (const input of inputs) {
                input.dataset.touched = 'true';
                if (!show(input, fieldError(input.name, input.value)) && !firstInvalid) {
                    firstInvalid = input;
                }
            }

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                toast('Please correct the highlighted fields.', 'error');
            }
        });
    };

    /* ------------------------------------------------------- student table */

    const initTable = () => {
        const root = $('[data-students]');
        if (!root || !window.fetch || !window.history.pushState) return;

        const tbody    = $('[data-rows]', root);
        const summary  = $('[data-summary]', root);
        const pages    = $('[data-pages]', root);
        const searchIn = $('[data-search]', root);
        const liveNote = $('[data-live]', root);
        const endpoint = root.dataset.endpoint;
        const csrf     = root.dataset.csrf;

        // Read from data-state-* rather than data-sort/data-page: sharing those
        // names with the link hooks makes closest() match this container.
        let state = {
            search: searchIn ? searchIn.value : '',
            sort:   root.dataset.stateSort,
            order:  root.dataset.stateOrder,
            page:   Number(root.dataset.statePage) || 1
        };
        let inFlight = null;

        const buildRow = (student, rowNumber) => {
            const tr = el('tr');

            tr.append(el('td', { className: 'col-num', textContent: String(rowNumber) }));

            const cells = [
                ['ID', String(student.id)],
                ['First name', student.first_name],
                ['Last name', student.last_name],
                ['Email', student.email],
                ['Telephone', student.telephone]
            ];

            for (const [label, value] of cells) {
                const td = el('td', { textContent: value });
                td.dataset.label = label;
                tr.append(td);
            }

            const edit = el('a', { className: 'link-button', textContent: 'Edit' });
            edit.href = `update.php?id=${encodeURIComponent(student.id)}`;

            const form = el('form', { className: 'inline-form', method: 'post', action: 'delete.php' });
            form.dataset.confirm = `Delete ${student.first_name} ${student.last_name}? This cannot be undone.`;
            form.dataset.confirmTitle = 'Delete student';
            form.dataset.confirmAccept = 'Delete';
            form.append(el('input', { type: 'hidden', name: 'csrf_token', value: csrf }));
            form.append(el('input', { type: 'hidden', name: 'id', value: String(student.id) }));
            form.append(el('button', { type: 'submit', className: 'link-button link-button--danger', textContent: 'Delete' }));

            const actions = el('td', {}, [el('div', { className: 'cell-actions' }, [edit, form])]);
            actions.dataset.label = 'Actions';
            tr.append(actions);

            return tr;
        };

        const renderEmpty = (search) => {
            const cell = el('td', { colSpan: 7 });
            const box  = el('div', { className: 'empty' });
            box.append(el('strong', {
                textContent: search ? 'No students match that search' : 'No students yet'
            }));
            box.append(el('span', {
                textContent: search
                    ? 'Try a different name, email or phone number.'
                    : 'Register the first student to see them listed here.'
            }));
            cell.append(box);
            tbody.replaceChildren(el('tr', {}, [cell]));
        };

        const renderPager = (meta) => {
            if (!pages) return;
            pages.replaceChildren();

            const link = (page, label, { current = false, rel = null } = {}) => {
                const a = el('a', { className: 'pager__page', textContent: label });
                a.href = buildUrl({ ...state, page });
                a.dataset.page = String(page);
                // rel distinguishes Previous/Next from the numbered link that
                // happens to point at the same page.
                if (rel) a.rel = rel;
                if (current) a.setAttribute('aria-current', 'page');
                return a;
            };

            if (meta.page > 1) pages.append(link(meta.page - 1, 'Previous', { rel: 'prev' }));

            for (const page of meta.window) {
                if (page === null) {
                    pages.append(el('span', { className: 'pager__gap', textContent: '…' }));
                } else {
                    pages.append(link(page, String(page), { current: page === meta.page }));
                }
            }

            if (meta.page < meta.total_pages) pages.append(link(meta.page + 1, 'Next', { rel: 'next' }));
        };

        const renderSortHeaders = () => {
            for (const th of $$('th[data-column]', root)) {
                const column = th.dataset.column;
                const active = column === state.sort;
                const next   = active && state.order === 'ASC' ? 'DESC' : 'ASC';

                if (active) {
                    th.setAttribute('aria-sort', state.order === 'ASC' ? 'ascending' : 'descending');
                } else {
                    th.removeAttribute('aria-sort');
                }

                const trigger = $('[data-sort]', th);
                if (trigger) {
                    trigger.href = buildUrl({ ...state, sort: column, order: next, page: 1 });
                    const arrow = $('.arrow', trigger);
                    if (arrow) arrow.textContent = active ? (state.order === 'ASC' ? '▲' : '▼') : '';
                }
            }
        };

        const buildUrl = (next) => {
            const params = new URLSearchParams();
            if (next.search) params.set('search', next.search);
            if (next.sort)   params.set('sort', next.sort);
            if (next.order)  params.set('order', next.order);
            if (next.page > 1) params.set('page', String(next.page));
            const query = params.toString();
            return query ? `table.php?${query}` : 'table.php';
        };

        const load = async (next, { push = true, replace = false } = {}) => {
            // Held locally until the response arrives. Committing first left
            // state describing a table that was never rendered when a request
            // failed, so the next sort click computed its direction from it.
            const pending = { ...state, ...next };
            root.dataset.busy = 'true';

            // Abandon a slower earlier request so results cannot arrive out of
            // order and overwrite the newest ones.
            if (inFlight) inFlight.abort();
            const controller = new AbortController();
            inFlight = controller;

            const params = new URLSearchParams({
                search: pending.search,
                sort:   pending.sort,
                order:  pending.order,
                page:   String(pending.page)
            });

            try {
                const response = await fetch(`${endpoint}?${params}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });

                if (response.status === 401) {
                    toast('Your session expired. Reloading the sign-in page.', 'error');
                    window.location.href = 'login.php?timeout=1';
                    return;
                }
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const payload = await response.json();
                const meta    = payload.meta;

                // The server has the last word on which page was served.
                state = { ...pending, page: meta.page };

                if (payload.data.length === 0) {
                    renderEmpty(meta.search);
                } else {
                    tbody.replaceChildren(
                        ...payload.data.map((student, index) => buildRow(student, meta.offset + index + 1))
                    );
                }

                if (summary) {
                    summary.textContent = meta.total_rows === 0
                        ? 'No students'
                        : `Showing ${meta.offset + 1}–${meta.offset + payload.data.length} of ${meta.total_rows}`;
                }
                if (liveNote) {
                    liveNote.textContent = `${meta.total_rows} student${meta.total_rows === 1 ? '' : 's'} found.`;
                }

                renderPager(meta);
                renderSortHeaders();

                const exportLink = $('[data-export]', root);
                if (exportLink) {
                    exportLink.href = meta.search
                        ? `export.php?search=${encodeURIComponent(meta.search)}`
                        : 'export.php';
                }

                // Typing pushes nothing: one history entry per settled
                // keystroke would bury the page the admin arrived from.
                if (replace) {
                    window.history.replaceState({ ...state }, '', buildUrl(state));
                } else if (push) {
                    window.history.pushState({ ...state }, '', buildUrl(state));
                }
            } catch (error) {
                if (error.name === 'AbortError') return;
                toast('Could not refresh the list. Showing the last result.', 'error');
            } finally {
                if (inFlight === controller) {
                    inFlight = null;
                    root.dataset.busy = 'false';
                }
            }
        };

        if (searchIn) {
            const form = searchIn.closest('form');
            if (form) {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    load({ search: searchIn.value, page: 1 });
                });
            }
            searchIn.addEventListener('input', debounce(() => {
                load({ search: searchIn.value, page: 1 }, { replace: true });
            }, 300));
        }

        // One listener for links that exist now and for rows rendered later.
        // Each selector is anchored to its container so a stray match cannot
        // reach this handler.
        root.addEventListener('click', (event) => {
            const sortLink = event.target.closest('thead [data-sort]');
            if (sortLink) {
                event.preventDefault();
                const header = sortLink.closest('th[data-column]');
                if (!header) return;
                const column = header.dataset.column;
                const order  = column === state.sort && state.order === 'ASC' ? 'DESC' : 'ASC';
                load({ sort: column, order, page: 1 });
                return;
            }

            const pageLink = event.target.closest('[data-pages] [data-page]');
            if (pageLink) {
                event.preventDefault();
                load({ page: Number(pageLink.dataset.page) });
            }
        });

        // Back and forward must move through the list, not out of the page.
        window.addEventListener('popstate', (event) => {
            const params = new URLSearchParams(window.location.search);
            load(event.state || {
                search: params.get('search') || '',
                sort:   params.get('sort') || 'first_name',
                order:  params.get('order') || 'ASC',
                page:   Number(params.get('page')) || 1
            }, { push: false });
        });

        window.history.replaceState({ ...state }, '', buildUrl(state));
    };

    /* ----------------------------------------------------------------- boot */

    const start = () => {
        initTheme();
        initConfirm();
        initValidation();
        initTable();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
