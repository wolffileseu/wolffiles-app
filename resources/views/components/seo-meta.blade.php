{{-- SEO Meta Tags Component --}}
{{-- Usage: @include('components.seo-meta', ['seo' => $seo]) --}}
@php $seo = array_merge(\App\Services\SeoService::defaults(), $seo ?? []); @endphp

@if(isset($seo['description']))
    <meta name="description" content="{{ $seo['description'] }}">
@endif
@if(isset($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
@endif

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $seo['og:site_name'] ?? 'Wolffiles.eu' }}">
@if(isset($seo['og:title']))
    <meta property="og:title" content="{{ $seo['og:title'] }}">
@endif
@if(isset($seo['og:description']))
    <meta property="og:description" content="{{ $seo['og:description'] }}">
@endif
@if(isset($seo['og:image']))
    <meta property="og:image" content="{{ $seo['og:image'] }}">
@endif
@if(isset($seo['og:url']))
    <meta property="og:url" content="{{ $seo['og:url'] }}">
@endif
<meta property="og:type" content="{{ $seo['og:type'] ?? 'website' }}">
<meta property="og:locale" content="{{ $seo['og:locale'] ?? 'en_US' }}">

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $seo['twitter:card'] ?? 'summary' }}">
@if(isset($seo['og:title']))
    <meta name="twitter:title" content="{{ $seo['og:title'] }}">
@endif
@if(isset($seo['og:description']))
    <meta name="twitter:description" content="{{ $seo['og:description'] }}">
@endif
@if(isset($seo['og:image']))
    <meta name="twitter:image" content="{{ $seo['og:image'] }}">
@endif

{{-- Canonical URL --}}
@if(isset($seo['og:url']))
    <link rel="canonical" href="{{ $seo['og:url'] }}">
@endif
