<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch;

use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

/*
 * IMPORTANT NOTE:
 * This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.
 */

class Client implements ClientInterface
{
    use AdapterCommonTrait;

    public ?ElasticsearchClient $elasticsearchClient = null;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = 'p+6FezswTw96zzK5rrlc';
    private $cert;

    public string $productIndexName = 'products';

    public function initialize(): void
    {
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$this->host])
            ->setBasicAuthentication($this->username, $this->password)

            /*
             * Do me! Do not bypass SSL verification but instead provide a certificate.
             *  At the moment, we set the ssl verification to false only for a quick test.
             */
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