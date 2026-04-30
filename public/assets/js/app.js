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

// Mobile trust strip: keeps the duplicated marquee loop exact on every viewport.
(function initTrustStrip() {
    const track = document.querySelector('.trust-track');
    if (!track) return;
    const setCycle = () => {
        const items = [...track.querySelectorAll('.trust-item:not(.is-duplicate)')];
        if (!items.length) return;
        const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0') || 0;
        const cycle = items.reduce((sum, item) => sum + item.getBoundingClientRect().width, 0) + (gap * items.length);
        track.style.setProperty('--trust-cycle', cycle + 'px');
    };
    setCycle();
    window.addEventListener('resize', window.debounce ? window.debounce(setCycle, 120) : setCycle, { passive: true });
    window.addEventListener('load', setCycle, { once: true });
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
// PREMIUM: Generic visual datepicker for native date inputs
// ============================================================
(function initPremiumDateInputs() {
    const monthNames = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
    const dow = ['D','S','T','Q','Q','S','S'];
    const pad = (n) => String(n).padStart(2, '0');
    const iso = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const parse = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value || '') ? new Date(value + 'T12:00:00') : null;
    const label = (value, placeholder) => {
        const d = parse(value);
        return d ? d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }).replace('.', '') : (placeholder || 'Selecionar data');
    };

    function enhance(input) {
        if (input.dataset.premiumDateBound || input.dataset.premiumDate === 'off') return;
        input.dataset.premiumDateBound = '1';
        const wrapper = document.createElement('div');
        wrapper.className = 'premium-date-field';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('premium-date-native');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'premium-date-trigger';
        trigger.innerHTML = '<span></span><i data-lucide="calendar-days"></i>';
        wrapper.appendChild(trigger);

        const popover = document.createElement('div');
        popover.className = 'premium-date-popover';
        popover.hidden = true;
        document.body.appendChild(popover);

        let current = parse(input.value) || new Date();
        current = new Date(current.getFullYear(), current.getMonth(), 1);

        function setValue(value) {
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            updateTrigger();
        }
        function updateTrigger() {
            const span = trigger.querySelector('span');
            span.textContent = label(input.value, input.getAttribute('placeholder'));
            span.className = input.value ? '' : 'date-placeholder';
        }
        function isDisabled(dayIso) {
            if (input.min && dayIso < input.min) return true;
            if (input.max && dayIso > input.max) return true;
            return false;
        }
        function positionPopover() {
            const rect = wrapper.getBoundingClientRect();
            const width = Math.min(320, window.innerWidth - 32);
            let left = Math.min(Math.max(16, rect.left), window.innerWidth - width - 16);
            const popoverHeight = popover.offsetHeight || 360;
            let top = rect.bottom + 8;
            if (top + popoverHeight > window.innerHeight - 12 && rect.top > popoverHeight + 12) {
                top = rect.top - popoverHeight - 8;
            }
            popover.style.width = width + 'px';
            popover.style.left = left + 'px';
            popover.style.top = top + 'px';
        }
        function render() {
            const year = current.getFullYear();
            const month = current.getMonth();
            const first = new Date(year, month, 1);
            const days = new Date(year, month + 1, 0).getDate();
            const todayIso = iso(new Date());
            let html = `<div class="premium-date-head">
                <button type="button" class="premium-date-nav is-year" data-nav="prev-year" aria-label="Ano anterior"><i data-lucide="chevrons-left"></i></button>
                <button type="button" class="premium-date-nav" data-nav="prev" aria-label="Mês anterior"><i data-lucide="chevron-left"></i></button>
                <div class="premium-date-title">${monthNames[month]} de ${year}</div>
                <button type="button" class="premium-date-nav" data-nav="next" aria-label="Próximo mês"><i data-lucide="chevron-right"></i></button>
                <button type="button" class="premium-date-nav is-year" data-nav="next-year" aria-label="Próximo ano"><i data-lucide="chevrons-right"></i></button>
            </div><div class="premium-date-grid">`;
            dow.forEach(d => { html += `<div class="premium-date-dow">${d}</div>`; });
            for (let i = 0; i < first.getDay(); i++) html += '<button type="button" class="premium-date-day is-muted" tabindex="-1"></button>';
            for (let d = 1; d <= days; d++) {
                const value = `${year}-${pad(month + 1)}-${pad(d)}`;
                const cls = ['premium-date-day'];
                if (value === input.value) cls.push('is-selected');
                if (value === todayIso) cls.push('is-today');
                html += `<button type="button" class="${cls.join(' ')}" data-day="${value}" ${isDisabled(value) ? 'disabled' : ''}>${d}</button>`;
            }
            html += `</div><div class="premium-date-actions">
                <button type="button" class="premium-date-action" data-action="today">Hoje</button>
                <button type="button" class="premium-date-action" data-action="clear">Limpar</button>
            </div>`;
            popover.innerHTML = html;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            positionPopover();
        }
        function open() {
            document.querySelectorAll('.premium-date-field.is-open').forEach(w => {
                if (w !== wrapper) {
                    w.classList.remove('is-open');
                }
            });
            document.querySelectorAll('.premium-date-popover').forEach(p => { if (p !== popover) p.hidden = true; });
            wrapper.classList.add('is-open');
            popover.hidden = false;
            render();
        }
        function close() { wrapper.classList.remove('is-open'); popover.hidden = true; }
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const d = parse(input.value);
            if (d) current = new Date(d.getFullYear(), d.getMonth(), 1);
            updateTrigger();
            popover.hidden ? open() : close();
        });
        trigger.addEventListener('mousedown', (e) => e.stopPropagation());
        popover.addEventListener('mousedown', (e) => e.stopPropagation());
        popover.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const nav = e.target.closest('[data-nav]');
            const day = e.target.closest('[data-day]');
            const action = e.target.closest('[data-action]');
            if (nav) {
                if (nav.dataset.nav === 'next-year') current.setFullYear(current.getFullYear() + 1);
                else if (nav.dataset.nav === 'prev-year') current.setFullYear(current.getFullYear() - 1);
                else current.setMonth(current.getMonth() + (nav.dataset.nav === 'next' ? 1 : -1));
                render();
            } else if (day) {
                setValue(day.dataset.day);
                close();
            } else if (action) {
                if (action.dataset.action === 'today') setValue(iso(new Date()));
                if (action.dataset.action === 'clear') setValue('');
                close();
            }
        });
        input.addEventListener('input', () => {
            const d = parse(input.value);
            if (d) current = new Date(d.getFullYear(), d.getMonth(), 1);
            updateTrigger();
        });
        window.addEventListener('scroll', () => { if (!popover.hidden) positionPopover(); }, { passive: true });
        window.addEventListener('resize', () => { if (!popover.hidden) positionPopover(); });
        new MutationObserver(updateTrigger).observe(input, { attributes: true, attributeFilter: ['value'] });
        updateTrigger();
    }

    function bindAll() { document.querySelectorAll('input[type="date"]').forEach(enhance); }
    document.addEventListener('DOMContentLoaded', bindAll);
    document.addEventListener('click', (e) => {
        if (e.target.closest('.premium-date-field') || e.target.closest('.premium-date-popover')) return;
        document.querySelectorAll('.premium-date-field.is-open').forEach(w => {
            w.classList.remove('is-open');
        });
        document.querySelectorAll('.premium-date-popover').forEach(p => { p.hidden = true; });
    });
    new MutationObserver(bindAll).observe(document.body, { childList: true, subtree: true });
})();

