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
    protected $params;
    protected $services;
    protected $entryManager;
    protected $formManager;
    protected $listManager;
    protected $wiki;
    protected $config;


    public function __construct(
        ParameterBagInterface $params,
        ContainerInterface $services,
        EntryManager $entryManager,
        FormManager $formManager,
        ListManager $listManager,
        Wiki $wiki
    ) {
        $this->params = $params;
        $this->services = $services;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->listManager = $listManager;
        $this->wiki = $wiki;
        $config = $this->checkConfig($params->get('dataSources'));
        $this->config = $config;
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

    public function createDocument(array $entry)
    {
        dump($this->wiki->config, $entry['bf_documents']);
        exit;
        $baseUrl = rtrim($this->documentsType[$documentTypeKey]['url'], '/');
        $generatedUrl = "{$baseUrl}/p/".$this->createDocumentId($entry['bf_titre'], 35);
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
    public function showDocument(array $data)
    {
        return;
    }

    // HELPERS
    protected function getService($class)
    {
        return $this->services->get($class);
    }
}
