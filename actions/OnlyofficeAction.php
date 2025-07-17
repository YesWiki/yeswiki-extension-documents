<?php

namespace YesWiki\Documents;

use YesWiki\Core\YesWikiAction;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class OnlyofficeAction extends YesWikiAction
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
        $privateKey = 'pdTqExa88TXo3LF3Op0rSMQI8qOgqtD8';
        $config = [
            'document' => [
                'fileType' => 'docx',
                'key' => 'Khirz6zTPdfd7',
                'title' => 'Pomme.docx',
                'url' => 'https://www.duxburysystems.com/documentation/dbt12.4/samples/word/french-fr.docx',
            ],
            'documentType' => 'word',
            'height' => '800px',
            'width' => '100%',
        ];
        $jwt = JWT::encode($config, $privateKey, 'HS256');
        //$decoded = JWT::decode($jwt, new Key($privateKey, 'HS256'));

        return <<<HTML
<div id="onlyoffice-doc"></div>
<script type="text/javascript" src="https://onlyoffice.yeswiki.net/web-apps/apps/api/documents/api.js"></script>
<script>
const config = {
  document: {
    fileType: "docx",
    key: "Khirz6zTPdfd7",
    title: "Pomme.docx",
    url: "https://www.duxburysystems.com/documentation/dbt12.4/samples/word/french-fr.docx",
  },
  documentType: "word",
  height: "1500px",
  width: "100%",
  token: "$jwt"
};

console.log("Generated JWT:", config.token);
const docEditor = new DocsAPI.DocEditor("onlyoffice-doc", config);

</script>
HTML;
    }
}