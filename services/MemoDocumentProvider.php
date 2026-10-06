<?php

namespace YesWiki\Documents\Service;

/** Memo: with an API key the board is created on Memo and belongs to that account, otherwise only its address is made. */
class MemoDocumentProvider extends DocumentProvider
{
    public function providerName(): string
    {
        return 'memo';
    }

    public function createDocument(array $docConfig, array $entry): string
    {
        if (empty($docConfig['url'])) {
            throw new \RuntimeException(_t('DOCUMENTS_PROVIDER_CONFIG_MISSING', ['provider' => 'Memo']));
        }
        $title = (string)($entry['bf_titre'] ?? _t('DOCUMENTS_MEMO_NEW'));
        $baseUrl = rtrim((string)$docConfig['url'], '/');
        $key = $docConfig['credentials'] ?? '';
        if (is_string($key) && $key !== '') {
            return $this->createRemoteBoard($baseUrl, $key, $title);
        }

        return "{$baseUrl}/" . $this->createDocumentId($title);
    }

    /** Asks Memo to create the board and returns the address it answers with. */
    protected function createRemoteBoard(string $baseUrl, string $key, string $title): string
    {
        $ch = curl_init("{$baseUrl}/api/boards");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$key}",
            ],
            CURLOPT_POSTFIELDS => (string)json_encode(['title' => $title]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $answer = is_string($body) ? json_decode($body, true) : null;

        if ($status !== 200 || !is_array($answer) || empty($answer['url']) || !is_string($answer['url'])) {
            throw new \RuntimeException(_t('DOCUMENTS_MEMO_API_ERROR', ['baseUrl' => "{$baseUrl}/api/boards", 'status' => (string)$status, 'reason' => is_array($answer) && is_string($answer['error'] ?? null) ? $answer['error'] : '']));
        }

        return $answer['url'];
    }
}
