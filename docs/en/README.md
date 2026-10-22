# documents extension

Attaches a collaborative document to an entry and shows it in the page, where several
people edit it at once.

Four providers are supported: Etherpad, HedgeDoc, Memo and OnlyOffice. The first three work
as soon as the extension is on, using the yeswiki.net servers.

## What the extension installs

The extension's migration, run on install or by `./yeswicli migrate`, creates the `ListStatut` list (being written, finished,
archived) and form 1500, "Documents collaboratifs". That form carries a `documents` field:
creating an entry means picking a kind of document, and saving the entry creates it on that
service.

The `documents` field can be added to any other form too: it is in the form builder's
palette, as "Collaborative document".

## Choosing a field's document types

In the form builder, the "Document types offered" setting lists the configured services,
one checkbox each. A new field has all of them ticked.

- Several ticked: whoever creates the entry picks one of those types.
- One ticked: nothing is asked, and a document of that type is created when the entry is
  saved.
- None ticked: every configured service is offered. Form 1500 is set up this way.

The setting is stored in the field's `document_types` key, for example
`"document_types": "etherpad,memo"`.

## The `{{document}}` action

It is in the editor's palette, under Media, as "Collaborative document".

```
{{document type="etherpad"}}
```

On the first visit, the action creates an entry of form 1500 holding a new document, then
rewrites the page to write that entry's id into it:

```
{{document type="etherpad" id="doc-etherpad-dans-la-page-accueil"}}
```

Later visits show that document. Only someone who can edit the page triggers the creation;
anyone else sees a message.

| Parameter | Purpose |
|---|---|
| `type` | `etherpad`, `hedgedoc`, `memo`, or any other configured service |
| `id` | the entry whose document to show; without it, a document is created |
| `formId` | the form the entry is created in, 1500 by default (key `documentsFormId`) |

The editor's preview does not run the action: it shows a short message instead.

## Configuration

Each service is declared by a set of keys in `yeswiki.config.php`, prefixed with
`documents` and the service name. The keys of the bundled services can also be changed
from the wiki's configuration screen, in the "Documents collaboratifs" group.

| Key | Required | Purpose |
|---|---|---|
| `documents<Service>Url` | yes | URL of the service's server |
| `documents<Service>Title` | yes | label shown in the form |
| `documents<Service>Description` | yes | description shown in the form |
| `documents<Service>Iframe` | no | shows the document in an iframe rather than behind a link |
| `documents<Service>Credentials` | no | key or secret shared with the service |

A service missing one of the first three keys is not offered, and the form says which one.

For Memo, `documentsMemoCredentials` is the API key of an account, made from "My account"
on that Memo. With it, Memo creates the board and it belongs to that account. Without it,
the board comes into being on its first visit.

## OnlyOffice

The service name starts with `OnlyOffice`, and what follows picks the file type:
`OnlyOfficeDocx`, `OnlyOfficeXlsx`, `OnlyOfficePptx`. With no suffix, it is a `docx`.

```php
'documentsOnlyOfficeDocxUrl' => 'https://onlyoffice.example.org',
'documentsOnlyOfficeDocxTitle' => 'Office document',
'documentsOnlyOfficeDocxDescription' => 'Word processor',
'documentsOnlyOfficeDocxIframe' => true,
'documentsOnlyOfficeDocxCredentials' => 'a-secret-of-at-least-32-characters',
```

The secret can also be set in `documentsCredentials`, per service:
`'documentsCredentials' => ['onlyofficedocx' => '…']`. That one wins over
`documents<Service>Credentials`.

On creation, a blank file is copied into `files/` through the wiki's storage layer (an S3
bucket works too), and OnlyOffice opens it from its public address.

### The secret must be at least 32 characters

The token sent to OnlyOffice is signed with HS256. Since version 7 of `firebase/php-jwt`, a
key under 32 bytes is refused and the document does not show. Lengthening the secret, on
both sides, is the only fix.

### The OnlyOffice callback

When a document has changed, the OnlyOffice server calls `/<Page>/onlyoffice?filename=…`
so that the wiki rewrites the file. The callback is accepted only when:

- it is signed with the secret of a configured OnlyOffice service;
- the download address it carries points at that same service's server;
- the filename names an existing office file directly under `files/`.

Any other callback is refused, and the JSON answer says why. So without a secret set on
both sides, documents stop saving.
