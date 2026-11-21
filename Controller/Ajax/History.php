<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Controller\Ajax;

class History extends AbstractAjax
{
    public function execute()
    {
        try {
            $base = strtoupper((string) $this->getRequest()->getParam('from', 'USD'));
            $quote = strtoupper((string) $this->getRequest()->getParam('to', 'EUR'));
            $days = (int) $this->getRequest()->getParam('days', 30);
            return $this->json(['history' => $this->exchangeRateService->getHistory($base, $quote, $days)]);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
