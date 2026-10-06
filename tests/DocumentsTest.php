<?php

namespace YesWiki\Test\Documents;

use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use YesWiki\Content\Entity\PageBody;
use YesWiki\Content\Service\EntryManager;
use YesWiki\Content\Service\FormManager;
use YesWiki\Content\Service\ListManager;
use YesWiki\Content\Service\PageManager;
use YesWiki\Documents\Field\DocumentsField;
use YesWiki\Documents\Handler\OnlyOfficeHandler;
use YesWiki\Files\Service\Storage;
use YesWiki\Identity\Service\AuthenticationService;
use YesWiki\Kernel\Exception\ExitException;
use YesWiki\Kernel\Service\CurrentRequest;
use YesWiki\Kernel\Service\ExtensionRegistry;
use YesWiki\Kernel\Service\PageContext;
use YesWiki\Kernel\Service\RuntimeConfig;
use YesWiki\Render\Service\ActionRunner;
use YesWiki\Render\Service\Performer;
use YesWiki\Render\Service\TemplateEngine;
use YesWiki\Test\Core\YesWikiTestCase;

require_once 'tests/YesWikiTestCase.php';

/** The documents extension on a running wiki: its migration, its field, its action and the OnlyOffice callback. */
class DocumentsTest extends YesWikiTestCase
{
    private const SECRET = 'a-test-secret-long-enough-for-hs256-signing';
    private const OFFICE_URL = 'https://office.example.org';

    /** @var list<string> */
    private array $forms = [];

    /** @var list<string> */
    private array $tags = [];

    /** @var list<string> */
    private array $files = [];

    /** @var array<string, mixed> */
    private array $savedConfig = [];

