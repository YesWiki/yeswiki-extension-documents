<?php

namespace YesWiki\Documents;

use YesWiki\Core\YesWikiHandler;

class OnlyOfficeHandler extends YesWikiHandler
{
    public function run()
    {
        $body_stream = '';
        if (($body_stream = file_get_contents("php://input")) === false) {
            echo _t('DOCUMENTS_BAD_REQUEST');
        }
        error_log("coucou handler onlyoffice !".$body_stream, 0);
        $data = json_decode($body_stream, true);

        if (!empty($data) && $data["status"] == 2) {
            $downloadUri = $data["url"];

            if (($newData = file_get_contents($downloadUri)) === false) {
                return _t('DOCUMENTS_BAD_RESPONSE');
            } else {
                $file = filter_input(INPUT_GET, 'filename', FILTER_SANITIZE_SPECIAL_CHARS);
                if (!empty($file) && file_exists('files/'.$file)) {
                    file_put_contents('files/'.$file, $newData, LOCK_EX);
                } else {
                    return _t('DOCUMENTS_WRONG_NON_EXISTENT_FILENAME',
                        [
                        'type' => $file,
                        ]
                    );
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        return json_encode(['error' => 0]);
    }
}