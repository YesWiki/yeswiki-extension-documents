<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Documents\Service\DocumentProvider;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

class MemoDocumentProvider extends DocumentProvider
{
    public function __construct(
        ParameterBagInterface $params,
        ContainerInterface $services,
        EntryManager $entryManager,
        FormManager $formManager,
        ListManager $listManager,
        Wiki $wiki
    ) {
        parent::__construct($params, $services, $entryManager, $formManager, $listManager, $wiki);
    }

    /**
     * Check if config input is good enough to be used by Importer
     * @param array $config
     * @return array $config checked config
     */
    public function checkConfig(array $config)
    {
        return $config;
    }

    /*
     * @param array $docConfig La configuration du document.
     * @param array $entry Les données de l'entrée Bazar.
     * @return string L'URL du document créé.
     */
    public function createDocument(array $docConfig, array $entry)
    {
        $title = $entry['bf_titre'] ?? 'Nouveau mémo';
        if (!$docConfig || !isset($docConfig['url'])) {
            throw new \RuntimeException("Configuration Memo invalide ou manquante.");
        }
        $baseUrl = rtrim($docConfig['url'], '/');
        $generatedUrl = "{$baseUrl}/".$this->createDocumentId($title);
        return $generatedUrl;
    }

    public function getDefaultInstance(): array
    {
        return [
          'memo' => [
              'service' => 'memo',
              'label' => _t('DOCUMENTS_MEMO_LABEL'),
              'description' => _t('DOCUMENTS_MEMO_DESCRIPTION'),
              'url' => 'https://memo.yeswiki.pro/',
              'iframe' => true,
          ],
        ];
    }
}
