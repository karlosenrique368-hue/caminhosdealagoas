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

    if (cfg.data && !(cfg.data instanceof FormData)) {
        cfg.headers['Content-Type'] = 'application/json';
        cfg.body = JSON.stringify(cfg.data);
    } else if (cfg.data) {
        cfg.body = cfg.data;
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
// PREMIUM: Card image slider (auto + hover speedup)
// ============================================================
(function initSliders() {
    document.querySelectorAll('[data-slider]').forEach(wrap => {
        const slides = wrap.querySelectorAll('.slide');
        if (slides.length < 2) return;
        let idx = 0, iv = null;
        const dotsWrap = wrap.querySelector('.slider-dots');
        const dots = dotsWrap ? [...dotsWrap.querySelectorAll('.dot')] : [];
        const go = (n) => {
            slides.forEach((s, i) => s.classList.toggle('active', i === n));
            dots.forEach((d, i) => d.classList.toggle('active', i === n));
            idx = n;
        };
        const next = () => go((idx + 1) % slides.length);
        const start = (delay = 3200) => { stop(); iv = setInterval(next, delay); };
        const stop  = () => { if (iv) { clearInterval(iv); iv = null; } };
        go(0);
        start();
        wrap.addEventListener('mouseenter', () => start(1500));
        wrap.addEventListener('mouseleave', () => start(3200));
        dots.forEach((d, i) => d.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); go(i); start(); }));
    });
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
