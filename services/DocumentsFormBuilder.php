<?php

namespace YesWiki\Documents\Service;

use YesWiki\Content\Entity\ExtendsFormBuilder;

/** Puts the `documents` field in the form builder, with the configured services to choose from. */
class DocumentsFormBuilder implements ExtendsFormBuilder
{
    public function __construct(private DocumentsService $documentsService)
    {
    }

    public function formBuilderScripts(): array
    {
        return ['extensions/documents/javascripts/form-builder-documents.js'];
    }

    public function formBuilderData(): array
    {
        $services = [];
        foreach ($this->documentsService->getAllDocumentsService() as $key => $service) {
            $services[$key] = (string)$service['label'];
        }

        return ['documents' => ['services' => $services]];
    }
}
