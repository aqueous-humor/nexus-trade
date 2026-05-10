<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NexusTrade')</title>
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #0f172a; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .header { background: #0b0f1a; padding: 24px 32px; }
        .header h1 { color: #00d4aa; margin: 0; font-size: 22px; letter-spacing: -0.5px; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-top: 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .label { color: #64748b; }
        .value { font-weight: 600; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 700; }
        .badge-win { background: #dcfce7; color: #166534; }
        .badge-loss { background: #fee2e2; color: #991b1b; }
        .badge-draw { background: #fef9c3; color: #854d0e; }
        .footer { background: #f1f5f9; padding: 16px 32px; font-size: 12px; color: #94a3b8; text-align: center; }
        .btn { display: inline-block; background: #00d4aa; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 16px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header"><h1>NexusTrade</h1></div>
    <div class="body">@yield('content')</div>
    <div class="footer">© {{ date('Y') }} NexusTrade. This is an automated notification.</div>
</div>
</body>
</html>
