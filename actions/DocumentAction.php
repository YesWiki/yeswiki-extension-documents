<?php

namespace YesWiki\Documents;

use YesWiki\Core\YesWikiAction;

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
        if (empty($this->arguments['type'])) {
            return('<div class="alert alert-danger">Action document: il faut préciser un type de document obligatoirement.</div>');
        }
        $this->wiki->config['documentsType'] = ['onlyoffice-doc', 'etherpad'];
        if (!in_array($this->arguments['type'], $this->wiki->config['documentsType'])) {
            return('<div class="alert alert-danger">Action document: le type de document doit être choisi parmi la liste suivante : "'.implode('", "', $this->wiki->config['documentsType']).'".</div>');
        }
        // TODO test if id exist, create the document if not.
        return '';
    }
}

