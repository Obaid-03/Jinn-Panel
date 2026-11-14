<?php
require_once __DIR__ . '/../_sample.php';
return [
    'layout' => 'cpanel',
    'role' => 'user',
    'path' => '/cpanel/domains',
    'flash' => [['success', 'Domain "acme.test" is set up - it goes live within a few seconds.']],
    'data' => [
        'title' => 'Domains',
        'domains' => sample_domains(),
        'usage' => sample_usage(),
        'pkg' => sample_pkg(),
        'phpVersions' => ['8.4', '8.3', '8.2'],
    ],
    'variants' => [
        'empty' => ['domains' => [], 'usage' => ['domains' => 0, 'databases' => 0, 'email_accounts' => 0, 'ftp_accounts' => 0]],
        'no-package' => ['domains' => [], 'pkg' => null],
    ],
];
