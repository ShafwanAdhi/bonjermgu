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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

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
