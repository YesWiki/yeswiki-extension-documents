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
        return $arg;
    }

    public function run(): ?string
    {
        $service = $this->getService(DocumentsService::class);
        $entryManager = $this->getService(EntryManager::class);
        $pageManager = $this->getService(PageManager::class);
        $availableDocs = array_keys($this->wiki->config['documentsType']);
        if (empty($this->arguments['type'])) {
            return('<div class="alert alert-danger">Action document: il faut préciser un type de document obligatoirement.</div>');
        }
        if (!in_array($this->arguments['type'], $availableDocs)) {
            return('<div class="alert alert-danger">Action document: le type de document doit être choisi parmi la liste suivante : "'.implode('", "', $availableDocs).'".</div>');
        }
        if (!empty($this->arguments['id'])) {
            $entry = $entryManager->getOne($this->arguments['id']);
            if (!empty($entry) && !empty($entry['bf_document_url'])) {
                return $service->showDocument(
                    $this->wiki->config['documentsType'][$entry['bf_documents']],
                    $entry['bf_document_url']
                );
            } else {
                return('<div class="alert alert-danger">Action document: la fiche '.$this->arguments['id'].' ne semble pas exister ou ne contient pas de document.</div>');
            }
        } else {
            $formId = '5';        // TODO : utiliser la valeur génériqueS
            $entry = $entryManager->create($formId, [
              'bf_titre' => 'Doc '.$this->arguments['type'].' dans la page '.$this->wiki->getPageTag(),
              'id_typeannonce' => $formId,
              'bf_statut' => 'en_cour_de_redaction',
              'bf_documents' => $this->arguments['type'],
              'antispam' => '1',
            ]);
            // we collect the page content and replace the action with no id
            $currentPage = $pageManager->getOne($this->wiki->getPageTag());
            $re = '/\{\{document(?!.*id="[^"]+").*\}\}/mU';
            $subst = '{{document type="'.$this->arguments['type'].'" id="'.$entry['id_fiche'].'"}}';
            $body = preg_replace($re, $subst, $currentPage['body'], 1);
            $pageManager->save($this->wiki->getPageTag(), $body);
            $this->wiki->redirect($this->wiki->href());
        }
        return '';
    }
}

