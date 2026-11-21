<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Controller\Ajax;

class Currencies extends AbstractAjax
{
    public function execute()
    {
        try {
            return $this->json(['currencies' => $this->exchangeRateService->getCurrencies()]);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
