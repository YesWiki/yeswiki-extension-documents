<?php

namespace YesWiki\Documents\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Content\Attribute\Field;
use YesWiki\Content\Field\BazarField;
use YesWiki\Documents\Service\DocumentsService;

/** `documents`: the visitor picks a kind of document, and saving the entry creates it on that service. */
#[Field(['documents'])]
class DocumentsField extends BazarField
{
    protected DocumentsService $documentsService;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->documentsService = $this->getService(DocumentsService::class);
    }

    protected function renderInput($entry)
    {
        $stored = $this->storedValue($entry);
        if (isset($entry['tag']) && $stored['documentUrl'] !== '') {
            return $this->render('@documents/document-link-input.twig', [
                'url' => $stored['documentUrl'],
            ]);
        }

        $services = $this->documentsService->getAllDocumentsService();

        return $this->render('@documents/document-types-input.twig', [
            'services' => $services,
            'errors' => $this->documentsService->configErrors(),
            'value' => $stored['documentType'],
        ]);
    }

    protected function renderStatic($entry)
    {
        $services = $this->documentsService->getAllDocumentsService();

        return $this->documentsService->showDocument(
            $services[$this->storedValue($entry)['documentType']] ?? null,
            is_array($entry) ? $entry : [],
            (string)$this->propertyName,
        );
    }

    public function getValueStructure()
    {
        return [$this->propertyName => [
            'documentType' => ['_mode_' => 'single', '_type_' => 'string'],
            'documentUrl' => ['_mode_' => 'single', '_type_' => 'string'],
        ]];
    }

    public function formatValuesBeforeSave($entry)
    {
        $value = $this->storedValue($entry);
        if ($value['documentUrl'] !== '' && isset($entry['tag']) && $this->isWebAddress($value['documentUrl'])) {
            return [(string)$this->propertyName => $value];
        }
        $services = $this->documentsService->getAllDocumentsService();
        if (!isset($services[$value['documentType']])) {
            return [(string)$this->propertyName => ['documentType' => $value['documentType'], 'documentUrl' => '']];
        }

        return [(string)$this->propertyName => [
            'documentType' => $value['documentType'],
            'documentUrl' => $this->documentsService->createDocument($services[$value['documentType']], is_array($entry) ? $entry : []),
        ]];
    }

    /**
     * The field's value, whatever was stored or submitted, as its two strings.
     *
     * @param array<string, mixed>|null $entry
     *
     * @return array{documentType: string, documentUrl: string}
     */
    protected function storedValue($entry): array
    {
        $value = $this->getValue($entry);
        $value = is_array($value) ? $value : [];

        return [
            'documentType' => is_string($value['documentType'] ?? null) ? $value['documentType'] : '',
            'documentUrl' => is_string($value['documentUrl'] ?? null) ? $value['documentUrl'] : '',
        ];
    }

    private function isWebAddress(string $url): bool
    {
        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
