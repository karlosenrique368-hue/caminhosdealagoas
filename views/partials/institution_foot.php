        </main>
    </div>
</div>
<script>
function institutionApi(url, opts = {}) {
    const token = document.querySelector('meta[name=csrf-token]').content;
    const headers = { 'X-CSRF-Token': token, 'X-Requested-With': 'XMLHttpRequest' };
    let body;
    if (opts.isForm) { body = opts.data; }
    else if (opts.data) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(opts.data); }
    return fetch(url, { method: opts.method || 'POST', headers, body, credentials: 'same-origin' })
        .then(r => r.json()).catch(() => ({ ok:false, msg:'Erro de rede.' }));
}
document.addEventListener('DOMContentLoaded', () => window.lucide && window.lucide.createIcons());
document.addEventListener('alpine:initialized', () => window.lucide && window.lucide.createIcons());
// Debounced MutationObserver — evita loop infinito porque createIcons() substitui <i> por <svg>
let _luTimer = null;
const mo = new MutationObserver((muts) => {
    for (const m of muts) {
        for (const n of m.addedNodes) {
            if (n.nodeType === 1 && n.tagName === 'I' && n.hasAttribute('data-lucide')) {
                if (_luTimer) return;
                _luTimer = setTimeout(() => { _luTimer = null; window.lucide && window.lucide.createIcons(); }, 80);
                return;
            }
        }
    }
});
mo.observe(document.body, { childList: true, subtree: true });
</script>
</body>
</html>
