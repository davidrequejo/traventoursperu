<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Demasiadas peticiones | Raices Home</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/brand-logos/logo-36x36.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#111111] text-[#171717]">
    <main class="relative min-h-screen overflow-hidden px-4 py-8">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-[url('/ynex_admin/images/media/media-fondo-login.jpg')] bg-cover bg-center"></div>
            <iframe
                class="pointer-events-none absolute left-1/2 top-1/2 min-h-full min-w-full -translate-x-1/2 -translate-y-1/2 border-0 sm:h-[56.25vw] sm:w-[177.78vh]"
                src="https://www.youtube.com/embed/BoB0QnUbCj0?autoplay=1&mute=1&controls=0&loop=1&playlist=BoB0QnUbCj0&playsinline=1&modestbranding=1&rel=0&showinfo=0"
                title="Video inmobiliario de fondo"
                allow="autoplay; encrypted-media; picture-in-picture"
                loading="lazy"></iframe>
            <div class="absolute inset-0 bg-black/70"></div>
            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(249,56,34,0.08),rgba(0,0,0,0.12))]"></div>
        </div>

        <section class="relative z-10 mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md items-center justify-center">
            <div class="w-full rounded-lg border border-slate-200 bg-white p-6 text-center shadow-xl shadow-black/20 sm:p-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#fff1ef] text-[#3f664c]">
                    <span class="text-xl font-bold">429</span>
                </div>

                <p class="mt-6 text-xs font-semibold uppercase tracking-[0.22em] text-[#3f664c]">
                    Seguridad
                </p>
                <h1 class="mt-2 text-2xl font-semibold text-[#111111]">
                    Demasiados intentos
                </h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Recibimos varias solicitudes en poco tiempo. Espera un momento y vuelve a intentarlo para proteger tu cuenta.
                </p>

                <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    Si estabas probando el acceso o el codigo 2FA, espera unos minutos antes de enviar otra vez.
                </div>

                <div class="mt-7 grid gap-3">
                    <button type="button" onclick="window.location.reload()" class="inline-flex w-full items-center justify-center rounded-md bg-[#111111] px-5 py-3 text-sm font-semibold uppercase tracking-[0.12em] text-white transition hover:bg-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2">
                        Intentar nuevamente
                    </button>
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-md border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-[#111111] transition hover:border-[#3f664c] hover:text-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2">
                        Volver al inicio de sesion
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
