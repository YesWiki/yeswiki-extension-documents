<?php

namespace YesWiki\Documents\Service;

/** HedgeDoc: `/new` redirects to a fresh note, whose address is kept. */
class HedgedocDocumentProvider extends DocumentProvider
{
    public function providerName(): string
    {
        return 'hedgedoc';
    }

    public function createDocument(array $docConfig, array $entry): string
    {
        $baseUrl = rtrim((string)($docConfig['url'] ?? ''), '/');
        $finalUrl = $this->followNew("{$baseUrl}/new");
        if ($finalUrl === '') {
            throw new \RuntimeException(_t('DOCUMENTS_CURL_ERROR', ['baseUrl' => "{$baseUrl}/new"]));
        }

        return $finalUrl;
    }

    /** Where HedgeDoc lands after the redirects of $url, '' when it cannot be reached. */
    protected function followNew(string $url): string
    {
        if ($url === '') {
            return '';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $ok = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        return $ok === false ? '' : $finalUrl;
    }
}
