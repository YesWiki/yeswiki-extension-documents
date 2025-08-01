<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Documents\Service\DocumentProvider;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;
use Firebase\JWT\JWT;

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
        $name = $this->createDocumentId($entry['bf_titre']);
        $generatedFileName = "files/{$name}.{$docConfig['options']['filetype']}";
        copy("tools/documents/assets/new.{$docConfig['options']['filetype']}", $generatedFileName);
        $generatedUrl = $this->wiki->config["base_url"];
        $generatedUrl = str_replace('/?', "/{$generatedFileName}", $generatedUrl);
        return $generatedUrl;
    }

    /**
     * Affiche un document Onlyoffice.
     * @param array $data Contient 'docConfig', 'entry', 'documentUrl', 'wiki'.
     * @return string Le HTML pour l'iframe Etherpad.
     */
    public function showDocument(array $data)
    {
        $docConfig = $data['docConfig'];
        $documentUrl = $data['documentUrl'];

        if ($docConfig['iframe'] === true) {
            $doc = pathinfo($documentUrl);
            $docTypes = [
              'docx' => 'word',
              'pdf' => 'pdf',
              'xlsx' => 'cell',
              'pptx' => 'slide',
              'md' => 'word',
              'vsdx' => 'diagram'
            ];
            $config = [
                        'document' => [
                            "fileType" => $doc['extension'],
                            "key" => $doc['filename'],
                            "title" => $doc['basename'],
                            "url" => $documentUrl,
                        ],
                        'editorConfig' => [
                            'callbackUrl' => $this->wiki->href('onlyoffice', '', 'filename='.$doc['basename'], false),
                            "user" => [
                                "id" => $this->wiki->GetUsername(),
                                "name" => $this->wiki->GetUsername(),
                            ],
                            "customization" => [
                                "features" => [
                                    "featuresTips" => false
                                ]
                            ],
                            "lang" => $GLOBALS['prefered_language'] ?? 'fr',
                        ],
                        'documentType' => $docTypes[$doc['filename']],
                        'height' => '1000px',
                        'width' => '100%',
                    ];
            $config['token'] = JWT::encode($config, $this->wiki->config['documentsCredentials'][$docConfig['provider-name']], 'HS256');
            $jsconfig = json_encode($config);
            return <<<HTML
<div id="onlyoffice-doc-{$doc['filename']}"></div>
<script type="text/javascript" src="{$docConfig['url']}/web-apps/apps/api/documents/api.js"></script>
<script>
const config = {$jsconfig};
const docEditor = new DocsAPI.DocEditor("onlyoffice-doc-{$doc['filename']}", config);

</script>
HTML;
        }
        return "<a target='_blank' class='btn btn-primary btn-xs' href='{$documentUrl}'>"._t('DOCUMENTS_OPEN_DOCUMENT')."</a><br />";
    }


    public function getDefaultInstance(): array
    {
        return [
          /* 'onlyoffice-docx' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_DOC_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_DOC_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => true, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'docx'] */
          /* ], */
          /* 'onlyoffice-xlsx' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_XLS_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_XLS_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => true, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'xlsx'] */
          /* ], */
          /* 'onlyoffice-pptx' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_PPT_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_PPT_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => true, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'pptx'] */
          /* ], */
          /* 'onlyoffice-md' => [ */
          /*   'service' => 'onlyoffice', */
          /*   'label' => _t('DOCUMENTS_ONLYOFFICE_MD_LABEL'), */
          /*   'description' => _t('DOCUMENTS_ONLYOFFICE_MD_DESCRIPTION'), */
          /*   'url' => 'https://onlyoffice.yeswiki.net', */
          /*   'iframe' => true, */
          /*   'need-credentials' => true, */
          /*   'options' => [ 'filetype' => 'md'] */
          /* ], */
        ];
    }
}
