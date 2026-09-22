# Extension documents

Ajoute un champ bazar qui attache un document collaboratif à une fiche. Le document
s'édite directement dans la page, à plusieurs.

Deux fournisseurs sont gérés : OnlyOffice et Etherpad.

## Configuration

Chaque fournisseur se déclare par un jeu de clés dans `wakka.config.php`, toutes
préfixées par `documents` suivi du nom du service.

| Clé | Obligatoire | Rôle |
|---|---|---|
| `documents<Service>Url` | oui | URL du serveur du service |
| `documents<Service>Title` | oui | libellé affiché dans le formulaire |
| `documents<Service>Description` | oui | description affichée dans le formulaire |
| `documents<Service>Iframe` | non | affiche le document dans une iframe |
| `documents<Service>Credentials` | non | secret partagé avec le service |

Exemple pour OnlyOffice :

```php
'documentsOnlyOfficeUrl' => 'https://onlyoffice.example.org',
'documentsOnlyOfficeTitle' => 'Document bureautique',
'documentsOnlyOfficeDescription' => 'Traitement de texte, tableur, présentation',
'documentsOnlyOfficeCredentials' => 'un-secret-d-au-moins-32-caracteres',
```

Les trois premières clés sont obligatoires ensemble : si l'une manque, l'extension
s'arrête sur un message d'erreur au lieu de démarrer à moitié.

## Le secret OnlyOffice doit faire au moins 32 caractères

Le jeton envoyé à OnlyOffice est signé en HS256. Depuis la version 7 de
`firebase/php-jwt`, une clé plus courte que la sortie du hash est refusée, donc moins
de 32 octets pour HS256. Un secret plus court fait échouer l'affichage du document.

Cette contrainte vient du correctif d'une faille de la branche 6 de la bibliothèque.
Allonger le secret, des deux côtés, est la seule marche à suivre.

## Utilisation

Ajouter le champ `documents` au formulaire bazar. À la création d'une fiche, le
document est créé sur le service et attaché à la fiche.

## Le rappel OnlyOffice

Le serveur OnlyOffice appelle `?onlyoffice&filename=…` quand un document a été
modifié, pour que le wiki réécrive le fichier.

Ce rappel n'est accepté que s'il est signé avec le secret de
`documentsCredentials['onlyoffice']`, que l'url de téléchargement qu'il donne pointe
sur le serveur OnlyOffice configuré, et que le nom de fichier désigne un fichier
existant du dossier `files/`. Tout le reste est refusé, et la réponse JSON en donne la
raison.

C'est pourquoi le secret doit être réglé des deux côtés : sans lui, le rappel est
refusé et les documents ne se sauvegardent plus.