// ============================================================
// Availability calendar used by detail pages (multi-date)
// ============================================================
window.availabilityCalendar = function availabilityCalendar(config) {
    const today = new Date(); today.setHours(0,0,0,0);
    return {
        mode: config.mode || 'fixed',
        map: config.map || {},
        basePrice: Number(config.basePrice || 0),
        checkoutBase: config.checkoutBase || '#',
        cartType: config.cartType || '',
        cartId: Number(config.cartId || 0),
        viewYear: today.getFullYear(),
        viewMonth: today.getMonth(),
        selectedDates: [],
        init(){ this.syncCartSelection(); },
        get selectedIso() { return this.selectedDates[0] || ''; },
        get modeLabel() {
            if (this.mode === 'open') return 'Datas abertas — selecione uma ou várias datas disponíveis';
            if (this.mode === 'on_request') return 'Sob consulta — combine a data pelo WhatsApp';
            return 'Selecione uma ou várias datas listadas abaixo';
        },
        get monthLabel() {
            return ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][this.viewMonth] + ' de ' + this.viewYear;
        },
        pad(n){ return n < 10 ? '0' + n : '' + n; },
        iso(y,m,d){ return y + '-' + this.pad(m + 1) + '-' + this.pad(d); },
        brl(v){ return 'R$ ' + Number(v || 0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); },
        get cells() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const startDow = first.getDay();
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const cells = [];
            for (let i = 0; i < startDow; i++) cells.push({ key: 'e' + i, empty: true });
            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(this.viewYear, this.viewMonth, d);
                const isoStr = this.iso(this.viewYear, this.viewMonth, d);
                const past = dateObj < today;
                const info = this.map[isoStr];
                let available = false, lowSeats = false, blocked = false, price = this.basePrice;
                if (past || this.mode === 'on_request') {
                    available = false;
                } else if (info) {
                    if (info.status === 'open' && Number(info.seats || 0) > 0) {
                        available = true;
                        lowSeats = Number(info.seats || 0) <= 3;
                        price = Number(info.price || price);
                    } else {
                        blocked = true;
                    }
                } else if (this.mode === 'open') {
                    available = true;
                }
                cells.push({ key: isoStr, iso: isoStr, day: d, empty: false, past, available, lowSeats, blocked, priceLabel: available ? this.brl(price).replace('R$ ','R$') : '', seats: info ? info.seats : null, price });
            }
            return cells;
        },
        prevYear(){ this.viewYear--; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
        nextYear(){ this.viewYear++; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
        prevMonth(){ if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; } else this.viewMonth--; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
        nextMonth(){ if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; } else this.viewMonth++; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
        isSelected(isoDate){ return this.selectedDates.includes(isoDate); },
        get cartSelectionKey(){
            if (this.cartType && this.cartId) return this.cartType + ':' + this.cartId;
            try {
                const url = new URL(this.checkoutBase, window.location.origin);
                for (const type of ['roteiro','pacote','transfer']) {
                    const value = url.searchParams.get(type);
                    if (value) return type + ':' + Number(value);
                }
            } catch(e) {}
            return '';
        },
        syncCartSelection(){
            const key = this.cartSelectionKey;
            if (!key) return;
            window.detailCalendarSelections = window.detailCalendarSelections || {};
            window.detailCalendarSelections[key] = this.selectedDates.slice();
        },
        select(cell){
            const i = this.selectedDates.indexOf(cell.iso);
            if (i >= 0) this.selectedDates.splice(i, 1); else this.selectedDates.push(cell.iso);
            this.selectedDates.sort();
            this.syncCartSelection();
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        formatDate(isoDate){ return new Date(isoDate + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' }); },
        get selectedLabel(){
            if (!this.selectedDates.length) return '';
            if (this.selectedDates.length === 1) return this.formatDate(this.selectedDates[0]);
            return this.selectedDates.length + ' datas selecionadas';
        },
        get selectedDetail(){
            if (!this.selectedDates.length) return '';
            if (this.selectedDates.length > 1) return this.selectedDates.map(d => this.formatDate(d)).join(' · ');
            const c = this.cells.find(x => x.iso === this.selectedDates[0]);
            if (!c) return '';
            const parts = [this.brl(c.price) + (this.checkoutBase.includes('transfer=') ? ' por veículo' : ' por pessoa')];
            if (c.seats !== null) parts.push(c.seats + ' vagas restantes');
            return parts.join(' · ');
        },
        get selectedCheckoutUrl(){
            const dates = encodeURIComponent(this.selectedDates.join(','));
            return this.checkoutBase + (this.checkoutBase.includes('?') ? '&' : '?') + 'dates=' + dates;
        },
    };
};

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
        const thumbs = wrap.querySelectorAll('.slider-thumbs .thumb');
        thumbs.forEach((t, i) => t.classList.toggle('active', i === next));
    }
    // Delegated arrow + dot clicks (works for static + dynamic sliders)
    document.addEventListener('click', (e) => {
        const arrow = e.target.closest('.slider-arrow');
        const dot   = e.target.closest('.slider-dots .dot');
        const thumb = e.target.closest('.slider-thumbs .thumb');
        if (!arrow && !dot && !thumb) return;
        const wrap = (arrow || dot || thumb).closest('[data-slider]');
        if (!wrap) return;
        e.preventDefault();
        e.stopPropagation();
        if (arrow) {
            const dir = arrow.classList.contains('next') ? 1 : -1;
            go(wrap, getIdx(wrap) + dir);
        } else if (dot) {
            const dots = [...wrap.querySelectorAll('.slider-dots .dot')];
            go(wrap, dots.indexOf(dot));
        } else if (thumb) {
            const thumbs = [...wrap.querySelectorAll('.slider-thumbs .thumb')];
            go(wrap, thumbs.indexOf(thumb));
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
// PREMIUM: Detail gallery lightbox (desktop + mobile)
// ============================================================
(function initGalleryLightbox() {
    let modal = null;
    let images = [];
    let index = 0;
    let touchStartX = 0;

    function ensureModal() {
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'gallery-lightbox-backdrop';
        modal.hidden = true;
        modal.innerHTML = `
            <button type="button" class="gallery-lightbox-close" data-gallery-close aria-label="Fechar galeria"><i data-lucide="x"></i></button>
            <button type="button" class="gallery-lightbox-arrow prev" data-gallery-action="prev" aria-label="Foto anterior"><i data-lucide="chevron-left"></i></button>
            <img class="gallery-lightbox-image" alt="Foto em destaque">
            <button type="button" class="gallery-lightbox-arrow next" data-gallery-action="next" aria-label="Próxima foto"><i data-lucide="chevron-right"></i></button>
            <div class="gallery-lightbox-counter"></div>
            <div class="gallery-lightbox-thumbs" aria-label="Miniaturas da galeria"></div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        modal.addEventListener('touchstart', (e) => { touchStartX = e.touches[0]?.clientX || 0; }, { passive: true });
        modal.addEventListener('touchend', (e) => {
            const dx = (e.changedTouches[0]?.clientX || 0) - touchStartX;
            if (Math.abs(dx) > 44) move(dx < 0 ? 1 : -1);
        }, { passive: true });
        return modal;
    }

    function render() {
        if (!modal || !images.length) return;
        const img = modal.querySelector('.gallery-lightbox-image');
        const counter = modal.querySelector('.gallery-lightbox-counter');
        const thumbs = modal.querySelector('.gallery-lightbox-thumbs');
        img.src = images[index];
        img.alt = 'Foto ' + (index + 1) + ' de ' + images.length;
        counter.textContent = (index + 1) + ' / ' + images.length;
        thumbs.innerHTML = images.map((src, i) => `<button type="button" class="gallery-lightbox-thumb${i === index ? ' active' : ''}" data-gallery-thumb="${i}" aria-label="Ver foto ${i + 1}"><img src="${src}" alt="Miniatura ${i + 1}"></button>`).join('');
        if (window.lucide) window.lucide.createIcons();
    }

    function move(delta) {
        if (!images.length) return;
        index = (index + delta + images.length) % images.length;
        render();
    }

    function open(nextImages, startIndex) {
        images = nextImages.filter(Boolean);
        if (!images.length) return;
        index = Math.max(0, Math.min(images.length - 1, Number(startIndex || 0)));
        ensureModal();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        render();
    }

    function close() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-gallery-close]')) { e.preventDefault(); close(); return; }
        const action = e.target.closest('[data-gallery-action]');
        if (action) { e.preventDefault(); move(action.dataset.galleryAction === 'next' ? 1 : -1); return; }
        const thumb = e.target.closest('[data-gallery-thumb]');
        if (thumb) { e.preventDefault(); index = Number(thumb.dataset.galleryThumb || 0); render(); return; }

        const opener = e.target.closest('[data-gallery-open]');
        if (!opener || e.target.closest('.slider-arrow, .slider-dots, .slider-thumbs, .heart-btn')) return;
        const holder = opener.closest('[data-gallery]');
        const raw = opener.dataset.gallery || holder?.dataset.gallery || '[]';
        let parsed = [];
        try { parsed = JSON.parse(raw); } catch(e) { parsed = []; }
        let startIndex = Number(opener.dataset.index || 0);
        const slider = opener.closest('[data-slider]');
        if (slider) {
            const active = [...slider.querySelectorAll('.detail-slider-main .slide')].findIndex(s => s.classList.contains('active'));
            if (active >= 0) startIndex = active;
        }
        e.preventDefault();
        open(parsed, startIndex);
    }, true);

    document.addEventListener('keydown', (e) => {
        if (!modal || modal.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') move(-1);
        if (e.key === 'ArrowRight') move(1);
    });
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
                    window.location.href = (window.BASE_PATH || '') + '/conta/login';
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
        const checkoutLink = document.getElementById('cart-checkout-link');
        if (subt) subt.textContent = state.count + (state.count === 1 ? ' item' : ' itens');
        if (totalEl) totalEl.textContent = state.total_fmt || 'R$ 0,00';
        if (checkoutLink && state.checkout_url) checkoutLink.href = state.checkout_url;
        if (!body || !empty || !footer) return;
        if (!state.items.length) {
            body.innerHTML = ''; body.style.display = 'none';
            empty.style.display = 'flex';
            footer.style.display = 'none';
        } else {
            body.style.display = 'block';
            empty.style.display = 'none';
            footer.style.display = 'block';
            body.innerHTML = state.items.map(it => {
                const dates = Array.isArray(it.travel_dates) && it.travel_dates.length ? it.travel_dates : (it.travel_date ? [it.travel_date] : []);
                const datesLabel = dates.length
                    ? dates.map(d => new Date(d+'T12:00:00').toLocaleDateString('pt-BR',{day:'2-digit',month:'short'})).join(', ')
                    : '';
                return `
                <div class="cart-item">
                    ${it.cover ? `<img src="${it.cover}" alt="">` : `<div style="width:72px;height:72px;border-radius:10px;background:linear-gradient(135deg,#5A8FB2,#E28D6E);display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--font-brand);font-size:28px">${it.title.charAt(0)}</div>`}
                    <div style="min-width:0">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.1em;color:var(--terracota);font-weight:700">${it.type_label || it.type}</div>
                        <div style="font-weight:600;color:var(--sepia);font-size:14px;line-height:1.3;margin:2px 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${it.title}</div>
                        ${datesLabel ? `<div style="font-size:11px;color:var(--horizonte);font-weight:600;margin-bottom:4px"><i data-lucide="calendar" style="width:11px;height:11px;display:inline;vertical-align:-1px"></i> ${dates.length > 1 ? dates.length + ' datas: ' : ''}${datesLabel}</div>` : ''}
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="display:inline-flex;align-items:center;border:1px solid var(--border-default);border-radius:8px;overflow:hidden">
                                <button onclick="window.cart.update('${it.key}', ${it.qty - 1})" style="width:26px;height:26px;font-size:14px;color:var(--text-secondary)">−</button>
                                <span style="padding:0 10px;font-size:13px;font-weight:600">${it.qty}</span>
                                <button onclick="window.cart.update('${it.key}', ${it.qty + 1})" style="width:26px;height:26px;font-size:14px;color:var(--text-secondary)">+</button>
                            </div>
                            <div style="font-weight:700;color:var(--terracota);font-size:14px">R$ ${it.subtotal.toFixed(2).replace('.',',')}</div>
                        </div>
                        ${it.checkout_url ? `<a href="${it.checkout_url}" style="display:inline-flex;align-items:center;gap:5px;margin-top:7px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--horizonte)"><i data-lucide="lock" style="width:12px;height:12px"></i>Reservar este item</a>` : ''}
                    </div>
                    <button onclick="window.cart.remove('${it.key}')" style="color:var(--text-muted);width:28px;height:28px;align-self:start" aria-label="Remover">
                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                    </button>
                </div>
            `}).join('');
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
    async function add(type, id, travelDate) {
        const travelDates = Array.isArray(travelDate) ? travelDate : (travelDate ? [travelDate] : []);
        const r = await apiCall('add', { type, id, travel_date: travelDates[0] || '', travel_dates: JSON.stringify(travelDates) });
        if (r.ok) {
            window.showToast && window.showToast('Adicionado ao carrinho!', 'success');
            open();
        } else {
            window.showToast && window.showToast(r.msg || 'Erro ao adicionar.', 'error');
        }
    }
    function addSelectedOrAsk(type, id, title) {
        const key = type + ':' + Number(id);
        const dates = (window.detailCalendarSelections && Array.isArray(window.detailCalendarSelections[key]))
            ? window.detailCalendarSelections[key].filter(Boolean)
            : [];
        if (dates.length) return add(type, id, dates);
        return askDate(type, id, title);
    }
    function askDate(type, id, title) {
        // Tenta abrir o modal Alpine. Se não houver listener, faz fallback com prompt nativo.
        let handled = false;
        const onHandled = () => { handled = true; };
        window.addEventListener('cart:ask-date-handled', onHandled, { once: true });
        const ev = new CustomEvent('cart:ask-date', { detail: { type, id, title: title || '' } });
        window.dispatchEvent(ev);
        setTimeout(() => {
            window.removeEventListener('cart:ask-date-handled', onHandled);
            if (!handled) {
                // Fallback robusto: prompt nativo
                const today = new Date();
                const def = today.toISOString().split('T')[0];
                const txt = window.prompt('Para quais datas você quer reservar? Separe por vírgula. (AAAA-MM-DD)', def);
                const dates = (txt || '').split(/[,;\s]+/).map(v => v.trim()).filter(Boolean);
                if (dates.length && dates.every(v => /^\d{4}-\d{2}-\d{2}$/.test(v))) add(type, id, dates);
                else if (txt) window.showToast && window.showToast('Data inválida. Use AAAA-MM-DD.', 'error');
            }
        }, 120);
    }
    async function remove(key) { await apiCall('remove', { key }); }
    async function update(key, qty) {
        if (qty < 1) return remove(key);
        await apiCall('update', { key, qty });
    }
    async function clear() {
        const r = await apiCall('clear', { clear: '1' });
        if (r.ok) window.showToast && window.showToast('Carrinho limpo.', 'success');
    }
    async function refresh() { await apiCall('get'); }

    document.addEventListener('DOMContentLoaded', refresh);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    return { open, close, add, addSelectedOrAsk, askDate, remove, update, clear, refresh, get state() { return state; } };
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

        function syncInputFiles() {
            if (uploadUrl || !input) return;
            if (typeof DataTransfer === 'undefined') return;
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;
        }

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
            syncInputFiles();
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
        preview.addEventListener('pointerdown', (e) => {
            if (!e.target.closest('.remove-btn')) return;
            e.preventDefault();
            e.stopPropagation();
        });
        preview.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-btn[data-idx]');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const idx = parseInt(btn.dataset.idx, 10);
            files.splice(idx, 1);
            syncInputFiles();
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
// Gallery editor remove buttons (admin forms)
// ============================================================
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.gallery-editor-remove');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const item = btn.closest('[data-gallery-item]');
    if (item) item.remove();
});

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
