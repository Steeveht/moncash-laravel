# Guide d'Utilisation - Tests en Local (Ngrok & Cloudflare)

Pour tester l'intégration MonCash en local, vous avez besoin que les serveurs de Digicel Haïti puissent communiquer avec votre machine. Ce guide détaille les deux solutions les plus courantes.

## Option 1 : Cloudflare Tunnel (Recommandé)

Cloudflare Tunnel est gratuit, illimité et ne présente pas d'écran d'avertissement aux robots (ce qui évite parfois des problèmes avec les webhooks).

### 1. Installation

Récupérez le binaire `cloudflared` pour votre OS.

### 2. Lancer le tunnel

Si votre serveur Laravel tourne sur le port `8000` :

```bash
cloudflared tunnel --url http://localhost:8000
```

### 3. Utilisation

Vous obtiendrez une URL du type `https://votre-id.trycloudflare.com`. Utilisez celle-ci comme base pour vos URLs dans le portail MonCash Business.

---

## Option 2 : Ngrok

Ngrok est la solution historique. Rapide et efficace.

### 1. Installation & Auth

Inscrivez-vous sur [ngrok.com](https://ngrok.com) et ajoutez votre token :

```bash
ngrok config add-authtoken VOTRE_TOKEN
```

### 2. Lancer le tunnel

```bash
ngrok http 8000
```

### 3. Gérer l'écran d'avertissement

Ngrok affiche parfois une page intermédiare. Pour les webhooks, cela ne pose en général pas de problème, mais pour les redirections (`Alert URL`), l'utilisateur verra un avertissement.

---

## Configuration Laravel (Webhook / IPN)

### Désactiver CSRF

Dans Laravel, les requêtes POST entrantes de MonCash seront bloquées par le middleware CSRF. Vous devez ajouter l'URL de votre callback aux exceptions.

#### Laravel 11+ (`bootstrap/app.php`)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/moncash/callback', // Remplacez par votre route
    ]);
})
```

#### Laravel 10 et moins (`VerifyCsrfToken.php`)

```php
protected $except = [
    'api/moncash/callback',
];
```

## Debugging

- Surveillez vos logs : `tail -f storage/logs/laravel.log`
- Utilisez `MonCash::payment()->verifyByOrderId($orderId)` si vous n'avez pas reçu le webhook à cause d'un problème de tunnel.

---

© 2026 Steeve / MonCash Laravel Package
