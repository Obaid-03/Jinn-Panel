<?php
declare(strict_types=1);

/** Deterministic sample statistics for the preview (charts, dashboards) (same numbers on every load). */

function stats_rand(int $seed): callable
{
    mt_srand($seed);
    return fn(float $lo = 0, float $hi = 1): float => $lo + mt_rand() / mt_getrandmax() * ($hi - $lo);
}

/** Traffic per bucket for the busiest sites, with a spike on one of them. @return array{labels:list<string>, series:list<array>, stacks:list<list<float>>, spike:int} */
function stats_traffic(string $range): array
{
    $r = stats_rand(42);
    [$n, $step, $fmt] = match ($range) {
        '7d' => [42, 4 * 3600, 'D H:00'],
        '30d' => [30, 86400, 'j M'],
        default => [24, 3600, 'H:00'],
    };
    $end = strtotime('2026-11-14 16:00:00');
    $sites = [
        ['name' => 'shop.acme.test', 'series' => 's1', 'base' => 2400],
        ['name' => 'acme.test', 'series' => 's2', 'base' => 1700],
        ['name' => 'bakery.test', 'series' => 's3', 'base' => 900],
        ['name' => 'clinic-north.example', 'series' => 's4', 'base' => 650],
        ['name' => 'Other sites', 'series' => 'so', 'base' => 1300],
    ];
    $mult = $range === '30d' ? 24 : ($range === '7d' ? 4 : 1);
    $spike = $range === '24h' ? 14 : ($range === '7d' ? 38 : 27);
    $labels = [];
    $stacks = [];
    for ($i = 0; $i < $n; $i++) {
        $ts = $end - ($n - 1 - $i) * $step;
        $labels[] = date($fmt, $ts);
        $hour = (int) date('G', $ts);
        $day = $range === '30d' ? 1.0 : 0.55 + 0.45 * sin(($hour - 8) / 24 * 2 * M_PI + M_PI / 2) ** 2;
        $col = [];
        foreach ($sites as $k => $s) {
            $v = $s['base'] * $mult * $day * $r(0.82, 1.15);
            if ($k === 0 && $i === $spike) {
                $v *= 3.4;
            }
            $col[] = round($v);
        }
        $stacks[] = $col;
    }
    return ['labels' => $labels, 'series' => array_map(fn($s) => ['name' => $s['name'], 'series' => $s['series']], $sites), 'stacks' => $stacks, 'spike' => $spike];
}

/** A smooth daily-ish series for sparklines and small charts. @return list<float> */
function stats_series(int $seed, int $n, float $base, float $wiggle = 0.25, float $trend = 0.0): array
{
    $r = stats_rand($seed);
    $out = [];
    $v = $base;
    for ($i = 0; $i < $n; $i++) {
        $v = max(0, $v * (1 + $r(-$wiggle, $wiggle) / 3) + $base * $trend / $n);
        $out[] = round($v * (0.9 + 0.2 * sin($i / 3)), 2);
    }
    return $out;
}

/** @return list<array<string,mixed>> */
function stats_accounts(): array
{
    $names = [
        ['acme', 'Acme Ltd', 'owner@acme.test', 'Business', 'resell'], ['bakery', 'The Bakery', 'hello@bakery.test', 'Starter', null],
        ['clinicnorth', 'Clinic North', 'it@clinic-north.example', 'Business', null], ['dunmore', 'Dunmore Law', 'office@dunmore.example', 'Business', 'resell'],
        ['eastgate', 'Eastgate School', 'web@eastgate.example', 'Unlimited', null], ['fernhill', 'Fern Hill Farm', 'shop@fernhill.example', 'Starter', 'resell'],
        ['grayson', 'Grayson Studio', 'hi@grayson.example', 'Business', null], ['harbour', 'Harbour Cafe', 'menu@harbour.example', 'Starter', null],
        ['ivyprint', 'Ivy Print', 'orders@ivyprint.example', 'Business', 'resell'], ['juno', 'Juno Yoga', 'class@juno.example', 'Starter', null],
        ['kestrel', 'Kestrel Motors', 'sales@kestrel.example', 'Business', null], ['lumen', 'Lumen Photo', 'studio@lumen.example', 'Starter', 'resell'],
    ];
    $out = [];
    foreach ($names as $i => [$u, $full, $email, $pkg, $res]) {
        $r = stats_rand(100 + $i);
        $limit = match ($pkg) { 'Starter' => 1024, 'Unlimited' => -1, default => 5120 };
        $used = $u === 'bakery' ? $limit * 1.08 : ($limit > 0 ? $limit * $r(0.12, 0.94) : $r(2000, 9000));
        $out[] = [
            'id' => 10 + $i, 'username' => $u, 'full_name' => $full, 'email' => $email, 'package' => $pkg, 'reseller' => $res,
            'disk_used_mb' => round($used), 'disk_limit_mb' => $limit, 'domains' => (int) round($r(1, 5)),
            'traffic' => stats_series(200 + $i, 14, $r(200, 4000), 0.35, $u === 'acme' ? 0.8 : 0),
            'status' => $u === 'harbour' ? 'suspended' : 'active',
            'created' => date('Y-m-d', strtotime('2026-11-14') - (int) $r(5, 400) * 86400),
        ];
    }
    return $out;
}
