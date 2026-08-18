@php
    $pageTitle = $title_page ?? $title ?? 'Panel';
    $brandName = 'Raices Home';
    $siteUrl = 'https://admin.raiceshome.com.pe';
    $description = 'Raices Home es una inmobiliaria especializada en la venta de lotes urbanos en Tarapoto, departamento de San Martin. Encuentra terrenos para vivienda, inversion y crecimiento patrimonial.';
    $keywords = 'Raices Home, inmobiliaria en Tarapoto, lotes urbanos, venta de lotes, terrenos en Tarapoto, terrenos en San Martin, bienes raices, inversion inmobiliaria, lotes para vivienda, lotes en venta';
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
<meta property="og:title" content="{{ $brandName }} - Venta de lotes urbanos en Tarapoto">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $logoUrl }}">
<meta property="og:url" content="{{ $siteUrl }}">
<meta property="og:site_name" content="{{ $brandName }}">
<meta property="og:locale" content="es_PE">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $brandName }} - Inmobiliaria en Tarapoto">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $logoUrl }}">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "RealEstateAgent",
  "name": "{{ $brandName }}",
  "url": "{{ $siteUrl }}",
  "logo": "{{ $logoUrl }}",
  "image": "{{ $logoUrl }}",
  "description": "{{ $description }}",
  "areaServed": [
    {
      "@type": "City",
      "name": "Tarapoto"
    },
    {
      "@type": "AdministrativeArea",
      "name": "San Martin"
    }
  ],
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Tarapoto",
    "addressRegion": "San Martin",
    "addressCountry": "PE"
  },
  "knowsAbout": [
    "Venta de lotes urbanos",
    "Terrenos en Tarapoto",
    "Inversion inmobiliaria",
    "Bienes raices"
  ],
  "serviceType": "Venta de lotes urbanos"
}
</script>

<link rel="manifest" href="{{ asset('assets/images/app-download/manifest.json') }}?v={{ now()->format('ymd') }}">
<meta name="theme-color" content="#ff321f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