    private string $setupDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($this->getWiki()->services->get(ExtensionRegistry::class)->all()['documents'])) {
            $this->markTestSkipped('documents is not switched on in this wiki');
        }
        require_once dirname(__DIR__) . '/migrations/20251018003958_DocumentsCreateBazarFormAndListForDocuments.php';
    }

    protected function tearDown(): void
    {
        $services = $this->getWiki()->services;
        $config = $services->get(RuntimeConfig::class);
        foreach ($this->savedConfig as $key => $value) {
            if ($value === null) {
                unset($config[$key]);
            } else {
                $config[$key] = $value;
            }
        }
        foreach ($this->tags as $tag) {
            $services->get(PageManager::class)->deleteOrphaned($tag);
        }
        foreach ($this->forms as $formId) {
            $services->get(FormManager::class)->delete($formId);
        }
        foreach ($this->files as $path) {
            $storage = $services->get(Storage::class);
            if ($storage->fileExists($path)) {
                $storage->delete($path);
            }
        }
        if ($this->setupDir !== '') {
            array_map('unlink', glob($this->setupDir . '/*/*.json') ?: []);
            array_map('rmdir', glob($this->setupDir . '/*') ?: []);
            rmdir($this->setupDir);
        }
        $services->get(AuthenticationService::class)->logout();
        parent::tearDown();
    }

    public function testTheMigrationCreatesTheListAndTheFormInTheEctoplasmeShapeOnce(): void
    {
        $services = $this->getWiki()->services;
        $forms = $services->get(FormManager::class);
        $listTag = 'ListDocumentsTest' . mt_rand(10000, 99999);
        $formId = (string)$forms->findNewId();
        $this->tags[] = $listTag;
        $this->forms[] = $formId;
        $this->setupDir = sys_get_temp_dir() . '/documents-setup-' . uniqid();
        mkdir($this->setupDir . '/lists', 0777, true);
        mkdir($this->setupDir . '/forms', 0777, true);
        copy(dirname(__DIR__) . '/setup/lists/ListStatut.json', "{$this->setupDir}/lists/{$listTag}.json");
        $form = json_decode((string)file_get_contents(dirname(__DIR__) . '/setup/forms/1500.json'), true);
        $form['template'][2]['linked_object'] = $listTag;
        file_put_contents("{$this->setupDir}/forms/{$formId}.json", json_encode($form));

        $migration = $this->migration();
        $migration->createLists($this->setupDir . '/lists');
        $migration->createForms($this->setupDir . '/forms');
        $migration->createLists($this->setupDir . '/lists');
        $migration->createForms($this->setupDir . '/forms');

        $list = $services->get(ListManager::class)->getOne($listTag);
        $this->assertSame('Statut', $list['title'] ?? null);
        $this->assertSame(['en_cour_de_redaction', 'finalise', 'archive'], array_column($list['nodes'] ?? [], 'id'));
        $this->assertSame(['title', 'nodes'], array_keys($services->get(PageManager::class)->getOne($listTag)['body'] ?? []));

        $stored = $forms->getOne($formId);
        $this->assertNotNull($stored);
        $this->assertSame('{{bf_titre}}', $stored['entry_title_template']);
        $this->assertSame(['texte', 'textelong', 'liste', 'listedatedeb', 'tags', 'texte', 'documents'], array_column($stored['template'], 'type'));
        $this->assertSame('bf_documents', $stored['template'][6]['name']);
        $this->assertInstanceOf(DocumentsField::class, $stored['prepared'][6]);
    }

    public function testTheMigrationRunReadsItsOwnSetupFiles(): void
    {
        $services = $this->getWiki()->services;
        $listExisted = $services->get(PageManager::class)->tagExists('ListStatut');
        $formExisted = $services->get(FormManager::class)->getOne('1500') !== null;
        if (!$listExisted) {
            $this->tags[] = 'ListStatut';
        }
        if (!$formExisted) {
            $this->forms[] = '1500';
        }

        $this->migration()->run();

        $this->assertTrue($services->get(ListManager::class)->isList('ListStatut'));
        $this->assertNotEmpty($services->get(FormManager::class)->findTypeOfFields('1500', ['DocumentsField']));
    }

    public function testTheFieldOffersEveryConfiguredService(): void
    {
        $field = $this->documentsField($this->createForm());

        $html = $field->renderInputIfPermitted(null);

        $this->assertStringContainsString('name="bf_documents[documentType]"', $html);
        $this->assertStringContainsString('value="etherpad"', $html);
        $this->assertStringContainsString('Document pad collaboratif', $html);
        $this->assertStringContainsString('Tableau de post-it', $html);
        $this->assertStringContainsString('yw-form-group', $html);
    }

    public function testTheActionShowsTheDocumentOfAnEntry(): void
    {
        $services = $this->getWiki()->services;
        $services->get(AuthenticationService::class)->connectFirstAdmin();
        $formId = $this->createForm();
        $entry = $services->get(EntryManager::class)->create($formId, [
            'bf_titre' => 'Documents test pad',
            'bf_documents' => ['documentType' => 'etherpad'],
            'antispam' => 1,
        ]);
        $this->tags[] = (string)$entry['tag'];
        $this->assertStringStartsWith('https://pad.yeswiki.net/p/documents_test_pad-', $entry['bf_documents']['documentUrl']);

        $html = $services->get(ActionRunner::class)->action('document', ['id' => $entry['tag'], 'formId' => $formId]);

        $this->assertStringContainsString('<iframe src="' . $entry['bf_documents']['documentUrl'] . '"', $html);
        $this->assertStringContainsString('Documents test pad', $html);
        $this->assertStringNotContainsString('yw-alert', $html);

        $updated = $services->get(EntryManager::class)->update((string)$entry['tag'], ['bf_titre' => 'Documents test pad renamed', 'antispam' => 1]);
        $this->assertSame($entry['bf_documents'], $updated['bf_documents']);
    }

    public function testTheActionCreatesTheEntryAndPinsItsIdInThePage(): void
    {
        $services = $this->getWiki()->services;
        $services->get(AuthenticationService::class)->connectFirstAdmin();
        $formId = $this->createForm();
        $tag = 'DocumentsTestPage' . mt_rand(10000, 99999);
        $this->tags[] = $tag;
        $services->get(PageManager::class)->save($tag, ['content' => "Avant\n{{document type=\"etherpad\" formId=\"{$formId}\"}}\nAprès"]);
        $context = $services->get(PageContext::class);
        $previousTag = $context->getTag();
        $context->setTag($tag);

        try {
            $services->get(ActionRunner::class)->action('document', ['type' => 'etherpad', 'formId' => $formId]);
            $this->fail('the action redirects once the entry is created');
        } catch (\Throwable $thrown) {
            $this->assertNotNull(ExitException::in($thrown));
        } finally {
            $context->setTag($previousTag);
        }

        $content = PageBody::content($services->get(PageManager::class)->getOne($tag, null, false)['body'] ?? []);
        $this->assertSame(1, preg_match('/\{\{document type="etherpad" id="([^"]+)"\}\}/', $content, $matches), $content);
        $entryTag = $matches[1] ?? '';
        $this->tags[] = $entryTag;
        $entry = $services->get(EntryManager::class)->getOne($entryTag);
        $this->assertSame('en_cour_de_redaction', $entry['bf_statut'] ?? null);
        $this->assertStringStartsWith('Avant', $content);
    }

    public function testAnUnsignedCallbackIsRefused(): void
    {
        $this->configureOnlyOffice();

        $answer = $this->answerTo(['status' => 2, 'url' => self::OFFICE_URL . '/cache/edited.docx'], 'whatever.docx', null);

        $this->assertSame(['error' => 1, 'message' => _t('DOCUMENTS_CALLBACK_NOT_SIGNED')], $answer);
    }

    public function testACallbackSignedWithAnotherSecretIsRefused(): void
    {
        $this->configureOnlyOffice();
        $payload = ['status' => 2, 'url' => self::OFFICE_URL . '/cache/edited.docx'];
        $payload['token'] = JWT::encode($payload, 'another-secret-that-is-also-long-enough', 'HS256');

        $this->assertSame(1, $this->answerTo($payload, 'whatever.docx', null)['error']);
    }

    public function testASignedCallbackCannotWriteOutsideFiles(): void
    {
        $this->configureOnlyOffice();

        foreach (['../yeswiki.config.php', 'files/../../index.php', '.htaccess', 'missing-' . uniqid() . '.docx'] as $filename) {
            $answer = $this->answerTo($this->signed(['status' => 2, 'url' => self::OFFICE_URL . '/cache/edited.docx']), $filename, null);
            $this->assertSame(['error' => 1, 'message' => _t('DOCUMENTS_WRONG_NON_EXISTENT_FILENAME', ['type' => $filename])], $answer, $filename);
        }
    }

    public function testASignedCallbackPointingElsewhereIsRefused(): void
    {
        $this->configureOnlyOffice();

        $answer = $this->answerTo($this->signed(['status' => 2, 'url' => 'https://evil.example.net/edited.docx']), 'whatever.docx', null);

        $this->assertSame(['error' => 1, 'message' => _t('DOCUMENTS_CALLBACK_FOREIGN_URL')], $answer);
    }

    public function testASignedCallbackRewritesTheFileThroughStorage(): void
    {
        $this->configureOnlyOffice();
        $storage = $this->getWiki()->services->get(Storage::class);
        $filename = 'documents-test-' . uniqid() . '.docx';
        $this->files[] = 'files/' . $filename;
        $storage->write('files/' . $filename, 'blank');

        $answer = $this->answerTo($this->signed(['status' => 2, 'url' => self::OFFICE_URL . '/cache/edited.docx']), $filename, 'edited bytes');

        $this->assertSame(['error' => 0], $answer);
        $this->assertSame('edited bytes', $storage->read('files/' . $filename));
    }

    public function testTheOnlyOfficeProviderCreatesTheBlankFileItLaterRewrites(): void
    {
        $this->configureOnlyOffice();
        $services = $this->getWiki()->services;
        $docConfig = $services->get(\YesWiki\Documents\Service\DocumentsService::class)->getAllDocumentsService()['onlyoffice'];

        $url = $services->get(\YesWiki\Documents\Service\DocumentsService::class)->createDocument($docConfig, ['bf_titre' => 'Rapport test']);

        $path = 'files/' . basename((string)parse_url($url, PHP_URL_PATH));
        $this->files[] = $path;
        $this->assertStringEndsWith('.docx', $path);
        $this->assertSame(file_get_contents(dirname(__DIR__) . '/assets/new.docx'), $services->get(Storage::class)->read($path));
        $this->assertSame($services->get(Storage::class)->url($path), $url);
    }

    private function migration(): \DocumentsCreateBazarFormAndListForDocuments
    {
        $services = $this->getWiki()->services;
        $migration = new \DocumentsCreateBazarFormAndListForDocuments();
        $migration->setServices($services);
        $migration->setParams($services->get(ParameterBagInterface::class));

        return $migration;
    }

    private function createForm(): string
    {
        $forms = $this->getWiki()->services->get(FormManager::class);
        $formId = (string)$forms->findNewId();
        $forms->create([
            'id' => $formId,
            'label' => 'Documents test form',
            'template' => [
                ['type' => 'texte', 'name' => 'bf_titre', 'label' => 'Titre', 'required' => '1'],
                ['type' => 'texte', 'name' => 'bf_statut', 'label' => 'Statut'],
                ['type' => 'documents', 'name' => 'bf_documents', 'label' => 'Document'],
            ],
            'entry_title_template' => '{{bf_titre}}',
        ]);
        $this->forms[] = $formId;

        return $formId;
    }

    private function documentsField(string $formId): DocumentsField
    {
        $fields = $this->getWiki()->services->get(FormManager::class)->findTypeOfFields($formId, ['DocumentsField']);
        $this->assertInstanceOf(DocumentsField::class, $fields[0] ?? null);

        return $fields[0];
    }

    private function configureOnlyOffice(): void
    {
        $config = $this->getWiki()->services->get(RuntimeConfig::class);
        $values = [
            'documentsOnlyOfficeUrl' => self::OFFICE_URL,
            'documentsOnlyOfficeTitle' => 'Bureautique',
            'documentsOnlyOfficeDescription' => 'OnlyOffice de test',
            'documentsOnlyOfficeIframe' => true,
            'documentsOnlyOfficeCredentials' => self::SECRET,
        ];
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $this->savedConfig)) {
                $this->savedConfig[$key] = $config[$key];
            }
            $config[$key] = $value;
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function signed(array $payload): array
    {
        return $payload + ['token' => JWT::encode($payload, self::SECRET, 'HS256')];
    }

    /**
     * What the handler answers to this callback body, with $download standing for the OnlyOffice server's file, or the registered handler when null.
     *
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function answerTo(array $body, string $filename, ?string $download): array
    {
        $services = $this->getWiki()->services;
        $currentRequest = $services->get(CurrentRequest::class);
        $before = $currentRequest->get();
        $currentRequest->replace(Request::create('/?PagePrincipale/onlyoffice&filename=' . urlencode($filename), 'POST', [], [], [], [], (string)json_encode($body)));
        $currentRequest->get()->query->set('filename', $filename);

        try {
            if ($download === null) {
                $output = $services->get(Performer::class)->run('onlyoffice', 'handler');
            } else {
                $handler = new class($download) extends OnlyOfficeHandler {
                    public function __construct(private string $bytes)
                    {
                    }

                    protected function download(string $url): string
                    {
                        return $this->bytes;
                    }
                };
                $handler->setServices($services);
                $handler->setParams($services->get(ParameterBagInterface::class));
                $handler->setTwig($services->get(TemplateEngine::class));
                $output = $handler->run();
            }
        } finally {
            $currentRequest->replace($before);
        }

        $answer = json_decode($output, true);
        $this->assertIsArray($answer, $output);

        return $answer;
    }
}
