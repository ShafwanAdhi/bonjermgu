@php
    $faviconPath = 'images/brand/bonjemgu-logo.svg';
    $faviconVersion = file_exists(public_path($faviconPath))
        ? filemtime(public_path($faviconPath))
        : '1';
    $faviconUrl = asset($faviconPath).'?v='.$faviconVersion;
@endphp

<link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
<link rel="shortcut icon" type="image/svg+xml" href="{{ $faviconUrl }}">
<meta name="theme-color" content="#F7D85A">
