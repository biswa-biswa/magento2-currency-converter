<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Block;

use Magento\Framework\View\Element\Template;

class Converter extends Template
{
    public function getCurrenciesUrl(): string
    {
        return $this->getUrl('currencyconverter/ajax/currencies');
    }

    public function getRateUrl(): string
    {
        return $this->getUrl('currencyconverter/ajax/rate');
    }

    public function getHistoryUrl(): string
    {
        return $this->getUrl('currencyconverter/ajax/history');
    }
}
