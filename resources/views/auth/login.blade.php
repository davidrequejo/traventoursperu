<x-guest-layout>
  <style>
    @keyframes loginFloatGlow {

      0%,
      100% {
        transform: translate3d(-8%, -6%, 0) scale(1);
        opacity: .55;
      }

      50% {
        transform: translate3d(10%, 8%, 0) scale(1.08);
        opacity: .82;
      }
    }

    @keyframes loginGridDrift {
      0% {
        background-position: 0 0;
      }

      100% {
        background-position: 72px 72px;
      }
    }

    @keyframes loginSheen {
      0% {
        transform: translateX(-120%) skewX(-18deg);
        opacity: 0;
      }

      18% {
        opacity: .28;
      }

      46%,
      100% {
        transform: translateX(220%) skewX(-18deg);
        opacity: 0;
      }
    }

    @keyframes loginAmbientDrift {

      0%,
      100% {
        transform: translate3d(-3%, -2%, 0) scale(1);
        opacity: .45;
      }

      50% {
        transform: translate3d(4%, 3%, 0) scale(1.08);
        opacity: .72;
      }
    }

    @keyframes loginSoftSweep {
      0% {
        transform: translate3d(-12%, 0, 0);
        opacity: .2;
      }

      50% {
        transform: translate3d(10%, -3%, 0);
        opacity: .42;
      }

      100% {
        transform: translate3d(-12%, 0, 0);
        opacity: .2;
      }
    }

    .login-float-glow {
      animation: loginFloatGlow 10s ease-in-out infinite;
    }

    .login-grid-drift {
      animation: loginGridDrift 18s linear infinite;
    }

    .login-panel-sheen {
      animation: loginSheen 8s ease-in-out infinite;
    }

    .login-ambient-drift {
      animation: loginAmbientDrift 13s ease-in-out infinite;
    }

    .login-soft-sweep {
      animation: loginSoftSweep 15s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {

      .login-float-glow,
      .login-grid-drift,
      .login-panel-sheen,
      .login-ambient-drift,
      .login-soft-sweep {
        animation: none;
      }
    }
  </style>

  <main class="min-h-screen bg-[#3f664c] text-[#171717]">
    <div class="relative isolate min-h-screen overflow-hidden">
      <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(249,56,34,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(17,17,17,0.12),transparent_34%),linear-gradient(135deg,#ffffff_0%,#f4f5f7_48%,#eceff3_100%)]"></div>
      <div class="absolute inset-x-0 bottom-0 -z-10 h-1/2 bg-[#111111]"></div>
      <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
        <div class="login-grid-drift absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,rgba(249,56,34,0.5)_1px,transparent_1px)] [background-size:42px_42px]"></div>
        <div class="login-ambient-drift absolute -left-24 top-0 h-[24rem] w-[24rem] rounded-full bg-[#3f664c]/18 blur-3xl sm:h-[34rem] sm:w-[34rem]"></div>
        <div class="login-ambient-drift absolute -right-28 bottom-4 h-[24rem] w-[24rem] rounded-full bg-[#3f664c]/16 blur-3xl [animation-delay:-5s] sm:h-[36rem] sm:w-[36rem]"></div>
        <div class="login-soft-sweep absolute left-[-12%] top-[42%] h-36 w-[124%] rounded-full bg-gradient-to-r from-transparent via-[#3f664c]/16 to-transparent blur-2xl"></div>
        <div class="login-soft-sweep absolute bottom-[9%] left-[-8%] h-32 w-[116%] rounded-full bg-gradient-to-r from-transparent via-white/12 to-transparent blur-2xl [animation-delay:-7s]"></div>
      </div>

      <section class="relative z-10 mx-auto grid min-h-screen w-full max-w-7xl items-center gap-5 px-4 py-5 sm:gap-7 sm:px-6 sm:py-8 md:px-8 lg:grid-cols-[1.08fr_0.92fr] lg:gap-8 lg:px-10">
        <div class="relative min-h-[240px] overflow-hidden rounded-lg bg-[#111111] shadow-2xl shadow-black/20 sm:min-h-[300px] md:min-h-[360px] lg:min-h-[620px]">
          <div class="absolute inset-0 bg-[url('/ynex_admin/images/media/media-fondo-login.jpg')] bg-cover bg-center"></div>
          <iframe
            class="pointer-events-none absolute left-1/2 top-1/2 min-h-full min-w-full -translate-x-1/2 -translate-y-1/2 border-0 sm:h-[56.25vw] sm:w-[177.78vh]"
            src="https://www.youtube.com/embed/BoB0QnUbCj0?autoplay=1&mute=1&controls=0&loop=1&playlist=BoB0QnUbCj0&playsinline=1&modestbranding=1&rel=0&showinfo=0"
            title="Video inmobiliario de fondo"
            allow="autoplay; encrypted-media; picture-in-picture"
            loading="lazy"></iframe>
          <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(17,17,17,0.94),rgba(17,17,17,0.58))]"></div>
          <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(249,56,34,0.18),rgba(0,0,0,0.58))]"></div>
          <div class="absolute right-0 top-0 h-32 w-32 bg-[#3f664c]/25 blur-3xl sm:h-48 sm:w-48"></div>
          <div class="login-panel-sheen pointer-events-none absolute inset-y-0 left-0 w-1/3 bg-white/20 blur-2xl"></div>

          <div class="relative flex h-full min-h-[240px] flex-col justify-between p-5 text-white sm:min-h-[300px] sm:p-7 md:min-h-[360px] lg:min-h-[620px] lg:p-10">
            <div>
              <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-2 text-xs font-medium text-white/90 backdrop-blur sm:px-4 sm:text-sm">
                Gestión Turismo Profesional
              </span>
            </div>

            <div class="max-w-2xl lg:max-w-xl">
              <p class="mb-3 text-xs font-semibold uppercase tracking-[0.22em] text-[#f93822] sm:mb-4 sm:text-sm">
                Traventours Perú
              </p>
              <h1 class="max-w-xl text-2xl font-semibold leading-tight sm:text-3xl md:text-4xl lg:text-5xl">
                Administra reservas de tours, paquetes,llegadas, salidas, clientes desde un solo lugar.
              </h1>
              <div class="mt-5 grid grid-cols-3 gap-2 sm:mt-7 sm:gap-3 lg:mt-8">
                <div class="rounded-md border border-white/15 bg-white/10 p-3 backdrop-blur sm:p-4">
                  <p class="text-lg font-semibold sm:text-2xl">360</p>
                  <p class="mt-1 text-sm text-white/75">Vista comercial</p>
                </div>
                <div class="rounded-md border border-white/15 bg-white/10 p-3 backdrop-blur sm:p-4">
                  <p class="text-lg font-semibold sm:text-2xl">24/7</p>
                  <p class="mt-1 text-sm text-white/75">Acceso seguro</p>
                </div>
                <div class="rounded-md border border-white/15 bg-white/10 p-3 backdrop-blur sm:p-4">
                  <p class="text-lg font-semibold sm:text-2xl">CRM</p>
                  <p class="mt-1 text-sm text-white/75">Seguimiento</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mx-auto w-full max-w-md">
          <div class="rounded-lg border border-white/80 bg-white/95 p-5 shadow-2xl shadow-black/12 backdrop-blur sm:p-7 md:p-8">
            <div class="mb-7 sm:mb-8">
              <a href="/" class="flex w-full items-center justify-center">
                <img src="{{ asset('assets/images/brand-logos/logo-100x35.png') }}" alt="{{ config('app.name', 'Traventours Peru') }}" class="h-auto w-[180px] sm:w-[210px]">
              </a>
              <p class="mt-7 text-xs font-semibold uppercase tracking-[0.22em] text-[#3f664c] sm:mt-8 sm:text-sm">
                Bienvenido
              </p>
              <h2 class="mt-2 text-2xl font-semibold text-[#111111] sm:text-3xl">
                Ingresa a tu cuenta
              </h2>
              <p class="mt-3 text-sm leading-6 text-slate-600">
                Accede al panel para gestionar propiedades, clientes y operaciones inmobiliarias.
              </p>
            </div>

            <x-validation-errors class="mb-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm" />

            @session('status')
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
              {{ $value }}
            </div>
            @endsession

            <form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
              @csrf

              <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Correo electronico</label>
                <input id="email" class="mt-2 block w-full rounded-md border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-[#3f664c] focus:ring-[#3f664c]" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="usuario@empresa.com">
              </div>

              <div>
                <div class="flex items-center justify-between gap-3">
                  <label for="password" class="block text-sm font-semibold text-slate-700">Contrasena</label>
                  <button id="toggle-password" type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2" aria-label="Mostrar contrasena" aria-pressed="false">
                    <i class="ri-eye-line text-xl leading-none" aria-hidden="true"></i>
                  </button>
                </div>
                <input id="password" class="mt-2 block w-full rounded-md border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-[#3f664c] focus:ring-[#3f664c]" type="password" name="password" required autocomplete="current-password" placeholder="Ingresa tu contrasena">
              </div>

              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <label for="remember_me" class="inline-flex items-center">
                  <input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-[#3f664c] shadow-sm focus:ring-[#3f664c]">
                  <span class="ms-2 text-sm text-slate-600">Mantener sesion iniciada</span>
                </label>

                @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-[#111111] transition hover:text-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2" href="{{ route('password.request') }}">
                  Olvide mi contrasena
                </a>
                @endif
              </div>

              <button type="submit" class="group relative inline-flex w-full items-center justify-center overflow-hidden rounded-md bg-[#111111] px-5 py-3 text-sm font-semibold uppercase tracking-[0.12em] text-white shadow-lg shadow-black/20 transition duration-300 hover:-translate-y-0.5 hover:bg-[#3f664c] hover:shadow-xl hover:shadow-[#3f664c]/30 focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-80 disabled:hover:translate-y-0 disabled:hover:bg-[#111111] disabled:hover:shadow-lg"
                id="login-submit-button">
                <span class="absolute inset-y-0 -left-1/3 w-1/3 bg-white/25 opacity-0 blur-md transition-all duration-500 group-hover:left-full group-hover:opacity-100"></span>
                <span class="relative flex items-center gap-2">
                  <svg id="login-loading-spinner" class="hidden h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                  </svg>
                  <span id="login-submit-text">Iniciar sesion</span>
                </span>
              </button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const passwordInput = document.getElementById('password');
      const toggleButton = document.getElementById('toggle-password');
      const toggleIcon = toggleButton?.querySelector('i');
      const loginForm = document.getElementById('login-form');
      const submitButton = document.getElementById('login-submit-button');
      const submitText = document.getElementById('login-submit-text');
      const loadingSpinner = document.getElementById('login-loading-spinner');

      if (passwordInput && toggleButton && toggleIcon) {
        toggleButton.addEventListener('click', () => {
          const showPassword = passwordInput.type === 'password';

          passwordInput.type = showPassword ? 'text' : 'password';
          toggleButton.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
          toggleButton.setAttribute('aria-label', showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena');
          toggleIcon.className = showPassword ? 'ri-eye-off-line text-xl leading-none' : 'ri-eye-line text-xl leading-none';
          passwordInput.focus();
        });
      }

      if (loginForm && submitButton && submitText && loadingSpinner) {
        loginForm.addEventListener('submit', () => {
          submitButton.disabled = true;
          submitButton.setAttribute('aria-busy', 'true');
          submitText.textContent = 'Procesando...';
          loadingSpinner.classList.remove('hidden');
        });
      }
    });
  </script>
</x-guest-layout>
