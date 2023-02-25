# Symfony Ecommerce
## Lancer le projet 
- Installez xampp si ce n'est déjà fait ;
- Clônez ce repo ou téléchargez le fichier.zip ;
- Placez ce dossier dans C:/xampp/htdocs ;
- Créez votre propre base de données nommée `symfonyecommerce` ;
- Importez le fichier `symfonyecommerce.sql` dans votre base de données ;
- Dans votre terminal, placez-vous dans votre dossier en tapant `c:/xampp/htdocs/symfony_ecommerce-main` ;
- Lancez le server en tapant `symfony server:start -d` ;
- Vous devriez maintenant voir : `Listening on http://127.0.0.1:8000` (un autre port peut être indiqué si le 8000 est déjà utilisé) ;
- En tapant cette adresse dans votre navigateur, vous devriez avoir accès au site ;
- Faites une inscription, puis dans votre base de données, dans la table `user`, dans la colonne `roles`, changer `"ROLE_USER","ROLE_CLIENT"` pour `"ROLE_USER","ROLE_ADMIN"`; cet utilsateur est donc maintenant admin et a ainsi accès aux onglets qui lui sont réservés.
