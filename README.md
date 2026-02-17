# MonCash Laravel Package

Une librairie Laravel robuste et agnostique pour l'intégration de l'API MonCash (Digicel Haïti). Ce package simplifie l'authentification, les paiements, les transferts et la vérification de comptes.

## Fonctionnalités

- **Authentification OAuth 2.0** : Gestion automatique et mise en cache du token.
- **Paiements** : Création de paiement et redirection facile.
- **Transferts (Business)** : Envoi d'argent P2P et vérification de solde.
- **Clients** : Vérification de l'existence d'un compte MonCash.
- **Agnostique** : Le cœur du SDK (`src/Sdk`) peut être utilisé hors de Laravel.

## Installation

```bash
composer require steeve/moncash-laravel
```

## Configuration

Publiez le fichier de configuration :

```bash
php artisan vendor:publish --tag=moncash-config
```

Ajoutez vos clés dans `.env` :

```env
MONCASH_MODE=sandbox
MONCASH_CLIENT_ID=votre_client_id
MONCASH_SECRET=votre_client_secret
```

## Utilisation Rapide

### 1. Paiement (Redirection)

```php
use MonCash;

// Dans votre contrôleur
public function pay() {
    $payment = MonCash::payment()->createPayment('ORDER_123', 500);
    return redirect($payment['redirect_url']);
}
```

### 2. Vérification (Webhook/Callback)

```php
use MonCash;

public function callback(Request $request) {
    $transactionId = $request->input('transactionId');
    $details = MonCash::payment()->verifyByTransactionId($transactionId);

    if ($details['payment']['message'] === 'successful') {
        // Succès
    }
}
```

### 3. Transfert d'argent

```php
MonCash::business()->transfert('50937000000', 100, 'Cadeau', 'REF_01');
```

## Architecture

- **`Steeve\MonCashLaravel\Sdk\`** : Contient la logique métier pure (Config, Auth, Payment, etc.).
- **`Steeve\MonCashLaravel\MonCash`** : Wrapper Laravel qui s'injecte via le Service Container.
- **`Steeve\MonCashLaravel\Facades\MonCash`** : Facade pour une utilisation statique fluide.

## License

MIT
