<?php

namespace YesWiki\Documents\Service;

/** Etherpad: a pad is created by its first visitor, so its address is all there is to make. */
class EtherpadDocumentProvider extends DocumentProvider
{
    public function providerName(): string
    {
        return 'etherpad';
    }

    public function createDocument(array $docConfig, array $entry): string
    {
        if (empty($docConfig['url'])) {
            throw new \RuntimeException(_t('DOCUMENTS_PROVIDER_CONFIG_MISSING', ['provider' => 'Etherpad']));
        }
        $baseUrl = rtrim((string)$docConfig['url'], '/');
        $title = (string)($entry['bf_titre'] ?? _t('DOCUMENTS_ETHERPAD_NEW'));

        return "{$baseUrl}/p/" . $this->createDocumentId($title, 35);
    }
}
