<?php

namespace YesWiki\Documents\Action;

use YesWiki\Content\Entity\PageBody;
use YesWiki\Content\Service\EntryManager;
use YesWiki\Content\Service\FormManager;
use YesWiki\Content\Service\PageManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Documents\Field\DocumentsField;
use YesWiki\Documents\Service\DocumentsService;
use YesWiki\Identity\Service\AclService;
use YesWiki\Kernel\Component\Category;
use YesWiki\Kernel\Component\Component;
use YesWiki\Kernel\Component\ProvidesComponents;
use YesWiki\Kernel\Component\Setting;
use YesWiki\Kernel\Performable\RegisteredAction;
use YesWiki\Kernel\Service\PageContext;
use YesWiki\Kernel\Service\Redirector;
use YesWiki\Kernel\Service\RuntimeConfig;
use YesWiki\Kernel\Service\UrlFormatter;

/** `{{document type="etherpad"}}`: creates an entry holding a new document, then pins its id in the page; `{{document id="..."}}` shows it. */
class DocumentAction extends YesWikiAction implements RegisteredAction, ProvidesComponents
{
    public const DRAFT_STATUS = 'en_cour_de_redaction';

    public static function performableName(): string
    {
        return 'document';
    }

    public function components(): array
    {
        return [
            Component::for('document')
                ->category(Category::Media)
                ->label(_t('DOCUMENTS_COMPONENT_LABEL'))
                ->description(_t('DOCUMENTS_COMPONENT_DESCRIPTION'))
                ->icon('file-text')
                ->settings(
                    Setting::choice('type', [
                        'etherpad' => _t('AB_document_action_etherpad'),
                        'hedgedoc' => _t('AB_document_action_hedgedoc'),
                        'memo' => _t('AB_document_action_memo'),
                    ])->label(_t('AB_document_action_type_label'))->required(),
                    Setting::entry('id')->label(_t('AB_document_action_id_label'))->hint(_t('AB_document_action_id_hint')),
                    Setting::number('formId')->label(_t('AB_document_action_formid_label'))->default(1500),
                ),
        ];
    }

    public function formatArguments($arg): array
    {
        $formId = trim((string)($arg['formId'] ?? ''));
        $defaultFormId = $this->getService(RuntimeConfig::class)['documentsFormId'];

        return [
            'type' => strtolower(trim((string)($arg['type'] ?? ''))),
            'id' => trim((string)($arg['id'] ?? '')),
            'formId' => $formId !== '' ? $formId : (is_scalar($defaultFormId) ? (string)$defaultFormId : '1500'),
        ];
    }

    public function run(): string
    {
        $service = $this->getService(DocumentsService::class);
        $pageContext = $this->getService(PageContext::class);
        if ($pageContext->getMethod() === 'render') {
            return $this->alert('info', _t('DOCUMENTS_ACTION_NO_PREVIEW'));
        }
        $availableServices = $service->getAllDocumentsService();
        $docField = $this->getService(FormManager::class)->findTypeOfFields($this->arguments['formId'], ['DocumentsField'])[0] ?? null;
        if (!$docField instanceof DocumentsField) {
            return $this->alert('danger', _t('DOCUMENTS_ACTION_FIELD_NOT_FOUND'));
        }
        $fieldName = (string)$docField->getPropertyName();

        if ($this->arguments['id'] !== '') {
            $entry = $this->getService(EntryManager::class)->getOne($this->arguments['id']);
            $documentType = $entry[$fieldName]['documentType'] ?? '';
            if (empty($entry) || empty($entry[$fieldName]['documentUrl']) || !is_string($documentType)) {
                return $this->alert('danger', _t('DOCUMENTS_ACTION_ENTRY_NOT_FOUND', ['id' => $this->arguments['id']]));
            }

            return $service->showDocument($availableServices[$documentType] ?? null, $entry, $fieldName);
        }

        if ($this->arguments['type'] === '') {
            return $this->alert('danger', _t('DOCUMENTS_ACTION_TYPE_MISSING'));
        }
        if (!isset($availableServices[$this->arguments['type']])) {
            return $this->alert('danger', _t('DOCUMENTS_ACTION_INVALID_TYPE', ['doc' => implode('", "', array_keys($availableServices))]));
        }

        $tag = $pageContext->getTag();
        if (!$this->getService(AclService::class)->hasAccess('write', $tag)) {
            return $this->alert('info', _t('DOCUMENTS_ACTION_NEEDS_WRITE'));
        }
        $entry = $this->getService(EntryManager::class)->create($this->arguments['formId'], [
            'bf_titre' => _t('DOCUMENTS_DOC_IN_PAGE', ['type' => $this->arguments['type'], 'page' => $tag]),
            'bf_statut' => self::DRAFT_STATUS,
            $fieldName => ['documentType' => $this->arguments['type']],
            'antispam' => '1',
        ]);
        $this->pinEntryInPage($tag, (string)$entry['tag']);
        $this->getService(Redirector::class)->redirect($this->getService(UrlFormatter::class)->href('', $tag, null, false));
    }

    /** Writes the new entry's id into the first `{{document}}` of the page that has none. */
    private function pinEntryInPage(string $tag, string $entryTag): void
    {
        $pageManager = $this->getService(PageManager::class);
        $page = $pageManager->getOne($tag);
        if (empty($page)) {
            return;
        }
        $content = PageBody::content($page['body']);
        $replacement = '{{document type="' . $this->arguments['type'] . '" id="' . $entryTag . '"}}';
        $newContent = preg_replace('/\{\{document(?![^}]*\bid="[^"]+")[^}]*\}\}/i', $replacement, $content, 1);
        if (is_string($newContent) && $newContent !== $content) {
            $pageManager->save($tag, array_merge(is_array($page['body']) ? $page['body'] : [], ['content' => $newContent]));
        }
    }

    private function alert(string $kind, string $message): string
    {
        return '<div class="yw-alert yw-alert--' . $kind . '">' . htmlspecialchars($message) . '</div>';
    }
}
