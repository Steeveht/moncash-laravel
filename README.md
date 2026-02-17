# MonCash Laravel Package

Une librairie Laravel robuste et agnostique pour l'intégration de l'API MonCash (Digicel Haïti). Ce package simplifie l'authentification, les paiements, les transferts et la vérification de comptes.

## Fonctionnalités

- **Authentification OAuth 2.0** : Gestion automatique, mise en cache intelligente et rafraîchissement optimisé du token.
- **Paiements** : Création de paiement, gestion des redirections et vérification par ID de transaction ou de commande.
- **Transferts (Business)** : Envoi d'argent P2P, vérification de solde et de statut de transfert.
- **Clients** : Vérification de l'existence et du statut d'un compte MonCash.
- **Agnostique** : Cœur du SDK (`src/Sdk`) indépendant, utilisable hors Laravel (Symfony, PHP pur).
- **Robuste** : Gestion complète des exceptions et configuration flexible (timeouts, lifetime du token).

## Installation

Installez le package via Composer :

```bash
composer require steeve/moncash-laravel
```

## Configuration

### 1. Publiez le fichier de configuration :

```bash
php artisan vendor:publish --tag=moncash-config
```

### 2. Variables d'environnement

Ajoutez vos clés dans votre fichier `.env` :

```env
MONCASH_MODE=sandbox
MONCASH_CLIENT_ID=votre_client_id
MONCASH_SECRET=votre_client_secret
```

- **sandbox**: Pour les tests.
- **live**: Pour la production.

### 3. Configuration du Portail MonCash (Business Portal)

Lors de la création de votre application sur le portail MonCash, voici à quoi correspondent les champs pour Laravel :

1. **Business Name** : Le nom de votre entreprise ou boutique.
2. **Website URL** : L'URL de base de votre site (ex: `https://votre-site.com`).
3. **Return URL (Link to receive the payment Notification)** : Votre URL de **Webhook/IPN**.
   - Exemple : `https://votre-site.com/api/moncash/callback`
4. **Alert URL (Thank you page)** : C'est l'URL de redirection **après** le paiement.
   - Exemple : `https://votre-site.com/payment/success`

> [!IMPORTANT]
> Assurez-vous que ces URLs sont accessibles publiquement et que votre route de "Return URL" ne bloque pas les requêtes POST (pensez au middleware CSRF).

---

## Utilisation

Vous pouvez utiliser la Facade `MonCash` ou l'injection de dépendance via la classe `Steeve\MonCashLaravel\MonCash`.

### A. Créer un Paiement (Redirection)

```php
use MonCash;

public function payer(Request $request)
{
    // 1. Créer le paiement (ID commande unique, Montant en Gourdes)
    $payment = MonCash::payment()->createPayment('ORDER-' . time(), 500);

    // 2. Rediriger l'utilisateur vers MonCash
    return redirect($payment['redirect_url']);
}
```

### B. Vérifier un Paiement (Callback / IPN)

Après le paiement, MonCash appelle votre webhook avec un `transactionId`.

```php
use MonCash;

public function callback(Request $request)
{
    $transactionId = $request->input('transactionId');

    try {
        $details = MonCash::payment()->verifyByTransactionId($transactionId);

        if ($details['payment']['message'] === 'successful') {
            $payer = $details['payment']['payer'];
            $amount = $details['payment']['cost'];
            return "Merci pour votre paiement de $amount HTG par $payer";
        }
    } catch (\Steeve\MonCashLaravel\Sdk\Exception\MonCashPaymentException $e) {
        return "Erreur : " . $e->getMessage();
    }
}
```

### C. Transfert d'Argent (P2P / Business)

```php
use MonCash;

try {
    // Numéro (509...), Montant, Description, Référence unique
    $transfert = MonCash::business()->transfert(
        '50937000000', 250, 'Paiement service', 'REF-12345'
    );
} catch (\Steeve\MonCashLaravel\Sdk\Exception\MonCashTransferException $e) {
    return "Echec : " . $e->getMessage();
}
```

### D. Consulter le Solde et Statut

```php
// Vérifier le solde du compte Business
$balance = MonCash::business()->prefundedBalance();

// Vérifier le statut d'une transaction pré-financée
$status = MonCash::business()->prefundedTransactionStatus('TRANSFER_ID');
```

### E. Vérifier un Compte Client

```php
$status = MonCash::customer()->customerStatus('50937000000');
```

## Gestion des Erreurs

Exceptions spécifiques disponibles :

- `Steeve\MonCashLaravel\Sdk\Exception\MonCashPaymentException`
- `Steeve\MonCashLaravel\Sdk\Exception\MonCashTransferException`
- `Steeve\MonCashLaravel\Sdk\Exception\MonCashException` (Base)

## Utilisation hors Laravel (PHP Natif) - Exemple Complet

Ce package est conçu pour être "agnostique". Voici comment l'utiliser dans un projet PHP classique avec une base de données (PDO).

### 1. Initialiser le SDK

```php
require 'vendor/autoload.php';

use Steeve\MonCashLaravel\Sdk\Config;
use Steeve\MonCashLaravel\Sdk\MonCashAuth;
use Steeve\MonCashLaravel\Sdk\MonCashPayment;
use GuzzleHttp\Client;

$config = new Config(Config::MODE_SANDBOX, 'VOTRE_CLIENT_ID', 'VOTRE_SECRET');
$client = new Client();
$auth = new MonCashAuth($config, $client);
$monCashPayment = new MonCashPayment($config, $auth, $client);
```

### 2. Créer une transaction (`checkout.php`)

