<x-guest-layout>
  <style>
    @keyframes sendingProgress {
      0% {
        transform: translateX(-100%);
      }

      55% {
        transform: translateX(-8%);
      }

      100% {
        transform: translateX(100%);
      }
    }

    .is-sending .sending-bar {
      display: block;
    }

    .is-sending .sending-bar span {
      animation: sendingProgress 1.1s ease-in-out infinite;
    }
  </style>

  <main class="relative min-h-screen overflow-hidden bg-[#111111] px-4 py-8 text-[#171717]">
    <div class="absolute inset-0">
      <div class="absolute inset-0 bg-[url('/ynex_admin/images/media/media-fondo-login.jpg')] bg-cover bg-center"></div>
      <iframe
        class="pointer-events-none absolute left-1/2 top-1/2 min-h-full min-w-full -translate-x-1/2 -translate-y-1/2 border-0 sm:h-[56.25vw] sm:w-[177.78vh]"
        src="https://www.youtube.com/embed/BoB0QnUbCj0?autoplay=1&mute=1&controls=0&loop=1&playlist=BoB0QnUbCj0&playsinline=1&modestbranding=1&rel=0&showinfo=0"
        title="Video inmobiliario de fondo"
        allow="autoplay; encrypted-media; picture-in-picture"
        loading="lazy"></iframe>
      <div class="absolute inset-0 bg-black/70"></div>
      <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(17,17,17,0.18),rgba(17,17,17,0.08))]"></div>
      <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(249,56,34,0.08),rgba(0,0,0,0.12))]"></div>
    </div>

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md items-center justify-center">
      <section class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/10 sm:p-8">
        <div class="mb-8 text-center">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#3f664c]">
            Nueva contrasena
          </p>
          <h1 class="mt-2 text-2xl font-semibold text-[#111111]">
            Crea tu nueva contrasena
          </h1>
          <p class="mt-3 text-sm leading-6 text-slate-600">
            Ingresa tu correo y define una contrasena segura para recuperar el acceso.
          </p>
        </div>

        <x-validation-errors class="mb-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm" />

        <form id="reset-password-form" method="POST" action="{{ route('password.update') }}" class="space-y-5">
          @csrf

          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          <div>
            <label for="email" class="block text-sm font-semibold text-slate-700">Correo electronico</label>
            <input id="email" class="mt-2 block w-full rounded-md border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-[#3f664c] focus:ring-[#3f664c]" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="usuario@empresa.com">
          </div>

          <div>
            <div class="flex items-center justify-between gap-3">
              <label for="password" class="block text-sm font-semibold text-slate-700">Nueva contrasena</label>
              <button type="button" class="toggle-password inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2" data-target="password" aria-label="Mostrar nueva contrasena" aria-pressed="false">
                <i class="ri-eye-line text-xl leading-none" aria-hidden="true"></i>
              </button>
            </div>
            <input id="password" class="mt-2 block w-full rounded-md border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-[#3f664c] focus:ring-[#3f664c]" type="password" name="password" required autocomplete="new-password" placeholder="Ingresa tu nueva contrasena">
          </div>

          <div>
            <div class="flex items-center justify-between gap-3">
              <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirmar contrasena</label>
              <button type="button" class="toggle-password inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2" data-target="password_confirmation" aria-label="Mostrar confirmacion" aria-pressed="false">
                <i class="ri-eye-line text-xl leading-none" aria-hidden="true"></i>
              </button>
            </div>
            <input id="password_confirmation" class="mt-2 block w-full rounded-md border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-[#3f664c] focus:ring-[#3f664c]" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repite tu nueva contrasena">
          </div>

          <div class="sending-bar hidden h-1 overflow-hidden rounded-full bg-slate-100">
            <span class="block h-full w-2/3 rounded-full bg-[#3f664c]"></span>
          </div>

          <button id="reset-password-button" type="submit" class="inline-flex w-full min-h-[44px] items-center justify-center gap-2 rounded-md bg-[#111111] px-5 py-3 text-sm font-semibold uppercase tracking-[0.12em] text-white shadow-lg shadow-black/15 transition hover:bg-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2 disabled:cursor-wait disabled:bg-[#3f664c] disabled:opacity-90">
            <span id="reset-password-spinner" class="hidden h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-white/35 border-t-white" aria-hidden="true"></span>
            <span id="reset-password-text">Actualizar contrasena</span>
          </button>

          <div class="text-center">
            <a class="text-sm font-semibold text-[#111111] transition hover:text-[#3f664c] focus:outline-none focus:ring-2 focus:ring-[#3f664c] focus:ring-offset-2" href="{{ route('login') }}">
              Volver al inicio de sesion
            </a>
          </div>
        </form>
      </section>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.toggle-password').forEach((button) => {
        const input = document.getElementById(button.dataset.target);
        const icon = button.querySelector('i');

        if (!input || !icon) {
          return;
        }

        button.addEventListener('click', () => {
          const showPassword = input.type === 'password';

          input.type = showPassword ? 'text' : 'password';
          button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
          icon.className = showPassword ? 'ri-eye-off-line text-xl leading-none' : 'ri-eye-line text-xl leading-none';
          input.focus();
        });
      });

      const form = document.getElementById('reset-password-form');
      const button = document.getElementById('reset-password-button');
      const buttonText = document.getElementById('reset-password-text');
      const spinner = document.getElementById('reset-password-spinner');

      if (!form || !button || !buttonText || !spinner) {
        return;
      }

      form.addEventListener('submit', () => {
        if (!form.checkValidity()) {
          return;
        }

        form.classList.add('is-sending');
        button.disabled = true;
        spinner.classList.remove('hidden');
        buttonText.textContent = 'Actualizando...';
      });
    });
  </script>
</x-guest-layout>
