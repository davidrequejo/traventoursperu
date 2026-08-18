<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>No encontrado | Traventours Peru</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/brand-logos/logo-36x36.png') }}">
    <style>
        :root {
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
            background: linear-gradient(135deg, rgba(255, 50, 31, .08), transparent 34%), var(--soft);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .panel {
            width: min(100%, 540px);
            padding: 34px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--white);
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
            margin-bottom: 16px;
            padding: 7px 11px;
            border: 1px solid #fed7d2;
            border-radius: 999px;
            background: #fff5f3;
            color: #b42318;
            font-size: 13px;
            font-weight: 700;
        }

        h1 {
            margin: 0;
            font-size: clamp(28px, 5vw, 42px);
            line-height: 1.08;
        }

        p {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border-radius: 6px;
            padding: 0 18px;
            font-weight: 700;
            text-decoration: none;
        }

        .actions a.primary {
            background: var(--brand);
            color: var(--white);
        }

        .actions a.secondary {
            border: 1px solid var(--line);
            background: var(--white);
            color: var(--ink);
        }

        @media (max-width: 480px) {
            .actions {
                display: grid;
                width: 100%;
            }

            .actions a {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="panel" role="main">
        <div class="brand">
            <img src="{{ asset('assets/images/brand-logos/logo-raices-home.png') }}" alt="Traventours Peru">
            <span>Traventours Peru</span>
        </div>

        <div class="status">404 - No encontrado</div>

        <h1>La pagina que buscas no existe.</h1>
        <p>
            Puede que el enlace este roto, la direccion este incompleta o la pagina haya sido movida.
            Verifica la URL o vuelve al inicio.
        </p>

        <div class="actions">
            <a class="primary" href="{{ route('inicio') }}">Volver al inicio</a>
            <a class="secondary" href="javascript:history.back()">Regresar</a>
        </div>
    </main>
</body>
</html>
