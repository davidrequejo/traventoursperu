<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Servicio no disponible | Raices Home</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/brand-logos/logo-36x36.png') }}">
    <style>
        :root {
            color-scheme: light;
            --brand: #ff321f;
            --ink: #17202a;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f8fafc;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(255, 50, 31, .08), transparent 34%),
                linear-gradient(315deg, rgba(15, 118, 110, .08), transparent 38%),
                var(--soft);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .panel {
            width: min(100%, 560px);
            padding: 34px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .brand span {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            padding: 7px 11px;
            border: 1px solid #fed7d2;
            border-radius: 999px;
            background: #fff5f3;
            color: #b42318;
            font-size: 13px;
            font-weight: 700;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand);
        }

        h1 {
            margin: 0;
            font-size: clamp(28px, 5vw, 42px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        p {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        a,
        button {
            min-height: 44px;
            border-radius: 6px;
            border: 1px solid transparent;
            padding: 0 18px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        button {
            background: var(--brand);
            color: var(--white);
        }

        a {
            display: inline-flex;
            align-items: center;
            border-color: var(--line);
            background: var(--white);
            color: var(--ink);
        }

        .hint {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            color: #7c8797;
            font-size: 13px;
        }

        .fix {
            margin-top: 18px;
            padding: 14px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.6;
        }

        .code {
            display: inline-flex;
            align-items: center;
            margin-top: 12px;
            padding: 5px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            font-weight: 700;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .panel {
                padding: 26px;
            }

            .actions {
                display: grid;
            }

            a,
            button {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="panel" role="main">
        <div class="brand">
            <img src="{{ asset('assets/images/brand-logos/logo-raices-home.png') }}" alt="Raices Home">
            <span>Raices Home</span>
        </div>

        <div class="status">
            <span class="dot" aria-hidden="true"></span>
            Servicio temporalmente no disponible
        </div>

        <h1>{{ $databaseError['title'] ?? 'Estamos recuperando la conexion.' }}</h1>
        <p>
            {{ $databaseError['message'] ?? 'En este momento no podemos acceder a la base de datos. Tu informacion esta protegida; por favor intenta nuevamente en unos minutos.' }}
        </p>

        @isset($databaseError['suggestion'])
            <div class="fix">
                <strong>Que revisar:</strong> {{ $databaseError['suggestion'] }}
            </div>
        @endisset

        @isset($databaseError['code'])
            <span class="code">MYSQL {{ $databaseError['code'] }}</span>
        @endisset

        <div class="actions">
            <button type="button" onclick="window.location.reload()">Intentar nuevamente</button>
            <a href="{{ url('/') }}">Ir al inicio</a>
        </div>

        <p class="hint">
            Si eres administrador, corrige la configuracion y luego ejecuta php artisan config:clear si la configuracion estaba en cache.
        </p>
    </main>
</body>
</html>
