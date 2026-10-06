<?php

namespace YesWiki\Documents\Service;

use function Symfony\Component\String\u;

use YesWiki\Render\Service\TemplateEngine;

/** One kind of collaborative document: how it is created, and how it shows in a page. */
abstract class DocumentProvider
{
    public function __construct(protected TemplateEngine $templateEngine)
    {
    }

    /** The name a configured service is matched against, `etherpad` for `documentsEtherpadUrl`. */
    abstract public function providerName(): string;

    /**
     * The address of a new document for this entry.
     *
     * @param array<string, mixed> $docConfig
     * @param array<string, mixed> $entry
     */
    abstract public function createDocument(array $docConfig, array $entry): string;

    /** A slug of the title followed by a unique number, at most $maxSize characters. */
    public function createDocumentId(string $title, int $maxSize = 255): string
    {
        $slug = (string)u($title)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9\s-]/', '')
            ->replaceMatches('/\s+/', '_')
            ->trim('_');
        $uniqueId = time() . mt_rand(1000, 9999);
        if (strlen($slug) > ($maxSize - 15)) {
            $slug = substr($slug, 0, $maxSize - 15);
        }

        return $slug . '-' . $uniqueId;
    }

    /**
     * The document itself, framed in the page or behind a link.
     *
     * @param array<string, mixed> $docConfig
     */
    public function showDocument(array $docConfig, string $documentUrl): string
    {
        return $this->templateEngine->render('@documents/document-frame.twig', [
            'url' => $documentUrl,
            'iframe' => ($docConfig['iframe'] ?? false) === true,
        ]);
    }
}
