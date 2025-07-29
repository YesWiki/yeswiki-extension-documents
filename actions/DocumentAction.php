<?php

namespace YesWiki\Documents;

use YesWiki\Core\YesWikiAction;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Documents\Service\DocumentsService;

class DocumentAction extends YesWikiAction
{
    protected $authController;
    protected $pageManager;
    protected $templateEngine;
    protected $securityController;
    protected $userManager;

    /**
     * @param mixed $arg
     *
     * @return array<string,mixed>
     */
    public function formatArguments($arg): array
    {
        if (empty($arg['formId'])) {
            $arg['formId'] = $this->wiki->getConfigValue('documentsFormId');
        }
        return $arg;
    }

    public function run(): ?string
    {
        $service = $this->getService(DocumentsService::class);
        $entryManager = $this->getService(EntryManager::class);
        $pageManager = $this->getService(PageManager::class);
        $availableDocs = array_keys($this->wiki->config['documentsType']);
        if (empty($this->arguments['type'])) {
            return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_TYPE_MISSING') . '</div>');
        }
        if (!in_array($this->arguments['type'], $availableDocs)) {
            return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_INVALID_TYPE', implode('", "', $availableDocs)) . '</div>');
        }
        if (!empty($this->arguments['id'])) {
            $entry = $entryManager->getOne($this->arguments['id']);
            if (!empty($entry) && !empty($entry['bf_document_url'])) {
                return $service->showDocument(
                    $this->wiki->config['documentsType'][$entry['bf_documents']],
                    $entry
                );
            } else {
                return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_ENTRY_NOT_FOUND', $this->arguments['id']) . '</div>');
            }
        } else {
            $formId = $this->arguments['formId'];
            $entry = $entryManager->create($formId, [
              'bf_titre' => _t('DOCUMENTS_DOC_IN_PAGE', $this->arguments['type'], $this->wiki->getPageTag()),
              'id_typeannonce' => $formId,
              'bf_statut' => _t('DOCUMENTS_DRAFT_STATUS'),
              'bf_documents' => $this->arguments['type'],
              'antispam' => '1',
            ]);
            $currentPage = $pageManager->getOne($this->wiki->getPageTag());
            $re = '/\{\{document(?!.*id="[^"]+").*\}\}/mUi';
            $subst = '{{document type="'.$this->arguments['type'].'" id="'.$entry['id_fiche'].'"}}';
            $body = preg_replace($re, $subst, $currentPage['body'], 1);
            $pageManager->save($this->wiki->getPageTag(), $body);
            $this->wiki->redirect($this->wiki->href());
        }
        return '';
    }
}