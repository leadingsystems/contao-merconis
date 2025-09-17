<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Instrumentation;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;

class InstrumentedGuzzleFactory
{
	/**
	 * Create a Guzzle client configured to emit per-request timing and size logs via on_stats.
	 *
	 * @param array $baseConfig   Base Guzzle config (e.g., verify/certs). Will be merged.
	 * @param LoggerInterface|null $logger Optional PSR-3 logger; if null, logs go to error_log.
	 * @return Client
	 */
	public static function create(array $baseConfig = [], ?LoggerInterface $logger = null): Client
	{
		$stack = HandlerStack::create();

		$logFile = getenv('MERCONIS_ES_LOG_FILE') ?: '';
		$enabled = self::envFlag('MERCONIS_ES_LOG_TIMINGS', false);
		$sampleRate = self::envFloat('MERCONIS_ES_LOG_SAMPLE_RATE', 1.0);
		$slowTotalMs = self::envInt('MERCONIS_ES_SLOW_TOTAL_MS', 500);
		$slowServerMs = self::envInt('MERCONIS_ES_SLOW_SERVER_MS', 300);
		$slowNetworkMs = self::envInt('MERCONIS_ES_SLOW_NETWORK_MS', 200);

		$config = $baseConfig + [
			'handler' => $stack,
			// Always set decode_content to true so Content-Length reflects decoded sizes if possible
			'decode_content' => true,
		];

		// Attach on_stats to capture transfer timings and sizes after each request
		$config['on_stats'] = function (TransferStats $stats) use ($logger, $enabled, $sampleRate, $logFile, $slowTotalMs, $slowServerMs, $slowNetworkMs) {
			if (!$enabled) {
				return;
			}
			if ($sampleRate < 1.0 && mt_rand() / mt_getrandmax() > $sampleRate) {
				return;
			}

			$handler = $stats->getHandlerStats();
			$request = $stats->getRequest();
			$response = $stats->getResponse();

			$method = $request ? $request->getMethod() : '';
			$uri = $request ? (string) $request->getUri() : '';
			$path = $request ? $request->getUri()->getPath() : '';
			$host = $request ? $request->getUri()->getHost() : '';
			$status = $response ? $response->getStatusCode() : 0;

			$total = isset($handler['total_time']) ? (int) round(1000 * $handler['total_time']) : (int) round(1000 * $stats->getTransferTime());
			$dns = isset($handler['namelookup_time']) ? (int) round(1000 * $handler['namelookup_time']) : null;
			$connect = isset($handler['connect_time']) ? (int) round(1000 * $handler['connect_time']) : null;
			$tls = isset($handler['appconnect_time']) && isset($handler['connect_time'])
				? max(0, (int) round(1000 * ($handler['appconnect_time'] - $handler['connect_time'])))
				: null;
			$pretransfer = isset($handler['pretransfer_time']) ? (int) round(1000 * $handler['pretransfer_time']) : null;
			$starttransfer = isset($handler['starttransfer_time']) ? (int) round(1000 * $handler['starttransfer_time']) : null;
			$ttfb = ($pretransfer !== null && $starttransfer !== null) ? max(0, $starttransfer - $pretransfer) : null;
			$download = ($starttransfer !== null && isset($handler['total_time'])) ? max(0, (int) round(1000 * ($handler['total_time'] - $handler['starttransfer_time']))) : null;

			$sizeUpload = isset($handler['size_upload']) ? (int) $handler['size_upload'] : null;
			$sizeDownload = isset($handler['size_download']) ? (int) $handler['size_download'] : null;
			$reqHeaderSize = isset($handler['request_header']) ? strlen((string) $handler['request_header']) : (isset($handler['header_size']) ? (int) $handler['header_size'] : null);
			$respHeaderSize = isset($handler['header_size']) ? (int) $handler['header_size'] : null;

			$opaqueId = $request && $request->hasHeader('X-Opaque-Id') ? ($request->getHeader('X-Opaque-Id')[0] ?? '') : '';

			$esTook = null;
			$hitsTotal = null;
			$timedOut = null;
			$shards = null;
			if ($response) {
				$body = $response->getBody();
				$contents = '';
				try {
					$contents = (string) $body;
					if ($body->isSeekable()) {
						$body->rewind();
					}
				} catch (\Throwable $t) {
					$contents = '';
				}
				if ($contents !== '') {
					$data = null;
					try { $data = \json_decode($contents, true, 512, JSON_INVALID_UTF8_SUBSTITUTE); } catch (\Throwable $t) { $data = null; }
					if (\is_array($data)) {
						$esTook = isset($data['took']) && \is_numeric($data['took']) ? (int) $data['took'] : null;
						$hitsTotal = $data['hits']['total']['value'] ?? null;
						$timedOut = $data['timed_out'] ?? null;
						$shards = $data['_shards'] ?? null;
					}
				}
			}

			$networkOverhead = null;
			if ($dns !== null && $connect !== null && $download !== null && $ttfb !== null) {
				$tcp = max(0, $connect - $dns);
				$overTtfb = ($esTook !== null) ? max(0, (int) round(1000 * $ttfb) - $esTook) : (int) round(1000 * $ttfb);
				$networkOverhead = ($dns + $tcp + ($tls ?? 0) + $download + $overTtfb);
			}

			$operation = self::guessOperation($path);

			$payload = [
				'ts' => gmdate('c'),
				'identity' => [
					'opaque_id' => $opaqueId,
					'operation' => $operation,
					'method' => $method,
					'path' => $path,
					'host' => $host,
					'uri' => $uri,
				],
				'result' => [
					'status_code' => $status,
				],
				'timing_ms' => [
					'total' => $total,
					'es_took' => $esTook,
					'dns' => $dns,
					'tcp' => ($dns !== null && $connect !== null) ? max(0, $connect - $dns) : null,
					'tls' => $tls,
					'ttfb' => ($ttfb !== null) ? (int) round(1000 * $ttfb) : null,
					'download' => $download,
					'network_overhead_est' => $networkOverhead,
				],
				'sizes_bytes' => [
					'request_body' => $sizeUpload,
					'response_body' => $sizeDownload,
					'request_headers' => $reqHeaderSize,
					'response_headers' => $respHeaderSize,
				],
				'flags' => [
					'compressed' => ($response && $response->hasHeader('Content-Encoding')),
				],
			];

			if ($hitsTotal !== null || $timedOut !== null || $shards !== null) {
				$payload['result'] += [
					'hits' => $hitsTotal,
					'timed_out' => $timedOut,
					'shards' => $shards,
				];
			}

			$payload['duration_checks'] = [
				'slow_total' => $total > $slowTotalMs,
				'slow_server' => ($esTook !== null && $esTook > $slowServerMs),
				'slow_network' => ($networkOverhead !== null && $networkOverhead > $slowNetworkMs),
			];

			$line = \json_encode($payload, JSON_UNESCAPED_SLASHES);
			if ($logger) {
				$level = ($payload['duration_checks']['slow_total'] || $payload['duration_checks']['slow_server'] || $payload['duration_checks']['slow_network']) ? 'warning' : 'info';
				$logger->log($level, $line);
			} else {
				if (!empty($logFile)) {
					@file_put_contents($logFile, $line . "\n", FILE_APPEND);
				} else {
					@error_log($line);
				}
			}
		};

		return new Client($config);
	}

	private static function guessOperation(string $path): string
	{
		if (strpos($path, '/_search/scroll') !== false || $path === '/_search/scroll') return 'scroll';
		if (strpos($path, '/_search') !== false) return 'search';
		if (strpos($path, '/_msearch') !== false) return 'msearch';
		if (strpos($path, '/_bulk') !== false) return 'bulk';
		return 'other';
	}

	private static function envFlag(string $name, bool $default): bool
	{
		$val = getenv($name);
		if ($val === false) return $default;
		$val = strtolower((string) $val);
		return in_array($val, ['1', 'true', 'yes', 'on'], true);
	}

	private static function envFloat(string $name, float $default): float
	{
		$val = getenv($name);
		if ($val === false) return $default;
		return (float) $val;
	}

	private static function envInt(string $name, int $default): int
	{
		$val = getenv($name);
		if ($val === false) return $default;
		return (int) $val;
	}
}


