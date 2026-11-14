# Plan 1 v3 - design prototype (step 1)

The proposed new direction on four screens, with sample chart data, so it
can be judged on real pages before anything in the panel changes. Nothing
here is used by the app.

```bash
dev/preview/serve.sh                      # builds this stylesheet too
open http://127.0.0.1:8787/__proto        # the four screens
open http://127.0.0.1:8787/__preview      # the current pages, to compare
```

| Screen | URL |
|---|---|
| Sign in | `/__proto/login` (`?failed` shows the error state) |
| Admin dashboard | `/__proto/whm` (`?range=24h|7d|30d`) |
| Customer dashboard | `/__proto/cpanel` |
| Accounts list | `/__proto/accounts` (`?sort=name|disk|traffic|created`) |

What's in it:

- `input.css`, `tailwind.config.js` - the new tokens (Jinn violet accent,
  cool surface levels, IBM Plex Sans and Mono, aligned digits) under the
  same component class names as the app, so `Ui::` helpers render in the
  new look unchanged. `build.sh` writes `public/proto.css` (not committed).
- `Chart.php`, `public/charts.js` - the chart kit as SVG/HTML from PHP
  (sparkline, line/area with axes, stacked columns, ring, uptime strip,
  ranked bars, table view) and the hover/keyboard layer. No library.
- `public/fonts/` - IBM Plex Sans 400/500/600 and Plex Mono 400/500, latin
  subset, 92 KB, SIL Open Font License (`OFL-IBM-Plex.txt`).
- `sample.php` - deterministic sample data.

Chart colours follow the data-viz rules: series in a fixed order validated
for colour-vision deficiency in both themes (violet, orange, aqua, yellow,
grey for "other"), a legend for two or more series, a table view for every
chart, values in tooltips and on arrow keys.

Once approved, step 2 moves the tokens and fonts into the app, step 3 moves
`Chart.php` and `charts.js` into `app/`.
