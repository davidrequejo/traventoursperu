@include('layouts.seo_meta', ['title_page' => $title_page ?? 'Panel'])

<!-- Font Awesome 6.2 -->
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/fontawesome-free-6.2.0/css/all.min.css') }}">

<!-- Choices JS -->
<script src="{{ asset('ynex_admin/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

<!-- Main Theme JS -->
<script src="{{ asset('ynex_admin/js/main.js') }}"></script>

<!-- Bootstrap CSS -->
<link id="style" href="{{ asset('ynex_admin/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Theme CSS -->
<link rel="stylesheet" href="{{ asset('ynex_admin/css/styles.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/css/icons.css') }}">

<!-- Plugins CSS -->
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/node-waves/waves.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/simplebar/simplebar.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/@simonwep/pickr/themes/nano.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/choices.js/public/assets/styles/choices.min.css') }}">
<link rel="stylesheet"  href="{{ asset('ynex_admin/libs/data-table/datatables.css') }}" >
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('ynex_admin/libs/toastr/toastr.min.css') }}">

<!-- Estilos propios -->
<link rel="stylesheet" href="{{ asset('assets/css/style_new.css') }}?v={{ filemtime(public_path('assets/css/style_new.css')) }}" >
