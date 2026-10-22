<?php

namespace YesWiki\Documents\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Content\Attribute\Field;
use YesWiki\Content\Field\BazarField;
use YesWiki\Documents\Service\DocumentsService;

/** `documents`: the visitor picks a kind of document among those the field allows, and saving the entry creates it on that service. */
#[Field(['documents'])]
class DocumentsField extends BazarField
{
    protected const FIELD_DOCUMENT_TYPES = 6;

    protected DocumentsService $documentsService;

    /** @var list<string> */
    protected array $documentTypes;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->documentsService = $this->getService(DocumentsService::class);
        $this->documentTypes = array_values(array_filter(array_map('trim', explode(',', (string)($values[self::FIELD_DOCUMENT_TYPES] ?? '')))));
    }

    /** @return list<string> the service keys this field allows, every configured one when it names none */
    public function getDocumentTypes(): array
    {
        return $this->documentTypes;
    }

    /** @return array<string, array<string, mixed>> the configured services this field offers */
    public function allowedServices(): array
    {
        $services = $this->documentsService->getAllDocumentsService();
        if ($this->documentTypes === []) {
            return $services;
        }

        return array_intersect_key($services, array_flip($this->documentTypes));
    }

    protected function renderInput($entry)
    {
        $stored = $this->storedValue($entry);
        if (isset($entry['tag']) && $stored['documentUrl'] !== '') {
            return $this->render('@documents/document-link-input.twig', [
                'url' => $stored['documentUrl'],
            ]);
        }

        $services = $this->allowedServices();

        return $this->render('@documents/document-types-input.twig', [
            'services' => $services,
            'errors' => $this->documentsService->configErrors(),
            'value' => count($services) === 1 ? (string)array_key_first($services) : $stored['documentType'],
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
        $services = $this->allowedServices();
        if (count($services) === 1) {
            $value['documentType'] = (string)array_key_first($services);
        }
        if (!isset($services[$value['documentType']])) {
            return [(string)$this->propertyName => ['documentType' => $value['documentType'], 'documentUrl' => '']];
        }

        return [(string)$this->propertyName => [
            'documentType' => $value['documentType'],
            'documentUrl' => $this->documentsService->createDocument($services[$value['documentType']], is_array($entry) ? $entry : []),
        ]];
    }

    /** @return array{documentType: string, documentUrl: string} the field's value, whatever was stored or submitted */
    protected function storedValue(mixed $entry): array
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
