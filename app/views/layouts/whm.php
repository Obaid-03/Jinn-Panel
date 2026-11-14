<?php
$nav = [
    ['label' => null, 'items' => [
        ['href' => '/whm', 'label' => 'Dashboard', 'icon' => 'grid'],
    ]],
    ['label' => 'Accounts', 'items' => [
        ['href' => '/whm/accounts', 'label' => 'Accounts', 'icon' => 'users'],
        ['href' => '/whm/packages', 'label' => 'Packages', 'icon' => 'box'],
        ['href' => '/whm/migrations', 'label' => 'cPanel Migration', 'icon' => 'transfer'],
        ['href' => '/whm/activity', 'label' => 'Activity Log', 'icon' => 'activity'],
    ]],
];
if (Auth::isAdmin()) {
    $nav[] = ['label' => 'Server', 'items' => [
        ['href' => '/whm/alerts', 'label' => 'Alerts', 'icon' => 'bell'],
        ['href' => '/whm/backups', 'label' => 'Backups', 'icon' => 'archive'],
        ['href' => '/whm/dns', 'label' => 'DNS Zones', 'icon' => 'globe'],
        ['href' => '/whm/notifications', 'label' => 'Notifications', 'icon' => 'mail'],
    ]];
    $nav[] = ['label' => 'Configuration', 'items' => [
        ['href' => '/whm/server-config/mail', 'label' => 'Mail Settings', 'icon' => 'mail'],
        ['href' => '/whm/server-config/sftp', 'label' => 'SFTP Settings', 'icon' => 'folder-up'],
        ['href' => '/whm/server-config/php', 'label' => 'PHP Settings', 'icon' => 'settings'],
        ['href' => '/whm/server-config/php-extensions', 'label' => 'PHP Extensions', 'icon' => 'layers'],
        ['href' => '/whm/server-config/php-versions', 'label' => 'PHP Versions', 'icon' => 'code'],
        ['href' => '/whm/server-config/database', 'label' => 'Database Settings', 'icon' => 'database'],
        ['href' => '/whm/server-config/tuning', 'label' => 'Server Tweaks', 'icon' => 'sliders'],
    ]];
}
$quickActions = [
    ['href' => '/whm/accounts/create', 'label' => 'Create account', 'icon' => 'user-plus'],
    ['href' => '/whm/packages/create', 'label' => 'Create package', 'icon' => 'plus'],
    ['href' => '/whm/migrations/create', 'label' => 'Start a cPanel migration', 'icon' => 'transfer'],
];
$roleLabel = Auth::isAdmin() ? 'Administrator' : 'Reseller';
$securityHref = '/whm/security';
include __DIR__ . '/../partials/shell.php';
