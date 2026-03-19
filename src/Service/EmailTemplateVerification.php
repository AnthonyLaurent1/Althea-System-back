<?php
namespace App\Service;

use App\Entity\User;

class EmailTemplateVerification
{
    public function confirmationEmail(User $user, string $url): string
    {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Confirme ton email</title>
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
                    background-color: #4CAF50;
                    text-decoration: none;
                    border-radius: 5px;
                }
                a.button:hover {
                    background-color: #45a049;
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
                <p>Merci de vous être inscrit sur notre plateforme.</p>
                <p>Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
                <a class='button' href='" . $url . "'>Confirmer mon email</a>
                <p class='footer'>Si vous n'avez pas créé de compte, ignorez cet email.</p>
            </div>
        </body>
        </html>
        ";
    }
}
