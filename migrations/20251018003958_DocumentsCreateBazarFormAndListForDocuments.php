<?php

use YesWiki\Content\Entity\PageType;
use YesWiki\Content\Service\FormManager;
use YesWiki\Content\Service\PageManager;
use YesWiki\Core\YesWikiMigration;

/** The status list and the documents form the extension's field lives in, created once and left alone after. */
class DocumentsCreateBazarFormAndListForDocuments extends YesWikiMigration
{
    public function run()
    {
        $this->createLists(dirname(__DIR__) . '/setup/lists');
        $this->createForms(dirname(__DIR__) . '/setup/forms');
    }

    /** Each `<Tag>.json` of $directory becomes the Value list `<Tag>`, unless that tag is taken. */
    public function createLists(string $directory): void
    {
        $pageManager = $this->getService(PageManager::class);
        foreach (glob($directory . '/*.json') ?: [] as $filename) {
            $tag = basename($filename, '.json');
            $list = json_decode((string)file_get_contents($filename), true);
            if (!is_array($list) || $pageManager->tagExists($tag)) {
                continue;
            }
            $pageManager->save($tag, ['title' => (string)($list['title'] ?? $tag), 'nodes' => is_array($list['nodes'] ?? null) ? $list['nodes'] : []], '', true, null, PageType::LIST);
            $pageManager->cacheType($tag, PageType::LIST);
            $this->say("documents: list {$tag} created");
        }
    }

    /** Each `<id>.json` of $directory becomes form `<id>`, unless a form already has that id. */
    public function createForms(string $directory): void
    {
        $formManager = $this->getService(FormManager::class);
        foreach (glob($directory . '/*.json') ?: [] as $filename) {
            $formId = basename($filename, '.json');
            $form = json_decode((string)file_get_contents($filename), true);
            if (!is_array($form) || $formManager->getOne($formId) !== null) {
                continue;
            }
            $form['id'] = $formId;
            $formManager->create($form);
            $this->say("documents: form {$formId} created");
        }
    }
}
