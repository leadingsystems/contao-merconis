<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch;

use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client as GuzzleHttpClient;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Instrumentation\InstrumentedGuzzleFactory;

/*
 * IMPORTANT NOTE:
 * This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.
 */

class Client implements ClientInterface
{
    use AdapterCommonTrait;

    public ?ElasticsearchClient $elasticsearchClient = null;
    private string $projectDir;
    private string $environment;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = 'p+6FezswTw96zzK5rrlc';
    private $cert;

    public string $productIndexName = 'products';

    public function __construct(string $projectDir, string $environment)
    {
        $this->projectDir = $projectDir;
        $this->environment = $environment;
    }

    public function initialize(): void
    {
        $httpClient = InstrumentedGuzzleFactory::create([
            'verify' => false,
        ], null, $this->projectDir, $this->environment);

        $this->elasticsearchClient = ClientBuilder::create()
            ->setHttpClient($httpClient)
            ->setHosts([$this->host])
            ->setBasicAuthentication($this->username, $this->password)
            // ->setCABundle($this->cert)
            ->setSSLVerification(false)
            ->build();
    }

    public function createIndex(string $indexName, array $indexDefinition): OperationResult
    {
        $operationResult = new OperationResult();

        if (empty($indexDefinition)) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('No mapping definition found for index "' . $indexName . '"');
            return $operationResult;
        }

        try {
            $response = $this->elasticsearchClient->indices()->create([
                'index' => $indexName,
                'body' => $indexDefinition
            ]);


            if ($response['acknowledged'] ?? false) {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Index "' . $indexName . '" was created successfully');
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('Failed to create the "' . $indexName . '" index');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }
}