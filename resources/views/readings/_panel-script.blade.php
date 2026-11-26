{{--
    Раскрывающиеся строки приборов (readings.index и «Seadmete näidud» на странице объекта).
    Строка прибора: <tr data-panel-url="..." data-devid="..."> с кнопкой .rd-toggle.
    Данные прибора грузятся только при раскрытии, формы в панели отправляются через fetch,
    после правки строка обновляется через opts.onUpdate(row, device, devidChanged) — без перезагрузки списка.
--}}
<script>
    window.initDevicePanels = function (opts) {
        var body = opts.body;
        var wrap = opts.wrap;
        var rowSelector = opts.rowSelector;
        var columns = body.closest('table').tHead.rows[0].cells.length;

        function panelRowOf(row) {
            var next = row.nextElementSibling;
            return next && next.classList.contains('rd-panel-row') ? next : null;
        }

        // Панель не шире видимой части таблицы — иначе растягивается на всю ширину прокрутки
        function fitPanels() {
            body.querySelectorAll('.rd-panel').forEach(function (panel) { panel.style.width = wrap.clientWidth + 'px'; });
        }
        window.addEventListener('resize', fitPanels);

        function setOpen(row, open) {
            row.classList.toggle('open', open);
            row.querySelector('.rd-toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function toggle(row) {
            var panelRow = panelRowOf(row);
            if (panelRow) {
                var open = panelRow.hidden;
                panelRow.hidden = !open;
                setOpen(row, open);
                if (open) fitPanels();
                return;
            }
            panelRow = document.createElement('tr');
            panelRow.className = 'rd-panel-row';
            panelRow.innerHTML = '<td colspan="' + columns + '"><div class="rd-panel"><div class="rd-panel-loading">Laadin…</div></div></td>';
            row.after(panelRow);
            setOpen(row, true);
            fitPanels();
            load(row);
        }

        function load(row, message) {
            var panel = panelRowOf(row).querySelector('.rd-panel');
            panel.classList.add('busy');
            return fetch(row.dataset.panelUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) throw new Error('Laadimine ebaõnnestus (' + response.status + ')');
                    return response.text();
                })
                .then(function (html) {
                    panel.innerHTML = html;
                    if (window.initDatePickers) window.initDatePickers(panel);
                    if (message) showMessage(panel, message, false);
                })
                .catch(function (error) {
                    panel.innerHTML = '<div class="rd-panel-loading rd-panel-msg err"></div>';
                    panel.firstChild.textContent = error.message;
                })
                .finally(function () { panel.classList.remove('busy'); });
        }

        function showMessage(panel, text, isError) {
            var box = panel.querySelector('.rd-panel-msg');
            if (!box) return alert(text);
            box.textContent = text;
            box.className = 'rd-panel-msg ' + (isError ? 'err' : 'ok');
        }

        body.addEventListener('click', function (event) {
            if (event.target.closest('.rd-panel-row')) return;
            var row = event.target.closest(rowSelector);
            if (!row || !row.dataset.panelUrl || event.target.closest('a, input, select')) return;
            if (window.getSelection && String(window.getSelection())) return; // выделяли текст
            toggle(row);
        });

        // Если confirm() в onsubmit отменил отправку, defaultPrevented уже стоит
        body.addEventListener('submit', function (event) {
            var form = event.target;
            var panel = form.closest('.rd-panel');
            if (!panel || event.defaultPrevented) return;
            event.preventDefault();

            var row = panel.closest('.rd-panel-row').previousElementSibling;
            panel.classList.add('busy');
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        if (!response.ok) {
                            var first = data.errors ? Object.values(data.errors)[0] : null;
                            throw new Error((first && first[0]) || data.message || ('Viga ' + response.status));
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    if (data.device) {
                        var changed = String(row.dataset.devid) !== String(data.device.devid);
                        row.dataset.devid = data.device.devid;
                        row.dataset.panelUrl = data.device.panel_url;
                        if (opts.onUpdate) opts.onUpdate(row, data.device, changed);
                    }
                    return load(row, data.message);
                })
                .catch(function (error) {
                    panel.classList.remove('busy');
                    showMessage(panel, error.message, true);
                });
        });

        return { panelRowOf: panelRowOf };
    };
</script>
