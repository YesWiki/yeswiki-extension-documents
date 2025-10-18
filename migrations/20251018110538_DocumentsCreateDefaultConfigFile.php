<?php

use YesWiki\Core\YesWikiMigration;
use YesWiki\Core\Service\ConfigurationService;

class DocumentsCreateDefaultConfigFile extends YesWikiMigration
{
    public function run()
    {
        $params = $this->wiki->services->getParameterBag();
        $actualConfig = $params->all();
        if (empty($actualConfig['documentsType'])) {
            $config = $this->wiki->services->get(ConfigurationService::class)->getConfiguration('wakka.config.php');
            $config->load();
            $config['documentsType'] = [
            'etherpad' => [
                'service' => 'etherpad',
                'label' => 'Les pads yeswiki',
                'description' => 'Mieux que framapad',
                'url' => 'https://pad.yeswiki.net/',
                'iframe' => true,
            ],
            'memo' => [
                'service' => 'memo',
                'label' => 'Memo',
                'description' => 'Post-it new gen',
                'url' => 'https://memo.yeswiki.pro/',
                'iframe' => true,
            ],
            'hedgedoc' => [
                'service' => 'hedgedoc',
                'label' => 'Hedgedoc',
                'description' => 'Edition de markdown',
                'url' => 'https://md.yeswiki.net',
                'iframe' => true,
            ],
            ];
            $config->write();
        }
    }
}
