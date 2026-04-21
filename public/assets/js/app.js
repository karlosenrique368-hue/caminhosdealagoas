/**
 * Caminhos de Alagoas — App JS
 */

// Lucide init (debounced MutationObserver)
(function initLucide() {
    if (typeof lucide === 'undefined') return;
    lucide.createIcons();
    let timer = null;
    const obs = new MutationObserver(() => {
        if (timer) return;
        timer = setTimeout(() => {
            timer = null;
            try { lucide.createIcons(); } catch (e) {}
        }, 100);
    });
    obs.observe(document.body, { childList: true, subtree: true });
})();

// Navbar scroll effect
(function initNavbar() {
    const nav = document.querySelector('[data-navbar]');
    if (!nav) return;
    const forceSolid = document.body.classList.contains('has-solid-nav');
    const toggle = () => {
        if (forceSolid || window.scrollY > 40) nav.classList.add('nav-scrolled');
        else nav.classList.remove('nav-scrolled');
    };
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
})();

// Fetch helper with CSRF
window.caminhosApi = async function (url, opts = {}) {
    const defaults = { method: 'GET', headers: {} };
    const cfg = { ...defaults, ...opts };
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) cfg.headers['X-CSRF-Token'] = meta.content;
    cfg.headers['X-Requested-With'] = 'XMLHttpRequest';

    if (cfg.data && cfg.data instanceof FormData) {
        cfg.body = cfg.data;
    } else if (cfg.data && cfg.data instanceof URLSearchParams) {
        cfg.headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        cfg.body = cfg.data.toString();
    } else if (cfg.data) {
        cfg.headers['Content-Type'] = 'application/json';
        cfg.body = JSON.stringify(cfg.data);
    }

    const res = await fetch(url, cfg);
    let json;
    try { json = await res.json(); } catch (e) { json = { ok: false, msg: 'Resposta inválida' }; }
    return json;
};

