<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\Elasticsearch;

use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;

class Client implements ClientInterface
{
    private ?ElasticsearchClient $client = null;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = '7+3JVkR_XqSRohMRb-*s';
    private $cert;

    public function initialize(): void
    {
        $this->client = ClientBuilder::create()
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

    public function testConnection(): string
    {
        // Try to get the status of the cluster
        try {
            $response = $this->client->ping();
            if ($response) {
                return 'Elasticsearch is reachable!';
            } else {
                return 'Failed to reach Elasticsearch.';
            }
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function testProductsIndex(): string
    {
        try {
            // Make a simple search request on the 'product' index
            $params = [
                'index' => 'products',  // Your index name
                'body'  => [
                    'query' => [
                        'match_all' => new \stdClass(),
                    ]
                ]
            ];

            // Search the 'product' index
            $response = $this->client->search($params);

            // Check if any hits were returned
            if ($response['hits']['total']['value'] > 0) {
                return 'Found ' . $response['hits']['total']['value'] . ' documents in the product index.';
            } else {
                return 'No documents found in the product index.';
            }
        } catch (\Exception $e) {
            return 'Error during query: ' . $e->getMessage();
        }
    }

    public function getAdapterName(): string
    {
        return basename(__DIR__);
    }

    public function getAdapterDescription(): string
    {
        return 'This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.';
    }
}