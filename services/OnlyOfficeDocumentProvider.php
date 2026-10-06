<?php

namespace YesWiki\Documents\Service;

use Firebase\JWT\JWT;
use YesWiki\Files\Service\Storage;
use YesWiki\Identity\Service\AuthenticationService;
use YesWiki\Kernel\Service\LanguageService;
use YesWiki\Kernel\Service\RuntimeConfig;
use YesWiki\Kernel\Service\UrlFormatter;
use YesWiki\Render\Service\TemplateEngine;

/** OnlyOffice: a blank office file copied into `files/`, edited in the OnlyOffice editor, written back by the `onlyoffice` handler. */
class OnlyOfficeDocumentProvider extends DocumentProvider
{
    public const FILES_DIRECTORY = 'files';

    public const DOCUMENT_TYPES = [
        'docx' => 'word',
        'md' => 'word',
        'pdf' => 'pdf',
        'xlsx' => 'cell',
        'pptx' => 'slide',
    ];

    public function __construct(
        TemplateEngine $templateEngine,
        private Storage $storage,
        private RuntimeConfig $config,
        private UrlFormatter $urlFormatter,
        private AuthenticationService $authenticationService,
        private LanguageService $languageService,
    ) {
        parent::__construct($templateEngine);
    }

    public function providerName(): string
    {
        return 'onlyoffice';
    }

    public function createDocument(array $docConfig, array $entry): string
    {
        $extension = $this->fileType($docConfig);
        $path = self::FILES_DIRECTORY . '/' . $this->createDocumentId((string)($entry['bf_titre'] ?? 'document')) . '.' . $extension;
        $this->storage->write($path, $this->storage->readForeign(dirname(__DIR__) . "/assets/new.{$extension}"));

        return $this->storage->url($path);
    }

    /**
     * The extension of the files this service makes, `docx` unless the service is named after another one.
     *
     * @param array<string, mixed> $docConfig
     */
    public function fileType(array $docConfig): string
    {
        $type = strtolower((string)($docConfig['options']['filetype'] ?? ''));

        return isset(self::DOCUMENT_TYPES[$type]) ? $type : 'docx';
    }

    /**
     * The secret shared with this OnlyOffice server, '' when none is set.
     *
     * @param array<string, mixed> $docConfig
     */
    public function secret(array $docConfig): string
    {
        $credentials = $this->config['documentsCredentials'];
        $secret = is_array($credentials) ? ($credentials[$docConfig['provider-name'] ?? ''] ?? null) : null;
        if (!is_string($secret) || $secret === '') {
            $secret = $docConfig['credentials'] ?? '';
        }

        return is_string($secret) ? $secret : '';
    }

    public function showDocument(array $docConfig, string $documentUrl): string
    {
        if (($docConfig['iframe'] ?? false) !== true) {
            return parent::showDocument($docConfig, $documentUrl);
        }
        $doc = pathinfo((string)parse_url($documentUrl, PHP_URL_PATH));
        $extension = strtolower($doc['extension'] ?? '');
        $user = $this->authenticationService->getLoggedUserName();
        $config = [
            'document' => [
                'fileType' => $extension,
                'key' => $doc['filename'],
                'title' => $doc['basename'],
                'url' => $documentUrl,
            ],
            'editorConfig' => [
                'callbackUrl' => $this->urlFormatter->href('onlyoffice', null, ['filename' => $doc['basename']], false),
                'user' => [
                    'id' => $user === '' ? 'anonymous' : $user,
                    'name' => $user === '' ? _t('DOCUMENTS_ANONYMOUS') : $user,
                ],
                'customization' => ['features' => ['featuresTips' => false]],
                'lang' => $this->languageService->preferredLanguage(),
            ],
            'documentType' => self::DOCUMENT_TYPES[$extension] ?? 'word',
            'height' => '1000px',
            'width' => '100%',
        ];
        try {
            $config['token'] = JWT::encode($config, $this->secret($docConfig), 'HS256');
        } catch (\DomainException) {
            return $this->templateEngine->render('@core/alert-message.twig', [
                'type' => 'danger',
                'message' => _t('DOCUMENTS_SECRET_TOO_SHORT', ['key' => (string)($docConfig['provider-name'] ?? '')]),
            ]);
        }

        return $this->templateEngine->render('@documents/onlyoffice-editor.twig', [
            'id' => 'onlyoffice-doc-' . $doc['filename'],
            'apiUrl' => rtrim((string)$docConfig['url'], '/') . '/web-apps/apps/api/documents/api.js',
            'config' => $config,
        ]);
    }
}
