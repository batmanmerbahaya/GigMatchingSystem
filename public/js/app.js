/**
 * Gig Matching System — application JavaScript.
 *
 * Everything is wired through data attributes so the markup stays free of
 * inline handlers (required by the strict Content-Security-Policy):
 *
 *   data-account-toggle / data-account-menu   account menu under the user's name
 *   data-menu-toggle                          show/hide the navigation on small screens
 *   data-confirm="Question? Detail."          styled confirmation before a form submits
 *     data-confirm-ok="Yes, apply"            label of the confirm button
 *     data-confirm-tone="danger"              red confirm button for destructive actions
 *   data-flash                                toast that fades out automatically
 *   data-skeleton="jobs|list|table|detail|form"  show a skeleton while that link loads
 *   data-job-search / data-job-nav            Find Jobs filters, updated in place
 *   data-job-card / data-job-open             job card; opens the details pane on wide screens
 *   data-rate="url" data-rate-name="Name"     open the star rating dialog (History)
 *   data-pay-filter                           pay type switch and two-handle pay slider (Find Jobs)
 *   details[data-reply] / data-reply-cancel   reply box under a comment (job tracker)
 *   data-modal-target="id" / data-modal-close modal overlays
 *
 * Without JavaScript every link and form still works as a normal page load.
 */

