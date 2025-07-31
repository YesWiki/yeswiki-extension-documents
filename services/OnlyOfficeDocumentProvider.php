<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Documents\Service\DocumentProvider;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

class OnlyOfficeDocumentProvider extends DocumentProvider
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

    public function createDocument(array $data)
    {
        return;
    }
    public function getDefaultInstance(): array
    {
        return [
          /* 'onlyoffice-docx' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_DOC_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_DOC_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => false, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'docx'] */
          /* ], */
          /* 'onlyoffice-xlsx' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_XLS_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_XLS_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => false, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'xlsx'] */
          /* ], */
          /* 'onlyoffice-pptx' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_PPT_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_PPT_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => false, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'pptx'] */
          /* ], */
          /* 'onlyoffice-md' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_MD_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_MD_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => false, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'md'] */
          /* ], */
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
