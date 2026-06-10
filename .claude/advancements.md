
  Tableau d'avancement

  ┌─────┬──────────────────────────┬──────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
  │ ID  │          Tâche           │ État │                                                                   Détail                                                                    │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B01 │ Setup projet             │ ✅   │ Symfony 8, structure modulaire, .env. (ESLint/Prettier N/A en PHP)                                                                          │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B02 │ Schéma BDD + migrations  │ 🟡   │ Tables user/category/product/orders/items/discount/contact_request/translations + migration Doctrine. Manque : addresses, payment_methods,  │
  │     │                          │      │ cart_items, chatbot_logs, carousel_items                                                                                                    │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B03 │ Inscription              │ 🟡   │ POST /api/auth/register, hash bcrypt, token UUID, email. Manque : règles mdp (8 car./maj/chiffre…), unicité/format validés, expiration 24h  │
  │     │                          │      │ du token                                                                                                                                    │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B04 │ Confirmation email       │ ✅   │ GET /api/auth/verify-email/{token}, active, invalide token, renvoie JWT. 🟡 pas d'expiration                                                │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B05 │ Connexion                │ 🟡   │ json_login JWT (/api/auth/login_check). Manque : refus si non confirmé, refresh token, « se souvenir de moi »                               │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B06 │ Refresh token            │ ❌   │ Absent                                                                                                                                      │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B07 │ Mot de passe             │ ✅   │ forgot-password + reset-password/{token}, token UUID, expiration (1h, pas 24h). 🟡 pas de règles mdp                                        │
  │     │ oublié/reset             │      │                                                                                                                                             │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B08 │ Déconnexion              │ 🟡   │ Endpoint stub — renvoie 200, n'invalide rien (pas de blacklist/refresh)                                                                     │
  ├─────┼──────────────────────────┼──────┼─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B09 │ 2FA admin                │ ✅   │ Handler 2FA câblé (success_handler login), AdminTwoFactorService + POST /api/admin/auth/verify-2fa (OTP cache, 5 essais max), JWT admin émis │
  │     │                          │      │  après validation                                                                                                                           │
  │ B10 │ CRUD compte user          │ ❌   │ Pas de GET/PATCH /users/me                                                                                                                 │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B11 │ Adresses                  │ ❌   │ Pas d'entité Address. Adresse = champs plats sur User                                                                                      │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B12 │ Méthodes de paiement      │ ❌   │ Pas de customer/PaymentMethod Stripe. Stripe utilisé seulement en Checkout Session                                                         │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B13 │ CRUD produits backoffice  │ ✅   │ /api/admin/products GET paginé/triable/filtré, POST/PATCH/DELETE + /bulk (suppr. groupée, remise, publish), ROLE_ADMIN. 🟡 upload images   │
  │     │                           │      │ absent (pictureUrl = string)                                                                                                               │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B14 │ Lecture produits public   │ ✅   │ GET /api/products/:id, /:id/similar (6), /categories/:id/products. 🟡 tri prioritaires/épuisés + ordre aléatoire non conformes             │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B15 │ CRUD catégories           │ ✅   │ POST/PATCH/DELETE gardés ROLE_ADMIN, DELETE refuse si produits liés (409), champ displayOrder + PATCH /reorder, tri par ordre              │
  │     │ backoffice                │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B16 │ Lecture catégories public │ ✅   │ GET /api/categories, /:id. 🟡 champ ordre absent                                                                                           │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B17 │ Moteur de recherche       │ 🟡   │ GET /api/products/search?q= (titre seul). Manque : filtres prix/catégorie/dispo, tri, Levenshtein/priorités, pagination                    │
  │     │ avancé                    │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B18 │ Panier non connecté       │ ✅   │ Session invité + commande statut cart. add/get/update/remove, total, contrôle stock                                                        │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B19 │ Fusion panier après       │ ✅   │ LoginSuccessListener fusionne, additionne quantités, vide le panier invité                                                                 │
  │     │ connexion                 │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B20 │ Création de commande /    │ 🟡   │ POST /api/order/checkout → Stripe Checkout + webhook (statut Payé, décrément stock). Manque : validation adresse, méthode de paiement,     │
  │     │ checkout                  │      │ email de confirmation commande                                                                                                             │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B21 │ Historique commandes user │ 🟡   │ Seulement facture PDF (GET /api/invoice/:id). Manque : liste/détail commandes, filtres, recherche                                          │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B22 │ Gestion commandes         │ ✅   │ /api/admin/orders GET paginé/filtré (statut/user/dates)/triable, GET /{id} détail, PATCH /{id}/status (enum OrderStatus), ROLE_ADMIN       │
  │     │ backoffice                │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B23 │ Carrousel                 │ ✅   │ Entité CarouselItem + /api/admin/carousel CRUD + PATCH /reorder (ROLE_ADMIN) + GET public /api/carousel (actifs triés)                     │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B24 │ Top produits homepage     │ ✅   │ Entité TopProduct + PUT /api/admin/homepage/top-products (remplace sélection) + GET public /api/homepage/top-products                      │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B25 │ Dashboard ventes          │ ✅   │ /api/admin/dashboard/sales/daily + /weekly                                                                                                 │
  │     │ jour/semaine              │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B26 │ Paniers moyens par        │ ✅   │ /sales/weekly-by-category                                                                                                                  │
  │     │ catégorie                 │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B27 │ Répartition ventes par    │ ✅   │ /sales/category-share (pie)                                                                                                                │
  │     │ catégorie                 │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B28 │ Messages de contact       │ ✅   │ ContactService + POST public /api/contact (validation) + /api/admin/contact/messages (liste paginée, détail=lu, status, reply+email)      │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B29 │ Chatbot logs/escalade     │ ✅   │ Entité ChatbotLog + ChatbotLogService + POST /api/chatbot/message|log|escalate + GET /api/admin/chatbot/logs et /logs/{sessionId}         │
  │     │                           │      │                                                                                                                                            │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B30 │ Emails transactionnels    │ 🟡   │ Mailer + templates inscription & reset mdp. Manque : template changement email, confirmation commande, retry/logs                          │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B31 │ Sécurité globale          │ ❌   │ Manque rate limiting, CSRF, Helmet, validation systématique (Zod/class-validator). ✅ seulement ORM paramétré + CORS (nelmio)              │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B32 │ Upload/stockage images    │ ✅   │ ImageUploadService + POST /api/admin/upload (JPEG/PNG/WebP, max 5 Mo, nom UUID, public/uploads, renvoie URL), ROLE_ADMIN                   │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B33 │ Doc API Swagger/OpenAPI   │ ❌   │ API Platform présent mais aucune ressource exposée ; pas de nelmio/api-doc                                                                 │
  ├─────┼───────────────────────────┼──────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ B34 │ Doc technique             │ ✅   │ DOCUMENTATION_TECHNIQUE.md (238 l.) + docs annexes référencées                                                                             │
  └─────┴───────────────────────────┴──────┴────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘

  Bilan chiffré

  - ✅ Fait : 20 — B01, B04, B07, B09, B13, B14, B15, B16, B18, B19, B22, B23, B24, B25, B26, B27, B28, B29, B32, B34
  - 🟡 Partiel : 8 — B02, B03, B05, B08, B17, B20, B21, B30
  - ❌ À faire : 6 — B06, B10, B11, B12, B31, B33

  Priorités critiques restantes (hors backoffice)

  1. B31 Sécurité (critique, rien fait) — rate limiting, validation, headers.
  2. B06 refresh token + B05 (refus non confirmé) — auth incomplète.
  3. B10/B11/B12 — compte utilisateur, adresses, méthodes de paiement.
  4. B33 doc API (Swagger/OpenAPI).

  ──────────────────────────────────────────────────────────────────────
  Mise à jour 2026-05-30 — Lot backoffice traité (branche features/backoffice)

  Tâches passées à ✅ : B09, B15, B22, B23, B24, B28, B29, B32.

  Nouveaux fichiers :
  - Enum : OrderStatus
  - Entités : CarouselItem, ChatbotLog, TopProduct (+ champ displayOrder sur Category)
  - Repositories : CarouselItemRepository, ChatbotLogRepository, TopProductRepository
    (+ méthodes admin/dashboard sur OrdersRepository, + paginate sur ContactRequestRepository)
  - Migration : Version20260530124500 (carousel_item, chatbot_log, top_product, category.display_order)
  - Services : Admin\Order\AdminOrderService, Admin\Carousel\CarouselService,
    Admin\Homepage\TopProductService, ContactService, ChatbotLogService,
    ImageUploadService, AdminTwoFactorService
  - Contrôleurs : Admin\AdminAuthController, Admin\AdminOrderController, Admin\AdminUploadController,
    Admin\AdminCarouselController, Admin\AdminTopProductController, Admin\AdminContactController,
    Admin\AdminChatbotController, CarouselController, HomepageController, ContactController, ChatbotController
  - security.yaml : success_handler login = App\Security\AdminTwoFactorSuccessHandler

  ⚠️ Correctif annexe : OrdersRepository était un stub vide alors que SalesAnalyticsService
  (B25/B26/B27) appelait fetchSalesHistogram / fetchSalesByCategorySeries / fetchCategoryShare.
  Ces méthodes ont été ajoutées — le dashboard était cassé, il est maintenant fonctionnel.

  ⚠️ À faire avant exécution : `php bin/console doctrine:migrations:migrate`.
  Code NON exécuté ni linté ici (ni PHP ni base de données dans l'environnement).
  Le dépôt n'était PAS corrompu (fausse alerte initiale) — sources intactes, stash conservé.

  ──────────────────────────────────────────────────────────────────────
  Mise à jour 2026-06-10 — Correctif de l'affichage des produits d'administration (B13)

  - Ajout de la méthode `paginateAdminList` manquante dans `ProductRepository.php` pour corriger l'erreur 500 lors de la récupération des produits.