@auth
  <script>
    window.AppPermisos = @json(auth()->user()->permisosParaFrontend());
  </script>
@else
  <script>
    window.AppPermisos = {};
  </script>
@endauth
