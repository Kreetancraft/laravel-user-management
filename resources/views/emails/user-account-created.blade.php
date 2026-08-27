<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Welcome to :app', ['app' => config('app.name')]) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            color: #51545e;
            margin: 0;
            padding: 0;
            width: 100% !important;
            -webkit-text-size-adjust: none;
        }
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }
        .email-content {
            width: 100%;
            max-width: 570px;
            margin: 0 auto;
            padding: 24px;
        }
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }
        .email-masthead_name {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .email-body {
            width: 100%;
            background-color: #ffffff;
            border: 1px solid #e8e5ef;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .email-body_inner {
            padding: 45px;
        }
        h1 {
            margin-top: 0;
            color: #1e293b;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
        }
        p {
            margin-top: 0;
            color: #475569;
            font-size: 16px;
            line-height: 1.625;
            text-align: left;
        }
        .panel {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin: 24px 0;
            padding: 20px;
        }
        .panel p {
            margin: 0;
            font-size: 15px;
        }
        .panel .label {
            font-weight: bold;
            color: #64748b;
            margin-bottom: 4px;
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .panel .value {
            font-size: 16px;
            color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            margin: 24px 0;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
            -webkit-print-color-adjust: exact;
        }
        .email-footer {
            padding: 25px 0;
            text-align: center;
        }
        .email-footer p {
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Logo / Brand Header -->
                    <tr>
                        <td class="email-masthead">
                            <a href="{{ $loginUrl }}" class="email-masthead_name">
                                {{ config('app.name') }}
                            </a>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td>
                                        <h1>{{ __('Hello, :name!', ['name' => $user->name]) }}</h1>
                                        <p>{{ __('An administrator has created an account for you on :app.', ['app' => config('app.name')]) }}</p>
                                        <p>{{ __('Please use the "Forgot Password" link on the login page to set your initial password.') }}</p>

                                        <p style="text-align: center; margin: 30px 0;">
                                            <a href="{{ $loginUrl }}" class="button" target="_blank">{{ __('Sign In Now') }}</a>
                                        </p>

                                        <p>{{ __('Important: Once you have logged in, we highly recommend that you update your password. You can do this by navigating to Settings > Security.') }}</p>
                                        
                                        <p>{{ __('Regards,') }}<br>{{ config('app.name') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
