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

    public function createDocument(array $data)
    {
        $title = $data['bf_titre'] ?? 'Nouveau mémo';
        $docConfigKey = $data['bf_documents'];
        $defaultInstances = $this->getDefaultInstance();
        $config = $defaultInstances[$docConfigKey] ?? null;

        if (!$config || !isset($config['url'])) {
            throw new \RuntimeException("Configuration Memo invalide ou manquante.");
        }
        $baseUrl = rtrim($config['url'], '/');
        $generatedUrl = "{$baseUrl}/new/".$this->createDocumentId($title, 35); 
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

    /**
     * Affiche un document Memo.
     * @param array $data Contient 'docConfig', 'entry', 'documentUrl', 'wiki'.
     * @return string Le HTML pour l'affichage Memo.
     */
    public function showDocument(array $data)
    {
        $docConfig = $data['docConfig'];
        $documentUrl = $data['documentUrl'];

        if ($docConfig['iframe'] === true) {
            return "<iframe src='{$documentUrl}' style='width: 100%; height: 1000px; border: none;'></iframe>";
        }
        return "<a target='_blank' href='{$documentUrl}'>Cliquer pour ouvrir le document Memo</a>";
    }
}