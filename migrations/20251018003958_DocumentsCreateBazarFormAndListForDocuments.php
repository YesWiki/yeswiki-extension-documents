<?php

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiMigration;

class DocumentsCreateBazarFormAndListForDocuments extends YesWikiMigration
{
    public function run()
    {
        $pageManager = $this->getService(PageManager::class);
        $tripleStore = $this->getService(TripleStore::class);
        $dbService = $this->getService(DbService::class);
        $entryManager = $this->getService(EntryManager::class);
        $formManager = $this->getService(FormManager::class);

        $glob = glob('tools/documents/setup/lists/*.json');
        foreach ($glob as $filename) {
            $listname = str_replace(['tools/documents/setup/lists/', '.json'], '', $filename);
            if (file_exists($filename) && !$pageManager->getOne($listname)) {
                // save the page with the list value
                $pageManager->save($listname, file_get_contents($filename));
                // in case, there is already some triples for the list, delete them
                $tripleStore->delete($listname, 'http://outils-reseaux.org/_vocabulary/type', null);
                // create the triple to specify this page is a list
                $tripleStore->create($listname, 'http://outils-reseaux.org/_vocabulary/type', 'liste', '', '');
            }
        }

        $glob = glob('tools/documents/setup/forms/*.json');
        foreach ($glob as $filename) {
            $formId = str_replace(['tools/documents/setup/forms/', '.json'], '', $filename);

            // test if the form exists, if not, install it
            $form = $formManager->getOne($formId);
            if (empty($form)) {
                $form = json_decode(file_get_contents($filename), true);
                $formManager->create($form);
            }
        }
    }
}

