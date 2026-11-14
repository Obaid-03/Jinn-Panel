<?php
declare(strict_types=1);

/** WHM > Accounts. The rules (who may manage whom) live in AccountService. */
final class AccountController
{
    public static function index(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $user = Auth::user();
        $pdo = Database::app();
        $select = "SELECT u.*, p.name AS package_name, r.username AS reseller_username,
                          a.files_bytes + a.db_bytes + a.mail_bytes AS used_bytes, a.over_disk, a.over_bandwidth,
                          (SELECT COUNT(*) FROM domains d WHERE d.user_id = u.id) AS domain_count
                   FROM users u
                   LEFT JOIN packages p ON p.id = u.package_id
                   LEFT JOIN users r ON r.id = u.parent_id
                   LEFT JOIN account_usage a ON a.user_id = u.id";

        if ($user['role'] === 'admin') {
            $accounts = $pdo->query("$select ORDER BY FIELD(u.role, 'admin', 'reseller', 'user'), u.username")->fetchAll();
        } else {
            $stmt = $pdo->prepare("$select WHERE u.role = 'user' AND u.parent_id = ? ORDER BY u.username");
            $stmt->execute([$user['id']]);
            $accounts = $stmt->fetchAll();
        }

        View::render('whm/accounts', ['title' => 'Accounts', 'accounts' => $accounts, 'me' => $user], 'whm');
    }

    public static function show(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $me = Auth::user();
        $target = self::scopedOrBack((int) $params['id'], $me);
        $o = AccountService::overview($target);
        View::render('whm/account', [
            'title' => $target['username'],
            'a' => $target,
            'me' => $me,
            'o' => $o,
            'lastAdmin' => AccountService::isLastAdmin($target),
            'packages' => $target['role'] === 'admin' ? [] : AccountService::availablePackages($me),
            'pkgFeatures' => Features::forPackage($o['package']),
            'overrides' => Features::overrides($target),
        ], 'whm');
    }

    public static function create(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $me = Auth::user();
        View::render('whm/account_form', [
            'title' => 'Create Account',
            'account' => null,
            'packages' => AccountService::availablePackages($me),
            'roles' => AccountService::creatableRoles($me),
            'welcome' => NotificationService::settings()['welcome'],
        ], 'whm');
    }

    public static function store(): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $me = Auth::user();