(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

    function canAnimate(el) {
        return !reduceMotion && !!el && typeof el.animate === 'function';
    }

    function stopAnims(el) {
        if (el && el.getAnimations) {
            el.getAnimations().forEach(function (animation) { animation.cancel(); });
        }
    }

    function templateContent(id) {
        var template = document.getElementById(id);
        return template ? template.content.cloneNode(true) : document.createDocumentFragment();
    }

    function isPlainClick(event) {
        return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
    }

    function openDialog(dialog) {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    }

    function closeDialog(dialog) {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    }

    function delay(ms) {
        return new Promise(function (resolve) { window.setTimeout(resolve, ms); });
    }

    function fetchPage(url, signal) {
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
            signal: signal
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text().then(function (html) {
                return {
                    url: response.url || url,
                    doc: new DOMParser().parseFromString(html, 'text/html')
                };
            });
        });
    }

    /* ----------------------------------------------------------------------
       Modals (kept for pages that use data-modal-target)
       ---------------------------------------------------------------------- */

    function initModals() {
        document.querySelectorAll('[data-modal-target]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                var modal = document.getElementById(trigger.getAttribute('data-modal-target'));
                if (modal) {
                    modal.classList.add('is-open');
                    var firstField = modal.querySelector('input, select, textarea');
                    if (firstField) {
                        firstField.focus();
                    }
                }
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                var overlay = button.closest('.modal-overlay');
                if (overlay) {
                    overlay.classList.remove('is-open');
                }
            });
        });

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    overlay.classList.remove('is-open');
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
       Top bar: account menu and small-screen navigation
       ---------------------------------------------------------------------- */

    var closeMenus = function () {};

    function initTopnav() {
        var nav = document.querySelector('[data-topnav]');
        if (!nav) {
            return;
        }
        var accountButton = nav.querySelector('[data-account-toggle]');
        var accountMenu = nav.querySelector('[data-account-menu]');
        var menuButton = nav.querySelector('[data-menu-toggle]');

        function setAccount(open) {
            if (!accountMenu) {
                return;
            }
            accountMenu.hidden = !open;
            accountButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && canAnimate(accountMenu)) {
                accountMenu.animate([
                    { opacity: 0, transform: 'translateY(-4px) scale(0.98)' },
                    { opacity: 1, transform: 'none' }
                ], { duration: 140, easing: 'ease-out' });
            }
        }

        function setMenu(open) {
            nav.classList.toggle('is-open', open);
            if (menuButton) {
                menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        closeMenus = function () {
            setAccount(false);
            setMenu(false);
        };

        if (accountButton) {
            accountButton.addEventListener('click', function () {
                setAccount(accountMenu.hidden);
            });
        }

        if (menuButton) {
            menuButton.addEventListener('click', function () {
                setMenu(!nav.classList.contains('is-open'));
            });
        }

        document.addEventListener('click', function (event) {
            if (accountMenu && !accountMenu.hidden && !accountMenu.contains(event.target) && !accountButton.contains(event.target)) {
                setAccount(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && accountMenu && !accountMenu.hidden) {
                event.preventDefault();
                setAccount(false);
                accountButton.focus();
            }
        });
    }

    /* ----------------------------------------------------------------------
       Confirmation dialog for forms with data-confirm, and double-submit guard
       ---------------------------------------------------------------------- */

    function markBusy(form, submitter) {
        if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') {
            return;
        }
        form.setAttribute('data-busy', '');
        var button = submitter || form.querySelector('[type="submit"]');
        if (button) {
            button.classList.add('is-busy');
            button.setAttribute('aria-disabled', 'true');
        }
    }

    function submitForm(form, submitter) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
        } else {
            form.submit();
        }
    }

    function initConfirm() {
        var dialog = document.querySelector('[data-confirm-dialog]');
        var pendingForm = null;
        var pendingSubmitter = null;

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.hasAttribute('data-busy')) {
                event.preventDefault();
                return;
            }

            if (form.hasAttribute('data-confirm') && !form.hasAttribute('data-confirmed')) {
                event.preventDefault();
                var message = form.getAttribute('data-confirm');

                if (!dialog || typeof dialog.showModal !== 'function') {
                    if (window.confirm(message)) {
                        form.setAttribute('data-confirmed', '');
                        submitForm(form, event.submitter);
                    }
                    return;
                }

                var mark = message.indexOf('?');
                var danger = form.getAttribute('data-confirm-tone') === 'danger';
                var ok = dialog.querySelector('[data-confirm-ok]');
                dialog.querySelector('#confirm-title').textContent = mark === -1 ? message : message.slice(0, mark + 1);
                dialog.querySelector('#confirm-text').textContent = mark === -1 ? '' : message.slice(mark + 1).trim();
                dialog.querySelector('[data-confirm-icon]').classList.toggle('is-danger', danger);
                ok.textContent = form.getAttribute('data-confirm-ok') || 'Confirm';
                ok.className = 'btn ' + (danger ? 'btn-danger-solid' : 'btn-primary');
                pendingForm = form;
                pendingSubmitter = event.submitter || null;
                openDialog(dialog);
                return;
            }

            form.removeAttribute('data-confirmed');
            markBusy(form, event.submitter);
        });

        if (!dialog) {
            return;
        }

        dialog.querySelector('[data-confirm-ok]').addEventListener('click', function () {
            var form = pendingForm;
            var submitter = pendingSubmitter;
            pendingForm = null;
            pendingSubmitter = null;
            closeDialog(dialog);
            if (form) {
                form.setAttribute('data-confirmed', '');
                submitForm(form, submitter);
            }
        });

        dialog.querySelector('[data-confirm-cancel]').addEventListener('click', function () {
            pendingForm = null;
            closeDialog(dialog);
        });
    }

    /* ----------------------------------------------------------------------
       Toasts: slide up, stay a moment, fade away
       ---------------------------------------------------------------------- */

    function initFlash() {
        document.querySelectorAll('[data-flash]').forEach(function (flash) {
            if (canAnimate(flash)) {
                flash.animate([
                    { opacity: 0, transform: 'translate(-50%, 12px)' },
                    { opacity: 1, transform: 'translate(-50%, 0)' }
                ], { duration: 260, easing: EASE });
            }
            window.setTimeout(function () {
                if (!canAnimate(flash)) {
                    flash.remove();
                    return;
                }
                var out = flash.animate([
                    { opacity: 1, transform: 'translate(-50%, 0)' },
                    { opacity: 0, transform: 'translate(-50%, 12px)' }
                ], { duration: 220, easing: 'ease-in', fill: 'forwards' });
                out.onfinish = function () { flash.remove(); };
            }, 4200);
        });
    }

    /* ----------------------------------------------------------------------
       Skeleton screens while the next page loads
       ---------------------------------------------------------------------- */

    function hideNavSkeleton() {
        var main = document.querySelector('[data-page]');
        if (!main) {
            return;
        }
        main.classList.remove('is-navigating');
        main.removeAttribute('aria-busy');
        main.querySelectorAll(':scope > .nav-skeleton').forEach(function (node) { node.remove(); });
    }

    function showNavSkeleton(type) {
        var main = document.querySelector('[data-page]');
        if (!main || !document.getElementById('sk-' + type)) {
            return;
        }
        hideNavSkeleton();
        closeMenus();
        var wrap = document.createElement('div');
        wrap.className = 'nav-skeleton';
        wrap.setAttribute('aria-hidden', 'true');
        wrap.appendChild(templateContent('sk-' + type));
        main.prepend(wrap);
        main.classList.add('is-navigating');
        main.setAttribute('aria-busy', 'true');
        window.scrollTo(0, 0);
    }

    function initNavSkeletons() {
        document.addEventListener('click', function (event) {
            if (event.defaultPrevented || !isPlainClick(event)) {
                return;
            }
            var link = event.target.closest('a[data-skeleton]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) {
                return;
            }
            var url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) {
                return;
            }
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
                return;
            }
            showNavSkeleton(link.getAttribute('data-skeleton'));
        });

        // Coming back with the browser's Back button restores the page as it was.
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) {
                return;
            }
            hideNavSkeleton();
            document.querySelectorAll('form[data-busy]').forEach(function (form) { form.removeAttribute('data-busy'); });
            document.querySelectorAll('.is-busy').forEach(function (button) {
                button.classList.remove('is-busy');
                button.removeAttribute('aria-disabled');
            });
        });
    }

    /* ----------------------------------------------------------------------
       Small form helpers. initWithin also runs on content loaded into the details pane.
       ---------------------------------------------------------------------- */

    function initRegister() {
        document.querySelectorAll('.role-option .role-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll('.role-option').forEach(function (option) {
                    option.classList.toggle('is-selected', option.contains(radio) && radio.checked);
                });
            });
        });

        // Register: show the Contract Entity fields only for that role.
        var roleRadios = document.querySelectorAll('[data-role-radio]');

        function syncRoleSections() {
            var checked = document.querySelector('[data-role-radio]:checked');
            var role = checked ? checked.value : '';
            document.querySelectorAll('[data-role-section]').forEach(function (section) {
                section.hidden = section.getAttribute('data-role-section') !== role;
            });
        }

        roleRadios.forEach(function (radio) {
            radio.addEventListener('change', syncRoleSections);
        });

        if (roleRadios.length) {
            syncRoleSections();
        }
    }

    // Search: limit the district list to the chosen state.
    function initDistricts(root) {
        var stateSelect = root.querySelector('[data-state-select]');
        var districtSelect = root.querySelector('[data-district-select]');
        if (!stateSelect || !districtSelect) {
            return;
        }

        function syncDistricts() {
            var state = stateSelect.value;
            districtSelect.querySelectorAll('optgroup').forEach(function (group) {
                var visible = state === '' || group.getAttribute('data-state') === state;
                group.hidden = !visible;
                group.disabled = !visible;
            });
            var selected = districtSelect.options[districtSelect.selectedIndex];
            if (selected && selected.parentElement.disabled) {
                districtSelect.value = '';
            }
        }

        stateSelect.addEventListener('change', syncDistricts);
        syncDistricts();
    }

    function initWithin(root) {
        // Report Listing: the note is required when the reason is "other".
        root.querySelectorAll('[data-report-reason]').forEach(function (reason) {
            var note = reason.form ? reason.form.querySelector('[data-report-note]') : null;
            if (!note) {
                return;
            }
            function syncNote() {
                note.required = reason.value === 'other';
            }
            reason.addEventListener('change', syncNote);
            syncNote();
        });

        // Submit Work: warn before uploading more files than allowed.
        root.querySelectorAll('input[type="file"][data-max-files]').forEach(function (input) {
            var max = parseInt(input.getAttribute('data-max-files'), 10);
            input.addEventListener('change', function () {
                if (input.files && input.files.length > max) {
                    window.alert('You can upload at most ' + max + ' files per submission.');
                    input.value = '';
                }
            });
        });

        // Star ratings: say the chosen rating in words.
        root.querySelectorAll('[data-rate-input]').forEach(function (group) {
            var label = group.nextElementSibling;
            group.addEventListener('change', function () {
                var checked = group.querySelector('input:checked');
                if (checked && label && label.hasAttribute('data-rate-label')) {
                    label.textContent = checked.getAttribute('data-word') + ' · ' + checked.value + ' out of 5';
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
       History: star rating dialog
       ---------------------------------------------------------------------- */

    function initRatingDialog() {
        var dialog = document.querySelector('[data-rate-dialog]');
        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }
        var form = dialog.querySelector('[data-rate-form]');
        var submit = dialog.querySelector('[data-rate-submit]');
        var title = dialog.querySelector('[data-rate-title]');
        var label = dialog.querySelector('[data-rate-label]');

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-rate]');
            if (!trigger || !isPlainClick(event)) {
                return;
            }
            event.preventDefault();
            form.reset();
            form.action = trigger.getAttribute('data-rate');
            title.textContent = 'Rate ' + trigger.getAttribute('data-rate-name');
            label.textContent = 'Choose a rating';
            submit.disabled = true;
            openDialog(dialog);
        });

        form.addEventListener('change', function () {
            submit.disabled = !form.querySelector('input[name="rating"]:checked');
        });

        dialog.querySelector('[data-rate-cancel]').addEventListener('click', function () {
            closeDialog(dialog);
        });
    }

    /* ----------------------------------------------------------------------
       Find Jobs: filters update in place, and job details slide in on the right
       ---------------------------------------------------------------------- */

    function initFindJobs() {
        if (!document.querySelector('[data-job-search]')) {
            return;
        }

        var wide = window.matchMedia('(min-width: 1000px)');
        var openUrl = null;
        var detailRequest = null;
        var resultsRequest = null;
        var morph = null;

        function region() { return document.querySelector('[data-results-region]'); }
        function parts() {
            var r = region();
            return {
                split: r && r.querySelector('[data-split]'),
                list: r && r.querySelector('[data-list-pane]'),
                pane: r && r.querySelector('[data-detail-pane]')
            };
        }
        function cards() {
            var r = region();
            return r ? Array.prototype.slice.call(r.querySelectorAll('[data-job-card]')) : [];
        }
        function isShown(el) { return !!el && el.getClientRects().length > 0; }

        /* ---- Card morph: transform and opacity only ----
           The layout changes in one step. Each card is then drawn from its old box to its new one with
           translate + scale, while its parts are counter-scaled so text never looks stretched. Parts that
           disappear (date, footer) fade out where they were; the location slides up after a beat. */

        var STEPS = 30;
        var RADIUS = 16;
        var PARTS = [
            { sel: '.gig-head', follow: false },
            { sel: '.gig-pay-row', follow: false },
            { sel: '.meta li:nth-child(1)', follow: false },
            { sel: '.meta li:nth-child(2)', follow: true },
            { sel: '.gig-foot', follow: false }
        ];

        function easeOut(t) { return 1 - Math.pow(1 - t, 4); }
        function lerp(a, b, t) { return a + (b - a) * t; }
        function clamp01(x) { return x < 0 ? 0 : (x > 1 ? 1 : x); }

        function localRect(el, cardRect) {
            if (!isShown(el)) {
                return null;
            }
            var r = el.getBoundingClientRect();
            return { x: r.left - cardRect.left, y: r.top - cardRect.top, w: r.width };
        }

        function snapshot(list) {
            return list.map(function (card) {
                var rect = card.getBoundingClientRect();
                return { rect: rect, parts: PARTS.map(function (part) { return localRect(card.querySelector(part.sel), rect); }) };
            });
        }

        function makeGhost(card, sel, r) {
            var source = card.querySelector(sel);
            var ghost;
            if (sel.indexOf('li') !== -1) {
                ghost = document.createElement('ul');
                ghost.className = 'meta gig-ghost';
                var item = source.cloneNode(true);
                item.classList.add('gig-ghost');
                ghost.appendChild(item);
            } else {
                ghost = source.cloneNode(true);
                ghost.classList.add('gig-ghost');
            }
            ghost.setAttribute('aria-hidden', 'true');
            ghost.style.left = (r.x - card.clientLeft) + 'px';
            ghost.style.top = (r.y - card.clientTop) + 'px';
            ghost.style.width = r.w + 'px';
            card.appendChild(ghost);
            return ghost;
        }

        function counterFrame(t, vx, vy, lx, ly, csx, csy, opacity) {
            return {
                offset: t,
                transformOrigin: '0 0',
                opacity: opacity,
                transform: 'translate(' + (vx / csx - lx) + 'px, ' + (vy / csy - ly) + 'px) scale(' + (1 / csx) + ', ' + (1 / csy) + ')'
            };
        }

        function finishMorph() {
            if (!morph) {
                return;
            }
            var m = morph;
            morph = null;
            if (m.onDone) {
                m.onDone();
            }
            m.anims.forEach(function (animation) { animation.cancel(); });
            m.ghosts.forEach(function (ghost) { ghost.remove(); });
        }

        function morphCards(list, before, opts) {
            var m = { anims: [], ghosts: [], onDone: opts.onDone || null };
            var shift = opts.shiftY || 0;
            var measured = list.map(function (card) {
                var rect = card.getBoundingClientRect();
                return { rect: rect, parts: PARTS.map(function (part) { return localRect(card.querySelector(part.sel), rect); }) };
            });

            list.forEach(function (card, i) {
                var A = before[i].rect;
                var B = measured[i].rect;
                if (!A.width || !B.width) {
                    return;
                }
                var dx = A.left - B.left;
                var dy = A.top - B.top;
                var sx = A.width / B.width;
                var sy = A.height / B.height;
                var endsHidden = opts.clip && (B.bottom + shift < opts.clip.top || B.top + shift > opts.clip.bottom);
                var startsHidden = opts.startClip && (A.bottom < opts.startClip.top || A.top > opts.startClip.bottom);
                var timing = { duration: opts.duration, delay: Math.min(i, 8) * 24, easing: 'linear', fill: 'both' };
                var cardFrames = [];
                var partFrames = PARTS.map(function () { return []; });
                var targets = PARTS.map(function (part, j) {
                    var r0 = before[i].parts[j];
                    var r1 = measured[i].parts[j];
                    if (r0 && !r1) {
                        var ghost = makeGhost(card, part.sel, r0);
                        m.ghosts.push(ghost);
                        return ghost;
                    }
                    return (r0 || r1) ? card.querySelector(part.sel) : null;
                });

                for (var k = 0; k <= STEPS; k++) {
                    var t = k / STEPS;
                    var p = easeOut(t);
                    var csx = lerp(sx, 1, p);
                    var csy = lerp(sy, 1, p);
                    var opacity = 1;
                    if (endsHidden) {
                        opacity = 1 - clamp01((t - 0.45) / 0.55);
                    }
                    if (startsHidden) {
                        opacity = clamp01(t / 0.45);
                    }
                    cardFrames.push({
                        offset: t,
                        transformOrigin: '0 0',
                        opacity: opacity,
                        transform: 'translate(' + (dx * (1 - p)) + 'px, ' + (dy * (1 - p) + shift * p) + 'px) scale(' + csx + ', ' + csy + ')',
                        borderRadius: (RADIUS / csx).toFixed(2) + 'px / ' + (RADIUS / csy).toFixed(2) + 'px'
                    });

                    for (var j = 0; j < PARTS.length; j++) {
                        var r0 = before[i].parts[j];
                        var r1 = measured[i].parts[j];
                        if (r0 && r1) {
                            // Moves inside the card; the location waits a beat so the date can fade first.
                            var q = PARTS[j].follow ? easeOut(clamp01((t - 0.18) / 0.82)) : p;
                            partFrames[j].push(counterFrame(t, lerp(r0.x, r1.x, q), lerp(r0.y, r1.y, q), r1.x, r1.y, csx, csy, 1));
                        } else if (r0) {
                            // Disappears: fades out quickly where it was.
                            partFrames[j].push(counterFrame(t, r0.x, r0.y, r0.x, r0.y, csx, csy, 1 - easeOut(clamp01(t / 0.4))));
                        } else if (r1) {
                            // Appears: fades in once the card has nearly reached its new shape.
                            partFrames[j].push(counterFrame(t, r1.x, r1.y, r1.x, r1.y, csx, csy, easeOut(clamp01((t - 0.45) / 0.55))));
                        }
                    }
                }

                m.anims.push(card.animate(cardFrames, timing));
                targets.forEach(function (el, j) {
                    if (el && partFrames[j].length) {
                        m.anims.push(el.animate(partFrames[j], timing));
                    }
                });
            });

            morph = m;
            Promise.all(m.anims.map(function (animation) { return animation.finished; })).then(function () {
                if (morph === m) {
                    finishMorph();
                }
            }, function () {});
        }

        // Moves the page's bottom space inside the list while the details are open (see .has-split in app.css).
        function setPageSplit(on) {
            var main = document.querySelector('[data-page]');
            if (main) {
                main.classList.toggle('has-split', on);
            }
        }

        // Keep a card on screen, just under the top bar, after the layout changes.
        // Runs before the card animation measures where things ended up, so the motion stays continuous.
        function revealCard(card) {
            if (!card || !isShown(card)) {
                return;
            }
            var nav = document.querySelector('[data-topnav]');
            var limit = (nav ? nav.getBoundingClientRect().bottom : 0) + 12;
            var rect = card.getBoundingClientRect();
            if (rect.top < limit || rect.bottom > window.innerHeight - 12) {
                window.scrollBy(0, rect.top - limit - 4);
            }
        }

        function markActive(activeCard) {
            cards().forEach(function (card) {
                var on = card === activeCard;
                card.classList.toggle('is-active', on);
                var link = card.querySelector('[data-job-open]');
                if (link) {
                    if (on) {
                        link.setAttribute('aria-current', 'true');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                }
            });
        }

        function openSplit(p) {
            finishMorph();
            var list = cards();
            var animate = canAnimate(p.pane);
            var before = animate ? snapshot(list) : null;
            stopAnims(p.pane);
            p.pane.removeAttribute('style');
            p.pane.hidden = false;
            p.split.classList.add('is-split');
            setPageSplit(true);
            revealCard(p.list.querySelector('.gig.is-active'));

            if (!animate) {
                return;
            }

            morphCards(list, before, { duration: 600 });

            p.pane.animate([
                { opacity: 0, transform: 'translateX(40px)' },
                { opacity: 1, transform: 'none' }
            ], { duration: 480, easing: EASE, delay: 80, fill: 'backwards' });
        }

        function closePane(animate) {
            var p = parts();
            if (detailRequest) {
                detailRequest.abort();
                detailRequest = null;
            }
            if (!p.pane || p.pane.hidden) {
                openUrl = null;
                return;
            }
            finishMorph();
            var list = cards();
            var useAnimation = animate && canAnimate(p.pane);
            var listWasShown = isShown(p.list);
            var before = useAnimation ? snapshot(list) : null;
            var activeCard = p.list.querySelector('.gig.is-active');
            var activeLink = activeCard && activeCard.querySelector('[data-job-open]');
            stopAnims(p.pane);
            openUrl = null;
            markActive(null);

            if (!useAnimation) {
                p.split.classList.remove('is-split');
                setPageSplit(false);
                p.pane.hidden = true;
                revealCard(activeCard);
                return;
            }

            // Lift the pane out of the layout so the grid can spread out underneath while it fades away.
            var pr = p.pane.getBoundingClientRect();
            var wr = p.split.getBoundingClientRect();
            p.pane.style.position = 'absolute';
            p.pane.style.right = (wr.right - pr.right) + 'px';
            p.pane.style.width = pr.width + 'px';
            p.pane.style.zIndex = '3';
            p.split.classList.remove('is-split');
            setPageSplit(false);
            revealCard(activeCard);
            // Pin the fading pane to where it was on screen, even if the page scrolled.
            p.pane.style.top = (pr.top - p.split.getBoundingClientRect().top) + 'px';

            if (listWasShown) {
                morphCards(list, before, { duration: 560 });
            }

            var out = p.pane.animate([
                { opacity: 1, transform: 'none' },
                { opacity: 0, transform: 'translateX(32px)' }
            ], { duration: 220, easing: 'cubic-bezier(0.4, 0, 1, 1)', fill: 'forwards' });
            out.onfinish = function () {
                p.pane.hidden = true;
                p.pane.removeAttribute('style');
                out.cancel();
            };

            if (activeLink) {
                activeLink.focus({ preventScroll: true });
            }
        }

        function loadDetail(url, pane) {
            var body = pane.querySelector('[data-pane-body]');
            body.classList.remove('is-fresh');
            body.textContent = '';
            body.appendChild(templateContent('sk-pane'));
            pane.scrollTop = 0;
            pane.setAttribute('aria-busy', 'true');

            if (detailRequest) {
                detailRequest.abort();
            }
            var request = window.AbortController ? new AbortController() : null;
            detailRequest = request;

            // Keep the skeleton up for a moment so fast loads don't flicker.
            Promise.all([fetchPage(url, request ? request.signal : undefined), delay(300)]).then(function (results) {
                if (openUrl !== url) {
                    return;
                }
                var detail = results[0].doc.querySelector('[data-job-detail]');
                if (!detail) {
                    throw new Error('No job details on that page');
                }
                body.textContent = '';
                body.appendChild(document.adoptNode(detail));
                void body.offsetWidth;
                body.classList.add('is-fresh');
                pane.removeAttribute('aria-busy');
                initWithin(body);
            }).catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                window.location.href = url;
            });
        }

        function openPane(card, url) {
            var p = parts();
            if (!p.pane || !p.split) {
                window.location.href = url;
                return;
            }
            if (url === openUrl) {
                return;
            }
            var wasOpen = !p.pane.hidden;
            openUrl = url;
            markActive(card);
            if (!wasOpen) {
                openSplit(p);
            }
            p.pane.querySelector('[data-pane-full]').href = url;
            loadDetail(url, p.pane);
        }

        /* ---- Results that update in place ---- */

        function loadResults(url, push) {
            closePane(false);
            finishMorph();
            var r = region();
            var p = parts();
            if (p.list) {
                p.list.textContent = '';
                p.list.appendChild(templateContent('sk-cards'));
            }
            var count = r && r.querySelector('.results-count');
            if (count) {
                count.textContent = 'Loading jobs…';
            }
            if (r) {
                r.setAttribute('aria-busy', 'true');
            }

            if (resultsRequest) {
                resultsRequest.abort();
            }
            var request = window.AbortController ? new AbortController() : null;
            resultsRequest = request;

            Promise.all([fetchPage(url, request ? request.signal : undefined), delay(300)]).then(function (results) {
                var page = results[0];
                var newRegion = page.doc.querySelector('[data-results-region]');
                var newForm = page.doc.querySelector('[data-job-search]');
                if (!newRegion || !newForm) {
                    throw new Error('Unexpected page');
                }
                var oldForm = document.querySelector('[data-job-search]');
                var moreWasOpen = oldForm && oldForm.querySelector('details.more[open]');
                region().replaceWith(document.adoptNode(newRegion));
                oldForm.replaceWith(document.adoptNode(newForm));
                if (moreWasOpen) {
                    var more = newForm.querySelector('details.more');
                    if (more) {
                        more.open = true;
                    }
                }
                initDistricts(newForm);
                initPayFilter(newForm);
                if (push) {
                    window.history.pushState({ findJobs: true }, '', page.url);
                }
                cards().forEach(function (card, i) {
                    if (canAnimate(card)) {
                        card.animate([
                            { opacity: 0, transform: 'translateY(8px)' },
                            { opacity: 1, transform: 'none' }
                        ], { duration: 320, easing: EASE, delay: Math.min(i, 8) * 30, fill: 'backwards' });
                    }
                });
            }).catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                window.location.href = url;
            });
        }

        function searchUrl(form) {
            var params = new URLSearchParams();
            new FormData(form).forEach(function (value, key) {
                if (typeof value === 'string' && value !== '') {
                    params.append(key, value);
                }
            });
            var query = params.toString();
            return form.action + (query ? '?' + query : '');
        }

        document.addEventListener('submit', function (event) {
            var form = event.target.closest && event.target.closest('[data-job-search]');
            if (!form) {
                return;
            }
            event.preventDefault();
            loadResults(searchUrl(form), true);
        });

        document.addEventListener('click', function (event) {
            if (event.defaultPrevented || !isPlainClick(event)) {
                return;
            }

            var nav = event.target.closest('[data-job-nav], [data-results-region] .pagination a');
            if (nav) {
                event.preventDefault();
                loadResults(nav.href, true);
                return;
            }

            if (event.target.closest('[data-pane-close]')) {
                closePane(true);
                return;
            }

            var card = event.target.closest('[data-job-card]');
            var link = card && card.querySelector('[data-job-open]');
            if (!link) {
                return;
            }
            if (!wide.matches) {
                showNavSkeleton('detail');
                return;
            }
            event.preventDefault();
            openPane(card, link.href);
        });

        document.addEventListener('keydown', function (event) {
            var p = parts();
            if (event.key === 'Escape' && !event.defaultPrevented && p.pane && !p.pane.hidden && !document.querySelector('dialog[open]')) {
                closePane(true);
            }
        });

        // Going narrow while the pane is open: fall back to the plain grid.
        if (typeof wide.addEventListener === 'function') {
            wide.addEventListener('change', function () {
                if (!wide.matches) {
                    closePane(false);
                }
            });
        }

        initPayFilter(document.querySelector('[data-job-search]'));

        window.history.replaceState({ findJobs: true }, '', window.location.href);
        window.addEventListener('popstate', function () {
            loadResults(window.location.href, false);
        });
    }

    /* ----------------------------------------------------------------------
       Start
       ---------------------------------------------------------------------- */

    /* ----------------------------------------------------------------------
       Find Jobs pay filter: pay type switch, two-handle slider, a dot per gig
         data-pay-filter with data-floor, data-ceil, data-step, data-amounts (from the server)
       ---------------------------------------------------------------------- */

    function initPayFilter(form) {
        var box = form && form.querySelector('[data-pay-filter]');
        if (!box) {
            return;
        }
        var submitTimer = null;

        function submitNow() {
            window.clearTimeout(submitTimer);
            submitForm(form);
        }

        function submitSoon() {
            window.clearTimeout(submitTimer);
            submitTimer = window.setTimeout(submitNow, 350);
        }

        // A new pay type has its own range, so the old amounts are cleared and the results reload.
        box.querySelectorAll('[data-pay-type]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                box.querySelectorAll('[data-pay-min], [data-pay-max]').forEach(function (input) { input.value = ''; });
                submitNow();
            });
        });

        if (!box.hasAttribute('data-floor')) {
            return;
        }

        var floor = Number(box.getAttribute('data-floor'));
        var ceil = Number(box.getAttribute('data-ceil'));
        var step = Number(box.getAttribute('data-step')) || 1;
        var amounts = [];
        try {
            amounts = JSON.parse(box.getAttribute('data-amounts') || '[]');
        } catch (error) {
            amounts = [];
        }

        var slider = box.querySelector('[data-pay-slider]');
        var lo = box.querySelector('[data-pay-lo]');
        var hi = box.querySelector('[data-pay-hi]');
        var minBox = box.querySelector('[data-pay-min]');
        var maxBox = box.querySelector('[data-pay-max]');
        var fill = box.querySelector('[data-pay-fill]');
        var dots = box.querySelector('[data-pay-dots]');
        var count = box.querySelector('[data-pay-count]');
        if (!slider || !lo || !hi || !minBox || !maxBox || ceil <= floor) {
            return;
        }

        slider.hidden = false;
        [lo, hi].forEach(function (input) {
            input.min = floor;
            input.max = ceil;
            input.step = step;
        });

        function clamp(n) { return Math.min(Math.max(n, floor), ceil); }
        function pct(n) { return (clamp(n) - floor) / (ceil - floor) * 100; }
        function readBox(input, fallback) {
            var value = parseFloat(input.value);
            return isNaN(value) ? fallback : clamp(value);
        }

        var low = readBox(minBox, floor);
        var high = readBox(maxBox, ceil);
        if (low > high) {
            var swap = low;
            low = high;
            high = swap;
        }
        lo.value = low;
        hi.value = high;

        dots.textContent = '';
        amounts.forEach(function (amount) {
            var dot = document.createElement('span');
            dot.className = 'pay-dot';
            dot.style.left = pct(amount) + '%';
            dot.setAttribute('data-amount', amount);
            dots.appendChild(dot);
        });

        function render() {
            fill.style.left = pct(low) + '%';
            fill.style.right = (100 - pct(high)) + '%';
            var inRange = 0;
            dots.querySelectorAll('.pay-dot').forEach(function (dot) {
                var amount = Number(dot.getAttribute('data-amount'));
                var inside = amount >= low && amount <= high;
                dot.classList.toggle('is-out', !inside);
                if (inside) {
                    inRange++;
                }
            });
            count.textContent = inRange + (inRange === 1 ? ' gig' : ' gigs') + ' in this range';
            // When both handles meet at the top, keep the lower one grabbable.
            hi.style.zIndex = '2';
            lo.style.zIndex = low >= ceil - step ? '3' : '1';
        }

        // The ends of the slider mean "no limit", so the boxes stay empty there.
        function syncBoxes() {
            minBox.value = low <= floor ? '' : low;
            maxBox.value = high >= ceil ? '' : high;
        }

        function onSlide(which) {
            var a = Number(lo.value);
            var b = Number(hi.value);
            if (a > b - step) {
                if (which === 'lo') {
                    a = Math.max(floor, b - step);
                    lo.value = a;
                } else {
                    b = Math.min(ceil, a + step);
                    hi.value = b;
                }
            }
            low = a;
            high = b;
            syncBoxes();
            render();
        }

        function onBox() {
            low = readBox(minBox, floor);
            high = readBox(maxBox, ceil);
            if (low > high) {
                var swapped = low;
                low = high;
                high = swapped;
            }
            lo.value = low;
            hi.value = high;
            syncBoxes();
            render();
            submitNow();
        }

        lo.addEventListener('input', function () { onSlide('lo'); });
        hi.addEventListener('input', function () { onSlide('hi'); });
        // Results refresh when a handle is let go, not on every pixel of the drag.
        lo.addEventListener('change', submitSoon);
        hi.addEventListener('change', submitSoon);
        minBox.addEventListener('change', onBox);
        maxBox.addEventListener('change', onBox);
        render();
    }

    /* ----------------------------------------------------------------------
       Comment thread: one reply box open at a time, Ctrl + Enter sends, Esc closes
       ---------------------------------------------------------------------- */

    function closeReply(details) {
        details.open = false;
        var summary = details.querySelector('summary');
        if (summary) {
            summary.focus();
        }
    }

    function initReplies() {
        // "toggle" does not bubble, so listen while it travels down.
        document.addEventListener('toggle', function (event) {
            var details = event.target;
            if (!details.matches || !details.matches('details[data-reply]') || !details.open) {
                return;
            }
            document.querySelectorAll('details[data-reply][open]').forEach(function (other) {
                if (other !== details) {
                    other.open = false;
                }
            });
            var field = details.querySelector('textarea');
            if (field) {
                field.focus();
            }
        }, true);

        document.addEventListener('click', function (event) {
            var cancel = event.target.closest('[data-reply-cancel]');
            var details = cancel && cancel.closest('details[data-reply]');
            if (details) {
                closeReply(details);
            }
        });

        document.addEventListener('keydown', function (event) {
            var field = event.target;
            if (!(field instanceof HTMLTextAreaElement) || !field.form || !field.closest('details[data-reply], .composer')) {
                return;
            }
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                if (field.value.trim() !== '') {
                    submitForm(field.form);
                }
            } else if (event.key === 'Escape' && field.closest('details[data-reply]')) {
                event.preventDefault();
                closeReply(field.closest('details[data-reply]'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initModals();
        initReplies();
        initTopnav();
        initConfirm();
        initFlash();
        initNavSkeletons();
        initRegister();
        initDistricts(document);
        initWithin(document);
        initRatingDialog();
        initFindJobs();
    });
})();
