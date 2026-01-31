<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Email Template</title>
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    
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
            font-family: 'Titillium Web', Arial, sans-serif;
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

    <!-- Outer Viewport Table -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #F2F4F6;">
        <tr>
            <td align="center" style="padding: 20px;" class="wrapper-padding">
                
                <!-- Boxed Layout Container (Max 640px) -->
                <!--[if mso]>
                <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="640" align="center">
                <tr>
                <td>
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; background-color: transparent;" role="presentation">
                    
                    <!-- Header -->
                    @include('emails.partials.header')

                    <!-- Main Content Section -->
                    <tr>
                        <td align="center" class="gradient-bg" style="padding: 40px 20px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="content-table" role="presentation">
                                
                                <!-- Icon -->
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <!-- Icon Placeholder -->
                                        <img src="https://cdn-icons-png.flaticon.com/512/10629/10629607.png" width="80" height="80" alt="Icon" style="display: block; border: 0;">
                                    </td>
                                </tr>

                                <!-- Title -->
                                <tr>
                                    <td align="center" style="padding-bottom: 40px;">
                                        <h1 style="margin: 0; color: #454545; font-family: 'Titillium Web', sans-serif; font-weight: 700; font-size: 40px; line-height: 48px; text-align: center;">
                                            @yield('title', 'Email Title')
                                        </h1>
                                    </td>
                                </tr>

                                <!-- Content Card -->
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 16px; width: 100%;" role="presentation">
                                            <tr>
                                                <td class="card-padding" style="padding: 34px; font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #454545; line-height: 1.5;">
                                                    @yield('content')

                                                    @if(isset($actionUrl) && isset($actionText))
                                                    <div style="margin-top: 40px; text-align: center;">
                                                        <!--[if mso]>
                                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $actionUrl }}" style="height:48px;v-text-anchor:middle;width:200px;" arcsize="50%" stroke="f" fillcolor="#00EEB0">
                                                        <w:anchorlock/>
                                                        <center>
                                                        <![endif]-->
                                                            <a href="{{ $actionUrl }}" class="btn-gradient" style="color:#454545; font-family: 'Titillium Web', sans-serif; font-size: 16px;">
                                                                {{ $actionText }} <span style="font-size: 18px; line-height: 1;">&#8853;</span>
                                                            </a>
                                                        <!--[if mso]>
                                                        </center>
                                                        </v:roundrect>
                                                        <![endif]-->
                                                    </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Footer -->
                            @include('emails.partials.footer')
 
                        </td>
                    </tr>

                </table>
                <!--[if mso]>
                </td>
                </tr>
                </table>
                <![endif]-->

            </td>
        </tr>
    </table>

</body>
</html>