<?php
declare(strict_types=1);

final class PackageController
{
    public static function index(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $user = Auth::user();
        $pdo = Database::app();

        if ($user['role'] === 'admin') {
            $packages = $pdo->query('SELECT p.*, u.username AS owner_username, (SELECT COUNT(*) FROM users a WHERE a.package_id = p.id) AS account_count FROM packages p LEFT JOIN users u ON u.id = p.owner_id ORDER BY p.owner_id IS NOT NULL, p.id')->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT p.*, u.username AS owner_username, (SELECT COUNT(*) FROM users a WHERE a.package_id = p.id) AS account_count FROM packages p LEFT JOIN users u ON u.id = p.owner_id WHERE p.owner_id IS NULL OR p.owner_id = ? ORDER BY p.owner_id IS NOT NULL, p.id');
            $stmt->execute([$user['id']]);
            $packages = $stmt->fetchAll();
        }

        View::render('whm/packages', ['title' => 'Packages', 'packages' => $packages], 'whm');
    }

    public static function create(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        View::render('whm/package_form', ['title' => 'Create Package', 'pkg' => null, 'accounts' => 0], 'whm');
    }

    public static function edit(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $pkg = self::ownedOrBack((int) $params['id']);
        $n = Database::app()->prepare('SELECT COUNT(*) FROM users WHERE package_id = ?');
        $n->execute([$pkg['id']]);
        View::render('whm/package_form', ['title' => 'Edit ' . $pkg['name'], 'pkg' => $pkg, 'accounts' => (int) $n->fetchColumn()], 'whm');
    }

    /** Changes a package's limits: they apply to every account on it (hard disk quotas at the next hourly run). */
    public static function update(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $user = Auth::user();
        $pkg = self::ownedOrBack((int) $params['id']);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Flash::error('Package name is required.');
            header("Location: /whm/packages/{$pkg['id']}/edit");
            exit;
        }
        $v = self::limits($user);
        Database::app()->prepare(
            'UPDATE packages SET name = ?, disk_quota_mb = ?, bandwidth_mb = ?, max_domains = ?, max_databases = ?, max_email_accounts = ?, max_ftp_accounts = ?, max_accounts = ? WHERE id = ?'
        )->execute([$name, ...$v, $pkg['id']]);
        // SFTP logins carry the disk quota; the account runtime picks up the rest.
        $users = Database::app()->prepare("SELECT id FROM users WHERE package_id = ? AND role = 'user'");
        $users->execute([$pkg['id']]);
        $quotaBytes = $v[0] * 1024 * 1024;
        foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            $logins = Database::app()->prepare('SELECT username FROM ftp_accounts WHERE user_id = ?');
            $logins->execute([$uid]);
            foreach ($logins->fetchAll(PDO::FETCH_COLUMN) as $login) {
                try {
                    SftpService::updateUser((string) $login, ['quota_size' => $quotaBytes]);
                } catch (Throwable $e) {
                    error_log("package update, sftp $login: " . $e->getMessage());
                }
            }
        }
        Flash::ok("Package \"$name\" saved. Its accounts have the new limits (hard disk quotas follow within the hour).");
        header('Location: /whm/packages');
        exit;
    }

    /** The limits from the form, in column order. @return list<int> */
    private static function limits(array $user): array
    {
        return [
            max(1, (int) ($_POST['disk_quota_mb'] ?? 1024)),
            max(1, (int) ($_POST['bandwidth_mb'] ?? 10240)),
            max(1, (int) ($_POST['max_domains'] ?? 1)),
            max(1, (int) ($_POST['max_databases'] ?? 1)),
            max(0, (int) ($_POST['max_email_accounts'] ?? 5)),
            max(0, (int) ($_POST['max_ftp_accounts'] ?? 1)),
            // Only an admin's packages can carry reseller account limits.
            $user['role'] === 'admin' ? max(0, (int) ($_POST['max_accounts'] ?? 0)) : 0,
        ];
    }

    /** A package the current user may change (admins: all; resellers: their own). */
    private static function ownedOrBack(int $id): array
    {
        $user = Auth::user();
        $s = Database::app()->prepare('SELECT * FROM packages WHERE id = ?');
        $s->execute([$id]);
        $pkg = $s->fetch();
        if (!$pkg || ($user['role'] !== 'admin' && (int) $pkg['owner_id'] !== (int) $user['id'])) {
            Flash::error($pkg ? 'Global packages can only be changed by the server administrator.' : 'Package not found.');
            header('Location: /whm/packages');
            exit;
        }
        return $pkg;
    }

    public static function store(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $user = Auth::user();

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Flash::error('Package name is required.');
            header('Location: /whm/packages/create');
            exit;
        }

        $stmt = Database::app()->prepare(
            'INSERT INTO packages (owner_id, name, disk_quota_mb, bandwidth_mb, max_domains, max_databases, max_email_accounts, max_ftp_accounts, max_accounts)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['role'] === 'admin' ? null : $user['id'], $name, ...self::limits($user)]);

        Flash::ok("Package \"$name\" created.");
        header('Location: /whm/packages');
        exit;
    }

    public static function destroy(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $user = Auth::user();
        $id = (int) $params['id'];

        $stmt = Database::app()->prepare('SELECT * FROM packages WHERE id = ?');
        $stmt->execute([$id]);
        $pkg = $stmt->fetch();

        if (!$pkg || ($user['role'] !== 'admin' && (int) $pkg['owner_id'] !== $user['id'])) {
            Flash::error('Package not found.');
            header('Location: /whm/packages');
            exit;
        }

        // users.package_id is ON DELETE SET NULL: deleting a package in use
        // would leave its accounts with no limits at all (Quota denies
        // everything, UsageService stops enforcing disk/bandwidth).
        $inUse = Database::app()->prepare('SELECT COUNT(*) FROM users WHERE package_id = ?');
        $inUse->execute([$id]);
        $count = (int) $inUse->fetchColumn();
        if ($count > 0) {
            Flash::error("Package \"{$pkg['name']}\" is used by $count account" . ($count === 1 ? '' : 's') . '; it can only be deleted once no account uses it.');
            header('Location: /whm/packages');
            exit;
        }

        $del = Database::app()->prepare('DELETE FROM packages WHERE id = ?');
        $del->execute([$id]);

        Flash::ok('Package deleted.');
        header('Location: /whm/packages');
        exit;
    }
}
