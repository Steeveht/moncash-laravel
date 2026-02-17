<?php

return [
    // Mode: 'sandbox' (test) ou 'live' (production)
    'mode' => env('MONCASH_MODE', 'sandbox'),

    // Clés d'API (Business Portal) [cite: 19]
    'client_id' => env('MONCASH_CLIENT_ID', ''),
    'client_secret' => env('MONCASH_SECRET', ''),

    // URLs de l'API REST [cite: 14]
    'api_url' => [
        'sandbox' => 'https://sandbox.moncashbutton.digicelgroup.com/Api',
        'live'    => 'https://moncashbutton.digicelgroup.com/Api',
    ],

    // URLs de la passerelle de paiement (Gateway) [cite: 16]
    'gateway_url' => [
        'sandbox' => 'https://sandbox.moncashbutton.digicelgroup.com/Moncash-middleware',
        'live'    => 'https://moncashbutton.digicelgroup.com/Moncash-middleware',
    ],
    
    // Temps d'expiration du token (en secondes) - Par défaut 50s pour être sûr
    'token_lifetime' => 50,
];