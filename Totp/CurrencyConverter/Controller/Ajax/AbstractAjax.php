<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Totp\CurrencyConverter\Model\ExchangeRateService;

abstract class AbstractAjax extends Action
{
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        protected ExchangeRateService $exchangeRateService
    ) {
        parent::__construct($context);
    }

    protected function json(array $data, int $status = 200)
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($status);
        return $result->setData($data);
    }

    protected function error(\Throwable $exception)
    {
        return $this->json(['error' => $exception->getMessage()], 400);
    }
}
