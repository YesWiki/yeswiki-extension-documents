<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Documents\Service\DocumentProvider;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

class MemoDocumentProvider extends DocumentProvider
{
    public function __construct(
        ParameterBagInterface $params,
        ContainerInterface $services,
        EntryManager $entryManager,
        FormManager $formManager,
        ListManager $listManager,
        Wiki $wiki
    ) {
        parent::__construct($params, $services, $entryManager, $formManager, $listManager, $wiki);
    }

    /**
     * Check if config input is good enough to be used by Importer
     * @param array $config
     * @return array $config checked config
     */
    public function checkConfig(array $config)
    {
        return $config;
    }

    /*
     * @param array $docConfig La configuration du document.
     * @param array $entry Les données de l'entrée Bazar.
     * @return string L'URL du document créé.
     */
    public function createDocument(array $docConfig, array $entry)
    {
        $title = $entry['bf_titre'] ?? 'Nouveau mémo';
        if (!$docConfig || !isset($docConfig['url'])) {
            throw new \RuntimeException("Configuration Memo invalide ou manquante.");
        }
        $baseUrl = rtrim($docConfig['url'], '/');

        // Avec une clé, c'est Memo qui crée le tableau et renvoie son adresse.
        // Le tableau appartient alors au compte que la clé représente, qui le
        // retrouve dans sa liste et peut le fermer aux autres. Sans clé, on se
        // contente de fabriquer une adresse : le tableau naîtra au premier
        // visiteur, si ce Memo laisse faire.
        $key = $docConfig['credentials'] ?? false;
        if (!empty($key)) {
            return $this->createRemoteBoard($baseUrl, $key, $title);
        }

        $generatedUrl = "{$baseUrl}/".$this->createDocumentId($title);
        return $generatedUrl;
    }

    /**
     * Demande à Memo de créer le tableau et renvoie l'URL qu'il donne.
     * @param string $baseUrl L'adresse du Memo.
     * @param string $key La clé API d'un compte sur ce Memo.
     * @param string $title L'intitulé du tableau à créer.
     * @return string L'URL du tableau créé.
     */
    protected function createRemoteBoard(string $baseUrl, string $key, string $title)
    {
        $ch = curl_init("{$baseUrl}/api/boards");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$key}",
            ],
            CURLOPT_POSTFIELDS => json_encode(['title' => $title]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $answer = is_string($body) ? json_decode($body, true) : null;

        if ($status !== 200 || empty($answer['url'])) {
            throw new \RuntimeException(_t(
                'DOCUMENTS_MEMO_API_ERROR',
                [
                    'baseUrl' => "{$baseUrl}/api/boards",
                    'status' => $status,
                    'reason' => $answer['error'] ?? '',
                ]
            ));
        }

        return $answer['url'];
    }

    public function getDefaultInstance(): array
    {
        return [
          'memo' => [
              'service' => 'memo',
              'label' => _t('DOCUMENTS_MEMO_LABEL'),
              'description' => _t('DOCUMENTS_MEMO_DESCRIPTION'),
              'url' => 'https://memo.yeswiki.pro/',
              'iframe' => true,
              'credentials' => '',
          ],
        ];
    }
}
