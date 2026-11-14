// Charts (Chart.php) - the hover layer: a crosshair and one tooltip listing
// every series at the pointer (line), or the hovered column's parts and total
// (columns). Arrow keys do the same when the chart has focus. The chart is
// complete without this file; values are also in its table view.
(function () {
    'use strict';
    document.querySelectorAll('[data-chart]').forEach(function (root) {
        var data;
        try { data = JSON.parse(root.getAttribute('data-chart')); } catch (e) { return; }
        var plot = root.querySelector('[data-chart-plot]');
        if (!plot || !data.labels || !data.labels.length) return;
        var n = data.labels.length;
        var cols = data.kind === 'columns';
        var cross = null;
        if (!cols) {
            cross = document.createElement('div');
            cross.className = 'chart-cross';
            cross.hidden = true;
            plot.appendChild(cross);
        }
        // Line charts: a dot on each line where the crosshair is.
        var dots = cols ? [] : data.series.map(function (s) {
            var d = document.createElement('span');
            d.className = 'pointer-events-none absolute h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-surface ' + s.series;
            d.hidden = true;
            plot.appendChild(d);
            return d;
        });
        var tip = document.createElement('div');
        tip.className = 'chart-tip';
        tip.hidden = true;
        tip.setAttribute('role', 'status');
        root.appendChild(tip);
        var current = -1;

        function row(series, value) {
            var r = document.createElement('div');
            r.className = 'chart-tip-row';
            var name = document.createElement('span');
            name.className = 'flex items-center gap-2 text-muted';
            var key = document.createElement('span');
            key.className = (cols ? 'chart-swatch ' : 'chart-key ') + series.series;
            name.appendChild(key);
            name.appendChild(document.createTextNode(series.name));
            var v = document.createElement('strong');
            v.className = 'font-semibold tabular-nums text-fg';
            v.textContent = value;
            r.appendChild(name);
            r.appendChild(v);
            return r;
        }

        function show(i) {
            i = Math.max(0, Math.min(n - 1, i));
            current = i;
            tip.textContent = '';
            var head = document.createElement('div');
            head.className = 'mb-1 text-[12px] text-subtle';
            head.textContent = data.labels[i];
            tip.appendChild(head);
            // Values lead: biggest first for stacks, series order for lines.
            var rows = data.series.map(function (s) { return { s: s, v: s.values[i] }; });
            if (cols) rows = rows.slice().reverse();
            rows.forEach(function (r) { tip.appendChild(row(r.s, r.v)); });
            if (cols && data.totals) {
                var t = document.createElement('div');
                t.className = 'chart-tip-row mt-1 border-t border-line pt-1';
                var l = document.createElement('span');
                l.className = 'text-muted';
                l.textContent = 'Total';
                var v = document.createElement('strong');
                v.className = 'font-semibold tabular-nums text-fg';
                v.textContent = data.totals[i];
                t.appendChild(l);
                t.appendChild(v);
                tip.appendChild(t);
            }
            var pr = plot.getBoundingClientRect();
            var rr = root.getBoundingClientRect();
            var x = cols ? (i + 0.5) / n : (n > 1 ? i / (n - 1) : 0.5);
            var px = pr.left - rr.left + x * pr.width;
            if (cross) {
                cross.hidden = false;
                cross.style.left = (x * 100) + '%';
            }
            dots.forEach(function (d, k) {
                var y = data.series[k].y ? data.series[k].y[i] : null;
                d.hidden = y === null;
                d.style.left = (x * 100) + '%';
                d.style.top = y + '%';
            });
            root.querySelectorAll('.chart-col[data-active]').forEach(function (c) { c.removeAttribute('data-active'); });
            var col = cols ? root.querySelector('.chart-col[data-i="' + i + '"]') : null;
            if (col) col.setAttribute('data-active', '');
            tip.hidden = false;
            var w = tip.offsetWidth;
            var left = px + 12;
            if (left + w > rr.width) left = px - w - 12;
            tip.style.left = Math.max(0, left) + 'px';
            tip.style.top = '4px';
        }
        function hide() {
            tip.hidden = true;
            if (cross) cross.hidden = true;
            dots.forEach(function (d) { d.hidden = true; });
            current = -1;
        }
        function indexAt(clientX) {
            var pr = plot.getBoundingClientRect();
            var f = (clientX - pr.left) / pr.width;
            return cols ? Math.floor(f * n) : Math.round(f * (n - 1));
        }
        root.addEventListener('pointermove', function (e) {
            var pr = plot.getBoundingClientRect();
            if (e.clientX < pr.left || e.clientX > pr.right) { hide(); return; }
            show(indexAt(e.clientX));
        });
        root.addEventListener('pointerleave', hide);
        root.addEventListener('blur', hide);
        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                show(current < 0 ? (e.key === 'ArrowRight' ? 0 : n - 1) : current + (e.key === 'ArrowRight' ? 1 : -1));
            } else if (e.key === 'Home') { e.preventDefault(); show(0); }
            else if (e.key === 'End') { e.preventDefault(); show(n - 1); }
            else if (e.key === 'Escape') { hide(); }
        });
    });
})();
