# Sport Manager - Gestion de Tournois Sportifs

Projet Symfony pour la gestion de tournois et de matchs sportifs avec notifications par email.

## 🚀 Fonctionnalités

- **Gestion des Tournois** : Création, édition, suppression via API et interface Admin Twig.
- **Inscriptions** : Système d'inscription des joueurs aux tournois avec validation par l'admin.
- **Matchs & Scores** : Saisie des scores par les joueurs ou l'admin. Mise à jour automatique du statut des matchs (`pending` -> `in_progress` -> `finished`).
- **Notifications (Mailtrap)** :
    - Email envoyé aux participants lorsqu'un vainqueur est désigné.
    - Email de rappel envoyé à l'adversaire lorsqu'un joueur met à jour son score.
- **Statistiques** : Commande console personnalisée pour calculer les performances des joueurs.
- **Validation Stricte** : Contraintes sur les dates, scores positifs, unicité des emails/pseudos.

## 🛠️ Installation

1. **Cloner le projet** :
   ```bash
   git clone <repository_url>
   cd Sport_manager
   ```

2. **Installer les dépendances** :
   ```bash
   composer install
   ```

3. **Configuration de l'environnement** :
   - Créer un fichier `.env.local`.
   - Configurer la base de données (SQLite par défaut) : `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"`
   - Configurer Mailtrap : `MAILER_DSN=smtp://USER:PASSWORD@sandbox.smtp.mailtrap.io:2525`

4. **Initialiser la base de données** :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console doctrine:fixtures:load --no-interaction
   ```

5. **Lancer le serveur** :
   ```bash
   symfony server:start
   ```

## 📊 Commandes Personnalisées

### Statistiques de joueur
Pour consulter les victoires/défaites d'un joueur :
```bash
php bin/console app:player-stats <userId> [tournamentId]
```

## 🧪 Tests

Les tests unitaires et fonctionnels couvrent les règles métier critiques (droits de modification, validation des joueurs confirmés, auto-finish des matchs).

Lancer les tests :
```bash
php bin/phpunit
```

## 👤 Utilisateurs par défaut (Fixtures)

- **Admin** : `admin@sportmanager.com` / `password`
- **Joueurs** : `joueur1@sportmanager.com`, `joueur2@sportmanager.com`, etc.
