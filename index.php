<?php
http_response_code(200);
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
            --panel: rgba(6, 16, 9, 0.82);
            --green: #39ff88;
            --green-2: #00b85f;
            --acid: #c8ff4d;
            --text: #eefcf1;
            --muted: #8ab898;
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
            overflow-x: hidden;
            background:
                radial-gradient(circle at 50% 9%, rgba(57, 255, 136, 0.16), transparent 28rem),
                radial-gradient(circle at 14% 82%, rgba(0, 184, 95, 0.14), transparent 26rem),
                linear-gradient(135deg, rgba(57, 255, 136, 0.04) 0 1px, transparent 1px 34px),
                var(--bg);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(rgba(255,255,255,0.025) 50%, rgba(0,0,0,0.04) 50%);
            background-size: 100% 4px;
            animation: scan 8s linear infinite;
            opacity: 0.42;
        }

        body::after {
            content: "";
            position: fixed;
            width: 32rem;
            height: 32rem;
            left: 50%;
            top: 42%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            border: 1px solid rgba(57, 255, 136, 0.1);
            border-radius: 50%;
            box-shadow: 0 0 110px rgba(57, 255, 136, 0.18), inset 0 0 60px rgba(57, 255, 136, 0.045);
            animation: pulse 3.8s ease-in-out infinite;
        }

        main {
            width: min(1120px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            display: grid;
            align-content: center;
            gap: 28px;
            padding: 48px 0;
            position: relative;
            z-index: 1;
        }

        .hero {
            display: grid;
            gap: 18px;
        }

        .eyebrow {
            color: var(--acid);
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        .logo {
            position: relative;
            width: fit-content;
            margin: 0;
            font-size: clamp(4.7rem, 17vw, 13rem);
            line-height: 0.8;
            letter-spacing: 0;
            font-weight: 1000;
            color: var(--green);
            text-shadow:
                0 0 10px rgba(57, 255, 136, 0.76),
                0 0 34px rgba(57, 255, 136, 0.46),
                0 0 96px rgba(0, 184, 95, 0.25);
            isolation: isolate;
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
            color: #88ffd0;
            text-shadow: -3px 0 rgba(0, 255, 153, 0.85);
            clip-path: inset(0 0 58% 0);
            transform: translate(-3px, -1px);
            animation: glitch-top 2.15s steps(2, end) infinite;
        }

        .logo::after {
            color: #d6ff5f;
            text-shadow: 3px 0 rgba(199, 255, 75, 0.75);
            clip-path: inset(48% 0 0 0);
            transform: translate(3px, 1px);
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

        .subhead {
            max-width: 720px;
            margin: 0;
            color: var(--muted);
            font-size: clamp(1rem, 2vw, 1.25rem);
            line-height: 1.6;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 18px;
            align-items: stretch;
        }

        .panel {
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(57, 255, 136, 0.09), var(--panel));
            border-radius: 8px;
            box-shadow: 0 24px 90px rgba(0, 0, 0, 0.42);
            overflow: hidden;
        }

        .panel header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px;
            border-bottom: 1px solid var(--line);
            background: rgba(57, 255, 136, 0.055);
        }

        .panel h2 {
            margin: 0;
            font-size: 0.86rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--green);
        }

        .live {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 24px var(--green);
            animation: blink 1.4s ease-in-out infinite;
        }

        .panel-body {
            padding: 18px;
        }

        .endpoint {
            display: grid;
            gap: 10px;
        }

        .endpoint code {
            display: block;
            width: 100%;
            padding: 16px;
            border: 1px solid rgba(57, 255, 136, 0.2);
            border-radius: 6px;
            color: #d9ffe6;
            background: rgba(0, 0, 0, 0.35);
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
            overflow-wrap: anywhere;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .metric {
            min-height: 92px;
            padding: 15px;
            border: 1px solid rgba(57, 255, 136, 0.18);
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.22);
        }

        .metric span {
            display: block;
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .metric strong {
            display: block;
            margin-top: 10px;
            color: var(--acid);
            font-size: 1.55rem;
        }

        .status-list {
            display: grid;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .status-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid rgba(57, 255, 136, 0.11);
        }

        .status-list li:last-child {
            border-bottom: 0;
        }

        .tag {
            color: var(--muted);
            font-size: 0.83rem;
        }

        .value {
            color: var(--green);
            font-weight: 800;
        }

        @keyframes scan {
            from { background-position: 0 0; }
            to { background-position: 0 80px; }
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.35; transform: translate(-50%, -50%) scale(0.96); }
            50% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.06); }
        }

        @keyframes blink {
            0%, 100% { opacity: 0.35; transform: scale(0.82); }
            50% { opacity: 1; transform: scale(1); }
        }

        @keyframes logo-flicker {
            0%, 9%, 11%, 19%, 21%, 100% { opacity: 1; filter: none; }
            10%, 20% { opacity: 0.82; filter: brightness(1.6); }
            67% { text-shadow: 0 0 8px rgba(57, 255, 136, 0.7), 0 0 64px rgba(57, 255, 136, 0.52); }
            68% { text-shadow: -5px 0 rgba(0, 255, 153, 0.65), 5px 0 rgba(200, 255, 77, 0.42), 0 0 36px rgba(57, 255, 136, 0.55); }
            69% { text-shadow: 0 0 10px rgba(57, 255, 136, 0.76), 0 0 34px rgba(57, 255, 136, 0.46), 0 0 96px rgba(0, 184, 95, 0.25); }
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
            0%, 74%, 100% { clip-path: inset(0 0 58% 0); transform: translate(-2px, -1px); opacity: 0.5; }
            75% { clip-path: inset(8% 0 70% 0); transform: translate(-10px, -2px); opacity: 0.95; }
            76% { clip-path: inset(18% 0 58% 0); transform: translate(7px, 1px); }
            77% { clip-path: inset(0 0 72% 0); transform: translate(-4px, 0); }
            78% { clip-path: inset(0 0 58% 0); transform: translate(-2px, -1px); opacity: 0.55; }
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

        @media (max-width: 780px) {
            main {
                width: min(100% - 22px, 1120px);
                padding: 34px 0;
            }

            .grid,
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="hero" aria-label="Zombie API">
            <div class="eyebrow">greenline gateway online</div>
            <h1 class="logo" data-text="ZOMBIE"><span>ZOMBIE</span></h1>
            <p class="subhead">A hardened Vanguard API control surface with a toxic green signal layer, live endpoint status, and a clean gaming-grade interface.</p>
        </section>

        <section class="grid">
            <article class="panel">
                <header>
                    <h2>API Endpoint</h2>
                    <span class="live" aria-hidden="true"></span>
                </header>
                <div class="panel-body endpoint">
                    <code>POST /gateway.php</code>
                    <div class="metrics" aria-label="Service metrics">
                        <div class="metric">
                            <span>status</span>
                            <strong>ONLINE</strong>
                        </div>
                        <div class="metric">
                            <span>runtime</span>
                            <strong>PHP</strong>
                        </div>
                        <div class="metric">
                            <span>deploy</span>
                            <strong>RENDER</strong>
                        </div>
                    </div>
                </div>
            </article>

            <aside class="panel">
                <header>
                    <h2>System</h2>
                    <span class="live" aria-hidden="true"></span>
                </header>
                <div class="panel-body">
                    <ul class="status-list">
                        <li><span class="tag">Gateway</span><span class="value">Armed</span></li>
                        <li><span class="tag">Health</span><span class="value">/health.php</span></li>
                        <li><span class="tag">Mode</span><span class="value">Zombie</span></li>
                    </ul>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
