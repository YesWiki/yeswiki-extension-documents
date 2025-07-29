<?php

namespace YesWiki\Documents\Service;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Wiki;

class DocumentsService
{
    protected $wiki;
    protected const DOCUMENTS_TYPE_DEFAULT = [
        'etherpad' => [
            'service' => 'etherpad',
            'label' => 'Etherpad',
            'description' => 'Un document collaboratif simple',
            'url' => 'https://pad.yeswiki.net/',
            'iframe' => true,
        ],
        'memo' => [
            'service' => 'memo',
            'label' => 'Memo',
            'description' => 'Un tableau de post-it collaboratif',
            'url' => 'https://memo.yeswiki.pro/',
            'iframe' => false,
        ],
        'hedgedoc' => [
            'service' => 'hedgedoc',
            'label' => 'HedgeDoc',
            'description' => 'Un editeur de markdown collaboratif',
            'url' => 'https://md.yeswiki.net',
            'iframe' => false,
        ],
        /* 'onlyoffice-doc' => [ */
        /* 'service' => 'onlyoffice', */
        /* 'label' => 'Docx Only-office', */
        /* 'description' => 'Document docx Only-office', */
        /* 'url' => 'https://onlyoffice.yeswiki.net', */
        /* 'iframe' => false, */
        /* 'need-credentials' => true */
        /* ] */

    ];

    public function __construct(Wiki $wiki)
    {
        $this->wiki = $wiki;
        $defaultConfigWithKeys = [
            'etherpad' => [
                'service' => 'etherpad',
                'label' => _t('DOCUMENTS_ETHERPAD_LABEL'),
                'description' => _t('DOCUMENTS_ETHERPAD_DESCRIPTION'),
                'url' => 'https://pad.yeswiki.net/',
                'iframe' => true,
            ],
            'memo' => [
                'service' => 'memo',
                'label' => _t('DOCUMENTS_MEMO_LABEL'),
                'description' => _t('DOCUMENTS_MEMO_DESCRIPTION'),
                'url' => 'https://memo.yeswiki.pro/',
                'iframe' => false,
            ],
            'hedgedoc' => [
                'service' => 'hedgedoc',
                'label' => _t('DOCUMENTS_HEDGEDOC_LABEL'),
                'description' => _t('DOCUMENTS_HEDGEDOC_DESCRIPTION'),
                'url' => 'https://md.yeswiki.net',
                'iframe' => false,
            ],
            /* 'onlyoffice-doc' => [ */
            /* 'service' => 'onlyoffice', */
            /* 'label' => _t('DOCUMENTS_ONLYOFFICE_DOC_LABEL'), */
            /* 'description' => _t('DOCUMENTS_ONLYOFFICE_DOC_DESCRIPTION'), */
            /* 'url' => 'https://onlyoffice.yeswiki.net', */
            /* 'iframe' => false, */
            /* 'need-credentials' => true */
            /* ] */
        ];

        $initialConfig = $this->wiki->getConfigValue('documentsType') ?? [];
        $mergedConfig = array_replace_recursive($defaultConfigWithKeys, $initialConfig);

        $this->initDocumentsConfig($mergedConfig);
    }

    /** initiDocumentsConfig() - validate config and add default values, if needed.
     *
     * @return void
     */
    public function initDocumentsConfig($config)
    {
        $result = [];
        foreach ($config as $key => $value) {
            if (is_array($value) && isset($value['label'], $value['description'], $value['url'], $value['service'])) {
                $result[$key] = [
                    'service' => $value['service'],
                    'label' => $value['label'],
                    'description' => $value['description'],
                    'url' => $value['url'],
                    'iframe' => $value['iframe'] ?? false,
                    'need-credentials' => $value['need-credentials'] ?? false
                ];
            } else {
               
                die(_t('DOCUMENTS_INVALID_CONFIG_ERROR', $key));
            }
        }
        // dump($this->getWiki()->getConfigValue('documentsCredentials'));
        foreach ($result as $key => $value) {
            if ($value['need-credentials']) {
                $credentials = $this->wiki->config['documentsCredentials'][$key] ?? null;
                if (empty($credentials)) {
                   
                    die(_t('DOCUMENTS_MISSING_CREDENTIALS_ERROR', $key, $key));
                }
            }
        }
        $this->wiki->config['documentsType'] = $result;
    }

    public function showDocument($docConfig, array $entry = [])
    {
        $documentUrl = $entry['bf_document_url'] ?? null;
        if (empty($documentUrl)) {
           
            return _t('DOCUMENTS_NO_URL_GENERATED');
        }
        $output = '';

        if ($docConfig['service'] == 'onlyoffice') {
            $doc = pathinfo($documentUrl);
            $config = [
                        'document' => [
                            "fileType" => $doc['extension'],
                            "key" => $doc['filename'],
                            "title" => $doc['basename'],
                            "url" => $documentUrl,
                        ],
                        'editorConfig' => [
                            'callbackUrl' => $this->getWiki()->href('onlyoffice', '', 'filename='.$doc['basename'], false),
                            "user" => [
                                "id" => $this->getWiki()->GetUsername(),
                                "name" => $this->getWiki()->GetUsername(),
                            ],
                            "customization" => [
                                "features" => [
                                    "featuresTips" => false
                                ]
                            ],
                            "lang" => $GLOBALS['prefered_language'] ?? 'fr',
                        ],
                        'documentType' => 'word',
                        'height' => '1000px',
                        'width' => '100%',
                    ];
            $config['token'] = JWT::encode($config, $this->getWiki()->getConfigValue('documentsCredentials')[$documentTypeKey], 'HS256');
            $jsconfig = json_encode($config);
            $output .= <<<HTML
<div id="onlyoffice-doc"></div>
<script type="text/javascript" src="{$docConfig['url']}/web-apps/apps/api/documents/api.js"></script>
<script>
const config = {$jsconfig};
const docEditor = new DocsAPI.DocEditor("onlyoffice-doc", config);

</script>
HTML;
        } else {
           
            $titre = $entry['bf_titre'] ?? _t('DOCUMENTS_UNKNOWN_TITLE');
           
            $statut = $entry['bf_statut'] ?? _t('DOCUMENTS_UNKNOWN_STATUS');

            if ($docConfig['iframe'] === true) {
                $output .= "<iframe src='{$documentUrl}' style='width: 100%; height: 1000px; border: none;'></iframe>";
            }

            $baseUrl = rtrim($this->wiki->getConfigValue('base_url'), '/');
            $editLink = "{$baseUrl}{$entry['id_fiche']}/edit";

            $output .= "<small><a target='_blank' href='{$documentUrl}'><b>{$titre} </b></a> (" . "{$docConfig['label']} - " . _t('DOCUMENTS_STATUS') . ": {$statut}) <a target='_blank' href='{$editLink}'>" . _t('DOCUMENTS_MODIFY') . "</a></small>";
        }
        return $output;

    }
}