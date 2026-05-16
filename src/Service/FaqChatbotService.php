<?php

namespace App\Service;

class FaqChatbotService
{
    public function getResponse(string $message, string $locale = 'fr'): array
    {
        $normalizedMessage = mb_strtolower(trim($message));
        $locale = $this->normalizeLocale($locale);

        $faq = $this->getFaqData();

        foreach ($faq as $item) {
            foreach ($item['keywords'] as $keyword) {
                if (str_contains($normalizedMessage, mb_strtolower($keyword))) {
                    $shouldSuggestContact = in_array($item['category'], ['support', 'delivery'], true);

                    return [
                        'answer' => $this->translate($item['answers'], $locale),
                        'matchedIntent' => $item['intent'],
                        'category' => $item['category'],
                        'canEscalate' => true,
                        'contactSuggested' => $shouldSuggestContact,
                        'redirectTo' => $shouldSuggestContact ? '/contact' : null,
                        'locale' => $locale,
                    ];
                }
            }

        }

        return [
            'answer' => $this->translate([
                'fr' => "Je n'ai pas trouve de reponse precise a votre demande. Vous pouvez utiliser le formulaire de contact pour envoyer votre message a notre equipe.",
                'en' => "I could not find an exact answer to your request. You can use the contact form to send your message to our support team.",
                'ru' => "Я не нашел точного ответа на ваш запрос. Вы можете воспользоваться формой обратной связи, чтобы отправить сообщение нашей службе поддержки."
            ], $locale),
            'matchedIntent' => 'fallback',
            'category' => 'fallback',
            'canEscalate' => true,
            'contactSuggested' => true,
            'redirectTo' => '/contact',
            'locale' => $locale,
        ];

    }

    private function normalizeLocale(string $locale): string
    {
        $locale = mb_strtolower(trim($locale));

        return in_array($locale, ['fr', 'en', 'ru'], true) ? $locale : 'fr';
    }

    private function translate(array $translations, string $locale): string
    {
        return $translations[$locale] ?? $translations['fr'] ?? '';
    }

