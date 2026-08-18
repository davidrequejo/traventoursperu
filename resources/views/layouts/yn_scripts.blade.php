<!-- Scroll To Top -->
<div class="scrollToTop">
  <span class="arrow"><i class="ri-arrow-up-s-fill fs-20"></i></span>
</div>
<div id="responsive-overlay"></div>
<!-- Scroll To Top -->

<!-- jQuery 3.6.0 -->
<script src="{{ asset('ynex_admin/libs/jquery/jquery.min.js') }}"></script>
<!-- Popper JS -->
<script src="{{ asset('ynex_admin/libs/@popperjs/core/umd/popper.min.js') }}"></script>
<!-- Bootstrap JS -->
<script src="{{ asset('ynex_admin/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- Defaultmenu JS -->
<script src="{{ asset('ynex_admin/js/defaultmenu.min.js') }}"></script>
<!-- Node Waves JS-->
<script src="{{ asset('ynex_admin/libs/node-waves/waves.min.js') }}"></script>
<!-- Sticky JS -->
<script src="{{ asset('ynex_admin/js/sticky.js') }}"></script>
<!-- Simplebar JS -->
<script src="{{ asset('ynex_admin/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('ynex_admin/js/simplebar.js') }}"></script>
<!-- Color Picker JS -->
<script src="{{ asset('ynex_admin/libs/@simonwep/pickr/pickr.es5.min.js') }}"></script>
<!-- Date & Time Picker JS -->
<script src="{{ asset('ynex_admin/libs/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('ynex_admin/libs/flatpickr/l10n/es.js') }}"></script>


<!-- :::::::::::::::::: P L U G I N   D I F E R E N T E S   A L   P R O Y E C T O :::::::::::::::::: -->
<!-- InputMask -->
<script src="{{ asset('ynex_admin/libs/inputmask/jquery.inputmask.min.js') }}"></script>

<!-- Moment -->
<script src="{{ asset('ynex_admin/libs/moment/moment.js') }}"></script>
<script src="{{ asset('ynex_admin/libs/moment/locale/es.js') }}"></script>

<!-- sweetalert2 -->
<script src="{{ asset('ynex_admin/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>

<!-- Toastr -->
<script src="{{ asset('ynex_admin/libs/toastr/toastr.min.js') }}"></script>

<!-- select2 -->
<script src="{{ asset('ynex_admin/libs/select2/js/select2.full.min.js') }}"></script>

<!-- jquery-validation -->
<script src="{{ asset('ynex_admin/libs/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('ynex_admin/libs/jquery-validation/additional-methods.min.js') }}"></script>
<script src="{{ asset('ynex_admin/libs/jquery-validation/localization/messages_es_PE.js') }}"></script>

<!-- JQuery ZOOM imagen -->
<script type="text/javascript" src="{{ asset('ynex_admin/libs/jquery-zoom/jquery.zoom.js') }}"></script>
<!-- Funciones Crud y Generales -->
<script type="text/javascript" src="{{ asset('assets/js/funcion_crud.js') }}?v={{ filemtime(public_path('assets/js/funcion_crud.js')) }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/funcion_general.js') }}?v={{ filemtime(public_path('assets/js/funcion_general.js')) }}"></script>
@include('layouts.permisos-js')
<script type="text/javascript" src="{{ asset('assets/js/permisos.js') }}?v={{ filemtime(public_path('assets/js/permisos.js')) }}"></script>

<!-- Data Table -->
<script src="{{ asset('ynex_admin/libs/data-table/datatables.js') }}"></script>
<script src="{{ asset('ynex_admin/libs/data-table/datetime.js') }}"></script>
