<?php
http_response_code(200);
require_once __DIR__ . '/gateway_users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = isset($_POST['ip']) && is_string($_POST['ip']) ? $_POST['ip'] : '';
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

    if ($ip !== '' && in_array($action, ['kill', 'ban', 'revive', 'unban'], true)) {
        zombie_update_gateway_user($ip, $action);
    }

    header('Location: /users.php');
    exit;
}

$now = time();
$users = zombie_load_gateway_users();
uasort($users, static fn(array $a, array $b): int => (int)($b['last_seen'] ?? 0) <=> (int)($a['last_seen'] ?? 0));

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function ago(int $timestamp, int $now): string
{
    $seconds = max(0, $now - $timestamp);
    if ($seconds < 60) {
        return $seconds . 's ago';
    }

    $minutes = intdiv($seconds, 60);
    if ($minutes < 60) {
        return $minutes . 'm ago';
    }

    return intdiv($minutes, 60) . 'h ago';
}

function user_state(array $user, int $now): string
{
    if (!empty($user['banned'])) {
        return 'BANNED';
    }

    if ((int)($user['killed_until'] ?? 0) > $now) {
        return 'KILLED';
    }

    if ($now - (int)($user['last_seen'] ?? 0) <= ZOMBIE_ACTIVE_SECONDS) {
        return 'ONLINE';
    }

    return 'OFFLINE';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <title>ZOMBIE Users</title>
    <style>
        :root {
            --bg: #010402;
            --green: #39ff88;
            --acid: #c8ff4d;
            --red: #ff3b5f;
            --text: #effff4;
            --muted: #8bb998;
            --line: rgba(57, 255, 136, 0.24);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 50% 0%, rgba(57, 255, 136, 0.18), transparent 28rem),
                linear-gradient(135deg, rgba(57, 255, 136, 0.04) 0 1px, transparent 1px 32px),
                var(--bg);
        }

        main {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 42px 0;
        }

        a {
            color: inherit;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
        }

        h1 {
            margin: 0;
            color: var(--green);
            font-size: clamp(2.4rem, 7vw, 5.4rem);
            line-height: 0.9;
            text-shadow: 0 0 34px rgba(57, 255, 136, 0.42);
        }

        .back {
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            text-decoration: none;
            color: var(--acid);
            background: rgba(4, 20, 10, 0.58);
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: rgba(4, 20, 10, 0.58);
            box-shadow: 0 24px 90px rgba(0, 0, 0, 0.34);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(57, 255, 136, 0.13);
        }

        th {
            color: var(--acid);
            font-size: 0.76rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .ip {
            color: #dfffe9;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
            font-weight: 800;
        }

        .muted {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .state {
            color: var(--green);
            font-weight: 900;
        }

        .state.bad {
            color: var(--red);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        button {
            cursor: pointer;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 12px;
            color: var(--text);
            background: rgba(0, 0, 0, 0.34);
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        button.kill {
            border-color: rgba(200, 255, 77, 0.42);
            color: var(--acid);
        }

        button.ban {
            border-color: rgba(255, 59, 95, 0.48);
            color: var(--red);
        }

        .empty {
            padding: 24px;
            color: var(--muted);
        }

        @media (max-width: 760px) {
            th:nth-child(3),
            td:nth-child(3),
            th:nth-child(4),
            td:nth-child(4) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="topbar">
            <h1>USERS</h1>
            <a class="back" href="/">Back</a>
        </div>

        <section class="panel">
            <?php if (count($users) === 0): ?>
                <div class="empty">No gateway users recorded yet.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th>Action</th>
                            <th>Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $ip => $user): ?>
                            <?php $state = user_state($user, $now); ?>
                            <tr>
                                <td>
                                    <div class="ip"><?= h((string)$ip) ?></div>
                                    <div class="muted"><?= h((string)($user['user_agent'] ?? 'unknown')) ?></div>
                                </td>
                                <td class="state <?= in_array($state, ['BANNED', 'KILLED'], true) ? 'bad' : '' ?>"><?= h($state) ?></td>
                                <td class="muted"><?= h(ago((int)($user['last_seen'] ?? 0), $now)) ?></td>
                                <td><?= h((string)($user['last_action'] ?? 'unknown')) ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="post">
                                            <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                            <input type="hidden" name="action" value="kill">
                                            <button class="kill" type="submit">Kill</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                            <input type="hidden" name="action" value="ban">
                                            <button class="ban" type="submit">Ban</button>
                                        </form>
                                        <?php if ($state === 'KILLED'): ?>
                                            <form method="post">
                                                <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                                <input type="hidden" name="action" value="revive">
                                                <button type="submit">Revive</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($state === 'BANNED'): ?>
                                            <form method="post">
                                                <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                                <input type="hidden" name="action" value="unban">
                                                <button type="submit">Unban</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
