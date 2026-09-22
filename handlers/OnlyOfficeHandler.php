<?php
// Handler /onlyoffice : rappel appelé par le serveur OnlyOffice quand un document
// a été modifié. Il n'écrit le fichier que si le rappel est signé par ce serveur.

namespace YesWiki\Documents;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;
use YesWiki\Core\YesWikiHandler;

class OnlyOfficeHandler extends YesWikiHandler
{
    private const PROVIDER = 'onlyoffice';
    private const STATUS_READY_FOR_SAVING = 2;
    private const FILES_DIRECTORY = 'files';

    public function run()
    {
        $body = file_get_contents('php://input');
        if ($body === false || $body === '') {
            return $this->answer(1, _t('DOCUMENTS_BAD_REQUEST'));
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return $this->answer(1, _t('DOCUMENTS_BAD_REQUEST'));
        }

        $payload = $this->verifiedPayload($data);
        if ($payload === null) {
            return $this->answer(1, _t('DOCUMENTS_CALLBACK_NOT_SIGNED'));
        }

        if (($payload['status'] ?? null) != self::STATUS_READY_FOR_SAVING) {
            return $this->answer(0);
        }

        $downloadUri = $payload['url'] ?? '';
        if (!$this->isDownloadUriAllowed($downloadUri)) {
            return $this->answer(1, _t('DOCUMENTS_CALLBACK_FOREIGN_URL'));
        }

        $target = $this->targetPath($this->getRequest()->query->get('filename'));
        if ($target === null) {
            return $this->answer(1, _t('DOCUMENTS_WRONG_NON_EXISTENT_FILENAME', [
                'type' => $this->getRequest()->query->get('filename'),
            ]));
        }

        $newData = file_get_contents($downloadUri);
        if ($newData === false) {
            return $this->answer(1, _t('DOCUMENTS_BAD_RESPONSE'));
        }
        file_put_contents($target, $newData, LOCK_EX);

        return $this->answer(0);
    }

    /** Le corps du rappel tel que le serveur OnlyOffice l'a signé, ou null si la signature ne vaut rien. */
    private function verifiedPayload(array $data)
    {
        $secret = $this->wiki->config['documentsCredentials'][self::PROVIDER] ?? '';
        if (!is_string($secret) || $secret === '') {
            return null;
        }

        $token = $this->tokenFromRequest($data);
        if ($token === null) {
            return null;
        }

        try {
            $payload = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Throwable $exception) {
            return null;
        }

        $payload = json_decode(json_encode($payload), true);
        if (!is_array($payload)) {
            return null;
        }

        return $payload['payload'] ?? $payload;
    }

    /** Le jeton du rappel, pris dans le corps ou dans l'en-tête Authorization. */
    private function tokenFromRequest(array $data)
    {
        if (!empty($data['token']) && is_string($data['token'])) {
            return $data['token'];
        }

        $header = $this->getRequest()->headers->get('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /** L'url de téléchargement pointe-t-elle bien sur le serveur OnlyOffice configuré ? */
    private function isDownloadUriAllowed($downloadUri): bool
    {
        if (!is_string($downloadUri) || $downloadUri === '') {
            return false;
        }

        $expected = parse_url($this->configuredServerUrl(), PHP_URL_HOST);
        $given = parse_url($downloadUri, PHP_URL_HOST);
        if (empty($expected) || empty($given)) {
            return false;
        }
        if (!in_array(strtolower((string) parse_url($downloadUri, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }

        return strcasecmp($expected, $given) === 0;
    }

    /** L'url du serveur OnlyOffice, quelle que soit la casse de la clé de configuration. */
    private function configuredServerUrl(): string
    {
        foreach ($this->wiki->config as $key => $value) {
            if (preg_match('/^documents(.*)Url$/i', $key, $matches)
                && strtolower($matches[1]) === self::PROVIDER
                && is_string($value)) {
                return $value;
            }
        }

        return '';
    }

    /** Le chemin du fichier à réécrire, ou null s'il sort du dossier des fichiers ou n'existe pas. */
    private function targetPath($filename)
    {
        if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) {
            return null;
        }

        $directory = realpath(self::FILES_DIRECTORY);
        if ($directory === false) {
            return null;
        }
        $target = realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if ($target === false || !is_file($target)) {
            return null;
        }
        if (strpos($target, $directory . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $target;
    }

    /** La réponse JSON attendue par le serveur OnlyOffice. */
    private function answer(int $error, ?string $message = null): string
    {
        header('Content-Type: application/json; charset=utf-8');
        $body = ['error' => $error];
        if ($message !== null) {
            $body['message'] = $message;
        }

        return json_encode($body);
    }
}
