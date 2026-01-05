<?php

namespace App\Service;

use Google\Client;
use Google\Service\Gmail;

class GmailService
{
    private Client $client;
    private Gmail $service;
    private string $tokenPath;

    public function __construct(string $credentialsPath, string $tokenPath)
    {
        $this->tokenPath = $tokenPath;

        $this->client = new Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // SCOPES COMPLETS
        $this->client->addScope([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_MODIFY,
            Gmail::GMAIL_SEND,
            Gmail::MAIL_GOOGLE_COM
        ]);

        // Charger token
        if (file_exists($tokenPath)) {
            $token = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken(
                    $this->client->getRefreshToken()
                );
                file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
            }
        }

        $this->service = new Gmail($this->client);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getTokenPath(): string
    {
        return $this->tokenPath;
    }

    /* ------------------------- LIST -------------------------- */

    public function listMessages(string $userId = 'me', array $params = [])
    {
        return $this->service->users_messages->listUsersMessages($userId, $params);
    }

    public function listMessagesIds(string $query = "", string $userId = "me"): array
    {
        $params = [];
        if ($query !== "") $params['q'] = $query;

        $messages = $this->service->users_messages->listUsersMessages($userId, $params);

        $ids = [];
        foreach ($messages->getMessages() ?? [] as $msg) {
            $ids[] = $msg->getId();
        }
        return $ids;
    }

    /* ------------------------- GET BODY -------------------------- */

    public function getMessageBody(string $id, string $userId = 'me'): string
    {
        $email = $this->service->users_messages->get($userId, $id, ['format' => 'full']);
        $payload = $email->getPayload();

        if ($payload->getBody() && $payload->getBody()->getSize() > 0) {
            return base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        }

        foreach ($payload->getParts() as $part) {
            if ($part->getMimeType() === "text/plain") {
                return base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
            }
        }

        return "(Aucun texte disponible)";
    }

    /* ------------------------- SEND -------------------------- */

    public function sendSimpleEmail(string $to, string $subject, string $body)
    {
        $raw  = "From: me\r\n";
        $raw .= "To: $to\r\n";
        $raw .= "Subject: $subject\r\n\r\n";
        $raw .= $body;

        $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        return $this->sendMessage($encoded);
    }

    public function sendMessage(string $rawMessageBase64, string $userId = 'me')
    {
        $msg = new Gmail\Message();
        $msg->setRaw($rawMessageBase64);
        return $this->service->users_messages->send($userId, $msg);
    }

    /* ------------------------- TRASH / DELETE -------------------------- */

    public function deleteMessage(string $id, string $userId = 'me')
    {
        return $this->service->users_messages->delete($userId, $id);
    }

    public function trashMessage(string $id, string $userId = 'me')
    {
        return $this->service->users_messages->trash($userId, $id);
    }

    public function untrashMessage(string $id, string $userId = 'me')
    {
        return $this->service->users_messages->untrash($userId, $id);
    }

    /* ------------------------- MODIFY -------------------------- */

    public function modifyMessage(string $id, array $addLabels = [], array $removeLabels = [], string $userId = 'me')
    {
        $mods = new Gmail\ModifyMessageRequest();
        $mods->setAddLabelIds($addLabels);
        $mods->setRemoveLabelIds($removeLabels);

        return $this->service->users_messages->modify($userId, $id, $mods);
    }


    public function getMessageMetadata(string $id, string $userId = 'me'): array
{
    $email = $this->service->users_messages->get($userId, $id, ['format' => 'full']);
    $headers = $email->getPayload()->getHeaders();

    $meta = [
        "from" => null,
        "to" => null,
        "subject" => null,
        "date" => null,
        "message_id" => null,
        "thread_id" => $email->getThreadId()
    ];

    foreach ($headers as $h) {
        switch (strtolower($h->getName())) {
            case "from":
                $meta["from"] = $h->getValue();
                break;
            case "to":
                $meta["to"] = $h->getValue();
                break;
            case "subject":
                $meta["subject"] = $h->getValue();
                break;
            case "date":
                $meta["date"] = $h->getValue();
                break;
            case "message-id":
                $meta["message_id"] = $h->getValue();
                break;
        }
    }

    return $meta;
}

public function extractEmail(string $fromHeader): ?string
{
    if (preg_match('/<(.+?)>/', $fromHeader, $m)) {
        return $m[1];
    }
    return $fromHeader;
}


}
