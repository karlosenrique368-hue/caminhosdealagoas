        </main>
    </div>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
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
</script>
</body>
</html>
