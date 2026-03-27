# API EcoGarden & co - Conseils de jardinage

## Description

EcoGarden & co est une entreprise spécialisée dans le domaine du jardinage et de l’agriculture écologique qui encourage les pratiques durables et respectueuses de l’environnement.  
L'API EcoGarden & co met à disposition des conseils de jardinage en accès libre pour aider les amateurs à cultiver leurs propres plantes, légumes et herbes aromatiques.  
Ce projet est une première version de test évolutive.

## Prérequis

- PHP 8.2 ou ultérieur
- Composer (gestionnaire de dépendances PHP) installé
- Symfony CLI installé
- Serveur web (exemple : Apache avec MAMP)
- Base de données MySQL ou équivalente

## Installation et utilisation

1. **Clonage du dépôt**

    ```bash
    git clone <url-du-projet>
    cd <dossier-du-projet>
    ```

2. **Installation des dépendances**

    ```bash
    composer install
    ```

3. **Configuration**

    Dupliquez le fichier **.env** et renommez-le **.env.local**.

    Dans ce fichier **.env.local**, renseignez votre clé API *OpenWeatherMap* :
    ```ini
    OPENWEATHERMAP_API_KEY=VOTRE_CLE_API
    ```

    Modifiez les informations suivantes si nécessaire :
    - Identifiants de connexion à la base de données
    - Chemin d'accès et passphrase des clés SSL

    Générez votre paire de clés privée et publique pour JWT :
    ```bash
    symfony console lexik:jwt:generate-keypair
    ```

4. **Base de données**

    **Méthode A : Génération des données**

    Créez la base de données :
    ```bash
    symfony console doctrine:database:create --if-not-exists
    ```

    Créez les tables :
    ```bash
    symfony console make:migration
    symfony console doctrine:migrations:migrate
    ```

    Chargez les fixtures :
    ```bash
    symfony console doctrine:fixtures:load
    ```

    **Méthode B : Utilisation de la sauvegarde**

    Importez le fichier **api_ecogarden.sql** dans votre base de données avec phpMyAdmin, Adminer ou directement en ligne de commande :
    ```bash
    mysql -u <utilisateur> -p <nom_de_la_base> < api_ecogarden.sql
    ```

5. **Démarrage de l’API**

    ```bash
    symfony server:start -d
    ```

6. **Authentification**

    https://127.0.0.1:8000/api/1.0/auth

    Corps JSON attendu :
    - username (string) : adresse email
    - password (string) : mot de passe

    Exemple :
    ```json
    {
        "username": "dave@ecogarden.com",
        "password": "password"
    }
    ```

    Pour tester l'API, vous pouvez vous connecter avec **user@ecogarden.com** ou **admin@ecogarden.com** et le mot de passe **password**.

    Le token reçu doit ensuite être envoyé dans les requêtes sécurisées via le **header** :
    ```
    Authorization: Bearer <token>
    ```

6. **Interrogation des routes**

    GET **(IS_AUTHENTICATED_FULLY)** :
    - https://127.0.0.1:8000/api/1.0/tips : liste les conseils du mois courant.
    - https://127.0.0.1:8000/api/1.0/tips/{month} : récupère les conseils d'un mois donné.
    - https://127.0.0.1:8000/api/1.0/weather : récupère la météo de la ville associée au compte authentifié.
    - https://127.0.0.1:8000/api/1.0/weather/{city} : récupère la météo d'une ville donnée.

    POST :
    - https://127.0.0.1:8000/api/1.0/tips : ajoute un nouveau conseil **(ROLE_ADMIN)**.
    - https://127.0.0.1:8000/api/1.0/users : crée un nouveau compte **(PUBLIC_ACCESS)**.

    PUT **(ROLE_ADMIN)** :
    - https://127.0.0.1:8000/api/1.0/tips/{id} : met à jour un conseil selon son id.
    - https://127.0.0.1:8000/api/1.0/users/{id} : met à jour un compte selon son id.

    DELETE **(ROLE_ADMIN)** :
    - https://127.0.0.1:8000/api/1.0/tips/{id} : supprime un conseil selon son id.
    - https://127.0.0.1:8000/api/1.0/users/{id} : supprime un compte selon son id.

    Pour les méthodes POST et PUT, les corps JSON attendus sont documentés dans les routes des contrôleurs.

## Notes

- Le cache est activé pour réduire la charge sur les requêtes fréquentes.
- Les données météo proviennent de l'API *OpenWeatherMap*.