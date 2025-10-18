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
    }

    protected function renderInput($entry)
    {
        if (!empty($entry[$this->propertyName])) {
          if (!empty($entry[$this->propertyName]['documentUrl'])) {
            return "
                <div class='control-group form-group input-text input text'>
                    <label class='control-label col-sm-3'>" . _t('DOCUMENTS_LINK_ACCESS') . "</label>
                    <div class='controls col-sm-9'>"._t('DOCUMENTS_URL').": {$entry[$this->propertyName]['documentUrl']}
                    </div>
                </div>";

          } else {
            return '<div class="alert alert-danger">'._t('DOCUMENTS_URL_NOT_FOUND').'.</div>';
          }
        }
        $options = [];
        $services = $this->service->getAllDocumentsService();
        foreach ($services as $key => $type) {
            $options[$key] = "<h4>{$type['label']} <small> {$type['url']} </small> </h4>
                                    <p>{$type['description']}</p>";
        }
        return $this->render('@documents/radio-document-types.twig', [
            'options' => $options,
            'value' => $this->getValue($entry),
            'displayFilterLimit' => false
        ]);
    }

    protected function renderStatic($entry)
    {
      $services = $this->service->getAllDocumentsService();
      return $this->service->showDocument(
            $services[$entry[$this->propertyName]['documentType']] ?? null,
            $entry ?? [],
            $this->propertyName,
        );
    }

    public function getValueStructure()
    {
      return [$this->propertyName => [
        'documentType' => ['_mode_' => 'single', '_type_' => 'string'],
        'documentUrl' => ['_mode_' => 'single', '_type_' => 'string'],
      ]
      ];
    }

    public function formatValuesBeforeSave($entry)
    {
        $documentTypeKey = $entry[$this->propertyName]['documentType'] ?? null;

        // the document was already created
        if (!empty($entry[$this->propertyName]['documentUrl'])) {
            return $entry;
        }
        $services = $this->service->getAllDocumentsService();
        if ($documentTypeKey && isset($services[$documentTypeKey])) {
            $entry[$this->propertyName]['documentUrl'] = $this->service->createDocument(
                $services[$documentTypeKey],
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
