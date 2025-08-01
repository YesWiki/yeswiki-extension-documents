<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Documents\Service\DocumentProvider;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

class HedgedocDocumentProvider extends DocumentProvider
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
        $title = $data['bf_titre'] ?? 'Nouveau document Hedgedoc';
        $docConfigKey = $data['bf_documents'];
        $defaultInstances = $this->getDefaultInstance();
        $config = $defaultInstances[$docConfigKey] ?? null;

        if (!$config || !isset($config['url'])) {
            throw new \RuntimeException("Configuration Hedgedoc invalide ou manquante.");
        }
        $baseUrl = rtrim($config['url'], '/');
        $generatedUrl = "{$baseUrl}/".$this->createDocumentId($title, 35);
        return $generatedUrl;
    }

    public function getDefaultInstance(): array
    {
        return [
            'hedgedoc' => [
                'service' => 'hedgedoc',
                'label' => _t('DOCUMENTS_HEDGEDOC_LABEL'),
                'description' => _t('DOCUMENTS_HEDGEDOC_DESCRIPTION'),
                'url' => 'https://md.yeswiki.net',
                'iframe' => true,
            ],
        ];
    }
}

