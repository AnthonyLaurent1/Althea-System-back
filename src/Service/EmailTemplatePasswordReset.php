<?php
namespace App\Service;

use App\Entity\User;

class EmailTemplatePasswordReset
{
    public function resetPasswordEmail(User $user, string $url): string
    {
        return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Réinitialisation de votre mot de passe</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f6f6f6;
                color: #333;
                padding: 20px;
            }
            .container {
                background-color: #fff;
                border-radius: 8px;
                padding: 20px;
                max-width: 600px;
                margin: 0 auto;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            a.button {
                display: inline-block;
                padding: 12px 20px;
                margin-top: 20px;
                font-size: 16px;
                color: #fff;
                background-color: #f44336;
                text-decoration: none;
                border-radius: 5px;
            }
            a.button:hover {
                background-color: #d32f2f;
            }
            .footer {
                margin-top: 20px;
                font-size: 12px;
                color: #888;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Bonjour " . htmlspecialchars($user->getFirstName()) . ",</h2>
            <p>Vous avez demandé à réinitialiser votre mot de passe sur notre plateforme.</p>
            <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
            <a class='button' href='" . $url . "'>Réinitialiser mon mot de passe</a>
            <p class='footer'>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
        </div>
    </body>
    </html>
    ";
    }
}
