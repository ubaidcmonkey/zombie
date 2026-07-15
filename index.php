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
            --bg: #020603;
            --panel: rgba(7, 18, 10, 0.78);
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
                radial-gradient(circle at 50% 8%, rgba(57, 255, 136, 0.18), transparent 31rem),
                radial-gradient(circle at 12% 80%, rgba(0, 184, 95, 0.12), transparent 24rem),
                linear-gradient(135deg, rgba(57, 255, 136, 0.045) 0 1px, transparent 1px 30px),
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
            opacity: 0.55;
        }

        body::after {
            content: "";
            position: fixed;
            width: 28rem;
            height: 28rem;
            left: 50%;
            top: 46%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            border: 1px solid rgba(57, 255, 136, 0.1);
            border-radius: 50%;
            box-shadow: 0 0 90px rgba(57, 255, 136, 0.18);
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
            gap: 22px;
        }

        .eyebrow {
            color: var(--acid);
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: clamp(4.7rem, 17vw, 13rem);
            line-height: 0.82;
            letter-spacing: 0;
            color: var(--green);
            text-shadow:
                0 0 12px rgba(57, 255, 136, 0.7),
                0 0 46px rgba(57, 255, 136, 0.42),
                0 0 120px rgba(0, 184, 95, 0.22);
            animation: glitch 4.6s infinite;
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

        @keyframes glitch {
            0%, 92%, 100% { transform: translateX(0); filter: none; }
            93% { transform: translateX(-2px); filter: hue-rotate(20deg); }
            94% { transform: translateX(3px); }
            95% { transform: translateX(-1px); }
            96% { transform: translateX(0); filter: none; }
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
            <h1>ZOMBIE</h1>
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
