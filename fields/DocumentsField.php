<?php

namespace YesWiki\Documents\Field;

use YesWiki\Bazar\Field\BazarField;
use YesWiki\Documents\Service\DocumentsService;
use Psr\Container\ContainerInterface;

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
                    <label class='control-label col-sm-3'>" . _t('DOCUMENTS_LINK_ACCESS') . "</label>
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
            $entry['bf_document_url'] = $this->service->createDocument(
                $this->documentsType[$documentTypeKey],
                $entry
            );
        }
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
