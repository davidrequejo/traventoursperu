@php
    $pageTitle = $title_page ?? $title ?? 'Panel';
    $brandName = 'Traventours Peru';
    $siteUrl = url('/');
    $description = 'Traventours Peru es una agencia de viajes y turismo especializada en experiencias, reservas y servicios turisticos en Peru.';
    $keywords = 'Traventours Peru, agencia de viajes, turismo en Peru, reservas turisticas, tours, viajes, experiencias turisticas';
    $logoUrl = asset('assets/images/brand-logos/logo-raices-home.png');
    $faviconUrl = asset('assets/images/brand-logos/logo-36x36.png');
    $appleIconUrl = asset('assets/images/app-download/icon-192x192.png');
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-url" content="{{ url('/') }}">
<title>{{ $pageTitle }} | {{ $brandName }}</title>

<link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $appleIconUrl }}" sizes="192x192">
<link rel="canonical" href="{{ $siteUrl }}">

<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="author" content="{{ $brandName }}">
<meta name="publisher" content="{{ $brandName }}">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="language" content="Spanish">
<meta name="geo.region" content="PE-SAM">
<meta name="geo.placename" content="Tarapoto, San Martin, Peru">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $brandName }} - Agencia de viajes y turismo">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $logoUrl }}">
<meta property="og:url" content="{{ $siteUrl }}">
<meta property="og:site_name" content="{{ $brandName }}">
<meta property="og:locale" content="es_PE">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $brandName }} - Agencia de viajes y turismo">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $logoUrl }}">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TravelAgency",
  "name": "{{ $brandName }}",
  "url": "{{ $siteUrl }}",
  "logo": "{{ $logoUrl }}",
  "image": "{{ $logoUrl }}",
  "description": "{{ $description }}",
  "areaServed": [
    {
      "@type": "City",
      "name": "Peru"
    },
    {
      "@type": "AdministrativeArea",
      "name": "Peru"
    }
  ],
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Peru",
    "addressRegion": "Peru",
    "addressCountry": "PE"
  },
  "knowsAbout": [
    "Agencia de viajes",
    "Turismo en Peru",
    "Reservas turisticas",
    "Tours"
  ],
  "serviceType": "Servicios turisticos"
}
</script>

<link rel="manifest" href="{{ asset('assets/images/app-download/manifest.json') }}?v={{ now()->format('ymd') }}">
<meta name="theme-color" content="#ff321f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
