<?php

namespace YesWiki\Documents;

use YesWiki\Core\YesWikiAction;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
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
        $formManager = $this->getService(FormManager::class);
        $availableServices = $service->getAllDocumentsService();
        $availableDocs = array_keys($service->getAvailableServices($this->wiki->config));
        if ($this->wiki->getMethod() == 'render') {
            return('<div class="alert alert-info">'._t('DOCUMENTS_ACTION_NO_PREVIEW').'.</div>');
        }
        if (empty($this->arguments['type'])) {
            return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_TYPE_MISSING') . '</div>');
        }
        if (!in_array($this->arguments['type'], $availableDocs)) {
            return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_INVALID_TYPE', ['doc' => implode('", "', $availableDocs)]) . '</div>');
        }
        $formId = $this->arguments['formId'];
        $docField = $formManager->findTypeOfFields($formId, ['DocumentsField']);
        if (empty($docField[0])) {
            return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_FIELD_NOT_FOUND') . '</div>');
        } else {
            $docField = $docField[0];
            $docField = $docField->getPropertyName();
        }

        if (!empty($this->arguments['id'])) {
          $entry = $entryManager->getOne($this->arguments['id']);
            if (!empty($entry) && !empty($entry[$docField]['documentUrl'])) {
                return $service->showDocument(
                    $availableServices[$entry[$docField]['documentType']],
                    $entry,
                    $docField
                );
            } else {
                return('<div class="alert alert-danger">' . _t('DOCUMENTS_ACTION_ENTRY_NOT_FOUND', [ 'id' => $this->arguments['id']]) . '</div>');
            }
        } else {
                   $data = [
              'bf_titre' => _t(
                  'DOCUMENTS_DOC_IN_PAGE',
                  [
                  'type' => $this->arguments['type'],
                  'page' => $this->wiki->getPageTag()
                ]
              ),
              'id_typeannonce' => $formId,
              'bf_statut' => _t('DOCUMENTS_DRAFT_STATUS'),
              $docField => ['documentType' => $this->arguments['type']],
              'antispam' => '1',
            ];
            $entry = $entryManager->create($formId, $data);
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
