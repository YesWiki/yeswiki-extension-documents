<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Wiki;
use YesWiki\Bazar\Service\ListManager;
use Firebase\JWT\JWT;

class DocumentsService
{
    protected $params;
    protected $services;
    protected $entryManager;
    protected $formManager;
    protected $listManager;
    protected $wiki;
    protected $documentsDefault;
    protected $providers;

    public function __construct(
        ParameterBagInterface $params,
        ContainerInterface $services,
        EntryManager $entryManager,
        FormManager $formManager,
        ListManager $listManager,
        Wiki $wiki
    ) {
        $this->params = $params;
        $this->services = $services;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->listManager = $listManager;
        $this->wiki = $wiki;
        $this->providers = $this->instanciateDocumentProviders();
        $this->documentsDefault = $this->getAllDocumentsDefaults();

        $initialConfig = $this->wiki->config['documentsType'] ?? [];
        if (!empty($initialConfig)) {
            $this->initDocumentsConfig($initialConfig);
        } else {
            $this->initDocumentsConfig($this->documentsDefault);
        }
        // dump($this->providers);
        // $this->providers['Etherpad']->createDocument(['bf_documents' => 'etherpad']);
    }

    /** initiDocumentsConfig() - validate config and add default values, if needed.
     *
     * @return void
    */
    public function initDocumentsConfig($config)
    {
        $result = [];
        foreach ($config as $key => $value) {
            if (is_array($value) && isset($value['label'], $value['description'], $value['url'], $value['service'])) {
                $result[$key] = [
                    'provider-name' => $key,
                    'service' => $value['service'],
                    'label' => $value['label'],
                    'description' => $value['description'],
                    'url' => $value['url'],
                    'iframe' => $value['iframe'] ?? false,
                    'need-credentials' => $value['need-credentials'] ?? false,
                    'options' => $value['options'] ?? [],
                ];
            } else {
                die(_t(
                    'DOCUMENTS_INVALID_CONFIG_ERROR',
                    [
                    'key' => $key
                ]
                ));
            }
        }
        foreach ($result as $key => $value) {
            if ($value['need-credentials']) {
                $credentials = $this->wiki->config['documentsCredentials'][$key] ?? null;
                if (empty($credentials)) {
                    die(_t(
                        'DOCUMENTS_MISSING_CREDENTIALS_ERROR',
                        [
                        'key' => $key,
                        'value' => $key
                    ]
                    ));
                }
            }
        }
        $this->wiki->config['documentsType'] = $result;
    }

    public function getAvailableDocumentProviders()
    {

        $services = array_filter($this->wiki->services->getServiceIds(), function ($subject) {
            return preg_match('/DocumentProvider$/', $subject);
        });

        $docProviders = [];
        foreach ($services as $serv) {
            $short = explode('Service\\', $serv)[1];
            $shortClass = str_replace(['DocumentProvider'], '', $short);
            $docProviders[$shortClass] = $serv;
        }
        return $docProviders;
    }

    private function instanciateDocumentProviders()
    {
        $available = $this->getAvailableDocumentProviders();
        $docProviderClasses = [];
        foreach ($available as $docProvider => $className) {
            if (!empty($className) && class_exists($className)) {
                $docProviderClasses[$docProvider] = new $className(
                    $this->params,
                    $this->services,
                    $this->entryManager,
                    $this->formManager,
                    $this->listManager,
                    $this->wiki
                );
            } else {
                // Gérer le cas où la classe n'existe pas, peut-être loguer ou lancer une exception
                error_log("DocumentProvider class not found: {$className}");
            }
        }
        return $docProviderClasses;
    }

    private function getAllDocumentsDefaults()
    {
        $defaults = [];
        foreach ($this->providers as $name => $provider) {
            $defaults = array_merge($defaults, $provider->getDefaultInstance());
        }
        return $defaults;
    }

    /**
     * Affiche un document en déléguant au fournisseur approprié.
     * @param array $docConfig Configuration du type de document (issue de documentsType).
     * @param array $entry Données de l'entrée Bazar associée au document.
     * @return string Le HTML généré pour afficher le document.
     */
    public function showDocument($docConfig, array $entry = [])
    {
        $documentUrl = $entry['bf_document_url'] ?? null;
        if (empty($documentUrl)) {
            return _t('DOCUMENTS_NO_URL_GENERATED');
        }

        $providerName = ucfirst($docConfig['service']);
        if (!isset($this->providers[$providerName])) {
            return _t('DOCUMENTS_UNSUPPORTED_SERVICE', ['service' => $docConfig['service']]);
        }

        /** @var DocumentProvider $provider */
        $provider = $this->providers[$providerName];

        $output = $provider->showDocument([
            'docConfig' => $docConfig,
            'entry' => $entry,
            'documentUrl' => $documentUrl,
            'wiki' => $this->wiki
        ]);
        $titre = $entry['bf_titre'] ?? _t('DOCUMENTS_UNKNOWN_TITLE');
        $statut = $entry['bf_statut'] ?? _t('DOCUMENTS_UNKNOWN_STATUS');
        if ($statut !== _t('DOCUMENTS_UNKNOWN_STATUS')) {
            $statusLabel = $this->listManager->getLabel('ListStatut', $statut);
            $statut = empty($statusLabel) ? _t('DOCUMENTS_UNKNOWN_STATUS') : $statusLabel;
        }
        $baseUrl = rtrim($this->wiki->config['base_url'], '/');
        $editLink = $this->wiki->href('edit', $entry['id_fiche'], 'incomingurl='.$this->wiki->href());

        $output .= "<small><a target='_blank' href='{$documentUrl}'><b>{$titre} </b></a> (" . "{$docConfig['label']} - " . _t('DOCUMENTS_STATUS') . ": {$statut}) <a href='{$editLink}'>" . _t('DOCUMENTS_MODIFY') . "</a></small>";

        return $output;
    }
}
