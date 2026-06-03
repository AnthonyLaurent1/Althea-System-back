# Gestion des Traductions - Backoffice Produits

## Format des traductions

Pour créer ou modifier un produit avec traductions, utiliser le format suivant:

```json
{
  "title": "Titre FR",
  "description": "Description FR",
  "price": "99.99",
  "pictureUrl": "https://...",
  "categoryId": 1,
  "isPublished": true,
  "powerSupplyType": "Type alimentation FR",
  "medicalDomain": "Domaine médical FR",
  "isPortable": true,
  "isOneTimeUse": false,
  "inStock": 10,
  "translations": {
    "en": {
      "title": "Product Title",
      "description": "Product description",
      "powerSupplyType": "Power supply type",
      "medicalDomain": "Medical domain"
    },
    "ru": {
      "title": "Название продукта",
      "description": "Описание продукта",
      "powerSupplyType": "Тип питания",
      "medicalDomain": "Медицинская область"
    }
  }
}
```

## Endpoints

### Créer un produit avec traductions
```
POST /api/admin/products
```
Envoyer le JSON ci-dessus.

### Modifier un produit et ses traductions
```
PATCH /api/admin/products/{id}
PUT /api/admin/products/{id}
```

**Exemple:** Modifier seulement les traductions en:
```json
{
  "translations": {
    "en": {
      "title": "New English Title",
      "description": "New description"
    }
  }
}
```

### Récupérer un produit (avec traductions)
```
GET /api/admin/products/{id}
```

Retourne:
```json
{
  "id": 1,
  "title": "Titre FR",
  "description": "Description FR",
  "translations": {
    "en": {
      "title": "Product Title",
      "description": "Product description",
      "powerSupplyType": "Power supply type",
      "medicalDomain": "Medical domain"
    },
    "ru": {
      "title": "Название продукта",
      ...
    }
  },
  ...
}
```

## Notes

- Les champs `title` et `description` dans le corps de la requête sont **obligatoires** (langue par défaut: FR)
- `translations` est optionnel
- Pour chaque locale, seul `title` est obligatoire dans les traductions
- Les champs `description`, `powerSupplyType`, `medicalDomain` sont optionnels
- Si une traduction n'existe pas, elle est créée
- Si elle existe déjà, elle est mise à jour

## Affichage côté public

Le frontend utilise le paramètre `?locale=en` pour obtenir la traduction:

```
GET /api/products/{id}?locale=en
```

Retourne le produit avec la traduction EN appliquée (ou FR par défaut si EN n'existe pas).
