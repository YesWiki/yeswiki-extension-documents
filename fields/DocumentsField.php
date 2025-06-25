<?php
namespace YesWiki\Documents\Field;

use YesWiki\Bazar\Field\BazarField;
use Psr\Container\ContainerInterface;
use function Symfony\Component\String\u;


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
            $this->documentType = self::DOCUMENT_TYPE_DEFAULTS;
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
                    'url' => $value['url']
                ];
            } else {
                die("Invalid configuration for document type '$key'. Expected an array with 'label', 'description', and 'url'.");
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
        if (empty($documentUrl)) {
            return "Aucune URL générée";
        }
        $output = '';
        if ($this->documentType[$entry['bf_documents']]["iframe"] === true) {
            $output .= "<iframe src='{$documentUrl}' style='width: 100%; height: 1000px; border: none;'></iframe>";
        }

        return $output . '<a class="btn btn-primary" href="' . $documentUrl . '" target="_blank">Ouvrir le document dans une nouvelle fenêtre</a><br>';
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