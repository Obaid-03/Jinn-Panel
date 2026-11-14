<?php
declare(strict_types=1);

final class CpanelDashboardController
{
    public static function index(): void
    {
        Auth::requireRole(['user']);
        $me = Auth::user();
        $stmt = Database::app()->prepare('SELECT * FROM domains WHERE user_id = ? ORDER BY created_at DESC LIMIT 6');
        $stmt->execute([$me['id']]);
        $domains = $stmt->fetchAll();
        foreach ($domains as &$d) {
            $d['ssl'] = SslService::status((string) $d['domain_name'], (string) $d['ssl_mode'], (bool) $d['ssl_pinned']);
        }
        unset($d);

        View::render('cpanel/dashboard', [
            'title' => 'Dashboard',
            'me' => $me,
            'pkg' => Quota::package($me['id']),
            'usage' => Quota::usage($me['id']),
            'domains' => $domains,
        ], 'cpanel');
    }
}
