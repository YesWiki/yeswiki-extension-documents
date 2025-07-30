<?php

/**
 * English translation for Documents module
 */

$GLOBALS['translations'] = array_merge(
    $GLOBALS['translations'],
    array(
        'DOCUMENTS_ETHERPAD_LABEL' => 'Etherpad',
        'DOCUMENTS_ETHERPAD_DESCRIPTION' => 'A simple collaborative document',
        'DOCUMENTS_MEMO_LABEL' => 'Memo',
        'DOCUMENTS_MEMO_DESCRIPTION' => 'A collaborative sticky note board',
        'DOCUMENTS_HEDGEDOC_LABEL' => 'HedgeDoc',
        'DOCUMENTS_HEDGEDOC_DESCRIPTION' => 'A collaborative markdown editor',
        'DOCUMENTS_ONLYOFFICE_DOC_LABEL' => 'Docx Only-office',
        'DOCUMENTS_ONLYOFFICE_DOC_DESCRIPTION' => 'Only-office docx document',
        'DOCUMENTS_INVALID_CONFIG_ERROR' => 'Invalid configuration for document type \'%s\'. Expected an array with \'label\', \'description\', \'service\' and \'url\'.',
        'DOCUMENTS_MISSING_CREDENTIALS_ERROR' => 'Missing configuration for document type \'%s\'. Expected not empty value in the configuration config[\'documentsCredentials\'][\'%s\'].',
        'DOCUMENTS_NO_URL_GENERATED' => 'No URL generated',
        'DOCUMENTS_UNKNOWN_TITLE' => 'Unknown title',
        'DOCUMENTS_UNKNOWN_STATUS' => 'Unknown status',
        'DOCUMENTS_ACCESS_DOCUMENT' => 'Access document',
        'DOCUMENTS_IN_PAGE' => 'in page',
        'DOCUMENTS_STATUS' => 'Status',
        'DOCUMENTS_MODIFY' => 'Edit',
        'DOCUMENTS_CURL_ERROR' => 'cURL error while retrieving HedgeDoc URL. url: %s',
        'DOCUMENTS_WRONG_NON_EXISTENT_FILENAME' => 'Wrong or non-existent filename : files/%s',
        'DOCUMENTS_LINK_ACCESS' => 'Document access link',
        'DOCUMENTS_ACTION_TYPE_MISSING' => 'Document action: document type must be specified.',
        'DOCUMENTS_ACTION_INVALID_TYPE' => 'Document action: document type must be chosen from the following list: "%{doc}".',
        'DOCUMENTS_ACTION_ENTRY_NOT_FOUND' => 'Document action: entry %{id} does not seem to exist or does not contain a document.',
        'DOCUMENTS_DOC_IN_PAGE' => 'Doc %{type} in page %{page}',
        'DOCUMENTS_DRAFT_STATUS' => 'in_drafting',
        'DOCUMENTS_BAD_REQUEST' => 'Bad Request',
        'DOCUMENTS_BAD_RESPONSE' => 'Bad Response',
        'DOCUMENTS_ACTION_NO_PREVIEW' => 'The action {{document}} cannot be previewed',
        'AB_document_action_type_label' => 'Document type',
        'AB_document_action_etherpad' => 'Etherpad',
        'AB_document_action_hedgedoc' => 'HedgeDoc',
        'AB_document_action_memo' => 'Memo',
        'AB_document_action_formid_label' => 'Bazar Form Id associated',
        'AB_document_action_id_label' => 'Entry Id associated (id_fiche)',
        'AB_document_action_id_hint' => 'If not indicated, a new document will be generated.',

    )
);
