<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <meta name="x-apple-disable-message-reformatting">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="telephone=no" name="format-detection">
        <title>E-mails Billogg</title>

        <!-- Import Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
            body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; background-color: #F2F4F6; }
            
            /* Font Family Fix for Outlook */
            body, table, td, p, a, li, blockquote {
                font-family: 'gelion', 'dm sans', sans-serif !important;
            }
            
            /* Gradient Background Class */
            .gradient-bg {
                background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
                background-color: #C6FFEB; /* Fallback */
            }
            
            /* Button Style */
            .btn-gradient {
                background: linear-gradient(90deg, #57F287 0%, #00EEB0 50%, #00FFFF 100%);
                background-color: #00EEB0; /* Fallback */
                border-radius: 30px;
                padding: 12px 30px;
                color: #454545;
                text-decoration: none;
                font-weight: 700;
                display: inline-block;
                mso-padding-alt: 0;
                text-align: center;
            }

            .text-footer {
                font-weight: 400;
                font-size: 16px;
                line-height: 24px;
                letter-spacing: 0;
                color: #4F4F4F;
                margin-top: 8px;
                margin-bottom: 0;
            }

            .text-sm-footer {
                font-weight: 400;
                font-size: 14px;
                line-height: 20px;
                letter-spacing: 0;
                color: #A3A3A3;
                margin-top: 8px;
                margin-bottom: 0;
            }

            .title-text {
                font-family: 'Titillium Web', sans-serif;
                font-weight: 700;
                font-size: 40px;
                line-height: 48px;
                letter-spacing: 0;
                text-align: center;
                color: #454545;
                margin: 0;
            }
            /* Responsive */
            @media only screen and (max-width: 640px) {
                .content-table { width: 100% !important; padding: 20px !important; }
                .card-padding { padding: 20px !important; }
                h1 { font-size: 32px !important; line-height: 40px !important; }
                .wrapper-padding { padding: 0 !important; }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 0; background-color: #F2F4F6;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #F2F4F6;">
            <tr>
                <td align="center" class="wrapper-padding">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="width: 600px; background-color: transparent;" role="presentation">
                        @include('emails.layouts.header')
                        <tr>
                            <td align="center" class="gradient-bg" style="padding: 40px 20px;">
                                @include('emails.layouts.title')
                                @yield('content')
                                @include('emails.layouts.footer')
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
