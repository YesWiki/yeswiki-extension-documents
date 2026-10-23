<?php

namespace YesWiki\Documents\Service;

use YesWiki\Content\Field\EnumField;
use YesWiki\Content\Service\FormManager;
use YesWiki\Content\Service\ListManager;
use YesWiki\Kernel\Service\RuntimeConfig;
use YesWiki\Kernel\Service\UrlFormatter;
use YesWiki\Render\Service\TemplateEngine;

/** The document services configured on this wiki, and the providers that create and show their documents. */
class DocumentsService
{
    public const STATUS_LIST = 'ListStatut';

    /** @var array<string, DocumentProvider> */
    private array $providers = [];

    /** @var list<string> */
    private array $configErrors = [];

    public function __construct(
        private RuntimeConfig $config,
        private FormManager $formManager,
        private ListManager $listManager,
        private UrlFormatter $urlFormatter,
        private TemplateEngine $templateEngine,
        EtherpadDocumentProvider $etherpad,
        HedgedocDocumentProvider $hedgedoc,
        MemoDocumentProvider $memo,
        OnlyOfficeDocumentProvider $onlyOffice,
    ) {
        foreach ([$etherpad, $hedgedoc, $memo, $onlyOffice] as $provider) {
            $this->providers[$provider->providerName()] = $provider;
        }
    }

    /**
     * Each `documents<Service>Url` key of the configuration, as lowercase key => service name.
     *
     * @return array<string, string>
     */
    public function getAvailableServices(): array
    {
        $services = [];
        foreach (array_keys($this->config->all()) as $key) {
            if (is_string($key) && preg_match('/^documents(.+)Url$/', $key, $matches)) {
                $services[strtolower($matches[1])] = $matches[1];
            }
        }

        return $services;
    }

    /**
     * The fully configured services, keyed like getAvailableServices(); an incomplete one is left out and said in configErrors().
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllDocumentsService(): array
    {
        $fullServices = [];
        $this->configErrors = [];
        foreach ($this->getAvailableServices() as $key => $serviceName) {
            $url = $this->setting("documents{$serviceName}Url");
            $title = $this->setting("documents{$serviceName}Title");
            $description = $this->setting("documents{$serviceName}Description");
            $provider = $this->providerNameFor($key);
            if (empty($url) || empty($title) || empty($description) || $provider === null) {
                $this->configErrors[] = _t('DOCUMENTS_INVALID_CONFIG_ERROR', ['key' => $key]);
                continue;
            }
            $fullServices[$key] = [
                'provider-name' => $key,
                'provider' => $provider,
                'service' => $serviceName,
                'label' => $title,
                'description' => $description,
                'url' => $url,
                'iframe' => in_array($this->setting("documents{$serviceName}Iframe"), [true, 1, '1', 'true'], true),
                'credentials' => $this->setting("documents{$serviceName}Credentials") ?? false,
                'options' => ['filetype' => substr($key, strlen($provider))],
            ];
        }

        return $fullServices;
    }

    /**
     * Why some configured services were left out by the last getAllDocumentsService().
     *
     * @return list<string>
     */
    public function configErrors(): array
    {
        return $this->configErrors;
    }

    public function getProvider(string $name): ?DocumentProvider
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * The address of a new document of this service for this entry.
     *
     * @param array<string, mixed> $docConfig
     * @param array<string, mixed> $entry
     */
    public function createDocument(array $docConfig, array $entry = []): string
    {
        $provider = $this->getProvider((string)($docConfig['provider'] ?? ''));
        if ($provider === null) {
            throw new \RuntimeException(_t('DOCUMENTS_UNSUPPORTED_SERVICE', ['service' => (string)($docConfig['provider-name'] ?? '')]));
        }

        return $provider->createDocument($docConfig, $entry);
    }

    /**
     * The entry's document, with its title, status and edit link when shown outside the entry.
     *
     * @param array<string, mixed>|null $docConfig
     * @param array<string, mixed>      $entry
     */
    public function showDocument(?array $docConfig, array $entry, string $fieldId, bool $withMeta = true): string
    {
        $documentUrl = $entry[$fieldId]['documentUrl'] ?? null;
        if (empty($documentUrl) || !is_string($documentUrl)) {
            return $this->alert('warning', _t('DOCUMENTS_NO_URL_GENERATED'));
        }
        $provider = $docConfig === null ? null : $this->getProvider((string)($docConfig['provider'] ?? ''));
        if ($docConfig === null || $provider === null) {
            return $this->alert('danger', _t('DOCUMENTS_UNSUPPORTED_SERVICE', ['service' => (string)($entry[$fieldId]['documentType'] ?? '')]));
        }

        $document = $provider->showDocument($docConfig, $documentUrl);
        if (!$withMeta) {
            return $this->templateEngine->render('@documents/document.twig', ['document' => $document, 'withMeta' => false]);
        }

        return $this->templateEngine->render('@documents/document.twig', [
            'document' => $document,
            'withMeta' => true,
            'url' => $documentUrl,
            'title' => (string)($entry['title'] ?? $entry['bf_titre'] ?? _t('DOCUMENTS_UNKNOWN_TITLE')),
            'label' => (string)$docConfig['label'],
            'status' => $this->statusLabel($entry),
            'editUrl' => isset($entry['tag']) ? $this->urlFormatter->href('edit', (string)$entry['tag'], ['incomingurl' => $this->urlFormatter->href('', null, null, false)], false) : '',
        ]);
    }

    /**
     * The label of the entry's value in the status list, or "unknown status".
     *
     * @param array<string, mixed> $entry
     */
    private function statusLabel(array $entry): string
    {
        $form = $this->formManager->getOne((string)($entry['form_id'] ?? ''));
        foreach ($form['prepared'] ?? [] as $field) {
            if ($field instanceof EnumField && $field->getLinkedObjectName() === self::STATUS_LIST) {
                $value = $entry[(string)$field->getPropertyName()] ?? '';
                $label = is_scalar($value) && $value !== '' ? $this->listManager->getLabel(self::STATUS_LIST, (string)$value) : '';

                return $label === '' ? _t('DOCUMENTS_UNKNOWN_STATUS') : $label;
            }
        }

        return _t('DOCUMENTS_UNKNOWN_STATUS');
    }

    /** The provider a service key belongs to: its own name, or the provider it starts with (`onlyofficexlsx` is OnlyOffice). */
    private function providerNameFor(string $key): ?string
    {
        if (isset($this->providers[$key])) {
            return $key;
        }
        foreach (array_keys($this->providers) as $name) {
            if (str_starts_with($key, $name)) {
                return $name;
            }
        }

        return null;
    }

    private function setting(string $name): mixed
    {
        return $this->config[$name];
    }

    private function alert(string $type, string $message): string
    {
        return $this->templateEngine->render('@core/alert-message.twig', ['type' => $type, 'message' => htmlspecialchars($message)]);
    }
}
