# Présentation du projet FlexKlite

FlexKlite est une application web légère de gestion de tâches et de projets, développée en PHP et conteneurisée via Docker. Le projet s'appuie sur une architecture simple et portable sans base de données relationnelle complexe, privilégiant l'utilisation de fichiers JSON pour le stockage des données.

## Architecture et Technologies

- **Backend** : PHP 8.2 fonctionnant sur un serveur Apache.
- **Frontend** : HTML, CSS natif (`style.css`), et JavaScript (`app.js`). L'interface propose des vues dynamiques générées par PHP.
- **Base de données** : Stockage "Flat-file" utilisant des fichiers JSON situés dans le répertoire `src/db/`.
- **Déploiement** : Docker et Docker Compose pour un environnement de développement et de production reproductible.

## Fonctionnalités principales

D'après l'analyse de l'arborescence des fichiers sources, l'application intègre les fonctionnalités suivantes :

- **Vues de gestion** :
  - **Kanban** (`kanban.php`) : Pour la gestion visuelle des tâches par colonnes (ex: À faire, En cours, Terminé).
  - **Liste** (`liste.php`) : Pour une vue tabulaire ou linéaire des éléments.
- **Indicateurs de performance (KPI)** : Suivi et statistiques de l'activité via `kpi.php`.
- **Panneau d'administration** : Interface dédiée (`admin.php`) pour la configuration et la gestion de l'application.
- **Authentification et Sécurité** : Système de connexion et de gestion de session (`login.php`, `logon.php`, `logout.php`, `auth.php`).
- **Communication Asynchrone (API)** : Un contrôleur central (`api.php`) qui traite les requêtes AJAX provenant du frontend (notamment via `app.js`).
- **Gestion des Modales** : Fenêtres contextuelles gérées par `modals.php` pour l'interaction utilisateur (création, édition, etc.).
- **Sauvegarde/Export** : L'image Docker installe l'extension PHP `zip`, ce qui est explicitement fait pour un module de backup (sauvegarde du dossier des JSON sous forme d'archive).

## Instructions de déploiement (Docker)

Le projet est configuré "clé en main" grâce à Docker Compose.

1. **Prérequis** : Disposer de Docker et Docker Compose sur votre machine.
2. **Lancement** : À la racine du projet (où se trouve le fichier `docker-compose.yml`), exécutez la commande :
   ```bash
   docker-compose up -d --build
   ```
3. **Accès** : Une fois le conteneur démarré, l'application web est accessible depuis votre navigateur à l'adresse **`http://localhost:7010`**.

### Informations sur le conteneur
- Nom du conteneur : `flexKlite_app`.
- **Persistance des données** : Le dossier local `./src/db` est lié au répertoire `/var/www/html/db` du conteneur. Toutes vos modifications (création de tâches, utilisateurs, etc.) sont enregistrées sur votre machine locale et survivent au redémarrage du conteneur.
- Le `Dockerfile` s'assure automatiquement d'attribuer les bonnes permissions (`www-data`) pour que le serveur Apache puisse lire et écrire les fichiers JSON.
