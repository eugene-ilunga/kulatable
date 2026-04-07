<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Storage Guard Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f6f7fb; }
        .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 6px 20px rgba(0,0,0,0.05); max-width: 960px; margin: 0 auto; }
        h1 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 12px; letter-spacing: 0.04em; }
        .status-ok { color: #16a34a; font-weight: 700; }
        .status-issue { color: #dc2626; font-weight: 700; }
        .actions { margin-top: 16px; display: flex; gap: 12px; align-items: center; }
        .btn { padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-shield { 
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); 
            color: white; 
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .btn-shield svg { width: 20px; height: 20px; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .alert { padding: 12px 14px; border-radius: 8px; margin-top: 12px; }
        .alert-success { background: #ecfdf3; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-family: monospace; background: #eee; }
    </style>
</head>
<body>
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>{{ __('storageguard::messages.status_title') }}</h1>
        <div>
            <span class="badge">{{ __('storageguard::messages.permissions_badge') }}</span>
        </div>
    </div>

    @if(session('storageguard_message'))
        <div class="alert alert-success">{{ session('storageguard_message') }}</div>
    @endif

    @if($has_errors)
        <div class="alert alert-danger">{{ __('storageguard::messages.error_alert') }}</div>
    @endif

    <div class="actions">
        <form method="POST" action="{{ route('storageguard.fix') }}">
            @csrf
            <button type="submit" class="btn btn-shield">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.352-.272-2.636-.759-3.804a.75.75 0 00-.722-.515 11.209 11.209 0 01-7.877-3.08zM12 15.75h.007v.008H12v-.008z" clip-rule="evenodd" />
                    <path d="M12 7a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0112 7z" />
                </svg>
                {{ __('storageguard::messages.secure_repair') }}
            </button>
        </form>
        <a href="{{ route('storageguard.status') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            {{ __('storageguard::messages.refresh_status') }}
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('storageguard::messages.path') }}</th>
                <th>{{ __('storageguard::messages.status') }}</th>
                <th>{{ __('storageguard::messages.perms') }}</th>
                <th>{{ __('storageguard::messages.readable') }}</th>
                <th>{{ __('storageguard::messages.writable') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($statuses as $row)
            <tr>
                <td title="{{ $row['path'] }}">{{ Str::limit($row['path'], 60) }}</td>
                <td class="{{ $row['status'] === 'ok' ? 'status-ok' : 'status-issue' }}">{{ $row['status'] === 'ok' ? __('storageguard::messages.ok') : __('storageguard::messages.issue') }}</td>
                <td><span class="badge">{{ $row['perms'] }}</span></td>
                <td>{{ $row['readable'] ? __('storageguard::messages.yes') : __('storageguard::messages.no') }}</td>
                <td>{{ $row['writable'] ? __('storageguard::messages.yes') : __('storageguard::messages.no') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
