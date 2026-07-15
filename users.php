<?php
declare(strict_types=1);

session_start();
http_response_code(200);
require_once __DIR__ . '/gateway_users.php';

const ZOMBIE_PANEL_PASSWORD = 'ub12ub34';

$loginError = false;

if (isset($_GET['logout'])) {
    unset($_SESSION['zombie_users_auth']);
    header('Location: /users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['panel_action'] ?? '') === 'login') {
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if (hash_equals(ZOMBIE_PANEL_PASSWORD, $password)) {
        $_SESSION['zombie_users_auth'] = true;
        header('Location: /users.php');
        exit;
    }

    $loginError = true;
}

$authenticated = !empty($_SESSION['zombie_users_auth']);

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['panel_action'] ?? '') === 'control') {
    $ip = isset($_POST['ip']) && is_string($_POST['ip']) ? $_POST['ip'] : '';
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

    if ($ip !== '' && in_array($action, ['kill', 'ban', 'revive', 'unban'], true)) {
        zombie_update_gateway_user($ip, $action);
    }

    header('Location: /users.php');
    exit;
}

$now = time();
$users = $authenticated ? zombie_load_gateway_users() : [];
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
    <?php if ($authenticated): ?>
        <meta http-equiv="refresh" content="15">
    <?php endif; ?>
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
                radial-gradient(circle at 50% 0%, rgba(57, 255, 136, 0.2), transparent 30rem),
                radial-gradient(circle at 100% 100%, rgba(200, 255, 77, 0.08), transparent 28rem),
                linear-gradient(135deg, rgba(57, 255, 136, 0.04) 0 1px, transparent 1px 32px),
                var(--bg);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(rgba(255,255,255,0.024) 50%, rgba(0,0,0,0.05) 50%);
            background-size: 100% 4px;
            animation: scan 8s linear infinite;
            opacity: 0.7;
        }

        main {
            position: relative;
            z-index: 1;
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
            animation: rise 520ms ease-out both;
        }

        h1 {
            margin: 0;
            color: var(--green);
            font-size: clamp(2.4rem, 7vw, 5.4rem);
            line-height: 0.9;
            text-shadow: 0 0 34px rgba(57, 255, 136, 0.42);
            animation: title-glitch 4.2s infinite;
        }

        .nav-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
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
            transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
        }

        .back:hover {
            transform: translateY(-2px);
            border-color: rgba(200, 255, 77, 0.56);
            box-shadow: 0 0 30px rgba(200, 255, 77, 0.14);
        }

        .panel {
            position: relative;
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: rgba(4, 20, 10, 0.62);
            box-shadow: 0 24px 90px rgba(0, 0, 0, 0.34);
            animation: rise 680ms ease-out 80ms both;
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(110deg, transparent 0 35%, rgba(57, 255, 136, 0.12), transparent 65% 100%);
            transform: translateX(-120%);
            animation: panel-sweep 4.8s ease-in-out infinite;
        }

        table {
            position: relative;
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

        tbody tr {
            animation: row-in 520ms ease-out both;
            transition: background 160ms ease, transform 160ms ease;
        }

        tbody tr:hover {
            background: rgba(57, 255, 136, 0.06);
            transform: translateX(4px);
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

        .state::before {
            content: "";
            display: inline-block;
            width: 9px;
            height: 9px;
            margin-right: 8px;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 16px currentColor;
            animation: pulse-dot 1.4s ease-in-out infinite;
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
            transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 24px rgba(57, 255, 136, 0.12);
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

        .login-wrap {
            min-height: calc(100vh - 84px);
            display: grid;
            place-items: center;
        }

        .login-card {
            width: min(440px, 100%);
            padding: 26px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(4, 20, 10, 0.68);
            box-shadow: 0 24px 90px rgba(0, 0, 0, 0.34), 0 0 70px rgba(57, 255, 136, 0.08);
            animation: rise 620ms ease-out both;
        }

        .login-card h1 {
            margin-bottom: 14px;
            font-size: clamp(2.2rem, 11vw, 4.4rem);
        }

        .login-card p {
            margin: 0 0 18px;
            color: var(--muted);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--acid);
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        input[type="password"] {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--text);
            background: rgba(0, 0, 0, 0.34);
            font: inherit;
            outline: none;
        }

        input[type="password"]:focus {
            border-color: rgba(57, 255, 136, 0.62);
            box-shadow: 0 0 26px rgba(57, 255, 136, 0.12);
        }

        .login-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 16px;
        }

        .error {
            color: var(--red);
            font-weight: 800;
        }

        @keyframes scan {
            from { background-position: 0 0; }
            to { background-position: 0 80px; }
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes row-in {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 0.45; transform: scale(0.82); }
            50% { opacity: 1; transform: scale(1); }
        }

        @keyframes panel-sweep {
            0%, 42% { transform: translateX(-120%); }
            70%, 100% { transform: translateX(120%); }
        }

        @keyframes title-glitch {
            0%, 91%, 100% { transform: translateX(0); filter: none; }
            92% { transform: translateX(-2px); filter: brightness(1.5); }
            93% { transform: translateX(3px); }
            94% { transform: translateX(-1px); }
            95% { transform: translateX(0); filter: none; }
        }

        @media (max-width: 760px) {
            th:nth-child(3),
            td:nth-child(3),
            th:nth-child(4),
            td:nth-child(4) {
                display: none;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main>
        <?php if (!$authenticated): ?>
            <section class="login-wrap">
                <form class="login-card" method="post">
                    <input type="hidden" name="panel_action" value="login">
                    <h1>USERS</h1>
                    <p>Enter the panel password to control gateway users.</p>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" autofocus>
                    <div class="login-actions">
                        <button type="submit">Unlock</button>
                        <?php if ($loginError): ?>
                            <span class="error">Wrong password</span>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <div class="topbar">
                <h1>USERS</h1>
                <div class="nav-actions">
                    <a class="back" href="/">Back</a>
                    <a class="back" href="/users.php?logout=1">Logout</a>
                </div>
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
                                                <input type="hidden" name="panel_action" value="control">
                                                <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                                <input type="hidden" name="action" value="kill">
                                                <button class="kill" type="submit">Kill</button>
                                            </form>
                                            <form method="post">
                                                <input type="hidden" name="panel_action" value="control">
                                                <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                                <input type="hidden" name="action" value="ban">
                                                <button class="ban" type="submit">Ban</button>
                                            </form>
                                            <?php if ($state === 'KILLED'): ?>
                                                <form method="post">
                                                    <input type="hidden" name="panel_action" value="control">
                                                    <input type="hidden" name="ip" value="<?= h((string)$ip) ?>">
                                                    <input type="hidden" name="action" value="revive">
                                                    <button type="submit">Revive</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($state === 'BANNED'): ?>
                                                <form method="post">
                                                    <input type="hidden" name="panel_action" value="control">
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
        <?php endif; ?>
    </main>
</body>
</html>
