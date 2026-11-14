<?php
declare(strict_types=1);

final class WhmDashboardController
{
    public static function index(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $user = Auth::user();
        $pdo = Database::app();

        if ($user['role'] === 'admin') {
            $resellerCount = (int) $pdo->query("SELECT COUNT(*) c FROM users WHERE role='reseller'")->fetch()['c'];
            $userCount = (int) $pdo->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch()['c'];
            $domainCount = (int) $pdo->query('SELECT COUNT(*) c FROM domains')->fetch()['c'];
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) c FROM users WHERE role='user' AND parent_id = ?");
            $stmt->execute([$user['id']]);
            $resellerCount = 0;
            $userCount = (int) $stmt->fetch()['c'];

            $stmt = $pdo->prepare('SELECT COUNT(*) c FROM domains d JOIN users u ON u.id = d.user_id WHERE u.parent_id = ?');
            $stmt->execute([$user['id']]);
            $domainCount = (int) $stmt->fetch()['c'];
        }

        $admin = $user['role'] === 'admin';
        $recent = $pdo->prepare(
            'SELECT a.created_at, a.action, a.detail, actor.username AS actor_name, target.username AS target_name
             FROM activity_log a LEFT JOIN users actor ON actor.id = a.actor_id LEFT JOIN users target ON target.id = a.target_user_id'
            . ($admin ? '' : ' WHERE a.actor_id = ? OR target.parent_id = ?')
            . ' ORDER BY a.id DESC LIMIT 8'
        );
        $recent->execute($admin ? [] : [$user['id'], $user['id']]);

        View::render('whm/dashboard', [
            'title' => 'Dashboard',
            'me' => $user,
            'resellerCount' => $resellerCount,
            'userCount' => $userCount,
            'domainCount' => $domainCount,
            'suspendedCount' => self::suspendedCount($user),
            'alloc' => $admin ? null : ResellerService::allocation((int) $user['id']),
            'stats' => $admin ? ServerStatus::stats() : null,
            'alerts' => $admin ? AlertService::collect() : [],
            'recent' => $recent->fetchAll(),
            'logServices' => $admin ? self::logServices() : [],
            'selectedLog' => (string) ($_GET['log'] ?? 'frankenphp'),
            'logContent' => $admin ? self::readLog((string) ($_GET['log'] ?? 'frankenphp')) : null,
        ], 'whm');
    }

    private static function suspendedCount(array $user): int
    {
        if ($user['role'] === 'admin') {
            return (int) Database::app()->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'suspended'")->fetchColumn();
        }
        $s = Database::app()->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'suspended' AND parent_id = ?");
        $s->execute([$user['id']]);
        return (int) $s->fetchColumn();
    }

    public static function pullLog(): void
    {
        Auth::requireRole(['admin']);
        Csrf::requireValid();
        $service = (string) ($_POST['service'] ?? 'frankenphp');
        if (!in_array($service, self::logServices(), true)) {
            $service = 'frankenphp';
        }
        SystemWorkerService::enqueue("pull-{$service}", ['type' => 'pull_log', 'service' => $service, 'lines' => 1000]);
        Flash::ok("Pulling the latest 1000 lines for $service - ready in a few seconds.");
        header('Location: /whm?log=' . urlencode($service));
        exit;
    }

    /** Services with a live log snapshot - written every 5s by hostpanel-worker.php. */
    private static function logServices(): array
    {
        $services = ['mariadb', 'frankenphp', 'stalwart', 'sftpgo', 'knot'];
        foreach (glob(SystemWorkerService::OUT_DIR . '/live-jinnpanel-php-fpm_*.log') ?: [] as $f) {
            $services[] = str_replace(['live-', '_'], ['', '@'], basename($f, '.log'));
        }
        return $services;
    }

    private static function readLog(string $service): string
    {
        $log = SystemWorkerService::output('live-' . str_replace('@', '_', $service) . '.log');
        if ($log === null) {
            return "No log data yet for \"$service\" - the background worker refreshes this every 5 seconds; it may not have run yet.";
        }
        return $log ?: '(empty)';
    }
}
