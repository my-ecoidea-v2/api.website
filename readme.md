# My-EcoIdea-v2 API
L'api du site [my-ecoidea.org](https://my-ecoidea.org), créer par quentin

## Variables générales
Les rôles
```
[1] Administrateur
[2] Modérateur
[3] Officiel
[4] Soutien
```
Les utilisateurs
```
id
name
email
avatar
role
muted
muted_reason
banned
banned_reason
```
Les Eco-Idées
```
id
token
description
texte
keywords
links
```
Les Eco-Slogan
```
id
token
texte
```

## Utilisateurs
### Inscription /user/create/

```
Type : POST
Champs :
- name
- email
- key
- password
- password_confirmation
Réponse :
- user
- token
```
### Connexion /user/login/

```
Type : POST
Champs :
- email
- password
Réponse :
- user
- token
```
### Récupération des informations /user/get/

```
Type : GET
Authorisation : Bearer token
Réponse :
- user
```
### Connexion /user/modify/

```
Type : PUT
Authorisation : Bearer token
Champs :
- name
- email
- password
Réponse :
- user
```
### Supression /user/delete/

```
Type : DELETE
Authorisation : Bearer token
Champs :
- password
```
### Déconnection /user/logout/

```
Type : POST
Authorisation : Bearer token
```
### Récuperer Mes Favoris /user/meFavoris/

```
Type : GET
Authorisation : Bearer token
```
### Récuperer Mes Idées /user/meIdea/

```
Type : GET
Authorisation : Bearer token
```
### Récuperer Mes Publications /user/mePublications/

```
Type : GET
Authorisation : Bearer token
```
## Publications
### Créer /publication/create/

```
Type : POST
Authorisation : Bearer token
Champs :
- type
- anonyme (boolean)
Réponse :
- error or success
```
Pour le type = 1, Eco-Idée
```
Champs :
- description
- texte
- keyword_1
- keyword_2
- keyword_3
* link_1
* link_2
* link_3
```
Pour le type = 2, Eco-Slogan
```
Champs :
- texte
```
### Publier /publication/publish/

```
Type : PUT
Authorisation : Bearer token + R[2]
Champs :
- token
Réponse :
- error or success
```
### Supprimer /publication/delete/

```
Type : DELETE
Authorisation : Bearer token
Champs :
- reason
Réponse :
- error or success
```
### Récupérer par token /publication/get/

```
Type : GET
Authorisation : Bearer token
Champs :
- token
Réponse :
- error or success
- publication
```
### Fil d'actualité /publication/getFast/

```
Type : GET
Authorisation : Bearer token
Réponse :
- publications
```
### Fil de modération /publication/getModeration/

```
Type : GET
Authorisation : Bearer token + R[2]
Réponse :
- publications
```
### Rechercher /publication/search/

```
Type : GET
Authorisation : Bearer token
Réponse :
- awser
```

## Les intéractions
### Like /publication/like/

```
Type : PUT
Authorisation : Bearer token
Réponse :
- token
```
### Favoris /publication/favoris/

```
Type : PUT
Authorisation : Bearer token
Réponse :
- token
```
### Vu /publication/seen/

```
Type : PUT
Authorisation : Bearer token
Réponse :
- token
```
## Autres
### Retour bêta /retour-beta/

```
Type : POST
Authorisation : Bearer token
Field :
- texte
Réponse :
- error or success
```