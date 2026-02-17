<?php

namespace Steeve\MonCashLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Steeve\MonCashLaravel\Sdk\MonCashPayment payment()
 * @method static \Steeve\MonCashLaravel\Sdk\MonCashBusiness business()
 * @method static \Steeve\MonCashLaravel\Sdk\MonCashCustomer customer()
 * @method static \Steeve\MonCashLaravel\Sdk\MonCashAuth auth()
 * 
 * @see \Steeve\MonCashLaravel\MonCash
 */
class MonCash extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'moncash';
    }
}