        $result = AccountService::create($_POST, $me);
        if ($result['errors']) {
            Flash::error(implode(' ', $result['errors']));
            header('Location: /whm/accounts/create');
            exit;
        }
        $id = (int) $result['id'];
        Audit::target($id);
        $s = Database::app()->prepare('SELECT * FROM users WHERE id = ?');
        $s->execute([$id]);
        $row = $s->fetch();
        $note = '';
        if (!empty($_POST['send_welcome'])) {
            $note = NotificationService::welcome($row)
                ? " A welcome email went to {$row['email']}."
                : ' The welcome email could not be sent - check WHM > Notifications.';
        }
        Flash::ok("Account \"{$row['username']}\" created as {$row['role']}.$note");
        header("Location: /whm/accounts/$id");
        exit;
    }

    public static function edit(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        $me = Auth::user();
        $target = self::scopedOrBack((int) $params['id'], $me);
        View::render('whm/account_form', [
            'title' => 'Edit ' . $target['username'],
            'account' => $target,
            'packages' => AccountService::availablePackages($me),
            'roles' => [$target['role']],
            'welcome' => false,
        ], 'whm');
    }

    public static function update(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $me = Auth::user();
        $target = self::scopedOrBack((int) $params['id'], $me);
        Audit::target((int) $target['id']);
        $errors = AccountService::update($target, $_POST, $me);
        if ($errors) {
            Flash::error(implode(' ', $errors));
            header("Location: /whm/accounts/{$target['id']}/edit");
            exit;
        }
        Flash::ok("Account \"{$target['username']}\" saved." . ((string) ($_POST['password'] ?? '') !== '' ? ' Its new password is in effect and its other sessions were signed out.' : ''));
        header("Location: /whm/accounts/{$target['id']}");
        exit;
    }

    /** The account page's package switch. */
    public static function package(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $target = self::scopedOrBack((int) $params['id'], Auth::user());
        Audit::target((int) $target['id']);
        $problem = AccountService::changePackage($target, (int) ($_POST['package_id'] ?? 0), Auth::user());
        $problem === null
            ? Flash::ok("\"{$target['username']}\" is on the new package. Its limits and features apply now (hard disk quotas within the hour).")
            : Flash::error($problem);
        header("Location: /whm/accounts/{$target['id']}");
        exit;
    }

    /** The account page's feature switches (on top of its package). */
    public static function features(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $target = self::scopedOrBack((int) $params['id'], Auth::user());
        if ($target['role'] !== 'user') {
            self::back('Only hosting accounts have features.', true, (int) $target['id']);
        }
        Audit::target((int) $target['id']);
        AccountService::setFeatureOverrides($target, $_POST);
        Flash::ok("Features of \"{$target['username']}\" saved.");
        header("Location: /whm/accounts/{$target['id']}#features");
        exit;
    }

    public static function suspend(array $params): void
    {
        self::setStatus($params, 'suspended');
    }

    public static function unsuspend(array $params): void
    {
        self::setStatus($params, 'active');
    }

    public static function destroy(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $target = self::scopedOrBack((int) $params['id'], Auth::user());
        Audit::target((int) $target['id']);
        // Best-effort external cleanup (vhosts, DNS, databases + every MySQL
        // user the account owns, mailboxes, SFTP users), then the cascading
        // DB delete. The worker then removes its pools and Linux user and
        // moves its site folders aside (root-only).
        if (($problem = AccountService::delete($target)) !== null) {
            Flash::error($problem);
            header("Location: /whm/accounts/{$target['id']}");
            exit;
        }
        Flash::ok("Account \"{$target['username']}\" and its resources have been removed.");
        header('Location: /whm/accounts');
        exit;
    }

    /** Whether the account's PHP may run programs (exec, proc_open, ...): off by default. */
    public static function phpExec(array $params): void
    {
        Auth::requireRole(['admin']);
        Csrf::requireValid();
        $target = self::scopedOrBack((int) $params['id'], Auth::user());
        if ($target['role'] !== 'user') {
            self::back('Only hosting accounts run PHP.', true);
        }
        $on = !empty($_POST['allow']);
        Database::app()->prepare('UPDATE users SET php_exec = ? WHERE id = ?')->execute([$on ? 1 : 0, $target['id']]);
        AccountRuntime::sync((int) $target['id']);
        Audit::target((int) $target['id']);
        Flash::ok("\"{$target['username']}\": PHP " . ($on ? 'may now run programs (exec, proc_open, ...).' : 'may no longer run programs.'));
        header("Location: /whm/accounts/{$target['id']}");
        exit;
    }

    /** "Log in as": open a hosting account's own panel, then come back with the banner's button. */
    public static function loginAs(array $params): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $me = Auth::user();
        $target = self::scopedOrBack((int) $params['id'], $me);
        if ($target['role'] !== 'user' || $target['status'] !== 'active') {
            Flash::error('Only active hosting accounts can be opened.');
            header("Location: /whm/accounts/{$target['id']}");
            exit;
        }
        AccountService::impersonate($target, $me);
        header('Location: /cpanel');
        exit;
    }

    /** POST /impersonate/stop - back to the admin/reseller session. */
    public static function stopImpersonating(): void
    {
        Csrf::requireValid();
        $id = AccountService::stopImpersonating();
        header('Location: ' . ($id !== null ? "/whm/accounts/$id" : '/login'));
        exit;
    }

    private static function setStatus(array $params, string $status): void
    {
        Auth::requireRole(['admin', 'reseller']);
        Csrf::requireValid();
        $target = self::scopedOrBack((int) $params['id'], Auth::user());
        Audit::target((int) $target['id']);
        $problems = AccountService::setStatus($target, $status);
        if ($target['role'] !== 'user' && $problems) {
            self::back(ucfirst($problems[0]) . '.', true, (int) $target['id']);
        }
        $what = $status === 'suspended'
            ? ' Its sites, mail logins, SFTP, MySQL users and cron jobs are switched off.'
            : ' Its sites, mail logins, SFTP, MySQL users and cron jobs are back on.';
        Flash::ok("Account \"{$target['username']}\" is now $status." . ($target['role'] === 'user' ? $what : ''));
        if ($problems) {
            Flash::error('Not everything could be switched: ' . implode('; ', array_slice($problems, 0, 5)));
        }
        header('Location: ' . (($_POST['back'] ?? '') === 'list' ? '/whm/accounts' : "/whm/accounts/{$target['id']}"));
        exit;
    }

    /** @return array<string,mixed> */
    private static function scopedOrBack(int $id, array $me): array
    {
        $target = AccountService::findScoped($id, $me);
        if (!$target) {
            self::back('Account not found.', true);
        }
        return $target;
    }

    private static function back(string $message, bool $error = false, ?int $id = null): never
    {
        $error ? Flash::error($message) : Flash::ok($message);
        header('Location: ' . ($id ? "/whm/accounts/$id" : '/whm/accounts'));
        exit;
    }
}
