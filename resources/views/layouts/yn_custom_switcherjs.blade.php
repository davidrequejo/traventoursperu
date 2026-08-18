<!-- Custom-Switcher JS -->
<script src="{{ asset('ynex_admin/js/custom-switcher.min.js') }}"></script>

<!-- Custom JS -->
<script src="{{ asset('ynex_admin/js/custom.js') }}"></script>

<script>
  // Foco en el buscador de Select2.
  $(document).on('select2:open', () => {
    document.querySelector('.select2-search__field')?.focus();
  });
</script>

@if(auth()->check() && auth()->user()->user_update_sistema === '0')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.Swal) {
        return;
      }

      Swal.fire({
        title: '¿Has actualizado?',
        icon: 'question',
        html: `Existe una nueva actualización en el sistema. Descubre las últimas mejoras y características disponibles.<br>
        Link: <a class="text-blue" href="https://chromewebstore.google.com/detail/clear-cache/cppjkneekbjaeellbfkmgnhonkkjfpdn?hl=es" target="_blank">Limpiar caché</a>
        <br><br>
        @if(file_exists(public_path('ynex_admin/video/clear_cache_tutorial.gif')))
          <img src="{{ asset('ynex_admin/video/clear_cache_tutorial.gif') }}" alt="Tutorial para limpiar cache" width="400px">
        @endif`,
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle"></i> Ok, ya actualicé',
        cancelButtonText: '<i class="fa fa-thumbs-down"></i> Recuérdame más tarde',
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await fetch('{{ route('user.update-system-confirmed') }}', {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              },
              body: JSON.stringify({}),
            });

            if (!response.ok) {
              throw new Error('No se pudo guardar la confirmación.');
            }

            if (typeof toastr_success === 'function') {
              toastr_success('Listo', 'Se guardó tu confirmación de actualización.', 700);
            }
          } catch (error) {
            if (typeof toastr_error === 'function') {
              toastr_error('Error', 'No se pudo guardar la confirmación. Intenta nuevamente.', 700);
            }
          }
        } else if (typeof toastr_info === 'function') {
          toastr_info('Recuerde', 'Para dejar de recibir este mensaje, haz clic en: Ok, ya actualicé.', 700);
        }
      });
    });
  </script>
@endif
