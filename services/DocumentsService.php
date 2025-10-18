<?php

namespace YesWiki\Documents\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Wiki;
use YesWiki\Bazar\Service\ListManager;

class DocumentsService
{
    protected $params;
    protected $services;
    protected $entryManager;
    protected $formManager;
    protected $listManager;
    protected $wiki;
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
    }

    public function getAvailableServices($config): array 
    {
      $matches = preg_grep("/^documents(.*)Url$/", array_keys($config));
      $services = [];
      foreach ($matches as $ser) {
        $name = preg_replace('/^documents(.*)Url$/', '$1', $ser);
        $lowerName = strtolower($name);
        $services[$lowerName] = $name;
      }
      return $services;
    }

    public function getAllDocumentsService()
    {
      $fullServices = [];
      $availableServices = $this->getAvailableServices($this->wiki->config);
      foreach($availableServices as $service => $serviceName) {
        if (!empty($this->wiki->config['documents'.$serviceName.'Url']) && !empty($this->wiki->config['documents'.$serviceName.'Title']) && !empty($this->wiki->config['documents'.$serviceName.'Description'])) {
                $fullServices[$service] = [
                    'provider-name' => $service,
                    'service' => $serviceName,
                    'label' => $this->wiki->config['documents'.$serviceName.'Title'],
                    'description' => $this->wiki->config['documents'.$serviceName.'Description'],
                    'url' => $this->wiki->config['documents'.$serviceName.'Url'],
                    'iframe' => $this->wiki->config['documents'.$serviceName.'Iframe'] ?? false,
                    'credentials' => $this->wiki->config['documents'.$serviceName.'Credentials'] ?? false,
                ];
            } else {
                die(_t(
                    'DOCUMENTS_INVALID_CONFIG_ERROR',
                    [
                    'key' => $service
                ]
                ));
            }
      }
      return $fullServices;
    }

    public function getAvailableDocumentProviders()
    {

        $services = array_filter($this->wiki->services->getServiceIds(), function ($subject) {
            return preg_match('/DocumentProvider$/', $subject);
        });

        $docProviders = [];
        foreach ($services as $serv) {
            $short = explode('Service\\', $serv)[1];
            $shortClass = strtolower(str_replace(['DocumentProvider'], '', $short));
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

    /**
     * Créé un document en déléguant au fournisseur approprié.
     * @param array $docConfig Configuration du type de document (issue de documentsType).
     * @param array $entry Données de l'entrée Bazar associée au document.
     * @return string URL du document créé.
     */
    public function createDocument($docConfig, array $entry = [])
    {
        $providerName = strtolower($docConfig['service']);
        if (!isset($this->providers[$providerName])) {
            return _t('DOCUMENTS_UNSUPPORTED_SERVICE', ['service' => $providerName]);
        }
        $provider = $this->providers[$providerName];
        return $provider->createDocument($docConfig, $entry);
    }

    /**
     * Affiche un document en déléguant au fournisseur approprié.
     * @param array $docConfig Configuration du type de document (issue de documentsType).
     * @param array $entry Données de l'entrée Bazar associée au document.
     * @return string Le HTML généré pour afficher le document.
     */
    public function showDocument($docConfig, array $entry = [], $fieldId = '')
    {
        $documentUrl = $entry[$fieldId]['documentUrl'] ?? null;
        if (empty($documentUrl)) {
            return _t('DOCUMENTS_NO_URL_GENERATED');
        }
        if (!isset($this->providers[$docConfig['provider-name']])) {
            return _t('DOCUMENTS_UNSUPPORTED_SERVICE', ['service' => $docConfig['provider-name']]);
        }
        $provider = $this->providers[$docConfig['provider-name']];
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
