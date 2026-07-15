<?php
declare(strict_types=1);

const ZOMBIE_USER_DIR = __DIR__ . '/data';
const ZOMBIE_USER_FILE = ZOMBIE_USER_DIR . '/gateway-users.json';
const ZOMBIE_ACTIVE_SECONDS = 300;

function zombie_client_ip(): string
{
    $headers = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($headers as $header) {
        foreach (explode(',', $header) as $candidate) {
            $ip = trim($candidate);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return 'unknown';
}

function zombie_load_gateway_users(): array
{
    if (!is_file(ZOMBIE_USER_FILE)) {
        return [];
    }

    $json = file_get_contents(ZOMBIE_USER_FILE);
    if ($json === false || $json === '') {
        return [];
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function zombie_save_gateway_users(array $users): void
{
    if (!is_dir(ZOMBIE_USER_DIR)) {
        mkdir(ZOMBIE_USER_DIR, 0755, true);
    }

    $handle = fopen(ZOMBIE_USER_FILE, 'c+');
    if ($handle === false) {
        return;
    }

    flock($handle, LOCK_EX);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($users, JSON_PRETTY_PRINT));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function zombie_active_gateway_users(): int
{
    $now = time();
    $users = zombie_load_gateway_users();
    $active = array_filter(
        $users,
        static fn(array $user): bool => $now - (int)($user['last_seen'] ?? 0) <= ZOMBIE_ACTIVE_SECONDS
    );

    return count($active);
}

function zombie_track_gateway_user(): void
{
    $now = time();
    $ip = zombie_client_ip();
    $users = zombie_load_gateway_users();

    if (!isset($users[$ip]) || !is_array($users[$ip])) {
        $users[$ip] = [
            'first_seen' => $now,
            'hits' => 0,
        ];
    }

    $users[$ip]['last_seen'] = $now;
    $users[$ip]['hits'] = (int)($users[$ip]['hits'] ?? 0) + 1;
    $users[$ip]['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 180);

    zombie_save_gateway_users($users);
}
