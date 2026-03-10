# BT Demande - Guide de Test

Guide complet pour tester l'application BT Demande en local.

## 🏁 Préparation

### 1. Cloner et installer

```bash
git clone https://github.com/alex368/btdemande.git
cd btdemande
composer install
npm install
```

### 2. Configuration `.env.local`

```bash
cp .env .env.local
```

Adapter:
```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/btdemande?serverVersion=8.0"
GOOGLE_CLIENT_ID="your_google_client_id.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="your_secret"
MAILER_DSN="smtp://localhost"
```

### 3. Base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### 4. Démarrer l'app

```bash
symfony serve
# ou
php -S localhost:8000 -t public
```

Accès: **http://localhost:8000**

---

## 🧪 Scénarios de Test

### Test 1: Authentification Admin

**Utilisateur de test:**
- Email: `admin@test.com`
- Password: `admin123`

**Étapes:**
1. Aller à `/login`
2. Entrer les credentials
3. ✅ Redirection vers dashboard admin
4. Vérifier le menu admin (EasyAdmin)

---

### Test 2: Authentification Client

**Utilisateur de test:**
- Email: `client@test.com`
- Password: `client123`

**Étapes:**
1. Aller à `/login`
2. Login avec credentials client
3. ✅ Accès au portail client (`/customer/folder`)
4. Voir les demandes associées

---

### Test 3: Intégration Google OAuth

**Prérequis:** Credentials Google configurées

**Étapes:**
1. Aller à `/login`
2. Cliquer "Se connecter avec Google"
3. ✅ Authentification Google
4. ✅ Création/liaison compte automatique

---

### Test 4: Gestion des Demandes

**Étapes:**
1. Login en tant qu'admin
2. Aller à EasyAdmin → Funding Requests
3. ✅ Voir liste des demandes
4. Cliquer sur une demande → Voir détails
5. Éditer statut, ajouter commentaires
6. ✅ Changements sauvegardés

---

### Test 5: Portail Client

**Étapes:**
1. Login en tant que client
2. Aller à `/customer/folder`
3. ✅ Voir ses demandes
4. ✅ Voir ses dossiers clients
5. Accéder aux documents liés

---

### Test 6: Upload Documents

**Étapes:**
1. En tant qu'admin, aller à Documents
2. Upload un fichier (PDF, Excel, Word, etc.)
3. ✅ Fichier stocké et accessible
4. Voir aperçu/parsing du document

---

### Test 7: Chat

**Étapes:**
1. Login admin + client (2 navigateurs)
2. Aller à `/chat`
3. Admin envoie message
4. ✅ Client reçoit en temps réel
5. Client répond
6. ✅ Admin voit réponse

---

### Test 8: API Google Sheets/Docs

**Prérequis:** Google API configured + OAuth

**Étapes:**
1. Admin va à une demande
2. Sync avec Google Sheets (si implémenté)
3. ✅ Données exportées/importées

---

## 🔍 Tests Manuels - Checklist

### Formulaires
- [ ] Validation requis
- [ ] Messages d'erreur affichés
- [ ] Soumission réussie
- [ ] Redirection post-submit

### Base de données
- [ ] Migrations appliquées
- [ ] Fixtures chargées
- [ ] Requêtes fonctionnent (pas de N+1)
- [ ] Relations Doctrine OK

### Authentification
- [ ] Login/Logout OK
- [ ] Sessions persistantes
- [ ] Rôles/permissions OK
- [ ] Protection des routes

### Documents
- [ ] Upload PDF OK
- [ ] Upload Excel OK
- [ ] Parsing contenu OK
- [ ] Affichage aperçu OK

### Performance
- [ ] Page dashboard charge < 2s
- [ ] Pas de requêtes lentes
- [ ] Cache fonctionne

---

## 🐛 Debugging

### Voir les logs

```bash
tail -f var/log/dev.log
```

### Debug bar Symfony

La barre de debug est visible en bas si `APP_DEBUG=true`

### Inspecteur BDD

```bash
php bin/console doctrine:query:sql "SELECT * FROM funding_request LIMIT 5;"
```

### Reset BD

```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

---

## 📊 Test Automation (PHPUnit)

```bash
php bin/console test
# ou
./vendor/bin/phpunit
```

Tests dans: `tests/`

---

## 🚨 Erreurs Courantes

| Erreur | Solution |
|--------|----------|
| "Undefined variable $campany" | Vérifier les variables initialisées dans controllers |
| "SQLSTATE[HY000]" | Vérifier DATABASE_URL + connexion BD |
| "Google redirect URI mismatch" | Ajouter URI dans Google Console |
| "Migration failed" | `php bin/console doctrine:migrations:status` |
| "Assets not loaded" | `php bin/console assets:install` |

---

## ✅ Signoff Test

Si tout fonctionne:

```bash
echo "✅ All tests passed!"
git status  # Verify no uncommitted changes
npm run build  # Build assets
php bin/console cache:clear --env=prod
```

Ready to deploy! 🚀
