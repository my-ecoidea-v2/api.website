# Api.My-EcoIdea.org Documentation

### All features resume ###
[api/user/register]
[api/user/login]
[api/user/logout]
[api/user/modify]
[api/user/delete]
[api/user/get]
[api/publication/create]
[api/publication/publish]
[api/publication/delete]
[api/publication/get]
[api/publication/getAll]

[api/register]
## Création d'un utilisateur
> request type : json
> method : post
> fields :
- 'name' required | max:75
- 'key' //clés bêta
- 'email' required | max:191
- 'password' required | min :6
- 'password_confirmation' required |
return :
- 'status':'success/error'
- 'token':'[user_token]', 'user' :'[user_informations]'
- 'error :'[error_description]

[api/login]
## Connexion d'un utilisateur
> request type : json
> method : post
> fields :
- 'email' required | max:191
- 'password' required | min :6
return :
- 'status':'success/error'
- 'token':'[user_token]', 'user' :'[user_informations]'
- 'error :'[error_description]

[api/logout]
## Déconnexion d'un utilisateur
> request type : json
> method : post
> authorisation :
- Bearer token
return :
- 'status':'success/error'

[api/user]
## Récupération d'informations sur un utilisateur
> request type : json
> method : get
> authorisation :
- Bearer token
return :
- 'status':'success/error'
- 'user':'[user_information]'

[api/modify]
## Modification des informations du profil utilisateur
> request type : json
> method : put
> authorisation :
- Bearer token
> fields :
- 'password' required
- 'new_name'
- 'new_email'
- 'new_password'
return :
- 'status':'success/error'
- 'user':'[user_information]

[api/delete]
## Suppression de son profil
> request type : json
> method : delete
> authorisation :
- Bearer token
> fields : 
- 'password' required
return :
- 'status':'success/error'

[api/publication/create]
## Permet de créer une publication
> request type : json
> method : post
> authorisation :
- User Bearer token
> fields : 
- user_id required
- type_id required [1(idée)]
- anonyme required
### If type == 1
- description required
- keyword_1 required
- keyword_2 required
- keyword_3 required
- categorie_id required
- texte required
- link_1
- link_2
- link_3

[api/publication/publish]
## Publie une idée dans le fil d'actualité (acceptation d'idée)
> request type : json
> method : post
> authorisation :
- User Bearer token
> fields
- id required [publication_id]
- acceptBy required [user_id]

[api/publication/delete]
## Supprimer une publication
> request type : json
> method : post
> authorisation :
- User Bearer token
> fields :
- id

[api/publication/get]
## Récupère les informations d'une publication
> request type : json
> method : post
> authorisation :
- Publication Bearer token

[api/publication/getAll]
## Récupère les informations de toutes les publication
> request type : json
> method : post
> authorisation :
- User Bearer token

## User rôles
[0] Member
[1] Tester
[2] Supporter
[3] Moderator
[4] Administrator

## Error structure
[required] The field is empty but is required by the databse
[invalid] The field is invalid for the database, mayby too long/short, or invalid sytaxe
[used] The field is already used in database but it can by duplicated
[bad] The field don't match with the database value