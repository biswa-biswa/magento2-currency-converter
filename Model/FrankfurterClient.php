<?php
declare(strict_types=1);

namespace Totp\CurrencyConverter\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class FrankfurterClient
{
    private const CACHE_PREFIX = 'totp_currencyconverter_';

    private Curl $curl;
    private Json $json;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private string $baseUrl;

    public function __construct(
        Curl $curl,
        Json $json,
        CacheInterface $cache,
        LoggerInterface $logger,
        string $baseUrl = 'https://api.frankfurter.dev/v2'
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl;
    }

    public function get(
        string $path,
        array $query = [],
        int $ttl = 3600
    ): array {
        $url = rtrim($this->baseUrl, '/')
            . '/'
            . ltrim($path, '/');

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $cacheKey = self::CACHE_PREFIX . hash('sha256', $url);

        $cached = $this->cache->load($cacheKey);

        if ($cached !== false) {
            try {
                return $this->json->unserialize($cached);
            } catch (\Throwable $exception) {
                $this->cache->remove($cacheKey);
            }
        }

        try {
            $this->curl->setTimeout(20);
            $this->curl->addHeader('Accept', 'application/json');

            $this->logger->info(
                'Frankfurter API Request: ' . $url
            );

            $this->curl->get($url);

            $status = (int) $this->curl->getStatus();
            $body = (string) $this->curl->getBody();

            $this->logger->info(
                'Frankfurter API Response Status: ' . $status
            );

            if ($status < 200 || $status >= 300) {
                throw new LocalizedException(
                    __('Frankfurter API returned HTTP %1.', $status)
                );
            }

            $data = $this->json->unserialize($body);

            if (!is_array($data)) {
                throw new LocalizedException(
                    __('Unexpected response from Frankfurter API.')
                );
            }

            $this->cache->save(
                $this->json->serialize($data),
                $cacheKey,
                [],
                $ttl
            );

            return $data;

        } catch (LocalizedException $exception) {

            $this->logger->critical(
                'Frankfurter API Error: ' . $exception->getMessage()
            );

            throw $exception;

        } catch (\Throwable $exception) {

            $this->logger->critical(
                'Frankfurter Unexpected Error: '
                . $exception->getMessage(),
                ['exception' => $exception]
            );

            throw new LocalizedException(
                __('Unable to contact the exchange-rate service.')
            );
        }
    }
}
