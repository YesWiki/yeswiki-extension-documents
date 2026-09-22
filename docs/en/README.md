# documents extension

Adds a bazar field attaching a collaborative document to an entry. The document is
edited right inside the page, by several people at once.

Two providers are supported: OnlyOffice and Etherpad.

## Configuration

Each provider is declared by a set of keys in `wakka.config.php`, all prefixed with
`documents` followed by the service name.

| Key | Required | Purpose |
|---|---|---|
| `documents<Service>Url` | yes | URL of the service server |
| `documents<Service>Title` | yes | label shown in the form |
| `documents<Service>Description` | yes | description shown in the form |
| `documents<Service>Iframe` | no | shows the document inside an iframe |
| `documents<Service>Credentials` | no | shared secret with the service |

Example for OnlyOffice:

```php
'documentsOnlyOfficeUrl' => 'https://onlyoffice.example.org',
'documentsOnlyOfficeTitle' => 'Office document',
'documentsOnlyOfficeDescription' => 'Word processor, spreadsheet, presentation',
'documentsOnlyOfficeCredentials' => 'a-secret-of-at-least-32-characters',
```

The first three keys are required together: if one is missing the extension stops on an
error message instead of half starting.

## The OnlyOffice secret must be at least 32 characters

The token sent to OnlyOffice is signed with HS256. Since version 7 of
`firebase/php-jwt`, a key shorter than the hash output is refused, so under 32 bytes for
HS256. A shorter secret makes the document fail to display.

That constraint comes from the fix for a vulnerability in the library's version 6 line.
Lengthening the secret, on both sides, is the only way forward.

## Usage

Add the `documents` field to the bazar form. When an entry is created, the document is
created on the service and attached to the entry.

## The OnlyOffice callback

The OnlyOffice server calls `?onlyoffice&filename=…` when a document has been changed,
so that the wiki rewrites the file.

That callback is only accepted when it is signed with the
`documentsCredentials['onlyoffice']` secret, when the download url it carries points at
the configured OnlyOffice server, and when the filename names an existing file of the
`files/` folder. Everything else is refused, and the JSON answer says why.

This is why the secret has to be set on both sides: without it the callback is refused
and documents stop saving.
