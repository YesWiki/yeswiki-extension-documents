<?php

namespace YesWiki\Documents\Handler;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Documents\Service\DocumentsService;
use YesWiki\Documents\Service\OnlyOfficeDocumentProvider;
use YesWiki\Files\Service\Storage;
use YesWiki\Kernel\Performable\RegisteredHandler;

/** `/PageName/onlyoffice?filename=...`: the OnlyOffice server's callback, which rewrites the file only when the server signed it. */
class OnlyOfficeHandler extends YesWikiHandler implements RegisteredHandler
{
    private const STATUS_READY_FOR_SAVING = 2;

    public static function performableName(): string
    {
        return 'onlyoffice';
    }

    public function run(): string
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        if (!is_array($data)) {
            return $this->answer(1, _t('DOCUMENTS_BAD_REQUEST'));
        }

        $signed = $this->verifiedPayload($data);
        if ($signed === null) {
            return $this->answer(1, _t('DOCUMENTS_CALLBACK_NOT_SIGNED'));
        }
        [$payload, $serverUrl] = $signed;

        if (($payload['status'] ?? null) != self::STATUS_READY_FOR_SAVING) {
            return $this->answer(0);
        }

        $downloadUri = $payload['url'] ?? '';
        if (!is_string($downloadUri) || !$this->isDownloadUriAllowed($downloadUri, $serverUrl)) {
            return $this->answer(1, _t('DOCUMENTS_CALLBACK_FOREIGN_URL'));
        }

        $filename = $this->getRequest()->query->get('filename');
        $target = $this->targetPath($filename);
        if ($target === null) {
            return $this->answer(1, _t('DOCUMENTS_WRONG_NON_EXISTENT_FILENAME', ['type' => (string)$filename]));
        }

        $newData = $this->download($downloadUri);
        if ($newData === null) {
            return $this->answer(1, _t('DOCUMENTS_BAD_RESPONSE'));
        }
        $this->getService(Storage::class)->write($target, $newData);

        return $this->answer(0);
    }

    /**
     * The callback's body as one configured OnlyOffice server signed it, with that server's url, or null when no secret verifies it.
     *
     * @param array<string, mixed> $data
     *
     * @return array{0: array<string, mixed>, 1: string}|null
     */
    private function verifiedPayload(array $data): ?array
    {
        $token = $this->tokenFromRequest($data);
        if ($token === null) {
            return null;
        }
        $provider = $this->getService(OnlyOfficeDocumentProvider::class);
        foreach ($this->getService(DocumentsService::class)->getAllDocumentsService() as $docConfig) {
            if ($docConfig['provider'] !== $provider->providerName()) {
                continue;
            }
            $secret = $provider->secret($docConfig);
            if ($secret === '') {
                continue;
            }
            try {
                $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            } catch (\Throwable) {
                continue;
            }
            $payload = json_decode((string)json_encode($decoded), true);
            if (!is_array($payload)) {
                continue;
            }
            $payload = is_array($payload['payload'] ?? null) ? $payload['payload'] : $payload;

            return [$payload, (string)$docConfig['url']];
        }

        return null;
    }

    /**
     * The callback's token, from its body or from the Authorization header.
     *
     * @param array<string, mixed> $data
     */
    private function tokenFromRequest(array $data): ?string
    {
        if (!empty($data['token']) && is_string($data['token'])) {
            return $data['token'];
        }
        $header = (string)$this->getRequest()->headers->get('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /** Whether the download address is on the OnlyOffice server whose secret signed the callback. */
    private function isDownloadUriAllowed(string $downloadUri, string $serverUrl): bool
    {
        $expected = parse_url($serverUrl, PHP_URL_HOST);
        $given = parse_url($downloadUri, PHP_URL_HOST);
        if (!is_string($expected) || !is_string($given) || $expected === '' || $given === '') {
            return false;
        }
        if (!in_array(strtolower((string)parse_url($downloadUri, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }

        return strcasecmp($expected, $given) === 0;
    }

    /** The instance-relative path of the file to rewrite, or null unless it is an existing office file right under `files/`. */
    private function targetPath(mixed $filename): ?string
    {
        if (!is_string($filename) || $filename === '' || basename($filename) !== $filename || str_starts_with($filename, '.')) {
            return null;
        }
        if (!isset(OnlyOfficeDocumentProvider::DOCUMENT_TYPES[strtolower(pathinfo($filename, PATHINFO_EXTENSION))])) {
            return null;
        }
        $path = OnlyOfficeDocumentProvider::FILES_DIRECTORY . '/' . $filename;

        return $this->getService(Storage::class)->fileExists($path) ? $path : null;
    }

    /** The edited file's bytes, fetched from the OnlyOffice server. */
    protected function download(string $url): ?string
    {
        $bytes = @file_get_contents($url);

        return $bytes === false ? null : $bytes;
    }

    /** The JSON answer the OnlyOffice server expects. */
    private function answer(int $error, ?string $message = null): string
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $body = ['error' => $error];
        if ($message !== null) {
            $body['message'] = $message;
        }

        return (string)json_encode($body);
    }
}
