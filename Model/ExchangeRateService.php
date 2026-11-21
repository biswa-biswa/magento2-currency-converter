<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Model;

use Magento\Framework\Exception\LocalizedException;

class ExchangeRateService
{
    public function __construct(private FrankfurterClient $client)
    {
    }

    public function getCurrencies(): array
    {
        return $this->client->get('currencies', [], 86400);
    }

    public function getRate(string $base, string $quote): array
    {
        $this->validateCurrency($base);
        $this->validateCurrency($quote);

        if ($base === $quote) {
            return [
                'base' => $base,
                'quote' => $quote,
                'rate' => 1,
                'date' => gmdate('Y-m-d')
            ];
        }

        return $this->client->get(
            'rate/' . rawurlencode(strtolower($base)) . '/' . rawurlencode(strtolower($quote)),
            [],
            3600
        );
    }

    public function getHistory(string $base, string $quote, int $days = 30): array
    {
        $this->validateCurrency($base);
        $this->validateCurrency($quote);

        if ($base === $quote) {
            return [['base' => $base, 'quote' => $quote, 'rate' => 1, 'date' => gmdate('Y-m-d')]];
        }

        $days = max(7, min(365, $days));
        $to = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $from = $to->sub(new \DateInterval('P' . $days . 'D'));

        return $this->client->get('rates', [
            'base' => strtoupper($base),
            'quotes' => strtoupper($quote),
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d')
        ], 21600);
    }

    private function validateCurrency(string $code): void
    {
        if (!preg_match('/^[A-Z]{3}$/', strtoupper($code))) {
            throw new LocalizedException(__('Invalid currency code.'));
        }
    }
}
