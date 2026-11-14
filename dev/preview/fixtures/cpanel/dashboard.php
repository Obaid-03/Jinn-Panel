<?php
require_once __DIR__ . '/../_sample.php';
return [
    'layout' => 'cpanel', 'role' => 'user', 'path' => '/cpanel',
    'data' => ['title' => 'Dashboard', 'me' => ['id' => 10, 'username' => 'acme', 'full_name' => 'Acme Ltd', 'role' => 'user'], 'pkg' => sample_pkg(), 'usage' => sample_usage(), 'domains' => sample_domains()],
    'variants' => ['new-account' => ['domains' => [], 'usage' => ['domains' => 0, 'databases' => 0, 'email_accounts' => 0, 'ftp_accounts' => 0]]],
    'session' => ['impersonator' => ['id' => 2, 'username' => 'resell', 'session_version' => 1, 'target' => 10]],
];
