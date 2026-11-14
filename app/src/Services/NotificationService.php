<?php
declare(strict_types=1);

/**
 * The emails the panel sends: welcome, password reset, alerts, a test.
 * Which ones are on, the From address and where alerts go are set in
 * WHM > Notifications (panel_settings notify_*).
 */
final class NotificationService
{
    /** @return array{from:string, from_name:string, admin_email:string, welcome:bool, alerts:bool} */
    public static function settings(): array
    {
        return [
            'from' => Settings::get('notify_from'),
            'from_name' => Settings::get('notify_from_name'),
            'admin_email' => Settings::get('notify_admin_email'),
            'welcome' => Settings::get('notify_welcome', '1') === '1',
            'alerts' => Settings::get('notify_alerts', '1') === '1',
        ];
    }

    /** @param array<string,mixed> $in */
    public static function saveSettings(array $in): void
    {
        $from = trim((string) ($in['from'] ?? ''));
        if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The From address isn\'t a valid email address.');
        }
        $admin = trim((string) ($in['admin_email'] ?? ''));
        foreach (array_filter(array_map('trim', explode(',', $admin))) as $a) {
            if (!filter_var($a, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("\"$a\" isn't a valid email address.");
            }
        }
        $name = trim(preg_replace('/[\r\n"<>]/', '', (string) ($in['from_name'] ?? '')) ?? '');
        Settings::set('notify_from', $from);
        Settings::set('notify_from_name', mb_substr($name, 0, 80));
        Settings::set('notify_admin_email', $admin);
        Settings::set('notify_welcome', !empty($in['welcome']) ? '1' : '0');
        Settings::set('notify_alerts', !empty($in['alerts']) ? '1' : '0');
    }

    /** Where alerts go: the configured addresses, else every active admin's email. @return list<string> */
    public static function adminRecipients(): array
    {
        $list = array_values(array_filter(array_map('trim', explode(',', Settings::get('notify_admin_email'))), fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)));
        if ($list) {
            return $list;
        }
        return array_values(array_filter(
            Database::app()->query("SELECT email FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN),
            fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)
        ));
    }

    /**
     * Welcome email for a new account: where to sign in, the username, and
     * a link to set their own password (the password is never mailed).
     *
     * @param array<string,mixed> $user
     */
    public static function welcome(array $user): bool
    {
        $link = PasswordResetService::url(PasswordResetService::create((int) $user['id'], 'welcome'));
        $login = 'https://' . Auth::panelHost() . '/login';
        $role = (string) $user['role'];
        $what = $role === 'user'
            ? 'Your hosting account is ready. From the control panel you can add domains, mailboxes, databases and SFTP logins, and manage files, DNS and backups.'
            : 'Your ' . ($role === 'reseller' ? 'reseller' : 'administrator') . ' login for the hosting server is ready.';
        $text = "Hello" . (!empty($user['full_name']) ? ' ' . $user['full_name'] : '') . ",\n\n"
            . "$what\n\n"
            . "Sign in at: $login\nUsername: {$user['username']}\n\n"
            . "Use the password you were given, or choose your own with this link (valid for 3 days, works once):\n$link\n\n"
            . "Once signed in, you can turn on two-factor sign-in under Login security.";
        return Mailer::send((string) $user['email'], 'Your ' . Config::APP_NAME . ' account', $text);
    }

    /** @param array<string,mixed> $user */
    public static function passwordReset(array $user, string $ip): bool
    {
        $link = PasswordResetService::url(PasswordResetService::create((int) $user['id'], 'reset'));
        $text = "Someone (hopefully you) asked to reset the password of the " . Config::APP_NAME . " account \"{$user['username']}\" from the address $ip.\n\n"
            . "Choose a new password with this link. It works once, for the next hour:\n$link\n\n"
            . "If it wasn't you, ignore this email - your password stays as it is.";
        return Mailer::send((string) $user['email'], 'Reset your ' . Config::APP_NAME . ' password', $text);
    }

    /**
     * @param list<array{level:string,title:string,detail:string,href:?string}> $alerts
     */
    public static function alerts(array $alerts): int
    {
        $lines = [];
        foreach ($alerts as $a) {
            $lines[] = '- [' . strtoupper($a['level']) . '] ' . $a['title'] . ($a['detail'] !== '' ? "\n  " . $a['detail'] : '');
        }
        $text = 'The panel on ' . Config::SERVER_HOSTNAME . ' found ' . count($alerts) . ' new problem' . (count($alerts) === 1 ? '' : 's') . ":\n\n"
            . implode("\n", $lines) . "\n\nAll current alerts: https://" . Auth::panelHost() . "/whm/alerts\n\n"
            . 'You get one email per new problem; turn these off in WHM > Notifications.';
        $sent = 0;
        foreach (self::adminRecipients() as $to) {
            $sent += Mailer::send($to, '[' . Config::SERVER_HOSTNAME . '] ' . (count($alerts) === 1 ? $alerts[0]['title'] : count($alerts) . ' new server alerts'), $text) ? 1 : 0;
        }
        return $sent;
    }

    public static function test(string $to): bool
    {
        return Mailer::send($to, 'Test email from ' . Config::APP_NAME, "This is a test from the hosting panel on " . Config::SERVER_HOSTNAME . ".\n\nIf you can read it, the panel can send welcome emails, password resets and alerts.\n\nSent from: " . Mailer::from());
    }
}
