<?php

namespace YesWiki\Documents\Field;

use YesWiki\Bazar\Field\BazarField;
use YesWiki\Documents\Service\DocumentsService;
use Psr\Container\ContainerInterface;

use function Symfony\Component\String\u;

/**
 * @Field({"documents"})
 */
class DocumentsField extends BazarField
{
    protected $service = [];
    protected $documentsType = [];

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->service = $this->getService(DocumentsService::class);
        $this->documentsType = $this->getWiki()->getConfigValue('documentsType');
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
        foreach ($this->documentsType as $key => $type) {
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
        return $this->service->showDocument(
            $this->documentsType[$entry['bf_documents']] ?? null,
            $entry ?? null
        );
    }


    public function formatValuesBeforeSave($entry)
    {
        $documentTypeKey = $entry['bf_documents'] ?? null;
        $title = $entry['bf_titre'] ?? '';
        $generatedUrl = '';

        if (!empty($entry['bf_document_url'])) {
            return $entry;
        }

        if ($documentTypeKey && isset($this->documentsType[$documentTypeKey])) {
            $baseUrl = rtrim($this->documentsType[$documentTypeKey]['url'], '/');

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
                    copy('tools/documents/assets/model.docx', $generatedFileName);
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
            ]
        );
    }
}