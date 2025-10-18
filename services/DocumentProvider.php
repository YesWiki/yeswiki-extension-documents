<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

use function Symfony\Component\String\u;

abstract class DocumentProvider
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
        $config = $this->checkConfig($params->get('documentsType') ?? []);
        $this->config = $config;
    }

    /**
     * Check if config input is good enough to be used by the DocumentProvider
     * @param array $config
     * @return array $config checked config
     */
    public function checkConfig(array $config)
    {
        return $config;
    }

    public function createDocumentId($title, $maxSize = 255)
    {
        $slug = (string) u($title)
               ->ascii()
               ->lower()
               ->replaceMatches('/[^a-z0-9\s-]/', '')
               ->replaceMatches('/\s+/', '_')
               ->trim('_');
        $uniqueId = time() . mt_rand(1000, 9999);
        if (strlen($slug) > ($maxSize - 15)) {
            $slug = substr($slug, 0, ($maxSize - 15));
        }
        return $slug.'-'.$uniqueId;
    }

    public function createDocument(array $docConfig, array $entry)
    {
        return;
    }

    public function getDefaultInstance()
    {
        return [];
    }

    public function showDocument(array $data)
    {
        $docConfig = $data['docConfig'];
        $documentUrl = $data['documentUrl'];

        if ($docConfig['iframe'] === true) {
            return "<iframe src='{$documentUrl}' class='full-width' style='width: 100%; min-height: 1000px; border: none;'></iframe>";
        }
        return "<a target='_blank' class='btn btn-primary btn-xs' href='{$documentUrl}'>"._t('DOCUMENTS_OPEN_DOCUMENT')."</a><br />";
    }
}
