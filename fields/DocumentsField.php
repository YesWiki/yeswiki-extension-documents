<?php

namespace YesWiki\Documents\Field;

use YesWiki\Bazar\Field\BazarField;
use Psr\Container\ContainerInterface;

use function Symfony\Component\String\u;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * @Field({"documents"})
 */
class DocumentsField extends BazarField
{
    protected const DOCUMENT_TYPE_DEFAULTS = [
        'etherpad' => [
            'label' => 'Etherpad',
            'description' => 'Un document collaboratif simple',
            'url' => 'https://pad.yeswiki.net/',
            'iframe' => true,
        ],
        'memo' => [
            'label' => 'Memo',
            'description' => 'Un tableau de post-it collaboratif',
            'url' => 'https://memo.yeswiki.pro/',
            'iframe' => false,
        ],
        'hedgedoc' => [
            'label' => 'HedgeDoc',
            'description' => 'Un editeur de markdown collaboratif',
            'url' => 'https://md.yeswiki.net',
            'iframe' => false,
        ],
        'onlyoffice-doc' => [
            'label' => 'Docx Only-office',
            'description' => 'Document docx Only-office',
            'url' => 'https://onlyoffice.yeswiki.net',
            'iframe' => false,
            'need-credentials' => true

        ]

    ];

