<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Attribute\Route;

class IcloudCalendarController extends AbstractController
{
    public function __construct(private HttpClientInterface $client) {}

    #[Route('/icloud.ics', name: 'icloud_proxy')]
    public function ics(): Response
    {
        // Ton lien iCloud original
        $icloudUrl = 'webcal://p148-caldav.icloud.com/published/2/ODQ1MTA0NzQwNzg0NTEwNPQZJO8ftN7QPlbVihjryJwyOxIoS464DBD6A8bSAxCTB4JgWllkaWPte2dvb0CR-WxEYjOux47kZmcY2ibsCL0';

        // Correction automatique : webcal:// → https://
        $icloudUrl = preg_replace('/^webcal:\/\//', 'https://', $icloudUrl);

        $response = $this->client->request('GET', $icloudUrl);

        return new Response(
            $response->getContent(),
            200,
            ['Content-Type' => 'text/calendar']
        );
    }
}
