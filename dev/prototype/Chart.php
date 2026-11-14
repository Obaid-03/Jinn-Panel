<?php
declare(strict_types=1);

/**
 * Charts as server-rendered SVG/HTML - no library, readable without
 * JavaScript; charts.js adds the crosshair, tooltips and keyboard. Prototype
 * of step 3 (moves to app/src/Support/Chart.php once approved).
 *
 * Specs follow the data-viz rules: 2px lines, ~10% area wash, columns at
 * most 24px with a 4px rounded top and a 2px gap between stacked parts,
 * hairline grid, axis text in text colours (never the series colour),
 * series colours in a fixed validated order (s1..s4, "so" for Other),
 * a legend for two or more series, and a table view for every chart.
 */
final class Chart
{
    /** A clean axis maximum and its ticks (0 + 4 steps of 1/2/2.5/5 x 10^n). @return array{0:float,1:list<float>} */
    public static function scale(float $max, int $steps = 4): array
    {
        if ($max <= 0) {
            return [1.0, [0.0, 0.25, 0.5, 0.75, 1.0]];
        }
        $raw = $max / $steps;
        $mag = 10 ** floor(log10($raw));
        $step = $mag;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $mag >= $raw) {
                $step = $m * $mag;
                break;
            }
        }
        $ticks = [];
        for ($i = 0; $i <= $steps; $i++) {
            $ticks[] = $step * $i;
        }
        return [$step * $steps, $ticks];
    }

    /** 1,284 / 12.9K / 3.4M */
    public static function compact(float $n): string
    {
        $a = abs($n);
        return match (true) {
            $a >= 1e6 => rtrim(rtrim(number_format($n / 1e6, 1), '0'), '.') . 'M',
            $a >= 1e4 => rtrim(rtrim(number_format($n / 1e3, 1), '0'), '.') . 'K',
            default => number_format($n),
        };
    }

    public static function bytes(float $b): string
    {
        if ($b <= 0) {
            return '0 B';
        }
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min(count($u) - 1, (int) floor(log($b, 1024)));
        return rtrim(rtrim(number_format($b / 1024 ** $i, 1), '0'), '.') . ' ' . $u[$i];
    }

    private static function fmt(float $v, string $how): string
    {
        return match ($how) {
            'bytes' => self::bytes($v),
            'pct' => round($v) . '%',
            'load' => number_format($v, 2),
            default => self::compact($v),
        };
    }

    /**
     * A small trend line for a row or a stat (no axes). $o: series (s1..s4),
     * area (bool), height (px).
     *
     * @param list<float> $values
     */
    public static function sparkline(array $values, string $label, array $o = []): string
    {
        $n = count($values);
        if ($n < 2) {
            return '';
        }
        $max = max(1e-9, max($values));
        $min = min(0, min($values));
        $s = $o['series'] ?? 's1';
        $h = (int) ($o['height'] ?? 28);
        $pts = [];
        foreach ($values as $i => $v) {
            $pts[] = round($i / ($n - 1) * 100, 2) . ',' . round(100 - ($v - $min) / ($max - $min) * 92 - 4, 2);
        }
        $last = $values[$n - 1];
        $lastY = 100 - ($last - $min) / ($max - $min) * 92 - 4;
        $path = 'M' . implode(' L', $pts);
        return '<span class="relative block w-full" style="height:' . $h . 'px" role="img" aria-label="' . e($label) . '">'
            . '<svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">'
            . (!empty($o['area']) ? '<path d="' . $path . ' L100,100 L0,100 Z" class="fill-' . $s . '" style="fill:var(--jp-series-' . substr($s, 1) . ');fill-opacity:var(--jp-chart-wash)"/>' : '')
            . '<path d="' . $path . '" fill="none" stroke-width="1.75" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" style="stroke:var(--jp-series-' . substr($s, 1) . ')"/>'
            . '</svg>'
            . '<span class="absolute h-[7px] w-[7px] -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-surface ' . $s . '" style="left:100%;top:' . round($lastY, 2) . '%"></span>'
            . '</span>';
    }

    /**
     * Line (or area, for one series) over time, with axes and a crosshair.
     * $series: list of ['name' => ..., 'values' => list<float>, 'series' => 's1'].
     * $o: height, area, fmt (compact|bytes|pct|load), xEvery (label every n), id.
     *
     * @param list<array{name:string, values:list<float>, series?:string}> $series
     * @param list<string> $labels
     */
    public static function line(array $series, array $labels, string $label, array $o = []): string
    {
        $n = count($labels);
        $fmt = (string) ($o['fmt'] ?? 'compact');
        $h = (int) ($o['height'] ?? 220);
        $all = array_merge(...array_map(fn($s) => $s['values'], $series));
        [$max, $ticks] = self::scale((float) max($all ?: [0]));
        $svg = '';
        foreach ($series as $k => $s) {
            $cls = $s['series'] ?? 's' . ($k + 1);
            $pts = [];
            foreach ($s['values'] as $i => $v) {
                $pts[] = round($i / max(1, $n - 1) * 1000, 2) . ',' . round(100 - $v / $max * 100, 3);
            }
            $d = 'M' . implode(' L', $pts);
            $var = 'var(--jp-series-' . substr($cls, 1) . ')';
            if (!empty($o['area']) && count($series) === 1) {
                $svg .= '<path d="' . $d . ' L1000,100 L0,100 Z" style="fill:' . $var . ';fill-opacity:var(--jp-chart-wash)"/>';
            }
            $svg .= '<path d="' . $d . '" fill="none" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" style="stroke:' . $var . '"/>';
        }
        $data = ['kind' => 'line', 'labels' => $labels, 'series' => array_map(fn($s, $k) => [
            'name' => $s['name'], 'series' => $s['series'] ?? 's' . ($k + 1), 'values' => array_map(fn($v) => self::fmt((float) $v, $fmt), $s['values']),
            'y' => array_map(fn($v) => round(100 - $v / $max * 100, 2), $s['values']),
        ], $series, array_keys($series))];

        $html = '<div class="chart" style="height:' . $h . 'px" role="img" tabindex="0" aria-label="' . e($label) . '. Use the arrow keys to read values." data-chart="' . e((string) json_encode($data)) . '">';
        $html .= '<div class="chart-plot" style="left:48px;right:4px;top:6px;bottom:26px" data-chart-plot>';
        foreach ($ticks as $t) {
            $top = round(100 - $t / $max * 100, 3);
            $html .= '<div class="' . ($t == 0 ? 'chart-base' : 'chart-grid') . '" style="top:' . $top . '%"></div>'
                . '<span class="chart-ytick" style="right:100%;top:' . $top . '%">' . e(self::fmt($t, $fmt)) . '</span>';
        }
        $every = (int) ($o['xEvery'] ?? max(1, (int) ceil($n / 6)));
        $k = 0;
        foreach ($labels as $i => $l) {
            if ($i % $every === 0) {
                // Every other label drops out on phones, where they'd collide.
                $html .= '<span class="chart-xtick' . ($k++ % 2 ? ' max-sm:hidden' : '') . '" style="left:' . round($i / max(1, $n - 1) * 100, 3) . '%;top:100%">' . e($l) . '</span>';
            }
        }
        $html .= '<svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 1000 100" preserveAspectRatio="none" aria-hidden="true">' . $svg . '</svg>';
        foreach ($series as $k => $s) {
            $cls = $s['series'] ?? 's' . ($k + 1);
            $v = (float) end($s['values']);
            $html .= '<span class="absolute h-2 w-2 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-surface ' . $cls . '" style="left:100%;top:' . round(100 - $v / $max * 100, 3) . '%"></span>';
        }
        return $html . '</div></div>';
    }

    /**
     * Stacked columns, one per time bucket. $series: list of ['name', 'series' => s1..s4|so];
     * $stacks: per column, the value of each series. $o: height, fmt, xEvery,
     * annotate => [columnIndex, text].
     *
     * @param list<array{name:string, series:string}> $series
     * @param list<list<float>> $stacks
     * @param list<string> $labels
     */
    public static function columns(array $series, array $stacks, array $labels, string $label, array $o = []): string
    {
        $fmt = (string) ($o['fmt'] ?? 'compact');
        $h = (int) ($o['height'] ?? 240);
        $n = count($stacks);
        [$max, $ticks] = self::scale((float) max(array_map('array_sum', $stacks) ?: [0]));
        $data = ['kind' => 'columns', 'labels' => $labels, 'total' => true, 'series' => array_map(fn($s, $k) => [
            'name' => $s['name'], 'series' => $s['series'],
            'values' => array_map(fn($col) => self::fmt((float) ($col[$k] ?? 0), $fmt), $stacks),
        ], $series, array_keys($series)), 'totals' => array_map(fn($col) => self::fmt((float) array_sum($col), $fmt), $stacks)];

        $html = '<div class="chart" style="height:' . $h . 'px" role="img" tabindex="0" aria-label="' . e($label) . '. Use the arrow keys to read values." data-chart="' . e((string) json_encode($data)) . '">';
        $html .= '<div class="chart-plot" style="left:48px;right:4px;top:6px;bottom:26px" data-chart-plot>';
        foreach ($ticks as $t) {
            $top = round(100 - $t / $max * 100, 3);
            $html .= '<div class="' . ($t == 0 ? 'chart-base' : 'chart-grid') . '" style="top:' . $top . '%"></div>'
                . '<span class="chart-ytick" style="right:100%;top:' . $top . '%">' . e(self::fmt($t, $fmt)) . '</span>';
        }
        $html .= '<div class="absolute inset-0 flex items-stretch gap-[2px]">';
        foreach ($stacks as $i => $col) {
            $html .= '<div class="chart-col" data-i="' . $i . '">';
            foreach ($series as $k => $s) {
                $v = (float) ($col[$k] ?? 0);
                if ($v > 0) {
                    $html .= '<span class="' . e($s['series']) . '" style="height:' . round($v / $max * 100, 3) . '%"></span>';
                }
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        $every = (int) ($o['xEvery'] ?? max(1, (int) ceil($n / 8)));
        $k = 0;
        foreach ($labels as $i => $l) {
            if ($i % $every === 0) {
                $html .= '<span class="chart-xtick' . ($k++ % 2 ? ' max-sm:hidden' : '') . '" style="left:' . round(($i + 0.5) / $n * 100, 3) . '%;top:100%">' . e($l) . '</span>';
            }
        }
        if (isset($o['annotate'])) {
            [$ai, $text] = $o['annotate'];
            $top = 100 - array_sum($stacks[$ai]) / $max * 100;
            $left = ($ai + 0.5) / $n * 100;
            $html .= '<div class="pointer-events-none absolute flex -translate-x-1/2 -translate-y-full flex-col items-center" style="left:' . round($left, 3) . '%;top:' . round($top, 3) . '%">'
                . '<span class="whitespace-nowrap rounded-[6px] border border-line bg-surface px-2 py-0.5 text-[12px] font-medium text-fg shadow-sm">' . e($text) . '</span>'
                . '<span class="h-2.5 w-px bg-fg/40"></span></div>';
        }
        return $html . '</div></div>';
    }

    /** Legend: a swatch (rect for areas/bars, line key for lines) and the name, in series order. */
    public static function legend(array $series, bool $lines = false): string
    {
        $html = '<ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[12.5px] text-muted">';
        foreach ($series as $k => $s) {
            $cls = $s['series'] ?? 's' . ($k + 1);
            $html .= '<li class="flex items-center gap-1.5"><span class="' . ($lines ? 'chart-key' : 'chart-swatch') . ' ' . e($cls) . '"></span>' . e($s['name']) . '</li>';
        }
        return $html . '</ul>';
    }

    /** A ring gauge for one ratio against a limit. $o: tone (accent|warning|danger), size (px), center (html). */
    public static function ring(float $pct, string $label, array $o = []): string
    {
        $pct = max(0, min(100, $pct));
        $size = (int) ($o['size'] ?? 72);
        $tone = match ($o['tone'] ?? 'accent') {
            'warning' => 'rgb(var(--jp-warning))',
            'danger' => 'rgb(var(--jp-danger))',
            default => 'rgb(var(--jp-accent))',
        };
        $track = match ($o['tone'] ?? 'accent') {
            'warning' => 'rgb(var(--jp-warning) / 0.18)',
            'danger' => 'rgb(var(--jp-danger) / 0.18)',
            default => 'rgb(var(--jp-accent) / 0.15)',
        };
        return '<span class="relative inline-flex shrink-0 items-center justify-center" style="width:' . $size . 'px;height:' . $size . 'px" role="img" aria-label="' . e($label) . ': ' . round($pct) . '%">'
            . '<svg class="absolute inset-0 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">'
            . '<circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3.5" style="stroke:' . $track . '"/>'
            . '<circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3.5" stroke-linecap="round" pathLength="100" stroke-dasharray="' . round($pct, 2) . ' 100" style="stroke:' . $tone . '"/>'
            . '</svg><span class="relative text-[13px] font-semibold tabular-nums text-fg">' . ($o['center'] ?? round($pct) . '%') . '</span></span>';
    }

    /**
     * Uptime strip: one cell per check, oldest first. 'up' | 'slow' | 'down' | null (no data).
     * Status colours with the uptime figure as text beside it (never colour alone).
     *
     * @param list<?string> $checks
     */
    public static function uptime(array $checks, string $label): string
    {
        $html = '<div class="flex h-5 items-stretch gap-[2px]" role="img" aria-label="' . e($label) . '">';
        foreach ($checks as $c) {
            $cls = match ($c) {
                'up' => 'bg-success/70',
                'slow' => 'bg-warning',
                'down' => 'bg-danger',
                default => 'bg-surface-3',
            };
            $html .= '<span class="min-w-0 flex-1 rounded-[2px] ' . $cls . '"></span>';
        }
        return $html . '</div>';
    }

    /**
     * Ranked horizontal bars, largest first, one hue. $rows: [label, value, shown]
     *
     * @param list<array{0:string,1:float,2:string}> $rows
     */
    public static function ranked(array $rows, string $label, array $o = []): string
    {
        $max = max(1e-9, max(array_map(fn($r) => (float) $r[1], $rows) ?: [0]));
        $html = '<ol class="space-y-2.5" aria-label="' . e($label) . '">';
        foreach ($rows as [$name, $v, $shown]) {
            $html .= '<li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1">'
                . '<span class="truncate font-mono text-[12.5px] text-fg">' . e($name) . '</span>'
                . '<span class="text-right text-[12.5px] tabular-nums text-muted">' . e($shown) . '</span>'
                . '<span class="col-span-2 block h-1.5 rounded-full bg-accent/10"><span class="block h-full rounded-full s1" style="width:' . round($v / $max * 100, 2) . '%"></span></span>'
                . '</li>';
        }
        return $html . '</ol>';
    }

    /** The same numbers as a table, behind a disclosure. */
    public static function table(array $series, array $labels, array $o = []): string
    {
        $fmt = (string) ($o['fmt'] ?? 'compact');
        $html = '<details class="group mt-3"><summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[12.5px] text-muted hover:text-fg [&::-webkit-details-marker]:hidden">'
            . icon('list', 'h-3.5 w-3.5') . 'Show as table</summary><div class="table-wrap mt-2 max-h-72 overflow-y-auto rounded-[8px] border border-line"><table class="table"><thead><tr><th scope="col">' . e($o['first'] ?? 'Time') . '</th>';
        foreach ($series as $s) {
            $html .= '<th scope="col" class="text-right">' . e($s['name']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($labels as $i => $l) {
            $html .= '<tr><td>' . e($l) . '</td>';
            foreach ($series as $s) {
                $html .= '<td class="text-right">' . e(self::fmt((float) ($s['values'][$i] ?? 0), $fmt)) . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></div></details>';
    }
}
