<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Documents\Service\DocumentProvider;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

class EtherpadDocumentProvider extends DocumentProvider
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
    public function checkConfig(array $config) //
    {
        return $config;
    }

    /**
     * Crée un nouveau document Etherpad et retourne son URL.
     * @param array $entry Les données de l'entrée Bazar.
     * @return string L'URL du document créé.
     */
    public function createDocument(array $entry)
    {
        $defaultInstances = $this->getDefaultInstance();
        $config = $defaultInstances[$docConfigKey] ?? null;

        if (!$config || !isset($config['url'])) {
            throw new \RuntimeException("Configuration Etherpad invalide ou manquante.");
        }

        $baseUrl = rtrim($config['url'], '/');
        $title = $entry['bf_titre'] ?? 'Nouveau document';
        $generatedUrl = "{$baseUrl}/p/".$this->createDocumentId($title, 35);
        return $generatedUrl;
    }

    public function getDefaultInstance(): array
    {
        return [
          'etherpad' => [
                  'service' => 'etherpad',
                  'label' => _t('DOCUMENTS_ETHERPAD_LABEL'),
                  'description' => _t('DOCUMENTS_ETHERPAD_DESCRIPTION'),
                  'url' => 'https://pad.yeswiki.net/',
                  'iframe' => true,
          ]
        ];
    }

    /**
     * Affiche un document Etherpad.
     * @param array $data Contient 'docConfig', 'entry', 'documentUrl', 'wiki'.
     * @return string Le HTML pour l'iframe Etherpad.
     */
    public function showDocument(array $data)
    {
        $docConfig = $data['docConfig'];
        $documentUrl = $data['documentUrl'];

        if ($docConfig['iframe'] === true) {
            return "<iframe src='{$documentUrl}' style='width: 100%; height: 1000px; border: none;'></iframe>"; //
        }
        return "<a target='_blank' href='{$documentUrl}'>Cliquer pour ouvrir le document Etherpad</a>";
    }
}