```php
$orderId = "CMD-" . uniqid();
$amount = 1500;

try {
    $payment = $monCashPayment->createPayment($orderId, $amount);
    $token = $payment['payment_token']['token'];

    // Enregistrement en base de données
    $db = new PDO('mysql:host=localhost;dbname=votre_db', 'root', '');
    $stmt = $db->prepare("INSERT INTO transactions (order_id, amount, payment_token) VALUES (?, ?, ?)");
    $stmt->execute([$orderId, $amount, $token]);

    header('Location: ' . $payment['redirect_url']);
} catch (Exception $e) { echo "Erreur : " . $e->getMessage(); }
```

### 3. Confirmation de paiement (`webhook.php`)

```php
$transactionId = $_POST['transactionId'] ?? null;
if (!$transactionId) die("Accès refusé.");

try {
    $details = $monCashPayment->verifyByTransactionId($transactionId);
    if ($details['payment']['message'] === 'successful') {
        // Mise à jour de la base de données après vérification
        $db = new PDO('mysql:host=localhost;dbname=votre_db', 'root', '');
        $stmt = $db->prepare("UPDATE transactions SET status = 'SUCCESSFUL' WHERE order_id = ?");
        $stmt->execute([$details['payment']['order_id']]);
    }
} catch (Exception $e) { /* Log error */ }
```

## Architecture Headless / API-Only

Si vous utilisez un frontend séparé (React, Vue, Mobile) :

- Le **Backend** crée le paiement et renvoie l'URL de redirection au **Frontend**.
- Le **Return URL** (MonCash Portal) pointe vers votre **API** (Backend).
- L'**Alert URL** (MonCash Portal) pointe vers votre **Frontend** (React/Vue/Mobile).

## Utilisation avec Symfony

Puisque le SDK est agnostique, vous pouvez l'intégrer facilement dans Symfony en déclarant les classes comme services dans votre fichier `config/services.yaml` :

```yaml
# config/services.yaml
services:
  Steeve\MonCashLaravel\Sdk\Config:
    arguments:
      $mode: "%env(MONCASH_MODE)%"
      $clientId: "%env(MONCASH_CLIENT_ID)%"
      $clientSecret: "%env(MONCASH_SECRET)%"

  Steeve\MonCashLaravel\Sdk\MonCashAuth:
    arguments:
      $config: '@Steeve\MonCashLaravel\Sdk\Config'
      $client: '@GuzzleHttp\Client'

  Steeve\MonCashLaravel\Sdk\MonCashPayment:
    arguments:
      $config: '@Steeve\MonCashLaravel\Sdk\Config'
      $auth: '@Steeve\MonCashLaravel\Sdk\MonCashAuth'
      $client: '@GuzzleHttp\Client'

  # Répétez pour MonCashBusiness et MonCashCustomer si nécessaire
```

Ensuite, utilisez l'injection de dépendance dans vos contrôleurs :

```php
use Steeve\MonCashLaravel\Sdk\MonCashPayment;

public function pay(MonCashPayment $moncashPayment)
{
    $result = $moncashPayment->createPayment('ORDER-123', 500);
    return $this->redirect($result['redirect_url']);
}
```

## Utilisation avec Laravel + Base de données

### 1. Migration

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_id')->unique();
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending');
    $table->timestamps();
});
```

### 2. Contrôleur

```php
public function checkout() {
    $payment = MonCash::payment()->createPayment('CMD-001', 500);

    Order::create([
        'order_id' => 'CMD-001',
        'amount' => 500,
        'status' => 'pending'
    ]);

    return redirect($payment['redirect_url']);
}

public function callback(Request $request) {
    $details = MonCash::payment()->verifyByTransactionId($request->transactionId);

    if ($details['payment']['message'] === 'successful') {
        Order::where('order_id', $details['payment']['order_id'])
             ->update(['status' => 'success']);
    }
}
```

## Tester en local avec Ngrok (Guide de A à Z)

Pour que MonCash puisse envoyer des notifications de paiement à votre ordinateur local, vous devez utiliser un tunnel.

### 1. Installation

Téléchargez Ngrok sur [ngrok.com](https://ngrok.com/) et authentifiez-vous :

```bash
ngrok config add-authtoken VOTRE_TOKEN
```

### 2. Lancer le tunnel

Lancez votre serveur local (ex: port 8000), puis :

```bash
ngrok http 8000
```

Copiez l'adresse `Forwarding` (ex: `https://a1b2c3d4.ngrok-free.app`).

### 3. Configurer MonCash

Dans le portail MonCash Business, utilisez cette adresse pour le **Return URL** :

- Laravel : `https://a1b2c3d4.ngrok-free.app/api/moncash/callback`
- PHP Natif : `https://a1b2c3d4.ngrok-free.app/webhook.php`

### 4. Gérer le CSRF (Laravel)

N'oubliez pas d'ajouter votre route callback dans les exceptions du middleware CSRF (`bootstrap/app.php` ou `VerifyCsrfToken.php`).

### Pro Alternative : Cloudflare Tunnel

Pour une solution 100% gratuite et illimitée sans page d'avertissement :
`cloudflared tunnel --url http://localhost:8000`
Consultez le guide complet dans `USAGE_GUIDE.md`.

## Architecture

Le package suit une structure modulaire pour garantir sa flexibilité :

- **`src/Sdk/`** : Logique métier pure, sans aucune dépendance à Laravel.
- **`src/MonCash.php`** : Point d'entrée principal (Wrapper) pour Laravel.
- **`src/MonCashServiceProvider.php`** : Enregistrement automatique du package et de sa configuration.
- **`src/Facades/MonCash.php`** : Facade pour un accès statique élégant.

## License

MIT
