@props(['code', 'title', 'message'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    {{-- Deliberately no @vite/compiled assets here: this page must still
         render correctly even if the build itself is what's broken. --}}
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #faf8f4;
            color: #1f2417;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 26rem;
            background: #ffffff;
            border: 1px solid #e7e1d5;
            border-radius: 0.875rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }
        .code {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #24613b;
            margin: 0 0 0.75rem;
        }
        h1 {
            font-size: 1.375rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            color: #0b1f12;
        }
        p {
            margin: 0 0 1.5rem;
            color: #5b6355;
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .btn-primary { background: #24613b; color: #ffffff; }
        .btn-primary:hover { background: #1b4a2e; }
        .btn-secondary { background: #f3efe7; color: #1f2417; }
        .btn-secondary:hover { background: #e7e1d5; }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">Error {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
