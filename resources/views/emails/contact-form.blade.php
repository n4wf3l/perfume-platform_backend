<!DOCTYPE html>
<html>
<head>
    <title>Formulaire de Contact</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #000000;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #D4AF37;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #000000;
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .message-box {
            background-color: #f9f9f9;
            border-left: 4px solid #D4AF37;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #000000;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #D4AF37;
        }
        strong {
            color: #D4AF37;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nouveau Message de Contact</h1>
    </div>

    <p>Vous avez reçu un nouveau message du formulaire de contact:</p>

    <div class="message-box">
        <p><strong>Nom:</strong> {{ $data['name'] }}</p>
        <p><strong>Email:</strong> {{ $data['email'] }}</p>
        <p><strong>Sujet:</strong> {{ $data['subject'] }}</p>
        <p><strong>Message:</strong></p>
        <p>{{ $data['message'] }}</p>
    </div>

    <p>Ce message a été envoyé via le formulaire de contact de la plateforme de parfums Sogno D'Oro.</p>

    <div class="footer">
        <p>© {{ date('Y') }} Sogno D'Oro - Tous droits réservés.</p>
    </div>
</body>
</html>
