<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class InseeApiService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $inseeApiKey)
    {
        $this->client = $client;
        $this->apiKey = $inseeApiKey;
    }

    public function fetchCompanyBySiret(string $siret): ?array
    {
        
      $url = "https://api.insee.fr/api-sirene/3.11/siret/" . $siret;

try {
    $response = $this->client->request('GET', $url, [
        'headers' => [
            'X-INSEE-Api-Key-Integration' => $this->apiKey,
            'Accept' => 'application/json',
        ],
    ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                return $response->toArray(); // décodé automatiquement
            }

            return null;
        } catch (\Exception $e) {
            // Log si nécessaire
            return null;
        }
    }
}