// Toast
window.showToast = function (message, type = 'info') {
    let host = document.getElementById('toast-host');
    if (!host) {
        host = document.createElement('div');
        host.id = 'toast-host';
        host.style.cssText = 'position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(host);
    }
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info';
    toast.innerHTML = `<i data-lucide="${icon}" style="width:20px;height:20px;color:${type==='success'?'#5E7E55':type==='error'?'#DC2626':'#3A6B8A'}"></i><span style="font-size:14px;color:#2D3E4F">${message}</span>`;
    host.appendChild(toast);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    setTimeout(() => {
        toast.style.transition = 'opacity 300ms, transform 300ms';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

// Intersection observer for fade-in-up
(function initReveal() {
    const items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;
    const io = new IntersectionObserver((entries) => {
        entries.forEach(en => {
            if (en.isIntersecting) {
                en.target.classList.add('fade-in-up');
                io.unobserve(en.target);
            }
        });
    }, { threshold: 0.15 });
    items.forEach(i => io.observe(i));
})();

// BRL mask
document.addEventListener('input', (e) => {
    if (!e.target.classList.contains('brl-mask')) return;
    let v = e.target.value.replace(/\D/g, '');
    if (!v) { e.target.value = ''; return; }
    v = (parseInt(v, 10) / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    e.target.value = 'R$ ' + v;
});

// Phone mask
document.addEventListener('input', (e) => {
    if (!e.target.classList.contains('phone-mask')) return;
    let v = e.target.value.replace(/\D/g, '').slice(0, 11);
    if (v.length >= 10) v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    else if (v.length >= 6) v = v.replace(/(\d{2})(\d{4,5})/, '($1) $2');
    else if (v.length >= 2) v = v.replace(/(\d{2})/, '($1) ');
    e.target.value = v;
});

// ============================================================
// PREMIUM: Hero scroll-linked overlay
// ============================================================
(function initHeroScroll() {
    const hero = document.querySelector('.hero-premium');
    if (!hero) return;
    const update = () => {
        const y = window.scrollY || window.pageYOffset;
        const h = hero.offsetHeight || window.innerHeight;
        const prog = Math.min(1, Math.max(0, y / h));
        hero.style.setProperty('--scroll-prog', prog.toFixed(3));
        // Subtle parallax on background
        const bg = hero.querySelector('.hero-bg');
        if (bg) bg.style.transform = `scale(${1.08 + prog * 0.06}) translateY(${prog * 30}px)`;
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
})();

// ============================================================
// PREMIUM: Card image slider — manual only (arrows + swipe + dots, NO autoplay)
// Uses event delegation so dynamically-rendered sliders also work.
// ============================================================
(function initSliders() {
    function getIdx(wrap) {
        const slides = wrap.querySelectorAll('.slide');
        let idx = 0;
        slides.forEach((s, i) => { if (s.classList.contains('active')) idx = i; });
        return idx;
    }
    function go(wrap, n) {
        const slides = wrap.querySelectorAll('.slide');
        if (!slides.length) return;
        const total = slides.length;
        const next = ((n % total) + total) % total;
        slides.forEach((s, i) => s.classList.toggle('active', i === next));
        const dots = wrap.querySelectorAll('.slider-dots .dot');
        dots.forEach((d, i) => d.classList.toggle('active', i === next));
    }
    // Delegated arrow + dot clicks (works for static + dynamic sliders)
    document.addEventListener('click', (e) => {
        const arrow = e.target.closest('.slider-arrow');
        const dot   = e.target.closest('.slider-dots .dot');
        if (!arrow && !dot) return;
        const wrap = (arrow || dot).closest('[data-slider]');
        if (!wrap) return;
        e.preventDefault();
        e.stopPropagation();
        if (arrow) {
            const dir = arrow.classList.contains('next') ? 1 : -1;
            go(wrap, getIdx(wrap) + dir);
        } else if (dot) {
            const dots = [...wrap.querySelectorAll('.slider-dots .dot')];
            go(wrap, dots.indexOf(dot));
        }
    }, true); // capture phase so parent <a> never wins

    // Touch swipe (delegated via touch events on document is unreliable; bind per-wrap once)
    function bindSwipe(wrap) {
        if (wrap.dataset.swipeBound) return;
        wrap.dataset.swipeBound = '1';
        let sx = 0, sy = 0;
        wrap.addEventListener('touchstart', (e) => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
        wrap.addEventListener('touchend', (e) => {
            const dx = (e.changedTouches[0].clientX || 0) - sx;
            const dy = (e.changedTouches[0].clientY || 0) - sy;
            if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
                go(wrap, getIdx(wrap) + (dx < 0 ? 1 : -1));
            }
        }, { passive: true });
    }
    function bindAll() { document.querySelectorAll('[data-slider]').forEach(bindSwipe); }
    bindAll();
    new MutationObserver(bindAll).observe(document.body, { childList: true, subtree: true });
})();

// ============================================================
// PREMIUM: Wishlist toggle (delegated) — loads initial state on page load
// ============================================================
(function initWishlist() {
    // 1) Load current favorites and mark .heart-btn.active
    async function syncState() {
        const buttons = document.querySelectorAll('.heart-btn[data-fav-type][data-fav-id]');
        if (!buttons.length) return;
        try {
            const res = await fetch((window.BASE_PATH || '') + '/api/wishlist?action=list', { credentials: 'same-origin' });
            const j = await res.json();
            if (!j.ok || !Array.isArray(j.items)) return;
            const set = new Set(j.items);
            buttons.forEach(btn => {
                const key = btn.dataset.favType + ':' + btn.dataset.favId;
                btn.classList.toggle('active', set.has(key));
            });
        } catch (e) { /* silent */ }
    }
    document.addEventListener('DOMContentLoaded', syncState);
    // Re-sync ONLY when new heart buttons are added (never on class/attr changes,
    // which would race with our optimistic toggle and undo it mid-flight)
    let resyncTimer = null;
    new MutationObserver((muts) => {
        let hasNewBtns = false;
        for (const m of muts) {
            for (const n of m.addedNodes) {
                if (n.nodeType !== 1) continue;
                if (n.matches && n.matches('.heart-btn')) { hasNewBtns = true; break; }
                if (n.querySelector && n.querySelector('.heart-btn')) { hasNewBtns = true; break; }
            }
            if (hasNewBtns) break;
        }
        if (!hasNewBtns) return;
        if (resyncTimer) return;
        resyncTimer = setTimeout(() => { resyncTimer = null; syncState(); }, 250);
    }).observe(document.body, { childList: true, subtree: true });

    // 2) Click handler — capture phase to beat the parent <a> link
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.heart-btn[data-fav-type][data-fav-id]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';
        const type = btn.dataset.favType;
        const id = btn.dataset.favId;
        const wasActive = btn.classList.contains('active');
        btn.classList.toggle('active'); // optimistic
        try {
            const fd = new FormData();
            fd.append('entity_type', type);
            fd.append('entity_id', id);
            const r = await window.caminhosApi((window.BASE_PATH || '') + '/api/wishlist?action=toggle', { method: 'POST', data: fd });
            if (!r || !r.ok) {
                btn.classList.toggle('active', wasActive); // revert
                if (r && (r.msg === 'Faça login.' || r.msg === 'FaÃ§a login.')) {
                    window.location.href = (window.BASE_PATH || '') + '/login';
                    return;
                }
                if (window.showToast) window.showToast((r && r.msg) || 'Erro ao favoritar', 'error');
            } else {
                btn.classList.toggle('active', !!r.added); // sync with server truth
                if (window.showToast) window.showToast(r.added ? 'Adicionado aos favoritos' : 'Removido dos favoritos', r.added ? 'success' : 'info');
            }
        } catch (err) {
            btn.classList.toggle('active', wasActive);
            if (window.showToast) window.showToast('Erro de rede', 'error');
        } finally {
            btn.dataset.busy = '0';
        }
    }, true); // capture phase
})();

// ============================================================
// PREMIUM: Counter animation
// ============================================================
(function initCounters() {
    const els = document.querySelectorAll('[data-counter]');
    if (!els.length) return;
    const io = new IntersectionObserver((entries) => {
        entries.forEach(en => {
            if (!en.isIntersecting) return;
            io.unobserve(en.target);
            const el = en.target;
            el.classList.add('in-view');
            const raw = el.getAttribute('data-counter');
            const suffix = raw.replace(/[0-9]/g, '');
            const target = parseInt(raw.replace(/\D/g, ''), 10) || 0;
            const dur = 1400;
            const start = performance.now();
            const tick = (now) => {
                const p = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - p, 3);
                const v = Math.round(target * eased);
                el.textContent = v.toLocaleString('pt-BR') + suffix;
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    }, { threshold: 0.4 });
    els.forEach(e => io.observe(e));
})();

// ============================================================
// CART SYSTEM
// ============================================================
window.cart = (function () {
    function getEndpoint() {
        if (window.APP_BASE) return window.APP_BASE + '/api/cart';
        // Derive from any asset link
        const link = document.querySelector('link[rel="stylesheet"][href*="/assets/"]');
        if (link) {
            const m = link.href.match(/(.*)\/assets\//);
            if (m) { window.APP_BASE = m[1]; return m[1] + '/api/cart'; }
        }
        return '/api/cart';
    }
    let state = { count: 0, total: 0, items: [] };

    function render() {
        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = state.count;
            badge.style.display = state.count > 0 ? 'flex' : 'none';
        }
        const body = document.getElementById('cart-body');
        const empty = document.getElementById('cart-empty');
        const footer = document.getElementById('cart-footer');
        const subt = document.getElementById('cart-subtitle');
        const totalEl = document.getElementById('cart-total');
        if (subt) subt.textContent = state.count + (state.count === 1 ? ' item' : ' itens');
        if (totalEl) totalEl.textContent = state.total_fmt || 'R$ 0,00';
        if (!body || !empty || !footer) return;
        if (!state.items.length) {
            body.innerHTML = ''; body.style.display = 'none';
            empty.style.display = 'flex';
            footer.style.display = 'none';
        } else {
            body.style.display = 'block';
            empty.style.display = 'none';
            footer.style.display = 'block';
            body.innerHTML = state.items.map(it => `
                <div class="cart-item">
                    ${it.cover ? `<img src="${it.cover}" alt="">` : `<div style="width:72px;height:72px;border-radius:10px;background:linear-gradient(135deg,#5A8FB2,#E28D6E);display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--font-brand);font-size:28px">${it.title.charAt(0)}</div>`}
                    <div style="min-width:0">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.1em;color:var(--terracota);font-weight:700">${it.type}</div>
                        <div style="font-weight:600;color:var(--sepia);font-size:14px;line-height:1.3;margin:2px 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${it.title}</div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="display:inline-flex;align-items:center;border:1px solid var(--border-default);border-radius:8px;overflow:hidden">
                                <button onclick="window.cart.update('${it.key}', ${it.qty - 1})" style="width:26px;height:26px;font-size:14px;color:var(--text-secondary)">−</button>
                                <span style="padding:0 10px;font-size:13px;font-weight:600">${it.qty}</span>
                                <button onclick="window.cart.update('${it.key}', ${it.qty + 1})" style="width:26px;height:26px;font-size:14px;color:var(--text-secondary)">+</button>
                            </div>
                            <div style="font-weight:700;color:var(--terracota);font-size:14px">R$ ${it.subtotal.toFixed(2).replace('.',',')}</div>
                        </div>
                    </div>
                    <button onclick="window.cart.remove('${it.key}')" style="color:var(--text-muted);width:28px;height:28px;align-self:start" aria-label="Remover">
                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                    </button>
                </div>
            `).join('');
            if (typeof lucide !== 'undefined') setTimeout(() => lucide.createIcons(), 10);
        }
    }

    async function apiCall(action, body) {
        const opts = { method: body ? 'POST' : 'GET' };
        if (body) {
            const fd = new FormData();
            Object.entries(body).forEach(([k, v]) => fd.append(k, v));
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) fd.append('csrf_token', meta.content);
            opts.body = fd;
        }
        const res = await fetch(getEndpoint() + '?action=' + action, opts);
        const json = await res.json();
        if (json.ok) {
            state = json;
            render();
        }
        return json;
    }

    function open() {
        document.getElementById('cart-drawer')?.classList.add('open');
        document.getElementById('cart-backdrop')?.classList.add('open');
        document.body.style.overflow = 'hidden';
        refresh();
    }
    function close() {
        document.getElementById('cart-drawer')?.classList.remove('open');
        document.getElementById('cart-backdrop')?.classList.remove('open');
        document.body.style.overflow = '';
    }
    async function add(type, id) {
        const r = await apiCall('add', { type, id });
        if (r.ok) {
            window.showToast && window.showToast('Adicionado ao carrinho!', 'success');
            open();
        } else {
            window.showToast && window.showToast(r.msg || 'Erro ao adicionar.', 'error');
        }
    }
    async function remove(key) { await apiCall('remove', { key }); }
    async function update(key, qty) {
        if (qty < 1) return remove(key);
        await apiCall('update', { key, qty });
    }
    async function clear() { await apiCall('clear'); }
    async function refresh() { await apiCall('get'); }

    document.addEventListener('DOMContentLoaded', refresh);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    return { open, close, add, remove, update, clear, refresh, get state() { return state; } };
})();


// ============================================================
// AJAX FORMS — generic [data-ajax] form handler
// ============================================================
(function initAjaxForms() {
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.hasAttribute('data-ajax')) return;
        e.preventDefault();
        const url = form.getAttribute('action') || window.location.href;
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const btn = form.querySelector('[type="submit"]');
        if (btn) {
            btn.classList.add('btn-loading');
            btn.disabled = true;
            if (!btn.dataset.origHtml) btn.dataset.origHtml = btn.innerHTML;
            const content = btn.querySelector('.btn-content');
            if (content) content.dataset.origHtml = content.innerHTML;
            btn.insertAdjacentHTML('beforeend', '<span class="ajax-spinner" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%)"></span>');
        }
        try {
            const body = new FormData(form);
            const r = await fetch(url, {
                method,
                body: method === 'GET' ? undefined : body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            let data;
            try { data = await r.json(); } catch { data = { ok: r.ok, msg: 'Resposta inválida.' }; }
            if (data.ok) {
                showToast(data.msg || 'Feito!', 'success');
                // Clear password fields after success
                form.querySelectorAll('input[type="password"]').forEach(i => i.value = '');
                // Custom callback
                if (form.dataset.afterAjax === 'reload') {
                    setTimeout(() => location.reload(), 700);
                } else if (form.dataset.afterAjax === 'reset') {
                    form.reset();
                }
                form.dispatchEvent(new CustomEvent('ajax:success', { detail: data }));
            } else {
                showToast(data.msg || 'Erro ao processar.', 'error');
                form.dispatchEvent(new CustomEvent('ajax:error', { detail: data }));
            }
        } catch (err) {
            showToast('Erro de rede. Tente novamente.', 'error');
        } finally {
            if (btn) {
                btn.classList.remove('btn-loading');
                btn.disabled = false;
                const spinner = btn.querySelector('.ajax-spinner');
                if (spinner) spinner.remove();
            }
        }
    });
})();

// ============================================================
// UPLOAD ZONE — drag/drop + preview
// ============================================================
(function initUploadZones() {
    function hydrate(zone) {
        if (zone.dataset.hydrated) return;
        zone.dataset.hydrated = '1';
        const input = zone.querySelector('input[type="file"]');
        const preview = zone.querySelector('.upload-zone-preview') || (() => {
            const d = document.createElement('div');
            d.className = 'upload-zone-preview';
            zone.appendChild(d);
            return d;
        })();
        const progressBar = document.createElement('div');
        progressBar.className = 'upload-zone-progress';
        zone.appendChild(progressBar);

        const maxFiles = parseInt(zone.dataset.max || '10', 10);
        const uploadUrl = zone.dataset.url;
        const fieldName = zone.dataset.field || 'file';
        let files = [];

        function render() {
            preview.querySelectorAll('.upload-zone-preview-item[data-pending]').forEach(e => e.remove());
            files.forEach((f, idx) => {
                const item = document.createElement('div');
                item.className = 'upload-zone-preview-item';
                item.dataset.pending = '1';
                const url = URL.createObjectURL(f);
                item.innerHTML = `<img src="${url}" alt=""><button type="button" class="remove-btn" data-idx="${idx}"><i data-lucide="x" class="w-3 h-3"></i></button>`;
                preview.appendChild(item);
            });
            if (window.lucide) window.lucide.createIcons();
        }

        function handleFiles(fl) {
            const toAdd = Array.from(fl).slice(0, maxFiles - files.length);
            files = files.concat(toAdd);
            render();
            if (uploadUrl) uploadAll();
        }

        async function uploadAll() {
            if (!uploadUrl || !files.length) return;
            for (let i = 0; i < files.length; i++) {
                const fd = new FormData();
                fd.append(fieldName, files[i]);
                const csrf = document.querySelector('meta[name="csrf-token"]');
                if (csrf) fd.append('csrf_token', csrf.content);
                progressBar.style.width = ((i / files.length) * 100) + '%';
                try {
                    const r = await fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    const data = await r.json();
                    if (!data.ok) { showToast(data.msg || 'Falha no upload', 'error'); break; }
                    if (data.data && data.data.url) {
                        // Append persistent preview
                        const item = document.createElement('div');
                        item.className = 'upload-zone-preview-item';
                        item.innerHTML = `<img src="${data.data.url}" alt=""><input type="hidden" name="${fieldName}[]" value="${data.data.path || data.data.url}"><button type="button" class="remove-btn" onclick="this.closest('.upload-zone-preview-item').remove()"><i data-lucide="x" class="w-3 h-3"></i></button>`;
                        preview.appendChild(item);
                        if (window.lucide) window.lucide.createIcons();
                    }
                } catch (e) {
                    showToast('Erro de rede no upload', 'error');
                    break;
                }
            }
            progressBar.style.width = '100%';
            files = [];
            render();
            setTimeout(() => { progressBar.style.width = '0'; }, 600);
            showToast('Uploads concluídos!', 'success');
        }

        input.addEventListener('change', () => handleFiles(input.files));
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        });
        preview.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-btn[data-idx]');
            if (!btn) return;
            e.preventDefault();
            const idx = parseInt(btn.dataset.idx, 10);
            files.splice(idx, 1);
            render();
        });
    }
    document.querySelectorAll('.upload-zone').forEach(hydrate);
    // Re-hydrate dynamically added zones
    new MutationObserver((muts) => {
        muts.forEach(m => m.addedNodes.forEach(n => {
            if (n.nodeType === 1) {
                if (n.matches && n.matches('.upload-zone')) hydrate(n);
                n.querySelectorAll && n.querySelectorAll('.upload-zone').forEach(hydrate);
            }
        }));
    }).observe(document.body, { childList: true, subtree: true });
})();

// ============================================================
// Auto-grow textareas
// ============================================================
(function initAutoGrow() {
    document.addEventListener('input', (e) => {
        if (!e.target.matches('textarea.auto-grow')) return;
        e.target.style.height = 'auto';
        e.target.style.height = Math.min(400, e.target.scrollHeight) + 'px';
    });
})();
