{{--
    Print surface. No navigation, no chrome — docs/pages.md section 8.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Dokumen — Kebon Jeruk Multiguna' }}</title>

    <x-layouts.favicon />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print { display: none !important; }
            @page { margin: 16mm; }
        }
    </style>
</head>
<body class="bg-surface-soft print:bg-canvas">
    {{ $slot }}
</body>
</html>
