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

    /*
     * @param array $docConfig La configuration du document.
     * @param array $entry Les données de l'entrée Bazar.
     * @return string L'URL du document créé.
     */
    public function createDocument(array $docConfig, array $entry)
    {
        $baseUrl = rtrim($docConfig['url'], '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl."/new");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if ($finalUrl) {
            $generatedUrl = $finalUrl;
        } else {
            die(_t(
                'DOCUMENTS_CURL_ERROR',
                [
                    'baseUrl' => "{$baseUrl}/new"
                ]
            ));
        }
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
