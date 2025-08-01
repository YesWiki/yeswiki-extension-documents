<?php

/**
 * French translation for Documents module
 */

$GLOBALS['translations'] = array_merge(
    $GLOBALS['translations'],
    array(
        'DOCUMENTS_ETHERPAD_LABEL' => 'Etherpad',
        'DOCUMENTS_ETHERPAD_DESCRIPTION' => 'Un document collaboratif simple',
        'DOCUMENTS_MEMO_LABEL' => 'Memo',
        'DOCUMENTS_MEMO_DESCRIPTION' => 'Un tableau de post-it collaboratif',
        'DOCUMENTS_HEDGEDOC_LABEL' => 'HedgeDoc',
        'DOCUMENTS_HEDGEDOC_DESCRIPTION' => 'Un editeur de markdown collaboratif',
        'DOCUMENTS_ONLYOFFICE_DOC_LABEL' => 'Docx Only-office',
        'DOCUMENTS_ONLYOFFICE_DOC_DESCRIPTION' => 'Document docx Only-office',
        'DOCUMENTS_INVALID_CONFIG_ERROR' => 'Configuration invalide pour le type de document \'%{key}\'. Attendu un tableau avec \'label\', \'description\', \'service\' et \'url\'.',
        'DOCUMENTS_MISSING_CREDENTIALS_ERROR' => 'Configuration manquante pour le type de document \'%{key}\'. Attendu une valeur non vide dans la configuration config[\'documentsCredentials\'][\'%{value}\'].',
        'DOCUMENTS_NO_URL_GENERATED' => 'Aucune URL générée',
        'DOCUMENTS_UNKNOWN_TITLE' => 'Titre inconnu',
        'DOCUMENTS_UNKNOWN_STATUS' => 'Statut inconnu',
        'DOCUMENTS_ACCESS_DOCUMENT' => 'Accéder au document',
        'DOCUMENTS_IN_PAGE' => 'dans la page',
        'DOCUMENTS_STATUS' => 'Statut',
        'DOCUMENTS_MODIFY' => 'Modifier',
        'DOCUMENTS_CURL_ERROR' => 'Erreur cURL lors de la récupération de l\'URL HedgeDoc. url: %{baseUrl}',
        'DOCUMENTS_WRONG_NON_EXISTENT_FILENAME' => 'Nom de fichier incorrect ou inexistant : files/%{type}',
        'DOCUMENTS_LINK_ACCESS' => 'Lien d\'accès au document',
        'DOCUMENTS_ACTION_TYPE_MISSING' => 'Action document: il faut préciser un type de document obligatoirement.',
        'DOCUMENTS_ACTION_INVALID_TYPE' => 'Action document: le type de document doit être choisi parmi la liste suivante : "%{doc}".',
        'DOCUMENTS_ACTION_ENTRY_NOT_FOUND' => 'Action document: la fiche %{id} ne semble pas exister ou ne contient pas de document.',
        'DOCUMENTS_DOC_IN_PAGE' => 'Doc %{type} dans la page %{page}',
        'DOCUMENTS_DRAFT_STATUS' => 'en_cour_de_redaction',
        'DOCUMENTS_BAD_REQUEST' => 'Bad Request',
        'DOCUMENTS_BAD_RESPONSE' => 'Bad Response',
        'DOCUMENTS_ACTION_NO_PREVIEW' => 'L\'action {{document}} ne peut pas être prévisualisée',
        'DOCUMENTS_OPEN_DOCUMENT' => 'Ouvrir le document',
        'AB_document_action_type_label' => 'Type de document',
        'AB_document_action_etherpad' => 'Etherpad',
        'AB_document_action_hedgedoc' => 'HedgeDoc',
        'AB_document_action_memo' => 'Mémo',
        'AB_document_action_formid_label' => 'Identifiant de formulaire bazar associé',
        'AB_document_action_id_label' => 'Identifiant de fiche associée (id_fiche)',
        'AB_document_action_id_hint' => 'Si non renseigné, un nouveau document sera généré.',
    )
);

