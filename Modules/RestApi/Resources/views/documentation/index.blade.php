@php
    use Illuminate\Support\Str;

    $locale = $locale ?? app()->getLocale();
    $direction = $direction ?? (in_array($locale, ['ar', 'fa', 'ur']) ? 'rtl' : 'ltr');
    $isRtl = $direction === 'rtl';
    $fontFamily = $fontFamily ?? 'Inter';
    $logoUrl = $logoUrl ?? null;
    $moduleVersion = $moduleVersion ?? '1.0.0';
    $googleFontFamily = str_replace(' ', '+', $fontFamily);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('applicationintegration-docs::doc.api_docs') }} | REST API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $googleFontFamily }}:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-elevated: #ffffff;
            --surface-muted: #f1f5f9;
            --text: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --accent-muted: rgba(37, 99, 235, 0.08);
            --method-get: #059669;
            --method-post: #2563eb;
            --method-put: #d97706;
            --method-delete: #dc2626;
            --radius: 8px;
            --radius-lg: 12px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        body.dark {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-elevated: #334155;
            --surface-muted: #334155;
            --text: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border: #334155;
            --border-light: #475569;
            --accent-muted: rgba(59, 130, 246, 0.12);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: '{{ $fontFamily }}', 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            font-size: 15px;
        }

        a { color: inherit; text-decoration: none; }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: var(--accent);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: -0.02em;
        }

        .header-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }

        .version-badge {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            background: var(--surface-muted);
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .search-container {
            flex: 1;
            max-width: 360px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 8px 14px;
            padding-right: 56px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--text);
            font-size: 14px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-muted);
        }

        .search-kbd {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            padding: 3px 6px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-secondary);
            font-size: 16px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }

        .control-btn:hover {
            background: var(--surface-muted);
            border-color: var(--border);
        }

        /* Layout */
        .page {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 32px;
            max-width: 1400px;
            padding: 24px 24px 48px;
            margin: 0 auto;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 73px;
            align-self: start;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 16px 0;
            box-shadow: var(--shadow-sm);
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        .sidebar-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            padding: 0 16px 12px;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 12px;
        }

        .nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 0;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 13px;
            transition: background 0.15s, color 0.15s;
            border-{{ $isRtl ? 'right' : 'left' }}: 3px solid transparent;
        }

        .nav a:hover {
            background: var(--surface-muted);
            color: var(--text);
        }

        .nav a.active {
            background: var(--accent-muted);
            color: var(--accent);
            font-weight: 600;
            border-{{ $isRtl ? 'right' : 'left' }}-color: var(--accent);
        }

        .nav-icon {
            width: 4px;
            height: 4px;
            background: currentColor;
            border-radius: 50%;
            opacity: 0.5;
        }

        /* Content */
        .content {
            display: flex;
            flex-direction: column;
            gap: 24px;
            min-width: 0;
        }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .section-desc {
            color: var(--text-secondary);
            font-size: 14px;
            max-width: 640px;
            margin-top: 6px;
            line-height: 1.55;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            padding: 6px 12px;
            border-radius: var(--radius);
            background: var(--surface-muted);
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
            font-family: 'JetBrains Mono', monospace;
        }

        .section-badge:hover {
            background: var(--accent-muted);
            color: var(--accent);
            border-color: var(--accent);
        }

        /* Quick Info Grid */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .quick-item {
            padding: 14px;
            background: var(--surface-muted);
            border-radius: var(--radius);
            border: 1px solid var(--border-light);
        }

        .quick-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .quick-value {
            font-size: 14px;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text);
        }

        /* Endpoints */
        .endpoint {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            margin-top: 12px;
            background: var(--surface);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .endpoint:hover {
            border-color: var(--border);
            box-shadow: var(--shadow);
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .method {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
            letter-spacing: 0.03em;
            color: white;
            font-family: 'JetBrains Mono', monospace;
        }

        .method-GET { background: var(--method-get); }
        .method-POST { background: var(--method-post); }
        .method-PUT { background: var(--method-put); }
        .method-DELETE { background: var(--method-delete); }

        .endpoint-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--text-muted);
        }

        .auth-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            background: var(--accent-muted);
            color: var(--accent);
            border: 1px solid rgba(37, 99, 235, 0.2);
            font-weight: 500;
        }

        .endpoint-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text);
        }

        .endpoint-summary {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .copy-btn:hover {
            background: var(--accent-muted);
            color: var(--accent);
            border-color: var(--accent);
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 13px;
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .table th {
            text-align: {{ $isRtl ? 'right' : 'left' }};
            padding: 10px 14px;
            background: var(--surface-muted);
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-light);
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        /* Callouts */
        .callout {
            padding: 14px 16px;
            border-radius: var(--radius);
            background: var(--accent-muted);
            border: 1px solid rgba(37, 99, 235, 0.15);
            color: var(--text);
            margin: 12px 0;
            font-size: 13px;
        }

        .callout-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--accent);
            font-size: 13px;
        }

        .callout-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 4px;
        }

        .callout-item::before {
            content: '→';
            color: var(--accent);
            font-weight: 600;
        }

        /* Code Blocks */
        .code-shell {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-top: 12px;
            background: #1e293b;
        }

        .code-tabs {
            display: flex;
            gap: 2px;
            padding: 8px 12px;
            background: #0f172a;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .code-tab {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }

        .code-tab:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #e2e8f0;
        }

        .code-tab.active {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
        }

        .code-body {
            position: relative;
        }

        .code-copy {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }

        .code-copy:hover {
            background: rgba(37, 99, 235, 0.3);
            color: #fff;
        }

        pre {
            margin: 0;
            padding: 16px 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.55;
            overflow-x: auto;
            color: #e2e8f0;
        }

        /* TOC Links */
        .toc-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .toc-link {
            padding: 6px 12px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            transition: border-color 0.15s, color 0.15s, background 0.15s;
        }

        .toc-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-muted);
        }

        /* Public Banner */
        .public-banner {
            background: var(--accent-muted);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .public-banner p {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .page {
                grid-template-columns: 1fr;
                padding: 16px;
            }
            .sidebar {
                position: relative;
                top: 0;
                max-height: 280px;
            }
            .header {
                padding: 12px 16px;
                flex-wrap: wrap;
            }
            .search-container {
                order: 3;
                max-width: 100%;
                margin-top: 8px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--surface-muted); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
    </style>
</head>
<body class="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <header class="header">
        <div class="header-brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="logo-img" style="max-height: 36px; width: auto;  object-fit: contain;">
            @else
                <div class="logo-icon">API</div>
            @endif
            <div>
                <span class="header-title">{{ __('applicationintegration-docs::doc.api_docs') }}</span>
                <span class="version-badge">v{{ $moduleVersion }}</span>
            </div>
        </div>

        <div class="search-container">
            <input type="search" id="search-input" class="search-input" placeholder="{{ __('applicationintegration-docs::documentation.search_placeholder') ?? 'Search endpoints...' }}">
            <kbd class="search-kbd">⌘K</kbd>
        </div>

        <div class="header-controls">
            <button id="theme-toggle" class="control-btn" type="button" aria-label="Toggle theme">
                <span id="theme-icon">🌙</span>
            </button>
        </div>
    </header>

    <div class="page">
        <aside class="sidebar">
            <div class="sidebar-title">{{ __('applicationintegration-docs::doc.toc') }}</div>
            <ul class="nav">
                @foreach($toc as $link)
                    <li>
                        <a href="#{{ $link['id'] }}">
                            <span class="nav-icon"></span>
                            {{ $link['title'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <main class="content">
            @if($isPublic)
                <div class="card public-banner">
                    <p>{{ __('applicationintegration-docs::doc.public_banner') }}</p>
                </div>
            @endif

            <div class="card">
                <div class="toc-links">
                    @foreach($toc as $link)
                        <a href="#{{ $link['id'] }}" class="toc-link">{{ $link['title'] }}</a>
                    @endforeach
                </div>
            </div>

            @foreach($sections as $section)
                <section id="{{ $section['id'] }}" class="card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">{{ $section['title'] }}</h3>
                            <p class="section-desc">{{ $section['description'] }}</p>
                        </div>
                        <button type="button" class="section-badge copy-anchor" data-copy="{{ url()->current() }}#{{ $section['id'] }}">
                            #{{ $section['id'] }}
                        </button>
                    </div>

                    @if(!empty($section['quick'] ?? []))
                        <div class="quick-grid">
                            @foreach($section['quick'] as $item)
                                <div class="quick-item">
                                    <div class="quick-label">{{ $item['label'] }}</div>
                                    <div class="quick-value">{{ $item['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($section['endpoints'] ?? []))
                        @foreach($section['endpoints'] as $endpoint)
                            <div class="endpoint" data-searchable>
                                <div class="endpoint-header">
                                    <span class="method method-{{ $endpoint['method'] }}">{{ $endpoint['method'] }}</span>
                                    <span class="endpoint-path">{{ $endpoint['path'] }}</span>
                                    @if(!empty($endpoint['auth']))
                                        <span class="auth-badge">🔒 {{ __('applicationintegration-docs::doc.auth_required') }}</span>
                                    @endif
                                </div>

                                <h4 class="endpoint-title">{{ $endpoint['name'] }}</h4>
                                <p class="endpoint-summary">{{ $endpoint['summary'] }}</p>

                                <button type="button" class="copy-btn copy-endpoint" data-copy="{{ $baseUrl }}{{ $endpoint['path'] }}">
                                    📋 {{ __('applicationintegration-docs::doc.copy_link') }}
                                </button>

                                @if(!empty($endpoint['headers'] ?? []))
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('applicationintegration-docs::doc.header') }}</th>
                                                <th>{{ __('applicationintegration-docs::doc.value') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($endpoint['headers'] as $header)
                                                <tr>
                                                    <td>{{ $header['name'] }}</td>
                                                    <td>{{ $header['value'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif

                                @if(isset($endpoint['body']))
                                    <div class="callout">
                                        <div class="callout-title">📥 {{ __('applicationintegration-docs::doc.body_schema') }}</div>
                                    </div>
                                    <pre class="language-json"><code>{!! json_encode($endpoint['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</code></pre>
                                @endif

                                @if(!empty($endpoint['notes'] ?? []))
                                    <div class="callout">
                                        <div class="callout-title">💡 Notes</div>
                                        @foreach($endpoint['notes'] as $note)
                                            <div class="callout-item">{{ $note }}</div>
                                        @endforeach
                                    </div>
                                @endif

                                @include('applicationintegration::documentation.partials.code', [
                                    'method' => $endpoint['method'],
                                    'path' => $endpoint['path'],
                                    'body' => $endpoint['body'] ?? null,
                                    'response' => $endpoint['response'] ?? [],
                                    'baseUrl' => $baseUrl
                                ])
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($section['samples'] ?? []))
                        <div class="endpoint">
                            @include('applicationintegration::documentation.partials.code', [
                                'method' => $section['samples']['method'],
                                'path' => $section['samples']['path'],
                                'body' => $section['samples']['body'],
                                'response' => $section['samples']['response'],
                                'baseUrl' => $baseUrl
                            ])
                        </div>
                    @endif
                </section>
            @endforeach
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script>
        (function() {
            var themeToggle = document.getElementById('theme-toggle');
            var themeIcon = document.getElementById('theme-icon');
            var savedTheme = localStorage.getItem('docs-theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
                themeIcon.textContent = '☀️';
            }

            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark');
                var isDark = document.body.classList.contains('dark');
                themeIcon.textContent = isDark ? '☀️' : '🌙';
                localStorage.setItem('docs-theme', isDark ? 'dark' : 'light');
            });

            var searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', function(e) {
                var query = e.target.value.toLowerCase().trim();
                document.querySelectorAll('.endpoint').forEach(function(ep) {
                    ep.style.display = query === '' || ep.textContent.toLowerCase().indexOf(query) !== -1 ? 'block' : 'none';
                });
            });

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });

            document.querySelectorAll('.code-tabs').forEach(function(tabs) {
                tabs.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('code-tab')) return;
                    var lang = e.target.dataset.lang;
                    var shell = tabs.closest('.code-shell');
                    shell.querySelectorAll('.code-tab').forEach(function(btn) { btn.classList.remove('active'); });
                    e.target.classList.add('active');
                    shell.querySelectorAll('pre').forEach(function(pre) {
                        pre.style.display = pre.dataset.lang === lang ? 'block' : 'none';
                    });
                });
            });

            document.querySelectorAll('[data-copy]').forEach(function(el) {
                el.addEventListener('click', function() {
                    var val = el.getAttribute('data-copy');
                    if (val && navigator.clipboard) {
                        navigator.clipboard.writeText(val).then(function() {
                            var originalText = el.innerHTML;
                            el.innerHTML = '✓ Copied';
                            setTimeout(function() { el.innerHTML = originalText; }, 1500);
                        });
                    }
                });
            });

            document.querySelectorAll('.code-copy').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var codeBody = btn.closest('.code-body');
                    if (codeBody) {
                        var visiblePre = codeBody.querySelector('pre:not([style*="none"])') || codeBody.querySelector('pre');
                        if (visiblePre && navigator.clipboard) {
                            navigator.clipboard.writeText(visiblePre.textContent).then(function() {
                                var originalText = btn.textContent;
                                btn.textContent = '✓ Copied';
                                setTimeout(function() { btn.textContent = originalText; }, 1500);
                            });
                        }
                    }
                });
            });

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    var id = entry.target.getAttribute('id');
                    if (entry.isIntersecting) {
                        document.querySelectorAll('.nav a').forEach(function(link) { link.classList.remove('active'); });
                        var active = document.querySelector('.nav a[href="#' + id + '"]');
                        if (active) active.classList.add('active');
                    }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });

            document.querySelectorAll('section[id]').forEach(function(sec) { observer.observe(sec); });

            if (typeof Prism !== 'undefined') Prism.highlightAll();
        })();
    </script>
</body>
</html>