    protected $documentType = [];

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $conf = $this->getWiki()->getConfigValue("documentType");
        if (isset($conf) && is_array($conf)) {
            $this->documentType = $this->parseConfig($conf);
        } else {
            $this->documentType = $this->parseConfig(self::DOCUMENT_TYPE_DEFAULTS);
        }
    }

    protected function parseConfig($config)
    {
        $result = [];
        foreach ($config as $key => $value) {
            if (is_array($value) && isset($value['label'], $value['description'], $value['url'])) {
                $result[$key] = [
                    'label' => $value['label'],
                    'description' => $value['description'],
                    'url' => $value['url'],
                    'iframe' => $value['iframe'] ?? false, // S'assurer que iframe est toujours défini
                    'need-credentials' => $value['need-credentials'] ?? false
                ];
            } else {
                die("Invalid configuration for document type '$key'. Expected an array with 'label', 'description', and 'url'.");
            }
        }
        // dump($this->getWiki()->getConfigValue('documentsCredentials')); // Garder pour le débogage si besoin
        foreach ($result as $key => $value) {
            if ($value['need-credentials']) {
                $credentials = $this->getWiki()->getConfigValue('documentsCredentials')[$key] ?? null;
                if (empty($credentials)) {
                    die("Missing configuration for document type '$key'. Expected not empty value in the configuration config['documentsCredentials']['$key'].");
                }
                // Vous pouvez ajouter d'autres vérifications si d'autres credentials sont nécessaires pour OnlyOffice.
                // Par exemple, si vous avez besoin d'une 'default_document_url'
                if ($key === 'onlyoffice-doc' && !isset($credentials['default_document_url'])) {
                    // C'est un exemple, si vous voulez que OnlyOffice ouvre un document par défaut au lieu de créer un blanc
                    // Ou vous pouvez gérer la création d'un document vierge directement.
                }
            }
        }
        return $result;
    }

    protected function renderInput($entry)
    {
        if (!empty($entry['bf_document_url'])) {
            return "
                <div class='control-group form-group input-text input text'>
                    <label class='control-label col-sm-3'>Lien d'accès au document</label>
                    <div class='controls col-sm-9'>
                        <div class='input-group'>
                            <input class='form-control input-xxlarge' name='bf_document_url' value={$entry['bf_document_url']} readonly />
                            <input type='hidden' name='bf_documents' value={$entry['bf_documents']} />
                        </div>
                    </div>
                </div>
            ";
        }
        $options = [];
        foreach ($this->documentType as $key => $type) {
            $options[$key] = "<h4>{$type['label']} <small> {$type['url']} </small> </h4>
                                     <p>{$type['description']}</p>";
        }
        return $this->render('@bazar/inputs/radio.twig', [
            'options' => $options,
            'value' => $this->getValue($entry),
            'displayFilterLimit' => false
        ]);
    }

    protected function renderStatic($entry)
    {
        $documentUrl = $entry['bf_document_url'] ?? '';
        $documentTypeKey = $entry['bf_documents'] ?? null;

        if (empty($documentUrl)) {
            return "Aucune URL générée";
        }

        $output = '';
        
        if ($documentTypeKey == 'onlyoffice-doc') {
            $doc = pathinfo($documentUrl);
            $config = [
                'document' => [
                    "fileType" => $doc['extension'],
                    "key" => $doc['filename'],
                    "title" => $doc['basename'],
                    "url" => $documentUrl,
                ],
                'documentType' => 'word',
                'height' => '800px',
                'width' => '100%',
            ];
            dump($documentUrl);
            $jwt = JWT::encode($config, $this->getWiki()->getConfigValue('documentsCredentials')[$documentTypeKey], 'HS256');
            $output .= <<<HTML
<div id="onlyoffice-doc"></div>
<script type="text/javascript" src="{$this->documentType[$documentTypeKey]['url']}/web-apps/apps/api/documents/api.js"></script>
<script>
const config = {
  document: {
    fileType: "{$doc['extension']}",
    key: "{$doc['filename']}",
    title: "{$doc['basename']}",
    url: "{$documentUrl}",
  },
  documentType: "word",
  height: "1500px",
  width: "100%",
  token: "$jwt"
};

console.log("Generated JWT:", config.token);
const docEditor = new DocsAPI.DocEditor("onlyoffice-doc", config);

</script>
HTML;
        } else if ($this->documentType[$entry['bf_documents']]["iframe"] === true) {
            $output .= "<iframe src='{$documentUrl}' style='width: 100%; height: 1000px; border: none;'></iframe>";
        }

        return $output;
    }


    public function formatValuesBeforeSave($entry)
    {
        $documentTypeKey = $entry['bf_documents'] ?? null;
        $title = $entry['bf_titre'] ?? '';
        $generatedUrl = '';

        if (!empty($entry['bf_document_url'])) {
            return $entry;
        }

        if ($documentTypeKey && isset($this->documentType[$documentTypeKey])) {
            $baseUrl = rtrim($this->documentType[$documentTypeKey]['url'], '/');

            $slug = (string) u($title)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9\s-]/', '')
                ->replaceMatches('/\s+/', '_')
                ->trim('_');

            $uniqueId = time() . mt_rand(1000, 9999);

            switch ($documentTypeKey) {
                case 'etherpad':
                    $generatedUrl = "{$baseUrl}/p/{$slug}-{$uniqueId}";
                    break;
                case 'memo':
                    $generatedUrl = "{$baseUrl}/{$slug}-{$uniqueId}";
                    break;
                case 'hedgedoc':
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
                        die("Erreur cURL lors de la récupération de l'URL HedgeDoc. url: {$baseUrl}/new");
                    }
                    break;
                case 'onlyoffice-doc':
                    $generatedFileName = "files/{$slug}-{$uniqueId}.docx";
                    copy ('tools/documents/assets/model.docx', $generatedFileName);
                    $generatedUrl = "{$this->getWiki()->getConfigValue("base_url")}";
                    $generatedUrl = str_replace('/?', "/{$generatedFileName}", $generatedUrl);
                    break;
                default:
                    $generatedUrl = "{$baseUrl}/doc/{$slug}-{$uniqueId}";
                    break;
            }
        }

        $entry['bf_document_url'] = $generatedUrl;

        return $entry;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'reservation_button' => '<a class="btn btn-primary" href="#"><i class="fa fa-plus"></i>&nbsp;Je profite de ce trajet</a>'
            ]
        );
    }
}

