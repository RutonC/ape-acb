<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Painel · {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.png" type="image/png">
    @viteReactRefresh
    @vite('resources/admin/main.tsx')
</head>
<body>
    <noscript>O painel precisa de JavaScript activo.</noscript>
    <div id="admin"></div>
</body>
</html>
