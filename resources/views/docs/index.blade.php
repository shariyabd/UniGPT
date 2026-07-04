<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>UniGPT — Shared Documents</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: #f6f7fb; color: #1e2233;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }
        .wrap { max-width: 860px; margin: 0 auto; padding: 40px 20px 64px; }
        header { margin-bottom: 24px; }
        h1 { font-size: 1.5rem; margin: 0 0 4px; letter-spacing: -0.02em; }
        .sub { color: #6b7280; font-size: 0.9rem; }
        nav.crumbs { font-size: 0.85rem; margin: 16px 0 20px; color: #6b7280; }
        nav.crumbs a { color: #6d5efc; text-decoration: none; }
        nav.crumbs a:hover { text-decoration: underline; }
        .card { background: #fff; border: 1px solid #e6e8f0; border-radius: 14px; overflow: hidden; }
        .row {
            display: flex; align-items: center; gap: 14px; padding: 14px 18px;
            border-top: 1px solid #eef0f6; text-decoration: none; color: inherit;
        }
        .row:first-child { border-top: none; }
        .row:hover { background: #f9f9ff; }
        .icon { font-size: 1.2rem; width: 24px; text-align: center; flex: none; }
        .name { flex: 1; font-weight: 500; word-break: break-word; }
        .meta { color: #9096a5; font-size: 0.82rem; flex: none; }
        .dl { color: #6d5efc; font-size: 0.82rem; font-weight: 600; text-decoration: none; flex: none; }
        .dl:hover { text-decoration: underline; }
        .empty { padding: 28px 18px; color: #9096a5; text-align: center; }
        footer { margin-top: 28px; color: #9096a5; font-size: 0.8rem; text-align: center; }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <h1>Shared Documents</h1>
            <div class="sub">UniGPT — reports, architecture &amp; model, and the RAG question bank.</div>
        </header>

        <nav class="crumbs">
            @foreach ($breadcrumbs as $i => $crumb)
                @if ($i > 0) <span>/</span> @endif
                @if ($i === count($breadcrumbs) - 1)
                    <span>{{ $crumb['name'] }}</span>
                @else
                    <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="card">
            @forelse ($directories as $dir)
                <a class="row" href="{{ $dir['url'] }}">
                    <span class="icon">📁</span>
                    <span class="name">{{ $dir['name'] }}</span>
                    <span class="meta">{{ $dir['count'] }} item{{ $dir['count'] === 1 ? '' : 's' }}</span>
                </a>
            @empty
            @endforelse

            @foreach ($files as $file)
                <div class="row">
                    <span class="icon">📄</span>
                    <a class="name" href="{{ $file['url'] }}" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">
                        {{ $file['name'] }}
                    </a>
                    <span class="meta">{{ $file['size'] }}</span>
                    <a class="dl" href="{{ $file['downloadUrl'] }}">Download</a>
                </div>
            @endforeach

            @if (empty($directories) && empty($files))
                <div class="empty">This folder is empty.</div>
            @endif
        </div>

        <footer>© {{ date('Y') }} UniGPT</footer>
    </div>
</body>
</html>
