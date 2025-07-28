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
        /*     'service' => 'onlyoffice', */
        /*     'label' => 'Docx Only-office', */
        /*     'description' => 'Document docx Only-office', */
        /*     'url' => 'https://onlyoffice.yeswiki.net', */
        /*     'iframe' => false, */
        /*     'need-credentials' => true */
        /* ] */

    ];

    public function __construct(Wiki $wiki)
    {
        $this->wiki = $wiki;
        $this->initDocumentsConfig($this->wiki->config['documentsType'] ?? self::DOCUMENTS_TYPE_DEFAULT);
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
                    'iframe' => $value['iframe'] ?? false, // S'assurer que iframe est toujours défini
                    'need-credentials' => $value['need-credentials'] ?? false
                ];
            } else {
                die("Invalid configuration for document type '$key'. Expected an array with 'label', 'description', 'service' and 'url'.");
            }
        }
        // dump($this->getWiki()->getConfigValue('documentsCredentials')); // Garder pour le débogage si besoin
        foreach ($result as $key => $value) {
            if ($value['need-credentials']) {
                $credentials = $this->wiki->config['documentsCredentials'][$key] ?? null;
                if (empty($credentials)) {
                    die("Missing configuration for document type '$key'. Expected not empty value in the configuration config['documentsCredentials']['$key'].");
                }
            }
        }
        $this->wiki->config['documentsType'] = $result;
    }

    public function showDocument($docConfig, $documentUrl)
    {
        if (empty($documentUrl)) {
            return "Aucune URL générée";
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
                          "lang" => "fr"
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
            if ($docConfig['iframe'] === true) {
                $output .= "<iframe src='{$documentUrl}' style='width: 100%; height: 1000px; border: none;'></iframe>";
            } else {
                $output .= '<a target="_blank" href="'.$documentUrl.'">Voir le document</a>';
            }
        }
        return $output;

    }
}
