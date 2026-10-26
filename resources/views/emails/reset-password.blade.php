<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password — HaloKIP</title>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background-color: #f3f8fb;
            color: #334155;
            margin: 0;
            padding: 40px 20px;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e3e9ed;
            padding: 32px;
            box-shadow: 0 10px 15px -3px rgba(6, 48, 81, 0.05);
        }

        .app-title {
            font-size: 18px;
            font-weight: 800;
            color: #063051;
            line-height: 1.2;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        .app-subtitle {
            font-size: 12px;
            font-weight: 600;
            color: #4987A0;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        .email-body {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        .button-container {
            text-align: center;
            margin: 28px 0;
        }
        .button {
            display: inline-block;
            background-color: #184D77;
            color: #ffffff !important;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: bold;
            text-decoration: none;
            font-size: 14px;
            box-shadow: 0 4px 6px -1px rgba(24, 77, 119, 0.15);
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        .footer {
            font-size: 11px;
            color: #737780;
            margin-top: 32px;
            text-align: center;
            border-top: 1px solid #e3e9ed;
            padding-top: 16px;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with App Identity -->
        <div class="header-container" style="margin-bottom: 28px; border-bottom: 1px solid #e3e9ed; padding-bottom: 20px;">
            <div class="app-title">HaloKIP</div>
            <div class="app-subtitle">Provinsi Jawa Timur</div>
        </div>

        <!-- Body Content -->
        <div class="email-body">
            <p>Halo, <strong>{{ $user->nama_user }}</strong></p>
            <p>Kami menerima permintaan untuk mereset password akun Anda di aplikasi HaloKIP Provinsi Jawa Timur.</p>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Reset Password Anda</a>
            </div>
            
            <p>Tautan reset password ini hanya berlaku selama 60 menit.</p>
            <p>Jika Anda tidak merasa mengajukan permintaan ini, silakan abaikan email ini secara aman.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            © {{ date('Y') }} HaloKIP Provinsi Jawa Timur. All rights reserved.
        </div>
    </div>
</body>
</html>
