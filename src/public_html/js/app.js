// app.js - InicializaciÃ³n de PWA
(function() {
    'use strict';

    const medisoftOfflineEnabled = window.MEDISOFT_OFFLINE_ENABLED === true;

    // Verificar soporte de Service Worker
    if (medisoftOfflineEnabled && 'serviceWorker' in navigator && !window.BASE_URL) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register((window.BASE_URL || '') + '/service-worker.js', {
                scope: (window.BASE_URL || '') + '/'
            })
                .then(registration => {
                    console.log('Service Worker registrado:', registration);
                    
                    // Verificar actualizaciones
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'activated') {
                                // Mostrar notificaciÃ³n de actualizaciÃ³n
                                if (window.confirm('Nueva versiÃ³n disponible. Â¿Desea actualizar?')) {
                                    window.location.reload();
                                }
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Error al registrar Service Worker:', error);
                });
        });
    }

    // Detectar si la app estÃ¡ instalada
    let deferredPrompt;
    const installButton = document.getElementById('install-button');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Mostrar botÃ³n de instalaciÃ³n
        if (installButton) {
            installButton.style.display = 'block';
            
            installButton.addEventListener('click', () => {
                deferredPrompt.prompt();
                
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Usuario aceptÃ³ instalar la PWA');
                    }
                    deferredPrompt = null;
                });
            });
        }
    });

    // Detectar modo standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App ejecutÃ¡ndose en modo standalone');
        document.body.classList.add('pwa-standalone');
    }

    // Manejo offline/online
    function updateOnlineStatus() {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            if (navigator.onLine) {
                statusElement.textContent = 'En lÃ­nea';
                statusElement.className = 'text-green-600';
            } else {
                statusElement.textContent = 'Sin conexiÃ³n';
                statusElement.className = 'text-red-600';
            }
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // API de VibraciÃ³n (para notificaciones tÃ¡ctiles)
    window.vibrar = function(duration = 200) {
        if ('vibrate' in navigator) {
            navigator.vibrate(duration);
        }
    };

    // Compartir nativo
    window.compartir = function(titulo, texto, url) {
        if (navigator.share) {
            navigator.share({
                title: titulo,
                text: texto,
                url: url || window.location.href
            }).catch(console.error);
        } else {
            // Fallback: copiar al portapapeles
            const shareText = `${titulo}\n${texto}\n${url || window.location.href}`;
            navigator.clipboard.writeText(shareText).then(() => {
                (window.msToast ? window.msToast('success', null, 'Enlace copiado al portapapeles') : alert('Enlace copiado al portapapeles'));
            });
        }
    };

    function initMedisoftSilentDownloads() {
        if (document.documentElement.dataset.medisoftSilentDownloads === 'ready') {
            return;
        }

        document.documentElement.dataset.medisoftSilentDownloads = 'ready';

        document.addEventListener('click', event => {
            const link = event.target instanceof Element
                ? event.target.closest('a[data-medisoft-download="silent"]')
                : null;

            if (!link || event.defaultPrevented || event.button !== 0) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const href = link.getAttribute('href') || '';
            if (!href || href === '#' || href.startsWith('javascript:')) {
                return;
            }

            let downloadUrl;
            try {
                downloadUrl = new URL(href, window.location.href);
            } catch (error) {
                return;
            }

            if (downloadUrl.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            getMedisoftSilentDownloadFrame().src = downloadUrl.href;
        }, true);
    }

    function getMedisoftSilentDownloadFrame() {
        const frameId = 'medisoft-silent-download-frame';
        let frame = document.getElementById(frameId);

        if (!(frame instanceof HTMLIFrameElement)) {
            frame = document.createElement('iframe');
            frame.id = frameId;
            frame.name = frameId;
            frame.title = 'Descargas de documentos';
            frame.hidden = true;
            frame.tabIndex = -1;
            frame.style.display = 'none';
            frame.setAttribute('aria-hidden', 'true');
            document.body.appendChild(frame);
        }

        return frame;
    }

    function initMedisoftScrollMemory() {
        if (!document.body || !document.body.classList.contains('hotel-layout-scope')) {
            return;
        }

        if (document.documentElement.dataset.medisoftScrollMemory === 'ready') {
            return;
        }

        document.documentElement.dataset.medisoftScrollMemory = 'ready';

        const scrollTarget = getMedisoftScrollTarget();
        const storageKey = getMedisoftScrollKey();
        let saveTimer = 0;

        if ('scrollRestoration' in window.history) {
            try {
                window.history.scrollRestoration = 'manual';
            } catch (error) {}
        }

        restoreMedisoftScrollIfNeeded(scrollTarget, storageKey);

        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                restoreMedisoftScrollIfNeeded(scrollTarget, storageKey, true);
            }
        });

        const scheduleSave = () => {
            if (saveTimer) {
                return;
            }

            saveTimer = window.setTimeout(() => {
                saveTimer = 0;
                saveMedisoftScroll(scrollTarget, storageKey);
            }, 120);
        };

        getMedisoftScrollEventTarget(scrollTarget).addEventListener('scroll', scheduleSave, { passive: true });
        window.addEventListener('pagehide', () => saveMedisoftScroll(scrollTarget, storageKey), { passive: true });
        window.addEventListener('beforeunload', () => saveMedisoftScroll(scrollTarget, storageKey));
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                saveMedisoftScroll(scrollTarget, storageKey);
            }
        });

        document.addEventListener('click', event => {
            const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (!link || event.defaultPrevented || shouldIgnoreScrollMemoryLink(link, event)) {
                return;
            }

            saveMedisoftScroll(scrollTarget, storageKey);
        }, true);

        document.addEventListener('submit', () => saveMedisoftScroll(scrollTarget, storageKey), true);
    }

    function getMedisoftScrollTarget() {
        const mainContent = document.querySelector('.main-content');

        if (mainContent instanceof HTMLElement) {
            const styles = window.getComputedStyle(mainContent);
            const canScroll = /auto|scroll|overlay/i.test(styles.overflowY);

            if (canScroll) {
                return mainContent;
            }
        }

        return document.scrollingElement || document.documentElement;
    }

    function getMedisoftScrollEventTarget(target) {
        return isMedisoftDocumentScroll(target) ? window : target;
    }

    function isMedisoftDocumentScroll(target) {
        return target === document.documentElement
            || target === document.body
            || target === document.scrollingElement;
    }

    function getMedisoftScrollKey() {
        const hotelId = window.MEDISOFT_CONTEXT && window.MEDISOFT_CONTEXT.hotel_id
            ? String(window.MEDISOFT_CONTEXT.hotel_id)
            : 'hotel';
        return 'medisoft:hotel-scroll:v1:' + hotelId + ':' + window.location.pathname + window.location.search;
    }

    function shouldIgnoreScrollMemoryLink(link, event) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return true;
        }

        const href = link.getAttribute('href') || '';
        if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return true;
        }

        if (link.hasAttribute('download') || String(link.getAttribute('target') || '').toLowerCase() === '_blank') {
            return true;
        }

        try {
            const url = new URL(href, window.location.href);
            return url.origin !== window.location.origin;
        } catch (error) {
            return true;
        }
    }

    function saveMedisoftScroll(target, storageKey) {
        const position = getMedisoftScrollPosition(target);

        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify({
                top: position.top,
                left: position.left,
                savedAt: Date.now()
            }));
        } catch (error) {}
    }

    function getMedisoftScrollPosition(target) {
        if (isMedisoftDocumentScroll(target)) {
            return {
                top: window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0,
                left: window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0
            };
        }

        return {
            top: target.scrollTop || 0,
            left: target.scrollLeft || 0
        };
    }

    function restoreMedisoftScrollIfNeeded(target, storageKey, forceRestore) {
        if (!forceRestore && !shouldRestoreMedisoftScroll()) {
            return;
        }

        const record = readMedisoftScrollRecord(storageKey);
        if (!record) {
            return;
        }

        const restore = attempt => {
            const maxTop = getMedisoftMaxScrollTop(target);
            const top = Math.max(0, Math.min(record.top, maxTop));
            setMedisoftScrollPosition(target, top, record.left);

            if (attempt < 4 && record.top > maxTop + 4) {
                window.setTimeout(() => restore(attempt + 1), attempt === 0 ? 80 : 180);
            }
        };

        window.requestAnimationFrame(() => restore(0));
    }

    function shouldRestoreMedisoftScroll() {
        const navigationEntries = typeof performance !== 'undefined' && performance.getEntriesByType
            ? performance.getEntriesByType('navigation')
            : [];
        const navigationType = navigationEntries && navigationEntries[0]
            ? navigationEntries[0].type
            : '';

        if (navigationType === 'back_forward') {
            return true;
        }

        if (window.performance && window.performance.navigation) {
            return window.performance.navigation.type === 2;
        }

        return false;
    }

    function readMedisoftScrollRecord(storageKey) {
        try {
            const raw = window.sessionStorage.getItem(storageKey);
            if (!raw) {
                return null;
            }

            const record = JSON.parse(raw);
            if (!record || !Number.isFinite(Number(record.top))) {
                return null;
            }

            return {
                top: Number(record.top) || 0,
                left: Number(record.left) || 0
            };
        } catch (error) {
            return null;
        }
    }

    function getMedisoftMaxScrollTop(target) {
        if (isMedisoftDocumentScroll(target)) {
            const doc = document.documentElement;
            const body = document.body;
            return Math.max(0, Math.max(doc.scrollHeight, body ? body.scrollHeight : 0) - window.innerHeight);
        }

        return Math.max(0, target.scrollHeight - target.clientHeight);
    }

    function setMedisoftScrollPosition(target, top, left) {
        if (isMedisoftDocumentScroll(target)) {
            window.scrollTo({ top: top, left: left || 0, behavior: 'auto' });
            return;
        }

        target.scrollTop = top;
        target.scrollLeft = left || 0;
    }


    // Proteccion transversal de formularios del sistema hotelero:
    // errores visibles antes de enviar y recuperacion de borradores tras errores.
    const hotelFormDraftPrefix = 'medisoft:hotel-form-draft:';
    const hotelFormDraftMaxAge = 8 * 60 * 60 * 1000;
    const hotelPendingSubmitKey = 'medisoft:hotel-pending-form-submit';
    const hotelCompletedFormPrefix = 'medisoft:hotel-completed-form:';
    const hotelCompletedFormMaxAge = 45 * 60 * 1000;
    const hotelFormSaveTimers = new WeakMap();
    const hotelFormSubmitTimers = new WeakMap();
    const hotelFormSubmitState = new WeakMap();
    const hotelPreventiveFieldTimers = new WeakMap();
    const hotelPreventiveGroupTimers = new WeakMap();
    const hotelUnsavedForms = new WeakMap();
    const hotelUnsavedTrackedForms = new Set();
    let hotelNativeSubmitPatchInstalled = false;

    function initHotelFormGuard() {
        if (!document.body || !document.body.classList.contains('hotel-layout-scope')) {
            return;
        }

        if (document.documentElement.dataset.medisoftFormGuard === 'ready') {
            return;
        }
        document.documentElement.dataset.medisoftFormGuard = 'ready';
        const forms = Array.from(document.forms || []);
        const pageHasError = hasServerError();
        const pageHasSuccess = hasServerSuccess();

        injectFormGuardStyles();
        cleanupExpiredDrafts();
        cleanupCompletedFormRoutes();

        if (initHotelSmartNavigation(pageHasSuccess)) {
            return;
        }

        if (pageHasSuccess) {
            clearDraftsForCurrentPath();
        }

        forms.forEach((form, index) => {
            if (!isGuardedForm(form)) {
                return;
            }

            form.dataset.medisoftFormIndex = form.dataset.medisoftFormIndex || String(index);
            const draftKey = getDraftKey(form);

            if (pageHasError && shouldPersistForm(form)) {
                const restored = restoreDraft(form, draftKey);
                if (restored) {
                    showRecoveredNotice(form, draftKey);
                }
            }

            setupUnsavedTracking(form);

            form.addEventListener('input', event => {
                const field = getFormField(event.target);
                if (!field) {
                    return;
                }

                clearFieldError(field);
                scheduleDraftSave(form, draftKey);
                updateUnsavedState(form);
            });

            form.addEventListener('change', event => {
                const field = getFormField(event.target);
                if (!field) {
                    return;
                }

                clearFieldError(field);
                scheduleDraftSave(form, draftKey);
                updateUnsavedState(form);
            });

            setupPreventiveValidation(form);

            form.addEventListener('reset', () => {
                clearDraft(draftKey);
                clearFormErrors(form);
                window.setTimeout(() => markFormClean(form), 0);
            });

            form.addEventListener('submit', event => {
                if (shouldUseSubmitState(form) && form.dataset.msSubmitting === '1') {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                if (event.defaultPrevented) {
                    scheduleDraftSave(form, draftKey, true);
                    return;
                }

                scheduleDraftSave(form, draftKey, true);
                validatePreventiveForm(form, true);

                const invalidField = getFirstInvalidField(form);
                if (invalidField) {
                    event.preventDefault();
                    markInvalidFields(form);
                    showValidationSummary(form, invalidField);
                    focusInvalidField(invalidField);
                    return;
                }

                markFormSubmitting(form, event.submitter);
                rememberPendingFormSubmission(form);

                window.setTimeout(() => {
                    if (event.defaultPrevented) {
                        updateUnsavedState(form);
                        restoreFormSubmitting(form);
                    }
                }, 0);
            });
        });

        renderServerFieldErrors();

        document.addEventListener('click', event => {
            const submitButton = getSubmitButton(event.target);
            if (!submitButton || !submitButton.form || submitButton.form.dataset.msSubmitting !== '1') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
        }, true);

        document.addEventListener('invalid', event => {
            const field = getFormField(event.target);
            if (!field) {
                return;
            }

            const form = field.form;
            if (!isGuardedForm(form)) {
                return;
            }

            showFieldError(field);
            showValidationSummary(form, field);
        }, true);

        installGuardedNativeSubmitPatch();
        initUnsavedNavigationGuard();
    }

    function isGuardedForm(form) {
        return form instanceof HTMLFormElement
            && form.dataset.formGuard !== 'off'
            && !form.closest('.ms-admin-scope');
    }

    function shouldPersistForm(form) {
        const method = String(form.getAttribute('method') || 'get').toLowerCase();
        const target = String(form.getAttribute('target') || '').toLowerCase();

        return method !== 'get'
            && target !== '_blank'
            && !form.matches('[data-auto-filter-form], [data-no-draft]');
    }

    function shouldTrackUnsavedForm(form) {
        return shouldPersistForm(form)
            && form.dataset.unsavedWarning !== 'off'
            && !form.matches('[data-no-unsaved-warning]');
    }

    function getFormField(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        const field = target.closest('input, select, textarea');
        if (!field || !field.form) {
            return null;
        }

        return field;
    }

    function getDraftKey(form) {
        const method = String(form.getAttribute('method') || 'get').toLowerCase();
        const action = normalizeUrl(form.getAttribute('action') || window.location.pathname);
        const identity = form.id || form.getAttribute('name') || `form-${form.dataset.medisoftFormIndex || '0'}`;
        const hotelId = window.MEDISOFT_CONTEXT && window.MEDISOFT_CONTEXT.hotel_id
            ? `hotel-${window.MEDISOFT_CONTEXT.hotel_id}`
            : 'hotel';

        return `${hotelFormDraftPrefix}${hotelId}:${window.location.pathname}:${method}:${action}:${identity}`;
    }

    function normalizeUrl(value) {
        try {
            return new URL(value || window.location.pathname, window.location.origin).pathname;
        } catch (error) {
            return String(value || '').split('?')[0] || window.location.pathname;
        }
    }

    function initHotelSmartNavigation(pageHasSuccess) {
        if (!document.body || !document.body.classList.contains('hotel-layout-scope')) {
            return false;
        }

        if (pageHasSuccess) {
            rememberCompletedFormFromSuccessfulNavigation();
        } else {
            clearStalePendingFormSubmission();
        }

        initSmartBackLinkGuard();

        const redirectUrl = getCompletedFormRedirectUrl();
        if (redirectUrl) {
            window.location.replace(redirectUrl);
            return true;
        }

        if (document.documentElement.dataset.medisoftSmartBackPageShow !== 'ready') {
            document.documentElement.dataset.medisoftSmartBackPageShow = 'ready';
            window.addEventListener('pageshow', event => {
                const targetUrl = getCompletedFormRedirectUrl(event);
                if (targetUrl) {
                    window.location.replace(targetUrl);
                }
            });
        }

        return false;
    }

    function rememberPendingFormSubmission(form) {
        if (!shouldPersistForm(form)) {
            return;
        }

        const current = getAppRoute(window.location.href);
        if (!current || !isSmartBackFormRoute(current.path)) {
            return;
        }

        try {
            window.sessionStorage.setItem(hotelPendingSubmitKey, JSON.stringify({
                createdAt: Date.now(),
                url: current.key,
                path: current.path,
                fallback: inferSmartBackFallback(current.path)
            }));
        } catch (error) {}
    }

    function rememberCompletedFormFromSuccessfulNavigation() {
        const pending = readPendingFormSubmission();
        const candidates = [];

        if (pending && pending.path && isSmartBackFormRoute(pending.path)) {
            candidates.push({
                key: pending.url || pathToSmartBackKey(pending.path, ''),
                path: pending.path,
                fallback: pending.fallback || inferSmartBackFallback(pending.path)
            });
        }

        const referrer = getAppRoute(document.referrer || '');
        if (referrer && isSmartBackFormRoute(referrer.path)) {
            candidates.push({
                key: referrer.key,
                path: referrer.path,
                fallback: inferSmartBackFallback(referrer.path)
            });
        }

        candidates.forEach(markCompletedFormRoute);

        try {
            window.sessionStorage.removeItem(hotelPendingSubmitKey);
        } catch (error) {}
    }

    function readPendingFormSubmission() {
        let pending = null;
        try {
            pending = JSON.parse(window.sessionStorage.getItem(hotelPendingSubmitKey) || 'null');
        } catch (error) {
            pending = null;
        }

        if (!pending || !pending.createdAt || Date.now() - pending.createdAt > hotelCompletedFormMaxAge) {
            try {
                window.sessionStorage.removeItem(hotelPendingSubmitKey);
            } catch (error) {}
            return null;
        }

        return pending;
    }

    function clearStalePendingFormSubmission() {
        readPendingFormSubmission();
        try {
            window.sessionStorage.removeItem(hotelPendingSubmitKey);
        } catch (error) {}
    }

    function markCompletedFormRoute(route) {
        if (!route || !route.path || !isSmartBackFormRoute(route.path)) {
            return;
        }

        const record = {
            createdAt: Date.now(),
            path: route.path,
            fallback: route.fallback || inferSmartBackFallback(route.path)
        };

        const keys = Array.from(new Set([
            route.key || pathToSmartBackKey(route.path, ''),
            pathToSmartBackKey(route.path, '')
        ]));

        try {
            keys.forEach(key => {
                window.sessionStorage.setItem(hotelCompletedFormPrefix + key, JSON.stringify(record));
            });
        } catch (error) {}
    }

    function cleanupCompletedFormRoutes() {
        try {
            Object.keys(window.sessionStorage)
                .filter(key => key === hotelPendingSubmitKey || key.indexOf(hotelCompletedFormPrefix) === 0)
                .forEach(key => {
                    try {
                        const value = JSON.parse(window.sessionStorage.getItem(key) || 'null');
                        if (!value || !value.createdAt || Date.now() - value.createdAt > hotelCompletedFormMaxAge) {
                            window.sessionStorage.removeItem(key);
                        }
                    } catch (error) {
                        window.sessionStorage.removeItem(key);
                    }
                });
        } catch (error) {}
    }

    function getCompletedFormRedirectUrl(event) {
        const current = getAppRoute(window.location.href);
        if (!current || !isSmartBackFormRoute(current.path) || hasServerError()) {
            return '';
        }

        if (!isHistoryReturnNavigation(event)) {
            return '';
        }

        const record = getCompletedFormRoute(current);
        if (!record) {
            return '';
        }

        return toAppUrl(record.fallback || inferSmartBackFallback(current.path));
    }

    function getCompletedFormRoute(route) {
        const keys = Array.from(new Set([
            route.key,
            pathToSmartBackKey(route.path, '')
        ]));

        for (const key of keys) {
            try {
                const record = JSON.parse(window.sessionStorage.getItem(hotelCompletedFormPrefix + key) || 'null');
                if (record && record.createdAt && Date.now() - record.createdAt <= hotelCompletedFormMaxAge) {
                    return record;
                }
            } catch (error) {}
        }

        return null;
    }

    function isHistoryReturnNavigation(event) {
        if (event && event.persisted) {
            return true;
        }

        try {
            const navigation = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
            return navigation && navigation.type === 'back_forward';
        } catch (error) {
            return false;
        }
    }

    function initSmartBackLinkGuard() {
        if (document.documentElement.dataset.medisoftSmartBackLinks === 'ready') {
            return;
        }
        document.documentElement.dataset.medisoftSmartBackLinks = 'ready';

        document.addEventListener('click', event => {
            const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (!link || event.defaultPrevented || shouldIgnoreSmartBackLink(link, event) || !looksLikeBackLink(link)) {
                return;
            }

            const target = getAppRoute(link.href);
            if (!target || !isSmartBackFormRoute(target.path)) {
                return;
            }

            const record = getCompletedFormRoute(target);
            if (!record) {
                return;
            }

            event.preventDefault();
            window.location.assign(toAppUrl(record.fallback || inferSmartBackFallback(target.path)));
        }, true);
    }

    function shouldIgnoreSmartBackLink(link, event) {
        if (link.matches('[data-smart-back="off"], [data-no-smart-back]')) {
            return true;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return true;
        }

        const href = link.getAttribute('href') || '';
        return !href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')
            || link.hasAttribute('download')
            || String(link.getAttribute('target') || '').toLowerCase() === '_blank';
    }

    function looksLikeBackLink(link) {
        const text = [
            link.textContent,
            link.getAttribute('aria-label'),
            link.getAttribute('title'),
            link.className
        ].filter(Boolean).join(' ');

        return /\b(back|volver|atras|atr[aá]s|regresar|cancelar)\b/i.test(text);
    }

    function getAppRoute(value) {
        if (!value) {
            return null;
        }

        let url;
        try {
            url = new URL(value, window.location.href);
        } catch (error) {
            return null;
        }

        if (url.origin !== window.location.origin) {
            return null;
        }

        const basePath = getAppBasePath();
        let path = url.pathname || '/';
        if (basePath !== '/' && path.indexOf(basePath + '/') === 0) {
            path = path.slice(basePath.length);
        } else if (basePath !== '/' && path === basePath) {
            path = '/';
        }

        path = '/' + path.replace(/^\/+/, '').replace(/\/+$/, '');
        if (path === '') {
            path = '/';
        }

        return {
            path,
            search: url.search || '',
            key: pathToSmartBackKey(path, url.search || '')
        };
    }

    function getAppBasePath() {
        try {
            const base = new URL(window.BASE_URL || '/', window.location.origin).pathname.replace(/\/+$/, '');
            return base || '/';
        } catch (error) {
            return '/';
        }
    }

    function pathToSmartBackKey(path, search) {
        return `${path || '/'}${search || ''}`;
    }

    function isSmartBackFormRoute(path) {
        const segments = String(path || '')
            .toLowerCase()
            .split('/')
            .filter(Boolean);

        if (!segments.length) {
            return false;
        }

        return segments.some(segment => {
            return ['crear', 'nuevo', 'create', 'new', 'form', 'subir'].includes(segment)
                || ['editar', 'edit'].includes(segment)
                || segment.indexOf('editar-') === 0;
        });
    }

    function inferSmartBackFallback(path) {
        const segments = String(path || '')
            .toLowerCase()
            .split('/')
            .filter(Boolean);

        if (!segments.length) {
            return '/dashboard';
        }

        if (segments[0] === 'configuracion' && segments[1] === 'tarifas') {
            return '/configuracion/tarifas';
        }

        if (segments[0] === 'trabajadores' && segments[1] === 'nomina') {
            return '/trabajadores/nomina/periodos';
        }

        if (segments[0] === 'cuentas-por-cobrar') {
            return segments[1] === 'operativas' ? '/cuentas-por-cobrar/operativas' : '/cuentas-por-cobrar';
        }

        if (segments[0] === 'cuentas-por-pagar') {
            return '/cuentas-por-pagar';
        }

        return `/${segments[0]}`;
    }

    function toAppUrl(path) {
        try {
            const basePath = getAppBasePath();
            const cleanPath = '/' + String(path || '/dashboard').replace(/^\/+/, '');
            const scopedPath = basePath === '/'
                ? cleanPath
                : `${basePath}${cleanPath}`;
            return new URL(scopedPath, window.location.origin).href;
        } catch (error) {
            return '/dashboard';
        }
    }

    function scheduleDraftSave(form, draftKey, immediate) {
        if (!shouldPersistForm(form)) {
            return;
        }

        const existingTimer = hotelFormSaveTimers.get(form);
        if (existingTimer) {
            window.clearTimeout(existingTimer);
        }

        const save = () => saveDraft(form, draftKey);
        if (immediate) {
            save();
            return;
        }

        hotelFormSaveTimers.set(form, window.setTimeout(save, 220));
    }

    function saveDraft(form, draftKey) {
        if (!shouldPersistForm(form)) {
            return;
        }

        const fields = Array.from(form.elements || [])
            .filter(shouldPersistField)
            .map((field, index) => serializeField(field, index))
            .filter(Boolean);

        if (!fields.length) {
            return;
        }

        try {
            window.sessionStorage.setItem(draftKey, JSON.stringify({
                createdAt: Date.now(),
                path: window.location.pathname,
                fields
            }));
        } catch (error) {}
    }

    function restoreDraft(form, draftKey) {
        let draft;
        try {
            draft = JSON.parse(window.sessionStorage.getItem(draftKey) || 'null');
        } catch (error) {
            draft = null;
        }

        if (!draft || !Array.isArray(draft.fields)) {
            return false;
        }

        if (!draft.createdAt || Date.now() - draft.createdAt > hotelFormDraftMaxAge) {
            clearDraft(draftKey);
            return false;
        }

        let restored = 0;
        draft.fields.forEach(item => {
            const field = findField(form, item);
            if (!field || !shouldPersistField(field)) {
                return;
            }

            applyFieldValue(field, item);
            restored += 1;
        });

        if (restored > 0) {
            form.dispatchEvent(new CustomEvent('medisoft:form-restored', { bubbles: true }));
        }

        return restored > 0;
    }

    function shouldPersistField(field) {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
            return false;
        }

        if (!field.name || field.disabled || field.matches('[data-no-preserve]')) {
            return false;
        }

        const type = String(field.type || '').toLowerCase();
        return !['file', 'password', 'hidden', 'submit', 'button', 'image', 'reset'].includes(type)
            && field.name !== 'csrf_token'
            && field.name !== '_token';
    }

    function serializeField(field, index) {
        const type = String(field.type || '').toLowerCase();

        if (field instanceof HTMLSelectElement && field.multiple) {
            return {
                id: field.id || '',
                name: field.name,
                index,
                type,
                multiple: true,
                value: Array.from(field.selectedOptions).map(option => option.value)
            };
        }

        if (type === 'checkbox' || type === 'radio') {
            return {
                id: field.id || '',
                name: field.name,
                index,
                type,
                value: field.value,
                checked: field.checked
            };
        }

        return {
            id: field.id || '',
            name: field.name,
            index,
            type,
            value: field.value
        };
    }

    function findField(form, item) {
        if (item.id) {
            const byId = form.querySelector(`#${cssEscape(item.id)}`);
            if (byId) {
                return byId;
            }
        }

        const sameName = Array.from(form.elements || []).filter(field => field.name === item.name);
        if (sameName[item.index]) {
            return sameName[item.index];
        }

        return sameName.find(field => {
            const type = String(field.type || '').toLowerCase();
            return (type === 'radio' || type === 'checkbox') && field.value === item.value;
        }) || sameName[0] || null;
    }

    function applyFieldValue(field, item) {
        const type = String(field.type || '').toLowerCase();

        if (field instanceof HTMLSelectElement && item.multiple && Array.isArray(item.value)) {
            Array.from(field.options).forEach(option => {
                option.selected = item.value.includes(option.value);
            });
        } else if (type === 'checkbox' || type === 'radio') {
            field.checked = Boolean(item.checked);
        } else {
            field.value = item.value == null ? '' : String(item.value);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setupPreventiveValidation(form) {
        if (!(form instanceof HTMLFormElement) || form.dataset.preventiveValidation === 'off' || form.dataset.msPreventiveValidation === 'ready') {
            return;
        }

        form.dataset.msPreventiveValidation = 'ready';
        validatePreventiveForm(form, false);

        form.addEventListener('input', event => {
            const formField = getFormField(event.target);
            if (!formField) {
                return;
            }

            formField.dataset.msTouched = '1';
            if (isPreventiveField(formField)) {
                queuePreventiveValidation(form, formField, 240);
            }
            queuePreventiveGroupValidation(form, 260);
        });

        form.addEventListener('change', event => {
            const formField = getFormField(event.target);
            if (!formField) {
                return;
            }

            formField.dataset.msTouched = '1';
            if (isPreventiveField(formField)) {
                validatePreventiveField(form, formField, true);
            }
            validatePreventiveGroups(form, true);
        });

        form.addEventListener('focusout', event => {
            const formField = getFormField(event.target);
            if (!formField) {
                return;
            }

            formField.dataset.msTouched = '1';
            if (isPreventiveField(formField)) {
                validatePreventiveField(form, formField, true);
            }
            validatePreventiveGroups(form, false);
        });
    }

    function queuePreventiveValidation(form, field, delay) {
        const existingTimer = hotelPreventiveFieldTimers.get(field);
        if (existingTimer) {
            window.clearTimeout(existingTimer);
        }

        hotelPreventiveFieldTimers.set(field, window.setTimeout(() => {
            validatePreventiveField(form, field, false);
        }, delay));
    }

    function queuePreventiveGroupValidation(form, delay) {
        const existingTimer = hotelPreventiveGroupTimers.get(form);
        if (existingTimer) {
            window.clearTimeout(existingTimer);
        }

        hotelPreventiveGroupTimers.set(form, window.setTimeout(() => {
            validatePreventiveGroups(form, false);
        }, delay));
    }

    function validatePreventiveForm(form, force) {
        if (!(form instanceof HTMLFormElement) || form.dataset.preventiveValidation === 'off') {
            return true;
        }

        const fields = Array.from(form.elements || []).filter(isPreventiveField);
        fields.forEach(field => validatePreventiveField(form, field, Boolean(force)));
        validatePreventiveGroups(form, Boolean(force));

        return !Array.from(form.elements || []).some(field => typeof field.checkValidity === 'function' && !field.checkValidity());
    }

    function installGuardedNativeSubmitPatch() {
        if (hotelNativeSubmitPatchInstalled || !window.HTMLFormElement || !HTMLFormElement.prototype.submit) {
            return;
        }

        const nativeSubmit = HTMLFormElement.prototype.submit;
        HTMLFormElement.prototype.submit = function guardedSubmit() {
            if (!validateGuardedFormBeforeSubmit(this)) {
                return;
            }

            rememberPendingFormSubmission(this);
            markFormSubmitting(this, null);
            return nativeSubmit.apply(this, arguments);
        };

        hotelNativeSubmitPatchInstalled = true;
        window.MEDISOFT_VALIDATE_FORM = validateGuardedFormBeforeSubmit;
    }

    function validateGuardedFormBeforeSubmit(form) {
        if (!document.body || !document.body.classList.contains('hotel-layout-scope')) {
            return true;
        }

        if (!isGuardedForm(form) || form.dataset.preventiveValidation === 'off') {
            return true;
        }

        const target = String(form.getAttribute('target') || '').toLowerCase();
        const method = String(form.getAttribute('method') || 'get').toLowerCase();
        if (target === '_blank' || method === 'get' || form.matches('[data-auto-filter-form], [data-no-submit-state]')) {
            return true;
        }

        validatePreventiveForm(form, true);

        const invalidField = getFirstInvalidField(form);
        if (!invalidField) {
            return true;
        }

        restoreFormSubmitting(form);
        markInvalidFields(form);
        showValidationSummary(form, invalidField);
        focusInvalidField(invalidField);
        return false;
    }

    function validatePreventiveField(form, field, force) {
        if (!isPreventiveField(field)) {
            return true;
        }

        clearPreventiveValidity(field);

        let message = '';
        if (isNumericLikeField(field)) {
            message = getNumericPreventiveMessage(field);
        }

        if (message) {
            setPreventiveFieldError(field, message, force || shouldShowPreventiveError(field));
            return false;
        }

        const pairValid = validateRelatedDateRange(form, field, force || shouldShowPreventiveError(field));
        if (pairValid && typeof field.checkValidity === 'function' && field.checkValidity()) {
            clearFieldError(field);
        } else if (typeof field.checkValidity === 'function' && !field.checkValidity() && (force || shouldShowPreventiveError(field))) {
            showFieldError(field);
        }

        return pairValid && (typeof field.checkValidity !== 'function' || field.checkValidity());
    }

    function getPreventiveField(target) {
        const field = getFormField(target);
        return isPreventiveField(field) ? field : null;
    }

    function isPreventiveField(field) {
        if (!(field instanceof HTMLInputElement) || !field.form || field.disabled || field.readOnly || !field.name || field.matches('[data-no-preventive-validation]')) {
            return false;
        }

        const type = String(field.type || '').toLowerCase();
        if (['hidden', 'submit', 'button', 'image', 'reset', 'file', 'password'].includes(type)) {
            return false;
        }

        return isNumericLikeField(field) || isDateRangeField(field);
    }

    function isNumericLikeField(field) {
        if (!(field instanceof HTMLInputElement)) {
            return false;
        }

        const type = String(field.type || '').toLowerCase();
        if (['radio', 'checkbox', 'file', 'button', 'submit', 'reset', 'hidden'].includes(type)) {
            return false;
        }

        return type === 'number' || /(^|[_\[\]-])(monto|importe|total|precio|costo|tarifa|cantidad|anticipo|abono|deposito|descuento|salario|horas|plazo)([_\]\[-]|$)/i.test(String(field.name || ''));
    }

    function isDateRangeField(field) {
        if (!(field instanceof HTMLInputElement)) {
            return false;
        }

        const type = String(field.type || '').toLowerCase();
        if (!['date', 'datetime-local', 'month'].includes(type)) {
            return false;
        }

        return Boolean(getDateRangePair(field.form, field));
    }

    function getNumericPreventiveMessage(field) {
        const rawValue = String(field.value || '').trim();
        if (rawValue === '') {
            return '';
        }

        // En campos de dinero la coma es separador de miles, no decimal.
        const isMoneyManaged = field.matches('[data-money-format="true"]') || field.dataset.moneyReady === '1';
        const normalized = isMoneyManaged ? rawValue.replace(/,/g, '') : rawValue.replace(/,/g, '.');
        const numberValue = Number(normalized);
        if (!Number.isFinite(numberValue)) {
            return 'Ingresa un numero valido.';
        }

        const minValue = field.getAttribute('min');
        const maxValue = field.getAttribute('max');
        if (minValue !== null && minValue !== '' && Number.isFinite(Number(minValue)) && numberValue < Number(minValue)) {
            return 'El valor minimo permitido es ' + minValue + '.';
        }

        if (maxValue !== null && maxValue !== '' && Number.isFinite(Number(maxValue)) && numberValue > Number(maxValue)) {
            return 'El valor maximo permitido es ' + maxValue + '.';
        }

        if (field.dataset.allowNegative !== 'true' && !field.matches('[data-allow-negative]') && minValue === null && numberValue < 0) {
            return 'No uses valores negativos en ' + getFieldLabel(field) + '.';
        }

        const stepValue = field.getAttribute('step');
        const isMoneyField = field.matches('[data-money-format="true"]') || /(^|[_\[\]-])(monto|importe|total|precio|costo|tarifa|anticipo|abono|deposito|descuento|salario)([_\]\[-]|$)/i.test(String(field.name || ''));
        const decimalsCheckValue = isMoneyManaged ? rawValue.replace(/,/g, '') : rawValue;
        if ((isMoneyField || stepValue === '0.01') && hasMoreThanTwoDecimals(decimalsCheckValue)) {
            return 'Usa maximo 2 decimales.';
        }

        return '';
    }

    function hasMoreThanTwoDecimals(value) {
        const match = String(value || '').replace(',', '.').match(/\.([0-9]+)/);
        return Boolean(match && match[1] && match[1].length > 2);
    }

    function validatePreventiveGroups(form, force) {
        if (!(form instanceof HTMLFormElement)) {
            return true;
        }

        return [
            validatePurchaseRows(form, force),
            validatePaymentCrossFields(form, force),
            validateAttendanceTimePair(form, force)
        ].every(Boolean);
    }

    function validatePurchaseRows(form, force) {
        const productSelects = Array.from(form.querySelectorAll('select[name="producto_id[]"]'));
        if (!productSelects.length) {
            return true;
        }

        let valid = true;
        productSelects.forEach(select => {
            const row = select.closest('.purchase-line-row') || select.closest('[class*="row"]') || select.parentElement;
            const quantity = row ? row.querySelector('input[name="cantidad[]"]') : null;
            const cost = row ? row.querySelector('input[name="costo_unitario[]"]') : null;
            [select, quantity, cost].filter(Boolean).forEach(clearPreventiveValidity);

            const hasProduct = String(select.value || '').trim() !== '';
            const hasQuantity = quantity && String(quantity.value || '').trim() !== '';
            const hasCost = cost && String(cost.value || '').trim() !== '';
            const rowTouched = force || [select, quantity, cost].filter(Boolean).some(shouldShowPreventiveError);

            if ((hasQuantity || hasCost) && !hasProduct) {
                setPreventiveFieldError(select, 'Selecciona el producto de esta linea o limpia la cantidad/costo.', rowTouched);
                valid = false;
                return;
            }

            if (hasProduct && !hasQuantity && quantity) {
                setPreventiveFieldError(quantity, 'Captura la cantidad de este producto.', rowTouched);
                valid = false;
                return;
            }

            [select, quantity, cost].filter(Boolean).forEach(field => {
                if (typeof field.checkValidity === 'function' && field.checkValidity()) {
                    clearFieldError(field);
                }
            });
        });

        return valid;
    }

    function validatePaymentCrossFields(form, force) {
        const paymentFields = Array.from(form.querySelectorAll('input[name="monto_efectivo"], input[name="recibido_efectivo"], input[name="monto_tarjeta"], input[name="monto_transferencia"]'));
        if (!paymentFields.length) {
            return true;
        }

        let valid = true;
        paymentFields.forEach(clearPreventiveValidity);

        Array.from(form.querySelectorAll('input[name="monto_efectivo"]')).forEach(cashAmount => {
            const received = findPaymentSibling(form, cashAmount, 'recibido_efectivo');
            if (!received) {
                return;
            }

            clearPreventiveValidity(received);
            const checkbox = findPaymentCheckbox(form, cashAmount, 'efectivo');
            const amount = readPreventiveNumber(cashAmount);
            const receivedAmount = readPreventiveNumber(received);
            const methodActive = checkbox ? checkbox.checked : amount > 0 || shouldShowPreventiveError(received);
            const shouldValidate = methodActive && (force || shouldShowPreventiveError(received) || amount > 0);
            if (amount > 0 && shouldValidate && receivedAmount < amount) {
                setPreventiveFieldError(received, 'El efectivo recibido no cubre el monto a cobrar.', true);
                valid = false;
            } else if (typeof received.checkValidity === 'function' && received.checkValidity()) {
                clearFieldError(received);
            }
        });

        Array.from(form.querySelectorAll('input[name="monto_tarjeta"]')).forEach(cardAmount => {
            const amount = readPreventiveNumber(cardAmount);
            const checkbox = findPaymentCheckbox(form, cardAmount, 'tarjeta');
            const methodActive = checkbox ? checkbox.checked : amount > 0 || shouldShowPreventiveError(cardAmount);
            const shouldValidate = methodActive && (force || shouldShowPreventiveError(cardAmount) || amount > 0);

            if (methodActive && amount <= 0 && shouldValidate) {
                setPreventiveFieldError(cardAmount, 'Captura el monto de tarjeta.', true);
                valid = false;
                return;
            }

            if (methodActive && amount > 0 && !hasCheckedCardType(form, cardAmount) && shouldValidate) {
                setPreventiveFieldError(cardAmount, 'Selecciona si la tarjeta es credito o debito.', true);
                valid = false;
            } else if (typeof cardAmount.checkValidity === 'function' && cardAmount.checkValidity()) {
                clearFieldError(cardAmount);
            }
        });

        Array.from(form.querySelectorAll('input[name="monto_transferencia"]')).forEach(transferAmount => {
            const amount = readPreventiveNumber(transferAmount);
            const checkbox = findPaymentCheckbox(form, transferAmount, 'transferencia');
            const methodActive = checkbox ? checkbox.checked : amount > 0 || shouldShowPreventiveError(transferAmount);
            const shouldValidate = methodActive && (force || shouldShowPreventiveError(transferAmount) || amount > 0);

            if (methodActive && amount <= 0 && shouldValidate) {
                setPreventiveFieldError(transferAmount, 'Captura el monto de transferencia.', true);
                valid = false;
            } else if (typeof transferAmount.checkValidity === 'function' && transferAmount.checkValidity()) {
                clearFieldError(transferAmount);
            }
        });

        return valid;
    }

    function validateAttendanceTimePair(form, force) {
        const action = String(form.getAttribute('action') || '');
        if (!/\/asistencias(?:$|[?#/])/.test(action)) {
            return true;
        }

        const start = findFormFieldByName(form, 'hora_entrada');
        const end = findFormFieldByName(form, 'hora_salida');
        if (!start || !end) {
            return true;
        }

        clearPreventiveValidity(end);
        if (!start.value || !end.value) {
            if (typeof end.checkValidity === 'function' && end.checkValidity()) {
                clearFieldError(end);
            }
            return true;
        }

        if (end.value < start.value) {
            setPreventiveFieldError(end, 'La hora de salida no puede ser anterior a la entrada.', force || shouldShowPreventiveError(end));
            return false;
        }

        if (typeof end.checkValidity === 'function' && end.checkValidity()) {
            clearFieldError(end);
        }
        return true;
    }

    function findPaymentSibling(form, field, name) {
        const scope = field.closest('.rv-pay-option, .res-pay-method, .metodo-pago-item, .rv-pay-panel, .res-pay-panel') || form;
        return scope.querySelector('input[name="' + cssEscape(name) + '"]') || form.querySelector('input[name="' + cssEscape(name) + '"]');
    }

    function findPaymentCheckbox(form, field, method) {
        const suffix = getPaymentSuffix(field);
        const byId = form.querySelector('#check_' + cssEscape(method + suffix));
        if (byId) {
            return byId;
        }

        const scope = field.closest('.rv-pay-option, .res-pay-method, .metodo-pago-item') || form;
        return scope.querySelector('input[type="checkbox"]') || null;
    }

    function hasCheckedCardType(form, field) {
        const suffix = getPaymentSuffix(field);
        const radioName = suffix === '_tardio' ? 'tipo_tarjeta_tardio' : (suffix === '_cp' ? 'tipo_tarjeta_cp' : 'tipo_tarjeta');
        const scope = field.closest('.rv-pay-option, .res-pay-method, .metodo-pago-item, .rv-pay-panel, .res-pay-panel') || form;
        return Boolean(scope.querySelector('input[type="radio"][name="' + cssEscape(radioName) + '"]:checked') || form.querySelector('input[type="radio"][name="' + cssEscape(radioName) + '"]:checked'));
    }

    function getPaymentSuffix(field) {
        const id = String(field.id || '');
        if (id.endsWith('_tardio')) {
            return '_tardio';
        }
        if (id.endsWith('_cp')) {
            return '_cp';
        }
        return '';
    }

    function readPreventiveNumber(field) {
        const rawValue = String(field && field.value || '').trim();
        const isMoneyManaged = field
            && (field.matches('[data-money-format="true"]') || field.dataset.moneyReady === '1');
        const value = isMoneyManaged
            ? rawValue.replace(/,/g, '')
            : rawValue.replace(/,/g, '.');
        const numberValue = Number(value);
        return Number.isFinite(numberValue) ? numberValue : 0;
    }

    function validateRelatedDateRange(form, field, showErrors) {
        const pair = getDateRangePair(form, field);
        if (!pair || !pair.start || !pair.end) {
            return true;
        }

        clearPreventiveValidity(pair.end);

        if (!pair.start.value || !pair.end.value) {
            if (typeof pair.end.checkValidity === 'function' && pair.end.checkValidity()) {
                clearFieldError(pair.end);
            }
            return true;
        }

        if (pair.end.value < pair.start.value) {
            const message = getFieldLabel(pair.end) + ' no puede ser anterior a ' + getFieldLabel(pair.start) + '.';
            setPreventiveFieldError(pair.end, message, showErrors || shouldShowPreventiveError(pair.end));
            return false;
        }

        if (typeof pair.end.checkValidity === 'function' && pair.end.checkValidity()) {
            clearFieldError(pair.end);
        }

        return true;
    }

    function getDateRangePair(form, field) {
        if (!(form instanceof HTMLFormElement) || !(field instanceof HTMLInputElement)) {
            return null;
        }

        const name = String(field.name || '');
        const pairs = [
            ['fecha_inicio', 'fecha_fin'],
            ['fecha_desde', 'fecha_hasta'],
            ['desde', 'hasta'],
            ['periodo_inicio', 'periodo_fin'],
            ['fecha_entrada', 'fecha_salida'],
            ['fecha_programada', 'fecha_programada_fin'],
            ['fecha_programada', 'fecha_limite']
        ];

        for (const pair of pairs) {
            if (name === pair[0] || name === pair[1]) {
                const start = findFormFieldByName(form, pair[0]);
                const end = findFormFieldByName(form, pair[1]);
                if (start && end) {
                    return { start, end };
                }
            }
        }

        return null;
    }

    function findFormFieldByName(form, name) {
        try {
            return form.querySelector('[name="' + cssEscape(name) + '"]');
        } catch (error) {
            return Array.from(form.elements || []).find(field => field.name === name) || null;
        }
    }

    function shouldShowPreventiveError(field) {
        return field.dataset.msTouched === '1' || field.classList.contains('ms-form-invalid') || String(field.value || '').trim() !== '';
    }

    function setPreventiveFieldError(field, message, showError) {
        if (typeof field.setCustomValidity !== 'function') {
            return;
        }

        field.dataset.msPreventiveError = '1';
        field.setCustomValidity(message);
        if (showError) {
            showFieldErrorMessage(field, message);
        }
    }

    function clearPreventiveValidity(field) {
        if (!field || field.dataset.msPreventiveError !== '1' || typeof field.setCustomValidity !== 'function') {
            return;
        }

        field.setCustomValidity('');
        delete field.dataset.msPreventiveError;
    }

    function getFirstInvalidField(form) {
        const fields = Array.from(form.elements || []).filter(field => {
            return field instanceof HTMLInputElement
                || field instanceof HTMLSelectElement
                || field instanceof HTMLTextAreaElement;
        });

        return fields.find(field => {
            return !field.disabled && typeof field.checkValidity === 'function' && !field.checkValidity();
        }) || null;
    }

    function markInvalidFields(form) {
        Array.from(form.elements || []).forEach(field => {
            if (typeof field.checkValidity === 'function' && !field.disabled && !field.checkValidity()) {
                showFieldError(field);
            }
        });
    }

    function showFieldError(field) {
        showFieldErrorMessage(field, getFieldErrorMessage(field));
    }

    function showFieldErrorMessage(field, message) {
        field.classList.add('ms-form-invalid');
        field.setAttribute('aria-invalid', 'true');

        const errorId = getFieldErrorId(field);
        field.setAttribute('aria-describedby', mergeDescribedBy(field.getAttribute('aria-describedby'), errorId));

        let error = document.getElementById(errorId);
        if (!error) {
            error = document.createElement('p');
            error.id = errorId;
            error.className = 'ms-form-field-error';
            insertAfter(error, getErrorAnchor(field));
        }

        error.textContent = message;
    }

    function clearFieldError(field) {
        if (!field || !field.classList) {
            return;
        }

        delete field.dataset.msServerError;

        if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
            return;
        }

        field.classList.remove('ms-form-invalid');
        field.removeAttribute('aria-invalid');

        const errorId = getFieldErrorId(field);
        const error = document.getElementById(errorId);
        if (error) {
            error.remove();
        }

        const describedBy = String(field.getAttribute('aria-describedby') || '')
            .split(/\s+/)
            .filter(Boolean)
            .filter(id => id !== errorId)
            .join(' ');

        if (describedBy) {
            field.setAttribute('aria-describedby', describedBy);
        } else {
            field.removeAttribute('aria-describedby');
        }
    }

    function clearFormErrors(form) {
        form.querySelectorAll('.ms-form-invalid').forEach(field => clearFieldError(field));
        const summary = form.querySelector('.ms-form-error-summary');
        if (summary) {
            summary.remove();
        }
    }

    function getFieldErrorMessage(field) {
        const validity = field.validity || {};
        const label = getFieldLabel(field);

        if (validity.valueMissing) {
            if (field instanceof HTMLSelectElement) {
                return `Selecciona ${label}.`;
            }

            if (String(field.type || '').toLowerCase() === 'radio' || String(field.type || '').toLowerCase() === 'checkbox') {
                return `Marca ${label}.`;
            }

            return `Completa ${label}.`;
        }

        if (validity.customError && field.validationMessage) {
            return field.validationMessage;
        }

        if (validity.typeMismatch) {
            return String(field.type || '').toLowerCase() === 'email'
                ? 'Ingresa un correo valido.'
                : 'Revisa el formato de este dato.';
        }

        if (validity.patternMismatch) {
            return field.title || 'Usa el formato solicitado.';
        }

        if (validity.rangeUnderflow) {
            return `El valor debe ser mayor o igual a ${field.min}.`;
        }

        if (validity.rangeOverflow) {
            return `El valor debe ser menor o igual a ${field.max}.`;
        }

        if (validity.stepMismatch || validity.badInput) {
            return 'Ingresa un valor valido.';
        }

        if (validity.tooShort) {
            return `Escribe al menos ${field.minLength} caracteres.`;
        }

        if (validity.tooLong) {
            return `No debe pasar de ${field.maxLength} caracteres.`;
        }

        return field.validationMessage || 'Revisa este campo.';
    }

    function getFieldLabel(field) {
        if (field.id) {
            const explicitLabel = document.querySelector(`label[for="${cssEscape(field.id)}"]`);
            if (explicitLabel && explicitLabel.textContent.trim()) {
                return cleanLabel(explicitLabel.textContent);
            }
        }

        const wrapperLabel = field.closest('label');
        if (wrapperLabel && wrapperLabel.textContent.trim()) {
            return cleanLabel(wrapperLabel.textContent);
        }

        const ariaLabel = field.getAttribute('aria-label');
        if (ariaLabel) {
            return cleanLabel(ariaLabel);
        }

        const placeholder = field.getAttribute('placeholder');
        if (placeholder) {
            return cleanLabel(placeholder);
        }

        return 'este campo';
    }

    function cleanLabel(value) {
        return String(value || '')
            .replace(/\*/g, '')
            .replace(/\s+/g, ' ')
            .replace(/[:.]+$/g, '')
            .trim()
            .toLowerCase() || 'este campo';
    }

    function showValidationSummary(form, invalidField, detailMessage) {
        let summary = form.querySelector('.ms-form-error-summary');
        if (!summary) {
            summary = document.createElement('div');
            summary.className = 'ms-form-error-summary';
            summary.setAttribute('role', 'alert');
            summary.setAttribute('aria-live', 'polite');
            form.insertBefore(summary, form.firstElementChild);
        }

        const strong = document.createElement('strong');
        const detail = document.createElement('span');
        const serverMessage = detailMessage || (invalidField && invalidField.dataset ? invalidField.dataset.msServerError : '');

        strong.textContent = serverMessage ? 'No se pudo guardar este formulario.' : 'Revisa la informacion antes de guardar.';
        detail.textContent = serverMessage || 'Hay campos obligatorios o con formato incorrecto. No se envio nada todavia.';

        summary.replaceChildren(strong, detail);

        if (invalidField) {
            summary.dataset.targetField = invalidField.id || invalidField.name || '';
        } else {
            delete summary.dataset.targetField;
        }
    }

    function showRecoveredNotice(form, draftKey) {
        if (form.querySelector('.ms-form-recovered-notice')) {
            return;
        }

        const notice = document.createElement('div');
        notice.className = 'ms-form-recovered-notice';
        notice.setAttribute('role', 'status');
        notice.innerHTML = '<strong>Recupere tus datos.</strong><span>El guardado anterior tuvo un error y mantuve lo que habias capturado.</span><button type="button">Descartar</button>';

        const button = notice.querySelector('button');
        if (button) {
            button.addEventListener('click', () => {
                clearDraft(draftKey);
                notice.remove();
            });
        }

        form.insertBefore(notice, form.firstElementChild);
    }

    function focusInvalidField(field) {
        const anchor = getErrorAnchor(field);
        if (anchor && typeof anchor.scrollIntoView === 'function') {
            anchor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        window.setTimeout(() => {
            if (typeof field.focus === 'function') {
                field.focus({ preventScroll: true });
            }
        }, 180);
    }

    function shouldUseSubmitState(form) {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }

        if (form.dataset.submitState === 'off' || form.matches('[data-no-submit-state]')) {
            return false;
        }

        if (String(form.getAttribute('target') || '').toLowerCase() === '_blank') {
            return false;
        }

        const method = String(form.getAttribute('method') || 'get').toLowerCase();
        return method !== 'get' && !form.matches('[data-auto-filter-form]');
    }

    function markFormSubmitting(form, submitter) {
        if (!shouldUseSubmitState(form) || form.dataset.msSubmitting === '1') {
            return;
        }

        form.classList.add('ms-form-submitting');
        form.setAttribute('aria-busy', 'true');
        form.dataset.msSubmitting = '1';

        const buttons = getFormSubmitButtons(form);
        const primaryButton = submitter instanceof HTMLElement && submitter.form === form
            ? submitter
            : buttons[0] || null;

        if (primaryButton && !buttons.includes(primaryButton)) {
            buttons.unshift(primaryButton);
        }

        const states = buttons.map(button => {
            const state = {
                button,
                ariaDisabled: button.getAttribute('aria-disabled'),
                tabIndex: button.getAttribute('tabindex'),
                minWidth: button.style.minWidth,
                pointerEvents: button.style.pointerEvents,
                html: button instanceof HTMLButtonElement ? button.innerHTML : null,
                value: button instanceof HTMLInputElement ? button.value : null
            };

            button.classList.add('ms-submit-locked');
            button.setAttribute('aria-disabled', 'true');
            button.setAttribute('tabindex', '-1');
            button.style.pointerEvents = 'none';
            if (button.offsetWidth > 0) {
                button.style.minWidth = `${button.offsetWidth}px`;
            }

            if (button === primaryButton) {
                applySubmitLoadingLabel(button);
            }

            return state;
        });

        hotelFormSubmitState.set(form, states);

        const timer = window.setTimeout(() => restoreFormSubmitting(form), 120000);
        hotelFormSubmitTimers.set(form, timer);
    }

    function restoreFormSubmitting(form) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const timer = hotelFormSubmitTimers.get(form);
        if (timer) {
            window.clearTimeout(timer);
            hotelFormSubmitTimers.delete(form);
        }

        const states = hotelFormSubmitState.get(form) || [];
        states.forEach(state => {
            const button = state.button;
            if (!button) {
                return;
            }

            button.classList.remove('ms-submit-locked', 'ms-submit-loading');
            restoreNullableAttribute(button, 'aria-disabled', state.ariaDisabled);
            restoreNullableAttribute(button, 'tabindex', state.tabIndex);
            button.style.minWidth = state.minWidth || '';
            button.style.pointerEvents = state.pointerEvents || '';

            if (button instanceof HTMLButtonElement && state.html !== null) {
                button.innerHTML = state.html;
            } else if (button instanceof HTMLInputElement && state.value !== null) {
                button.value = state.value;
            }
        });

        hotelFormSubmitState.delete(form);
        form.classList.remove('ms-form-submitting');
        form.removeAttribute('aria-busy');
        delete form.dataset.msSubmitting;
    }

    function getFormSubmitButtons(form) {
        return Array.from(form.querySelectorAll('button, input[type="submit"]'))
            .filter(button => {
                if (button instanceof HTMLButtonElement) {
                    return !button.type || button.type.toLowerCase() === 'submit';
                }

                return button instanceof HTMLInputElement && String(button.type || '').toLowerCase() === 'submit';
            });
    }

    function getSubmitButton(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        const button = target.closest('button, input[type="submit"]');
        if (!button) {
            return null;
        }

        if (button instanceof HTMLButtonElement) {
            return !button.type || button.type.toLowerCase() === 'submit' ? button : null;
        }

        return button instanceof HTMLInputElement && String(button.type || '').toLowerCase() === 'submit'
            ? button
            : null;
    }

    function applySubmitLoadingLabel(button) {
        const loadingText = getSubmitLoadingText(button);
        button.classList.add('ms-submit-loading');

        if (button instanceof HTMLInputElement) {
            button.value = loadingText;
            return;
        }

        button.innerHTML = `<span class="ms-submit-loader" aria-hidden="true"></span><span>${loadingText}</span>`;
    }

    function getSubmitLoadingText(button) {
        const customText = button.getAttribute('data-loading-text');
        if (customText) {
            return customText;
        }

        const text = (button instanceof HTMLInputElement ? button.value : button.textContent || '').trim().toLowerCase();

        if (/cerrar|corte/.test(text)) {
            return 'Cerrando...';
        }

        if (/subir|cargar|archivo|imagen|documento/.test(text)) {
            return 'Cargando...';
        }

        if (/enviar|correo|email/.test(text)) {
            return 'Enviando...';
        }

        if (/eliminar|cancelar|anular|revertir|archivar/.test(text)) {
            return 'Procesando...';
        }

        if (/confirmar|registrar|guardar|actualizar|crear|agregar|aplicar/.test(text)) {
            return 'Guardando...';
        }

        return 'Procesando...';
    }

    function renderServerFieldErrors() {
        const errors = window.MEDISOFT_FIELD_ERRORS || {};
        if (!errors || typeof errors !== 'object') {
            return;
        }

        const affectedForms = new Map();
        const unmatchedMessages = [];
        const globalMessage = normalizeServerFieldMessage(errors._global || errors.global || errors.form || '');

        Object.entries(errors).forEach(([fieldName, messages]) => {
            if (!fieldName || ['_global', 'global', 'form'].includes(fieldName)) {
                return;
            }

            const message = normalizeServerFieldMessage(messages);
            if (!message) {
                return;
            }

            const fields = findFieldsForServerError(fieldName);
            if (!fields.length) {
                unmatchedMessages.push(message);
                return;
            }

            const displayFields = getServerErrorDisplayFields(fields);
            fields.forEach(field => {
                field.dataset.msServerError = message;
                if (!displayFields.includes(field)) {
                    field.classList.add('ms-form-invalid');
                    field.setAttribute('aria-invalid', 'true');
                }
            });

            displayFields.forEach(field => {
                showFieldErrorMessage(field, message);

                if (field.form && !affectedForms.has(field.form)) {
                    affectedForms.set(field.form, field);
                }
            });
        });

        affectedForms.forEach((field, form) => {
            showValidationSummary(form, field, field.dataset.msServerError || getFieldErrorMessage(field));
            focusInvalidField(field);
        });

        const summaryMessage = [globalMessage].concat(unmatchedMessages).filter(Boolean).join(' ');
        if (summaryMessage) {
            const firstAffected = affectedForms.keys().next().value;
            const fallbackForm = firstAffected || Array.from(document.forms || []).find(isGuardedForm);
            if (fallbackForm) {
                showValidationSummary(fallbackForm, affectedForms.get(fallbackForm) || null, summaryMessage);
            }
        }
    }

    function getServerErrorDisplayFields(fields) {
        if (!Array.isArray(fields) || fields.length <= 1) {
            return fields;
        }

        const groupedControls = fields.filter(field => {
            return field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(String(field.type || '').toLowerCase());
        });

        if (groupedControls.length === fields.length) {
            const visible = groupedControls.find(field => field.offsetParent !== null) || groupedControls[0];
            return visible ? [visible] : [];
        }

        return fields;
    }

    function normalizeServerFieldMessage(messages) {
        if (Array.isArray(messages)) {
            return messages.filter(Boolean).map(message => String(message).trim()).filter(Boolean).join(' ');
        }

        return String(messages || '').trim();
    }

    function findFieldsForServerError(fieldName) {
        const rawFieldName = String(fieldName || '').trim();
        const selectors = [
            `[name="${cssEscape(rawFieldName)}"]`,
            `[id="${cssEscape(rawFieldName)}"]`
        ];

        const bracketName = dottedNameToBracketName(rawFieldName);
        if (bracketName !== rawFieldName) {
            selectors.push(`[name="${cssEscape(bracketName)}"]`, `[id="${cssEscape(bracketName)}"]`);
        }

        const baseName = getServerFieldBaseName(rawFieldName);
        if (baseName && baseName !== rawFieldName) {
            selectors.push(`[name="${cssEscape(baseName)}[]"]`, `[name="${cssEscape(baseName)}"]`, `[id="${cssEscape(baseName)}"]`);
        }

        let fields = selectors.flatMap(selector => Array.from(document.querySelectorAll(selector)));

        if (!fields.length) {
            const normalizedError = normalizeServerFieldName(rawFieldName);
            fields = Array.from(document.querySelectorAll('input, select, textarea')).filter(field => {
                const name = field.getAttribute('name') || '';
                const id = field.getAttribute('id') || '';
                return serverFieldMatches(name, normalizedError, rawFieldName)
                    || serverFieldMatches(id, normalizedError, rawFieldName);
            });
        }

        return Array.from(new Set(fields)).filter(field => field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement);
    }

    function serverFieldMatches(candidate, normalizedError, rawFieldName) {
        if (!candidate) {
            return false;
        }

        if (candidate === rawFieldName || candidate.startsWith(rawFieldName + '[')) {
            return true;
        }

        const normalizedCandidate = normalizeServerFieldName(candidate);
        if (!normalizedCandidate || !normalizedError) {
            return false;
        }

        const candidateBase = normalizedCandidate.split('.')[0];
        const errorBase = normalizedError.split('.')[0];
        return normalizedCandidate === normalizedError
            || normalizedCandidate.startsWith(normalizedError + '.')
            || normalizedError.startsWith(normalizedCandidate + '.')
            || candidateBase === errorBase && /[.\[]/.test(rawFieldName);
    }

    function normalizeServerFieldName(value) {
        return String(value || '')
            .replace(/\[\]/g, '')
            .replace(/\[([^\]]+)\]/g, '.$1')
            .replace(/\.+/g, '.')
            .replace(/^\.|\.$/g, '');
    }

    function getServerFieldBaseName(value) {
        return normalizeServerFieldName(value).split('.')[0] || '';
    }

    function dottedNameToBracketName(fieldName) {
        const parts = String(fieldName || '').split('.').filter(Boolean);
        if (parts.length < 2) {
            return fieldName;
        }

        return parts[0] + parts.slice(1).map(part => `[${part}]`).join('');
    }

    function restoreNullableAttribute(element, name, value) {
        if (value === null || typeof value === 'undefined') {
            element.removeAttribute(name);
            return;
        }

        element.setAttribute(name, value);
    }

    function setupUnsavedTracking(form) {
        if (!shouldTrackUnsavedForm(form) || hotelUnsavedForms.has(form)) {
            return;
        }

        hotelUnsavedTrackedForms.add(form);
        hotelUnsavedForms.set(form, {
            initialSignature: getFormDirtySignature(form),
            dirty: false
        });
    }

    function updateUnsavedState(form) {
        const state = hotelUnsavedForms.get(form);
        if (!state || form.dataset.msSubmitting === '1') {
            return;
        }

        state.dirty = state.initialSignature !== getFormDirtySignature(form);
        form.classList.toggle('ms-form-dirty', state.dirty);
    }

    function markFormClean(form) {
        const state = hotelUnsavedForms.get(form);
        if (!state) {
            return;
        }

        state.initialSignature = getFormDirtySignature(form);
        state.dirty = false;
        form.classList.remove('ms-form-dirty');
    }

    function getFormDirtySignature(form) {
        try {
            return JSON.stringify(Array.from(form.elements || [])
                .filter(shouldIncludeInDirtySignature)
                .map((field, index) => serializeDirtyField(field, index)));
        } catch (error) {
            return String(Date.now());
        }
    }

    function shouldIncludeInDirtySignature(field) {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
            return false;
        }

        if (!field.name || field.disabled || field.matches('[data-no-unsaved-track]')) {
            return false;
        }

        const type = String(field.type || '').toLowerCase();
        return !['hidden', 'submit', 'button', 'image', 'reset'].includes(type)
            && field.name !== 'csrf_token'
            && field.name !== '_token';
    }

    function serializeDirtyField(field, index) {
        const type = String(field.type || '').toLowerCase();

        if (field instanceof HTMLSelectElement && field.multiple) {
            return [field.name, index, 'multi', Array.from(field.selectedOptions).map(option => option.value)];
        }

        if (type === 'checkbox' || type === 'radio') {
            return [field.name, index, type, field.value, field.checked];
        }

        if (type === 'file') {
            return [field.name, index, type, field.files ? Array.from(field.files).map(file => `${file.name}:${file.size}:${file.lastModified}`) : []];
        }

        return [field.name, index, type, field.value];
    }

    function initUnsavedNavigationGuard() {
        if (document.documentElement.dataset.medisoftUnsavedGuard === 'ready') {
            return;
        }
        document.documentElement.dataset.medisoftUnsavedGuard = 'ready';

        window.addEventListener('beforeunload', event => {
            if (!getFirstDirtyForm()) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });

        document.addEventListener('click', event => {
            const dismissControl = getUnsavedDismissControl(event.target);
            if (dismissControl && !event.defaultPrevented) {
                const dirtyDismissForm = getDirtyFormForDismiss(dismissControl);
                if (dirtyDismissForm && !confirmDiscardChanges(dirtyDismissForm)) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
            }

            const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (!link || event.defaultPrevented || shouldIgnoreUnsavedLink(link, event)) {
                return;
            }

            const dirtyForm = getFirstDirtyForm();
            if (!dirtyForm) {
                return;
            }

            if (confirmDiscardChanges(dirtyForm)) {
                hotelUnsavedTrackedForms.forEach(form => markFormClean(form));
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            focusDirtyForm(dirtyForm);
        }, true);
    }

    function getFirstDirtyForm() {
        for (const form of hotelUnsavedTrackedForms) {
            const state = hotelUnsavedForms.get(form);
            if (!state || !state.dirty || form.dataset.msSubmitting === '1' || !document.documentElement.contains(form)) {
                continue;
            }

            return form;
        }

        return null;
    }

    function shouldIgnoreUnsavedLink(link, event) {
        if (link.matches('[data-no-unsaved-warning], [data-unsaved-ignore]')) {
            return true;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return true;
        }

        const href = link.getAttribute('href') || '';
        if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return true;
        }

        if (link.hasAttribute('download') || String(link.getAttribute('target') || '').toLowerCase() === '_blank') {
            return true;
        }

        try {
            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) {
                return true;
            }

            return url.pathname === window.location.pathname
                && url.search === window.location.search
                && url.hash !== '';
        } catch (error) {
            return true;
        }
    }

    function getUnsavedDismissControl(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        const control = target.closest('button, [role="button"], [data-dismiss], [data-bs-dismiss]');
        if (!control || control.matches('[type="submit"], [data-no-unsaved-warning], [data-unsaved-ignore]')) {
            return null;
        }

        if (!control.closest('.modal, .modal-overlay, [role="dialog"], .fixed.inset-0')) {
            return null;
        }

        const text = [
            control.textContent,
            control.getAttribute('aria-label'),
            control.getAttribute('title'),
            control.getAttribute('onclick'),
            control.className
        ].filter(Boolean).join(' ');

        return /\b(cerrar|cancelar|close|dismiss)\b/i.test(text) ? control : null;
    }

    function getDirtyFormForDismiss(control) {
        const ownForm = control.closest('form');
        if (ownForm && isFormDirty(ownForm)) {
            return ownForm;
        }

        const modal = control.closest('.modal, .modal-overlay, [role="dialog"], .fixed.inset-0');
        if (!modal) {
            return null;
        }

        return Array.from(modal.querySelectorAll('form')).find(isFormDirty) || null;
    }

    function isFormDirty(form) {
        const state = hotelUnsavedForms.get(form);
        return Boolean(state && state.dirty && form.dataset.msSubmitting !== '1');
    }

    function confirmDiscardChanges(form) {
        if (window.confirm('Tienes cambios sin guardar. Si sales ahora se perderan.')) {
            markFormClean(form);
            return true;
        }

        focusDirtyForm(form);
        return false;
    }

    function focusDirtyForm(form) {
        const field = Array.from(form.elements || []).find(element => {
            return (element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement)
                && !element.disabled
                && element.type !== 'hidden';
        });

        const target = field || form;
        if (typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (field && typeof field.focus === 'function') {
            window.setTimeout(() => field.focus({ preventScroll: true }), 180);
        }
    }

    function hasServerError() {
        return Boolean(document.querySelector([
            '.flash-error',
            '.alert-error',
            '.invoice-alert.flash-error',
            '.fade-in .bg-red-50',
            '.fade-in [class*="text-red-700"]',
            '[data-flash-type="error"]'
        ].join(',')));
    }

    function hasServerSuccess() {
        return Boolean(document.querySelector([
            '.flash-success',
            '.alert-success',
            '.invoice-alert.flash-success',
            '.fade-in .bg-green-50',
            '.fade-in [class*="text-green-700"]',
            '[data-flash-type="success"]'
        ].join(',')));
    }

    function clearDraft(draftKey) {
        try {
            window.sessionStorage.removeItem(draftKey);
        } catch (error) {}
    }

    function clearDraftsForCurrentPath() {
        const currentPath = `:${window.location.pathname}:`;
        try {
            Object.keys(window.sessionStorage)
                .filter(key => key.indexOf(hotelFormDraftPrefix) === 0 && key.includes(currentPath))
                .forEach(key => window.sessionStorage.removeItem(key));
        } catch (error) {}
    }

    function cleanupExpiredDrafts() {
        try {
            Object.keys(window.sessionStorage)
                .filter(key => key.indexOf(hotelFormDraftPrefix) === 0)
                .forEach(key => {
                    try {
                        const draft = JSON.parse(window.sessionStorage.getItem(key) || 'null');
                        if (!draft || !draft.createdAt || Date.now() - draft.createdAt > hotelFormDraftMaxAge) {
                            window.sessionStorage.removeItem(key);
                        }
                    } catch (error) {
                        window.sessionStorage.removeItem(key);
                    }
                });
        } catch (error) {}
    }

    function mergeDescribedBy(current, extra) {
        return Array.from(new Set(String(current || '').split(/\s+/).filter(Boolean).concat(extra))).join(' ');
    }

    function getFieldErrorId(field) {
        const base = field.id || field.name || `field-${Math.random().toString(36).slice(2)}`;
        if (!field.dataset.msErrorId) {
            field.dataset.msErrorId = `ms-form-error-${base.replace(/[^a-z0-9_-]/gi, '-')}`;
        }

        return field.dataset.msErrorId;
    }

    function getErrorAnchor(field) {
        return field.closest('.form-group, .mb-3, .mb-4, .field, .input-group, label') || field;
    }

    function insertAfter(element, anchor) {
        if (!anchor || !anchor.parentNode) {
            return;
        }

        anchor.parentNode.insertBefore(element, anchor.nextSibling);
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/["\\#.;?+*~':!^$[\]()=>|/@]/g, '\\$&');
    }


    const hotelInstantSearchTimers = new WeakMap();
    const hotelInstantSearchDelay = 420;

    function initHotelInstantSearch() {
        if (!document.body || !document.body.classList.contains('hotel-layout-scope')) {
            return;
        }

        if (document.documentElement.dataset.medisoftInstantSearch === 'ready') {
            return;
        }
        document.documentElement.dataset.medisoftInstantSearch = 'ready';

        document.addEventListener('input', event => {
            const control = getInstantSearchControl(event.target);
            if (!control || !isInstantTextControl(control)) {
                return;
            }

            const form = control.form;
            const value = String(control.value || '').trim();
            const delay = value === '' ? 120 : hotelInstantSearchDelay;
            scheduleInstantSearch(form, delay);
        });

        document.addEventListener('change', event => {
            const control = getInstantSearchControl(event.target);
            if (!control || isInstantTextControl(control)) {
                return;
            }

            scheduleInstantSearch(control.form, 80);
        });
    }

    function getInstantSearchControl(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        const control = target.closest('input, select, textarea');
        if (!control || !control.form || control.matches('[data-auto-filter-ignore], [data-instant-search-ignore]')) {
            return null;
        }

        if (!isInstantSearchForm(control.form)) {
            return null;
        }

        if (!isInstantSearchControl(control)) {
            return null;
        }

        return control;
    }

    function isInstantSearchForm(form) {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }

        if (form.dataset.instantSearch === 'off' || form.matches('[data-no-instant-search]')) {
            return false;
        }

        if (String(form.getAttribute('target') || '').toLowerCase() === '_blank') {
            return false;
        }

        const method = String(form.getAttribute('method') || '').toLowerCase();
        if (method !== 'get') {
            return false;
        }

        if (form.matches('[data-auto-filter-form]')) {
            return true;
        }

        if (form.querySelector('input[type="search"]')) {
            return true;
        }

        if (Array.from(form.elements || []).some(isSearchishField)) {
            return true;
        }

        return Array.from(form.querySelectorAll('button, input[type="submit"]')).some(button => {
            const text = button instanceof HTMLInputElement ? button.value : button.textContent;
            return /\b(buscar|filtrar|aplicar filtros)\b/i.test(String(text || ''));
        });
    }

    function isInstantSearchControl(control) {
        if (control.disabled || control.readOnly || !control.name) {
            return false;
        }

        if (control instanceof HTMLSelectElement) {
            return true;
        }

        if (control instanceof HTMLTextAreaElement) {
            return isSearchishField(control);
        }

        if (!(control instanceof HTMLInputElement)) {
            return false;
        }

        const type = String(control.type || 'text').toLowerCase();
        if (['hidden', 'password', 'file', 'submit', 'button', 'image', 'reset'].includes(type)) {
            return false;
        }

        if (['checkbox', 'radio', 'date', 'month', 'week', 'number', 'range'].includes(type)) {
            return true;
        }

        return type === 'search' || isSearchishField(control);
    }

    function isInstantTextControl(control) {
        return control instanceof HTMLTextAreaElement
            || control instanceof HTMLInputElement && ['search', 'text', 'email', 'tel', 'url', 'number'].includes(String(control.type || 'text').toLowerCase());
    }

    function isSearchishField(field) {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
            return false;
        }

        const haystack = [
            field.name,
            field.id,
            field.getAttribute('placeholder'),
            field.getAttribute('aria-label'),
            field.getAttribute('title')
        ].filter(Boolean).join(' ');

        return /\b(buscar|busqueda|b?squeda|search|query|termino|t?rmino|filtro|estado|fecha|tipo|categoria|categor?a)\b/i.test(haystack);
    }

    function scheduleInstantSearch(form, delay) {
        if (!form || form.dataset.autoFilterSubmitting === '1') {
            return;
        }

        const previousTimer = hotelInstantSearchTimers.get(form);
        if (previousTimer) {
            window.clearTimeout(previousTimer);
        }

        hotelInstantSearchTimers.set(form, window.setTimeout(() => {
            submitInstantSearchForm(form);
        }, delay));
    }

    function submitInstantSearchForm(form) {
        if (!isInstantSearchForm(form) || form.dataset.autoFilterSubmitting === '1') {
            return;
        }

        const signature = getInstantSearchSignature(form);
        if (form.dataset.instantSearchLastSubmitted === signature) {
            return;
        }

        form.dataset.instantSearchLastSubmitted = signature;
        form.dataset.autoFilterSubmitting = '1';
        window.setTimeout(() => {
            if (form.dataset.autoFilterSubmitting === '1') {
                delete form.dataset.autoFilterSubmitting;
            }
        }, 1500);

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
    }

    function getInstantSearchSignature(form) {
        try {
            return new URLSearchParams(new FormData(form)).toString();
        } catch (error) {
            return String(Date.now());
        }
    }

    function injectFormGuardStyles() {
        if (document.getElementById('medisoft-form-guard-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'medisoft-form-guard-styles';
        style.textContent = `
            .ms-form-invalid {
                border-color: #dc2626 !important;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12) !important;
                background-color: color-mix(in srgb, #fef2f2 62%, #ffffff) !important;
            }

            .ms-form-field-error {
                margin: 0.35rem 0 0;
                color: #b42318;
                font-size: 0.82rem;
                font-weight: 700;
                line-height: 1.35;
            }

            .ms-form-error-summary,
            .ms-form-recovered-notice {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.85rem;
                margin: 0 0 1rem;
                padding: 0.9rem 1rem;
                border-radius: 0.9rem;
                border: 1px solid rgba(220, 38, 38, 0.22);
                background: color-mix(in srgb, #fef2f2 72%, #ffffff);
                color: #7f1d1d;
                box-shadow: 0 12px 32px rgba(31, 41, 55, 0.08);
            }

            .ms-form-error-summary {
                flex-direction: column;
                justify-content: flex-start;
            }

            .ms-form-error-summary strong,
            .ms-form-recovered-notice strong {
                display: block;
                font-size: 0.9rem;
                font-weight: 800;
                line-height: 1.2;
            }

            .ms-form-error-summary span,
            .ms-form-recovered-notice span {
                display: block;
                margin-top: 0.2rem;
                font-size: 0.84rem;
                line-height: 1.35;
            }

            .ms-form-recovered-notice {
                border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 22%, #d8e6dc);
                background: color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #fbfaf6);
                color: color-mix(in srgb, var(--brand-secondary, #0F172A) 82%, #1f2937);
            }

            .ms-form-recovered-notice button {
                flex: 0 0 auto;
                border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 26%, #d5d9cf);
                border-radius: 999px;
                padding: 0.35rem 0.75rem;
                background: rgba(255, 255, 255, 0.72);
                color: inherit;
                font-size: 0.78rem;
                font-weight: 800;
                cursor: pointer;
            }

            .ms-form-submitting [type="submit"] {
                cursor: progress;
                opacity: 0.82;
            }

            .ms-submit-locked {
                cursor: progress !important;
                user-select: none;
            }

            button.ms-submit-loading {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                white-space: nowrap;
            }

            .ms-submit-loader {
                width: 0.95em;
                height: 0.95em;
                border-radius: 999px;
                border: 2px solid currentColor;
                border-right-color: transparent;
                opacity: 0.9;
                animation: ms-submit-spin 0.72s linear infinite;
            }

            @keyframes ms-submit-spin {
                to {
                    transform: rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);
    }

    /* ===================================================================
     * MedisoftMoneyInput
     * Formatea con separadores de miles (comas) mientras el usuario escribe
     * importes de dinero, y entrega un valor limpio (sin comas) al backend.
     * Las vistas ya invocan window.MedisoftMoneyInput.{init,read,set,format,sanitize}.
     * Se activa en cualquier <input data-money-format="true">.
     * =================================================================== */
    (function initMedisoftMoneyInput() {
        var SELECTOR = 'input[data-money-format="true"]';

        function toNumber(value) {
            if (typeof value === 'number') {
                return Number.isFinite(value) ? value : 0;
            }
            var n = parseFloat(String(value == null ? '' : value).replace(/,/g, ''));
            return Number.isFinite(n) ? n : 0;
        }

        // Formatea respetando lo que se va escribiendo (sin forzar decimales).
        function formatWhileTyping(rawValue) {
            var s = String(rawValue == null ? '' : rawValue).replace(/[^\d.]/g, '');
            var dot = s.indexOf('.');
            if (dot !== -1) {
                // un solo punto decimal
                s = s.slice(0, dot + 1) + s.slice(dot + 1).replace(/\./g, '');
            }
            var hasDot = s.indexOf('.') !== -1;
            var parts = s.split('.');
            var intPart = (parts[0] || '').replace(/^0+(?=\d)/, '');
            var decPart = parts.length > 1 ? parts[1].slice(0, 2) : '';
            var intFmt = intPart === '' ? '' : Number(intPart).toLocaleString('en-US');
            if (hasDot) {
                return (intFmt === '' ? '0' : intFmt) + '.' + decPart;
            }
            return intFmt;
        }

        // Formatea un numero ya resuelto (para mostrar). opts.fixed => 2 decimales.
        function formatNumber(value, opts) {
            var num = toNumber(value);
            var fixed = opts && opts.fixed === true;
            return num.toLocaleString('en-US', {
                minimumFractionDigits: fixed ? 2 : 0,
                maximumFractionDigits: 2
            });
        }

        function caretFromRight(input) {
            try { return input.value.length - input.selectionStart; } catch (e) { return 0; }
        }
        function restoreCaret(input, fromRight) {
            try {
                var pos = Math.max(0, input.value.length - fromRight);
                input.setSelectionRange(pos, pos);
            } catch (e) {}
        }

        function handleInput(e) {
            var input = e.target;
            var fromRight = caretFromRight(input);
            input.value = formatWhileTyping(input.value);
            restoreCaret(input, fromRight);
        }

        function handleBlur(e) {
            var input = e.target;
            if (String(input.value).trim() === '') { return; }
            input.value = formatNumber(toNumber(input.value), { fixed: true });
        }

        function prepare(input) {
            if (!input || input.dataset.moneyReady === '1') { return; }
            if (input.type === 'number') { input.type = 'text'; }
            if (!input.getAttribute('inputmode')) { input.setAttribute('inputmode', 'decimal'); }
            input.dataset.moneyReady = '1';
            if (String(input.value).trim() !== '') {
                input.value = formatWhileTyping(input.value);
            }
            input.addEventListener('input', handleInput);
            input.addEventListener('blur', handleBlur);
        }

        function init(target) {
            if (target && target.nodeType === 1 && target.matches && target.matches('input')) {
                prepare(target);
                return;
            }
            var scope = (target && target.querySelectorAll) ? target : document;
            scope.querySelectorAll(SELECTOR).forEach(prepare);
        }

        function set(input, value) {
            if (!input) { return; }
            input.value = formatNumber(value, { fixed: true });
        }

        function read(inputOrValue) {
            var v = (inputOrValue && typeof inputOrValue === 'object' && 'value' in inputOrValue)
                ? inputOrValue.value
                : inputOrValue;
            return toNumber(v);
        }

        // Quita las comas antes de enviar para que el backend reciba un numero limpio.
        function sanitize(form) {
            if (!form || !form.querySelectorAll) { return; }
            form.querySelectorAll('input[data-money-ready="1"]').forEach(function (input) {
                if (String(input.value).trim() === '') { return; }
                input.value = String(toNumber(input.value));
            });
        }

        window.MedisoftMoneyInput = {
            init: init,
            prepare: prepare,
            read: read,
            set: set,
            format: formatNumber,
            sanitize: sanitize,
            parse: toNumber
        };

        function boot() {
            init(document);
            // Red de seguridad: cualquier form con campos de dinero se limpia antes de enviarse,
            // aunque la vista no llame explicitamente a sanitize().
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (form && form.querySelector && form.querySelector('input[data-money-ready="1"]')) {
                    sanitize(form);
                }
            }, true);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initMedisoftSilentDownloads();
            initMedisoftScrollMemory();
            initHotelFormGuard();
            initHotelInstantSearch();
        });
    } else {
        initMedisoftSilentDownloads();
        initMedisoftScrollMemory();
        initHotelFormGuard();
        initHotelInstantSearch();
    }

})();
