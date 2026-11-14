<?php
require_once __DIR__ . '/../_sample.php';
$accounts = sample_accounts();
foreach ($accounts as &$a) { $a += ['used_bytes' => $a['role'] === 'user' ? 1_800_000_000 : null, 'over_disk' => $a['username'] === 'bakery' ? 1 : 0, 'over_bandwidth' => 0, 'domain_count' => $a['role'] === 'user' ? 3 : 0]; }
unset($a);
array_unshift($accounts, ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com', 'role' => 'admin', 'status' => 'active', 'package_name' => null, 'reseller_username' => null, 'used_bytes' => null, 'over_disk' => 0, 'over_bandwidth' => 0, 'domain_count' => 0]);
return [
    'layout' => 'whm', 'role' => 'admin', 'path' => '/whm/accounts',
    'data' => ['title' => 'Accounts', 'accounts' => $accounts, 'me' => ['id' => 1, 'role' => 'admin', 'username' => 'admin'], 'packages' => sample_packages()],
    'variants' => [
        'empty' => ['accounts' => []],
        'reseller' => ['role' => 'reseller', 'data' => ['accounts' => array_slice($accounts, 2, 2), 'me' => ['id' => 2, 'role' => 'reseller', 'username' => 'resell']]],
    ],
];
