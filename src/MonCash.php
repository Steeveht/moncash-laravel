<?php

namespace Steeve\MonCashLaravel;

use Steeve\MonCashLaravel\Sdk\MonCashAuth;
use Steeve\MonCashLaravel\Sdk\MonCashBusiness;
use Steeve\MonCashLaravel\Sdk\MonCashCustomer;
use Steeve\MonCashLaravel\Sdk\MonCashPayment;

class MonCash
{
    private MonCashPayment $payment;
    private MonCashBusiness $business;
    private MonCashCustomer $customer;
    private MonCashAuth $auth;

    public function __construct(
        MonCashPayment $payment,
        MonCashBusiness $business,
        MonCashCustomer $customer,
        MonCashAuth $auth
    ) {
        $this->payment = $payment;
        $this->business = $business;
        $this->customer = $customer;
        $this->auth = $auth;
    }

    /**
     * Access the Payment module
     */
    public function payment(): MonCashPayment
    {
        return $this->payment;
    }

    /**
     * Access the Business module (Transfers)
     */
    public function business(): MonCashBusiness
    {
        return $this->business;
    }

    /**
     * Access the Customer module
     */
    public function customer(): MonCashCustomer
    {
        return $this->customer;
    }

    /**
     * Access the Auth module directly if needed
     */
    public function auth(): MonCashAuth
    {
        return $this->auth;
    }
}
