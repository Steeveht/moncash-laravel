# MonCash Laravel Package

Une librairie Laravel robuste et agnostique pour l'intégration de l'API MonCash (Digicel Haïti). Ce package simplifie l'authentification, les paiements, les transferts et la vérification de comptes.

## Fonctionnalités

- **Authentification OAuth 2.0** : Gestion automatique et mise en cache du token.
- **Paiements** : Création de paiement et redirection facile.
- **Transferts (Business)** : Envoi d'argent P2P et vérification de solde.
- **Clients** : Vérification de l'existence d'un compte MonCash.
- **Agnostique** : Le cœur du SDK (`src/Sdk`) peut être utilisé hors de Laravel.

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

## Utilisation hors Laravel (PHP Natif)

Ce package est conçu pour être "agnostique". Vous pouvez utiliser les classes du dossier `Sdk` dans n'importe quel projet PHP.

```php
require 'vendor/autoload.php';

use Steeve\MonCashLaravel\Sdk\Config;
use Steeve\MonCashLaravel\Sdk\MonCashAuth;
use Steeve\MonCashLaravel\Sdk\MonCashPayment;
use GuzzleHttp\Client;

// 1. Initialiser la configuration
$config = new Config(
    Config::MODE_SANDBOX,
    'votre_client_id',
    'votre_secret'
);

// 2. Créer le client HTTP et l'Auth
$client = new Client();
$auth = new MonCashAuth($config, $client);

// 3. Utiliser les modules
$payment = new MonCashPayment($config, $auth, $client);
$result = $payment->createPayment('ORDER-101', 250);

echo $result['redirect_url'];
```

## Architecture

- **`src/Sdk/`** : Logique métier pure, framework-agnostic.
- **`src/MonCash.php`** : Wrapper Laravel principal.
- **`src/Facades/MonCash.php`** : Accès statique simplifié.

## License

MIT
