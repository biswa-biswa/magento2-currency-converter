<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Controller\Ajax;

class Rate extends AbstractAjax
{
    public function execute()
    {
        try {
            $base = strtoupper((string) $this->getRequest()->getParam('from', 'USD'));
            $quote = strtoupper((string) $this->getRequest()->getParam('to', 'EUR'));
            return $this->json(['rate' => $this->exchangeRateService->getRate($base, $quote)]);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
