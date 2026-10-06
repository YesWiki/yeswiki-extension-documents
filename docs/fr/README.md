# Extension documents

Attache un document collaboratif à une fiche et l'affiche dans la page, où plusieurs
personnes l'éditent en même temps.

Quatre fournisseurs sont gérés : Etherpad, HedgeDoc, Mémo et OnlyOffice. Les trois premiers
sont réglés dès l'activation, sur les serveurs de yeswiki.net.

## Ce que l'extension installe

La migration de l'extension, lancée à l'installation ou par `./yeswicli migrate`, crée la liste `ListStatut` (en cours de rédaction, finalisé, archivé)
et le formulaire 1500, « Documents collaboratifs ». Ce formulaire porte un champ
`documents` : à la création d'une fiche, on choisit le type de document, et
l'enregistrement le crée sur le service choisi.

Le champ `documents` s'ajoute aussi à n'importe quel autre formulaire, depuis le
constructeur de formulaires.

## L'action `{{document}}`

Elle se trouve dans la palette de l'éditeur, rubrique Médias, sous le nom « Document
collaboratif ».

```
{{document type="etherpad"}}
```

À la première visite, l'action crée une fiche du formulaire 1500 avec un nouveau document,
puis réécrit la page pour y inscrire l'identifiant de cette fiche :

```
{{document type="etherpad" id="doc-etherpad-dans-la-page-accueil"}}
```

Les visites suivantes affichent ce document. Seule une personne qui peut modifier la page
déclenche la création ; les autres voient un message.

| Paramètre | Rôle |
|---|---|
| `type` | `etherpad`, `hedgedoc`, `memo`, ou tout autre service configuré |
| `id` | la fiche dont afficher le document ; absent, un document est créé |
| `formId` | le formulaire où créer la fiche, 1500 par défaut (clé `documentsFormId`) |

L'aperçu de l'éditeur n'exécute pas l'action : elle y affiche un simple message.

## Configuration

Chaque service se déclare par un jeu de clés dans `yeswiki.config.php`, préfixées par
`documents` puis le nom du service. Les clés des services fournis se modifient aussi depuis
l'écran de configuration du wiki, groupe « Documents collaboratifs ».

| Clé | Obligatoire | Rôle |
|---|---|---|
| `documents<Service>Url` | oui | URL du serveur du service |
| `documents<Service>Title` | oui | libellé affiché dans le formulaire |
| `documents<Service>Description` | oui | description affichée dans le formulaire |
| `documents<Service>Iframe` | non | affiche le document dans une iframe plutôt que derrière un lien |
| `documents<Service>Credentials` | non | clé ou secret partagé avec le service |

Un service dont l'une des trois premières clés manque n'est pas proposé, et le formulaire
dit lequel.

Pour Mémo, `documentsMemoCredentials` est la clé API d'un compte, créée depuis « Mon
compte » sur ce Mémo. Avec elle, Mémo crée le tableau, qui appartient à ce compte. Sans
elle, le tableau naît à la première visite.

## OnlyOffice

Le nom du service commence par `OnlyOffice`, et ce qui suit choisit le type de fichier :
`OnlyOfficeDocx`, `OnlyOfficeXlsx`, `OnlyOfficePptx`. Sans suffixe, c'est un `docx`.

```php
'documentsOnlyOfficeDocxUrl' => 'https://onlyoffice.example.org',
'documentsOnlyOfficeDocxTitle' => 'Document bureautique',
'documentsOnlyOfficeDocxDescription' => 'Traitement de texte',
'documentsOnlyOfficeDocxIframe' => true,
'documentsOnlyOfficeDocxCredentials' => 'un-secret-d-au-moins-32-caracteres',
```

Le secret se règle aussi dans `documentsCredentials`, par service :
`'documentsCredentials' => ['onlyofficedocx' => '…']`. Il passe avant
`documents<Service>Credentials`.

À la création, un fichier vierge est copié dans `files/`, par la couche de stockage du
wiki (un bucket S3 fonctionne aussi), et OnlyOffice l'ouvre depuis son adresse publique.

### Le secret doit faire au moins 32 caractères

Le jeton envoyé à OnlyOffice est signé en HS256. Depuis la version 7 de `firebase/php-jwt`,
une clé de moins de 32 octets est refusée, et le document ne s'affiche pas. Allonger le
secret, des deux côtés, est la seule solution.

### Le rappel OnlyOffice

Quand un document a été modifié, le serveur OnlyOffice appelle
`/<Page>/onlyoffice?filename=…` pour que le wiki réécrive le fichier. Ce rappel n'est
accepté que si :

- il est signé avec le secret d'un service OnlyOffice configuré ;
- l'adresse de téléchargement qu'il donne pointe sur le serveur de ce même service ;
- le nom de fichier désigne un fichier bureautique existant, directement dans `files/`.

Tout autre rappel est refusé, et la réponse JSON en donne la raison. Sans secret réglé des
deux côtés, les documents ne s'enregistrent donc plus.
