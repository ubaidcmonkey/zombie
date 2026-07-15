<?php
http_response_code(200);
require_once __DIR__ . '/gateway_users.php';

$onlineUsers = zombie_active_gateway_users();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZOMBIE</title>
    <style>
        :root {
            --bg: #010402;
            --green: #39ff88;
            --acid: #c8ff4d;
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
            overflow: hidden;
            background:
                radial-gradient(circle at 50% 42%, rgba(57, 255, 136, 0.22), transparent 24rem),
                radial-gradient(circle at 50% 110%, rgba(0, 184, 95, 0.16), transparent 32rem),
                linear-gradient(135deg, rgba(57, 255, 136, 0.045) 0 1px, transparent 1px 32px),
                var(--bg);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(rgba(255, 255, 255, 0.025) 50%, rgba(0, 0, 0, 0.05) 50%),
                radial-gradient(circle at 50% 50%, transparent 0 34%, rgba(0, 0, 0, 0.46) 72%);
            background-size: 100% 4px, 100% 100%;
            animation: scan 8s linear infinite;
        }

        body::after {
            content: "";
            position: fixed;
            width: min(62vw, 46rem);
            aspect-ratio: 1;
            left: 50%;
            top: 48%;
            transform: translate(-50%, -50%);
            border: 1px solid rgba(57, 255, 136, 0.12);
            border-radius: 50%;
            box-shadow:
                0 0 90px rgba(57, 255, 136, 0.18),
                inset 0 0 80px rgba(57, 255, 136, 0.045);
            pointer-events: none;
            animation: ring 4s ease-in-out infinite;
        }

        main {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            display: grid;
            place-items: center;
            padding: 40px 0;
        }

        .hero {
            width: 100%;
            display: grid;
            justify-items: center;
            text-align: center;
            gap: 22px;
        }

        .logo {
            position: relative;
            width: fit-content;
            margin: 0;
            color: var(--green);
            font-size: clamp(4.8rem, 17vw, 13.5rem);
            font-weight: 1000;
            line-height: 0.78;
            letter-spacing: 0;
            isolation: isolate;
            text-shadow:
                0 0 10px rgba(57, 255, 136, 0.78),
                0 0 38px rgba(57, 255, 136, 0.5),
                0 0 110px rgba(0, 184, 95, 0.28);
            animation: logo-flicker 5.5s infinite;
        }

        .logo::before,
        .logo::after {
            content: attr(data-text);
            position: absolute;
            inset: 0;
            pointer-events: none;
            mix-blend-mode: screen;
        }

        .logo::before {
            color: #91ffd2;
            clip-path: inset(0 0 60% 0);
            transform: translate(-3px, -1px);
            text-shadow: -4px 0 rgba(0, 255, 153, 0.82);
            animation: glitch-top 2.15s steps(2, end) infinite;
        }

        .logo::after {
            color: #d6ff5f;
            clip-path: inset(48% 0 0 0);
            transform: translate(3px, 1px);
            text-shadow: 4px 0 rgba(200, 255, 77, 0.72);
            animation: glitch-bottom 2.6s steps(2, end) infinite;
        }

        .logo span {
            position: relative;
            display: inline-block;
            animation: logo-main 4.2s cubic-bezier(.2,.8,.2,1) infinite;
        }

        .logo span::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 52%;
            height: 0.08em;
            background: rgba(200, 255, 77, 0.72);
            box-shadow: 0 0 18px rgba(200, 255, 77, 0.75);
            transform: scaleX(0);
            transform-origin: left;
            animation: slash 4.2s infinite;
        }

        .subtitle {
            margin: 0;
            color: var(--acid);
            font-size: clamp(1.1rem, 2.2vw, 1.75rem);
            font-weight: 900;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            text-shadow: 0 0 22px rgba(200, 255, 77, 0.44);
        }

        .signal {
            width: min(580px, 100%);
            height: 2px;
            overflow: hidden;
            background: rgba(57, 255, 136, 0.18);
            box-shadow: 0 0 28px rgba(57, 255, 136, 0.32);
        }

        .signal::before {
            content: "";
            display: block;
            width: 38%;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--green), var(--acid), transparent);
            animation: sweep 2.4s ease-in-out infinite;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 4px;
        }

        .chip {
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: #dbffe7;
            background: rgba(4, 20, 10, 0.58);
            box-shadow: 0 0 26px rgba(57, 255, 136, 0.08);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-decoration: none;
            text-transform: uppercase;
        }

        .chip strong {
            color: var(--green);
        }

        .user-count {
            color: var(--acid);
            border-color: rgba(200, 255, 77, 0.38);
            box-shadow: 0 0 30px rgba(200, 255, 77, 0.12);
        }

        @keyframes scan {
            from { background-position: 0 0, 0 0; }
            to { background-position: 0 80px, 0 0; }
        }

        @keyframes ring {
            0%, 100% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
            50% { opacity: 0.78; transform: translate(-50%, -50%) scale(1.05); }
        }

        @keyframes sweep {
            0% { transform: translateX(-120%); }
            55%, 100% { transform: translateX(280%); }
        }

        @keyframes logo-flicker {
            0%, 9%, 11%, 19%, 21%, 100% { opacity: 1; filter: none; }
            10%, 20% { opacity: 0.82; filter: brightness(1.55); }
            67% { text-shadow: 0 0 8px rgba(57, 255, 136, 0.7), 0 0 64px rgba(57, 255, 136, 0.52); }
            68% { text-shadow: -5px 0 rgba(0, 255, 153, 0.65), 5px 0 rgba(200, 255, 77, 0.42), 0 0 36px rgba(57, 255, 136, 0.55); }
            69% { text-shadow: 0 0 10px rgba(57, 255, 136, 0.78), 0 0 38px rgba(57, 255, 136, 0.5), 0 0 110px rgba(0, 184, 95, 0.28); }
        }

        @keyframes logo-main {
            0%, 83%, 100% { transform: translate(0, 0) skewX(0); }
            84% { transform: translate(-2px, 0) skewX(4deg); }
            85% { transform: translate(3px, -1px) skewX(-6deg); }
            86% { transform: translate(0, 0) skewX(0); }
            94% { transform: translate(1px, 0); }
            95% { transform: translate(-1px, 0); }
        }

        @keyframes glitch-top {
            0%, 74%, 100% { clip-path: inset(0 0 60% 0); transform: translate(-2px, -1px); opacity: 0.5; }
            75% { clip-path: inset(8% 0 70% 0); transform: translate(-10px, -2px); opacity: 0.95; }
            76% { clip-path: inset(18% 0 58% 0); transform: translate(7px, 1px); }
            77% { clip-path: inset(0 0 72% 0); transform: translate(-4px, 0); }
            78% { clip-path: inset(0 0 60% 0); transform: translate(-2px, -1px); opacity: 0.55; }
        }

        @keyframes glitch-bottom {
            0%, 64%, 100% { clip-path: inset(48% 0 0 0); transform: translate(2px, 1px); opacity: 0.45; }
            65% { clip-path: inset(64% 0 12% 0); transform: translate(9px, 2px); opacity: 0.95; }
            66% { clip-path: inset(42% 0 34% 0); transform: translate(-8px, -1px); }
            67% { clip-path: inset(74% 0 0 0); transform: translate(5px, 0); }
            68% { clip-path: inset(48% 0 0 0); transform: translate(2px, 1px); opacity: 0.48; }
        }

        @keyframes slash {
            0%, 72%, 100% { transform: scaleX(0); opacity: 0; }
            73% { transform: scaleX(1); opacity: 1; }
            75% { transform: scaleX(0.12); opacity: 0.55; }
            76% { transform: scaleX(0); opacity: 0; }
        }

        @media (max-width: 720px) {
            body {
                overflow-y: auto;
            }

            .subtitle {
                letter-spacing: 0.18em;
            }

            .chips {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="hero" aria-label="Zombie API">
            <h1 class="logo" data-text="ZOMBIE"><span>ZOMBIE</span></h1>
            <p class="subtitle">EMULATION LAYER</p>
            <div class="signal" aria-hidden="true"></div>
            <div class="chips" aria-label="Service status">
                <span class="chip"><strong>Online</strong></span>
                <a class="chip user-count" href="/users.php">Users: <strong><?= (int)$onlineUsers ?></strong></a>
                <span class="chip">POST /gateway.php</span>
                <span class="chip">Render</span>
            </div>
        </section>
    </main>
</body>
</html>
