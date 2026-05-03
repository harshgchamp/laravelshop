<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #4f46e5; padding: 32px 40px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 32px 40px; color: #374151; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .detail-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .detail-box p { margin: 4px 0; font-size: 14px; }
        .detail-box strong { color: #111827; }
        .footer { padding: 20px 40px; background: #f9fafb; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Welcome to {{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <p>Hi <strong>{{ $user->name }}</strong>,</p>
            <p>
                An admin account has been created for you by
                <strong>{{ $createdBy->name }}</strong>.
                You can now log in to the admin panel using the details below.
            </p>

            <div class="detail-box">
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Panel URL:</strong> {{ config('app.url') }}/login</p>
            </div>

            <p>
                Please log in and change your password on first sign-in.
                If you did not expect this email, contact your administrator.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. This is an automated message.
        </div>
    </div>
</body>
</html>