    private function getFaqData(): array
    {
        return [
            [
                'intent' => 'change_address',
                'category' => 'account',
                'keywords' => [
                    'adresse',
                    'changer adresse',
                    'modifier adresse',
                    'address',
                    'change address',
                    'update address',
                    'адрес',
                    'изменить адрес'
                ],
                'answers' => [
                    'fr' => "Vous pouvez modifier votre adresse depuis votre espace client si cette fonctionnalite est disponible sur votre compte. Si vous rencontrez un probleme, utilisez le formulaire de contact pour demander de l'aide.",
                    'en' => "You can update your address from your customer account if this feature is available. If you have any issue, please use the contact form to request support.",
                    'ru' => "Вы можете изменить свой адрес в личном кабинете, если эта функция доступна. Если у вас возникла проблема, воспользуйтесь формой обратной связи."
                ],
            ],
            [
                'intent' => 'change_password',
                'category' => 'account',
                'keywords' => [
                    'mot de passe',
                    'changer mot de passe',
                    'modifier mot de passe',
                    'password',
                    'change password',
                    'reset password',
                    'пароль',
                    'сменить пароль'
                ],
                'answers' => [
                    'fr' => "Si vous avez oublie votre mot de passe, utilisez la fonctionnalite de reinitialisation disponible sur la page de connexion.",
                    'en' => "If you forgot your password, use the password reset feature available on the login page.",
                    'ru' => "Если вы забыли пароль, воспользуйтесь функцией сброса пароля на странице входа."
                ],
            ],
            [
                'intent' => 'create_account',
                'category' => 'account',
                'keywords' => [
                    'creer un compte',
                    'inscription',
                    'creation compte',
                    'register',
                    'sign up',
                    'create account',
                    'создать аккаунт',
                    'регистрация'
                ],
                'answers' => [
                    'fr' => "Vous pouvez creer un compte depuis la page d'inscription en renseignant votre email, votre mot de passe, votre nom et votre prenom.",
                    'en' => "You can create an account from the registration page by entering your email, password, first name and last name.",
                    'ru' => "Вы можете создать аккаунт на странице регистрации, указав email, пароль, имя и фамилию."
                ],
            ],
            [
                'intent' => 'email_verification',
                'category' => 'account',
                'keywords' => [
                    'verification email',
                    'verifier email',
                    'confirmer email',
                    'confirmation email',
                    'email verification',
                    'подтверждение email',
                    'подтвердить почту'
                ],
                'answers' => [
                    'fr' => "Apres inscription, un email de verification est envoye. Vous devez cliquer sur le lien recu pour confirmer votre adresse email.",
                    'en' => "After registration, a verification email is sent. You need to click the link received to confirm your email address.",
                    'ru' => "После регистрации отправляется письмо с подтверждением. Вам нужно перейти по ссылке из письма, чтобы подтвердить адрес электронной почты."
                ],
            ],
            [
                'intent' => 'login_problem',
                'category' => 'account',
                'keywords' => [
                    'connexion',
                    'login',
                    'impossible de se connecter',
                    'probleme connexion',
                    'sign in',
                    'cannot login',
                    'не могу войти',
                    'вход'
                ],
                'answers' => [
                    'fr' => "Verifiez votre email et votre mot de passe. Si le probleme persiste, essayez la reinitialisation du mot de passe ou contactez le support.",
                    'en' => "Please check your email and password. If the issue continues, try resetting your password or contact support.",
                    'ru' => "Проверьте ваш email и пароль. Если проблема сохраняется, попробуйте сбросить пароль или обратитесь в поддержку."
                ],
            ],
            [
                'intent' => 'add_to_cart',
                'category' => 'order',
                'keywords' => [
                    'ajouter au panier',
                    'panier',
                    'add to cart',
                    'cart',
                    'добавить в корзину',
                    'корзина'
                ],
                'answers' => [
                    'fr' => "Vous pouvez ajouter un produit au panier depuis sa fiche produit. Le panier est ensuite consultable depuis la page commande.",
                    'en' => "You can add a product to the cart from its product page. The cart can then be viewed from the order page.",
                    'ru' => "Вы можете добавить товар в корзину со страницы товара. Затем корзину можно просмотреть на странице заказа."
                ],
            ],
            [
                'intent' => 'modify_cart',
                'category' => 'order',
                'keywords' => [
                    'modifier quantite',
                    'changer quantite',
                    'modifier panier',
                    'update cart',
                    'change quantity',
                    'изменить количество',
                    'изменить корзину'
                ],
                'answers' => [
                    'fr' => "Si vous etes connecte, vous pouvez modifier les quantites des produits presents dans votre panier avant validation de la commande.",
                    'en' => "If you are logged in, you can update product quantities in your cart before completing the order.",
                    'ru' => "Если вы авторизованы, вы можете изменить количество товаров в корзине перед оформлением заказа."
                ],
            ],
            [
                'intent' => 'remove_from_cart',
                'category' => 'order',
                'keywords' => [
                    'supprimer panier',
                    'retirer produit',
                    'remove item',
                    'remove from cart',
                    'удалить из корзины',
                    'убрать товар'
                ],
                'answers' => [
                    'fr' => "Vous pouvez retirer un produit du panier avant le paiement depuis la page de commande.",
                    'en' => "You can remove a product from the cart before payment from the order page.",
                    'ru' => "Вы можете удалить товар из корзины до оплаты на странице заказа."
                ],
            ],
            [
                'intent' => 'checkout',
                'category' => 'order',
                'keywords' => [
                    'checkout',
                    'passer commande',
                    'valider commande',
                    'paiement commande',
                    'complete order',
                    'оформить заказ',
                    'оплатить заказ'
                ],
                'answers' => [
                    'fr' => "Une fois votre panier pret, vous pouvez lancer le paiement depuis le bouton de validation. Le systeme vous redirigera ensuite vers Stripe.",
                    'en' => "Once your cart is ready, you can start the payment process from the checkout button. The system will then redirect you to Stripe.",
                    'ru' => "Когда ваша корзина готова, вы можете перейти к оплате с помощью кнопки оформления заказа. Затем система перенаправит вас на Stripe."
                ],
            ],
            [
                'intent' => 'payment_methods',
                'category' => 'payment',
                'keywords' => [
                    'methode paiement',
                    'paiement',
                    'payment',
                    'carte bancaire',
                    'cb',
                    'stripe',
                    'способ оплаты',
                    'оплата'
                ],
                'answers' => [
                    'fr' => "Le paiement en ligne est gere via Stripe. Les moyens de paiement disponibles sont ceux proposes par Stripe au moment du checkout.",
                    'en' => "Online payment is handled through Stripe. The available payment methods are those offered by Stripe during checkout.",
                    'ru' => "Онлайн-оплата осуществляется через Stripe. Доступные способы оплаты зависят от вариантов, предложенных Stripe при оформлении заказа."
                ],
            ],
            [
                'intent' => 'payment_error',
                'category' => 'payment',
                'keywords' => [
                    'paiement refuse',
                    'erreur paiement',
                    'payment error',
                    'payment failed',
                    'probleme stripe',
                    'ошибка оплаты',
                    'платеж не прошел'
                ],
                'answers' => [
                    'fr' => "Si votre paiement echoue, verifiez vos informations bancaires puis reessayez. Si le probleme persiste, contactez le support.",
                    'en' => "If your payment fails, please check your payment details and try again. If the problem continues, contact support.",
                    'ru' => "Если оплата не прошла, проверьте платежные данные и попробуйте снова. Если проблема сохраняется, свяжитесь с поддержкой."
                ],
            ],
            [
                'intent' => 'invoice',
                'category' => 'invoice',
                'keywords' => [
                    'facture',
                    'invoice',
                    'pdf facture',
                    'telecharger facture',
                    'счет',
                    'инвойс',
                    'скачать счет'
                ],
                'answers' => [
                    'fr' => "La facture est disponible uniquement pour une commande payee. Elle peut etre recuperee apres validation du paiement.",
                    'en' => "The invoice is available only for a paid order. It can be retrieved once the payment has been confirmed.",
                    'ru' => "Счет доступен только для оплаченного заказа. Его можно получить после подтверждения оплаты."
                ],
            ],
            [
                'intent' => 'delivery',
                'category' => 'delivery',
                'keywords' => [
                    'livraison',
                    'expedition',
                    'delivery',
                    'shipping',
                    'доставка',
                    'отправка'
                ],
                'answers' => [
                    'fr' => "Pour toute question liee a la livraison ou a l'expedition, merci de contacter l'equipe support via le formulaire de contact.",
                    'en' => "For any question related to delivery or shipping, please contact the support team through the contact form.",
                    'ru' => "По всем вопросам, связанным с доставкой или отправкой, пожалуйста, свяжитесь со службой поддержки через форму обратной связи."
                ],
            ],
            [
                'intent' => 'product_info',
                'category' => 'product',
                'keywords' => [
                    'produit',
                    'information produit',
                    'details produit',
                    'product',
                    'product details',
                    'товар',
                    'информация о товаре'
                ],
                'answers' => [
                    'fr' => "Les informations principales des produits sont disponibles sur leurs fiches : titre, description, prix, stock, categorie et caracteristiques.",
                    'en' => "Main product information is available on each product page: title, description, price, stock, category and characteristics.",
                    'ru' => "Основная информация о товарах доступна на страницах товаров: название, описание, цена, наличие, категория и характеристики."
                ],
            ],
            [
                'intent' => 'stock_availability',
                'category' => 'product',
                'keywords' => [
                    'stock',
                    'disponible',
                    'availability',
                    'rupture',
                    'наличие',
                    'в наличии',
                    'нет в наличии'
                ],
                'answers' => [
                    'fr' => "La disponibilite d'un produit depend du stock actuel affiche sur le site. Certains produits peuvent devenir indisponibles au moment de la commande si le stock change.",
                    'en' => "Product availability depends on the current stock shown on the website. Some products may become unavailable during checkout if stock changes.",
                    'ru' => "Наличие товара зависит от текущего остатка на сайте. Некоторые товары могут стать недоступными во время оформления заказа, если складской остаток изменится."
                ],
            ],
            [
                'intent' => 'translations',
                'category' => 'product',
                'keywords' => [
                    'langue',
                    'traduction',
                    'english',
                    'russian',
                    'francais',
                    'anglais',
                    'russe',
                    'язык',
                    'перевод'
                ],
                'answers' => [
                    'fr' => "Le catalogue peut etre consulte en plusieurs langues selon la configuration du site, notamment en francais, anglais et russe.",
                    'en' => "The catalog can be viewed in several languages depending on the site configuration, including French, English and Russian.",
                    'ru' => "Каталог может быть доступен на нескольких языках в зависимости от конфигурации сайта, включая французский, английский и русский."
                ],
            ],
            [
                'intent' => 'technical_problem',
                'category' => 'support',
                'keywords' => [
                    'bug',
                    'probleme technique',
                    'erreur technique',
                    'technical problem',
                    'site bloque',
                    'техническая проблема',
                    'ошибка',
                    'баг'
                ],
                'answers' => [
                    'fr' => "Si vous rencontrez un probleme technique, decrivez-le le plus precisement possible via le formulaire de contact afin que l'equipe puisse vous aider rapidement.",
                    'en' => "If you encounter a technical issue, please describe it as precisely as possible through the contact form so the team can help you quickly.",
                    'ru' => "Если вы столкнулись с технической проблемой, опишите ее как можно подробнее через форму обратной связи, чтобы команда могла быстрее помочь вам."
                ],
            ],
            [
                'intent' => 'human_support',
                'category' => 'support',
                'keywords' => [
                    'support',
                    'contact',
                    'agent',
                    'humain',
                    'assistance',
                    'support team',
                    'поддержка',
                    'связаться',
                    'оператор'
                ],
                'answers' => [
                    'fr' => "Si vous avez besoin d'une aide plus detaillee, vous pouvez utiliser le formulaire de contact pour envoyer votre demande a l'equipe support.",
                    'en' => "If you need more detailed help, you can use the contact form to send your request to the support team.",
                    'ru' => "Если вам нужна более подробная помощь, вы можете воспользоваться формой обратной связи, чтобы отправить запрос в службу поддержки."
                ],
            ],
            [
                'intent' => 'contact_form',
                'category' => 'support',
                'keywords' => [
                    'formulaire de contact',
                    'contact me',
                    'envoyer message',
                    'contacter equipe',
                    'contact form',
                    'send message',
                    'форма обратной связи',
                    'отправить сообщение'
                ],
                'answers' => [
                    'fr' => "Le formulaire de contact vous permet d'envoyer une demande detaillee avec votre email, un sujet et votre message.",
                    'en' => "The contact form allows you to send a detailed request with your email, a subject and your message.",
                    'ru' => "Форма обратной связи позволяет отправить подробный запрос с вашим email, темой и сообщением."
                ],
            ],
        ];
    }
}
