<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Terjadi Kesalahan') — HaloKIP</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --biru1: #02569B;
            --biru2: #0175C2;
            --kuning: #F2C94C;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f3f8fb;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .error-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(2, 86, 155, 0.08), 0 0 1px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            padding: 48px 36px;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, var(--biru1) 0%, var(--biru2) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
            letter-spacing: -0.05em;
        }
        .error-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }
        .error-message {
            font-size: 0.92rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--biru1);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(2, 86, 155, 0.2);
        }
        .btn-home:hover {
            background-color: var(--biru2);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 86, 155, 0.3);
        }
        .brand-footer {
            margin-top: 28px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.02em;
        }
        .icon-badge {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: #eff6ff;
            color: var(--biru1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-badge">@yield('icon', '🛡️')</div>
        <div class="error-code">@yield('code', 'Error')</div>
        <h1 class="error-title">@yield('heading', 'Terjadi Kesalahan')</h1>
        <p class="error-message">@yield('message', 'Halaman yang Anda tuju tidak dapat diakses saat ini.')</p>
        <div>
            <a href="{{ url('/') }}" class="btn-home">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
        <div class="brand-footer">
            HaloKIP &bull; Sistem Monitoring & Evaluasi KIP
        </div>
    </div>
</body>
</html>
