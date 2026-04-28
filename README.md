# PHPFullCalendar

Application web de gestion de calendriers partagés. Interface basée sur [FullCalendar.js](https://fullcalendar.io/), backend PHP avec une base de données MySQL et une authentification LDAP.

## Fonctionnalités

- Création et gestion de calendriers
- Événements : création, modification, suppression, export ICS
- Authentification LDAP multi-sources
- Contrôle d'accès à deux niveaux :
  - **droits globaux** (bitmask) : créer/supprimer des calendriers, gérer les ressources, administrer les droits
  - **droits par calendrier** (niveaux 1–4) : disponibilités, lecture, écriture, administration
- Droits attribuables à des utilisateurs, groupes ou entrées spéciales (`anonymous`, `authenticated`)
- Interface multilingue (français, anglais)

## Prérequis

- PHP 8.1+
- MySQL / MariaDB
- Apache avec `mod_rewrite`
- Memcached (gestion des sessions)
- Serveur LDAP (authentification)

## Installation

### 1. Base de données

```sql
mysql -u root -p < schema.sql
```

### 2. Configuration

Copier `config.php.example` en `config.php` et adapter :

```php
return [
    'secure' => true,                   // forcer HTTPS
    'timezone' => 'Europe/Paris',
    'database' => [
        'driver' => 'mysql',
        'host'   => 'localhost',
        'name'   => 'phpFullCalendar',
        'user'   => 'pfc',
        'pass'   => 'secret'
    ],
    'session' => [
        'ttl'     => 3600,
        'handler' => [
            'name'   => 'memcached',
            'host'   => '127.0.0.1',
            'port'   => 11211,
            'prefix' => 'pfc_'
        ],
        'cookie'  => [
            'name'     => 'pfc_session',
            'domain'   => 'pfc.example.com',
            'path'     => '/',
            'secure'   => true,
            'httponly' => true
        ]
    ],
    'authentication' => [
        'ldap' => [
            'method'     => 'LDAP',
            'parameters' => [
                'host'     => 'ldap://ldap.example.com:389',
                'startTLS' => true,
                'bindDN'   => 'uid=%s,ou=people,dc=example,dc=com'
            ]
        ]
    ],
    'information' => [
        'ldap' => [
            'method'     => 'LDAP',
            'parameters' => [
                'host'       => 'ldap://ldap.example.com:389',
                'startTLS'   => false,
                'base'       => 'ou=people,dc=example,dc=com',
                'filter'     => '(&(uid=%s)(objectClass=posixAccount))',
                'attributes' => ['cn', 'mail'],
                'name'       => 'cn',
                'email'      => 'mail'
            ]
        ]
    ],
    'groups' => [
        'ldap' => [
            // configuration optionnelle pour la résolution des groupes LDAP
        ]
    ]
];
```

### 3. Apache

```apache
# Assets statiques
Alias /public/ /var/www/phpfullcalendar/public/

# Tout le reste passe par http.php
AliasMatch ^(?!/public/).*$ /var/www/phpfullcalendar/http.php

<Directory /var/www/phpfullcalendar>
    Options -Indexes
    AllowOverride None
    Require all granted
</Directory>
```

Le fichier `http.php` assure lui-même le routage : l'URL `/Controller/method/parameters` est traduite en appel de méthode `_<verb>_<method>()` sur la classe `Controllers\<Controller>`.

## Structure

```
.
├── config.php              # configuration (non versionné)
├── config.php.example      # modèle de configuration
├── http.php                # point d'entrée unique + routeur
├── schema.sql              # schéma MySQL
├── public/
│   ├── scripts/
│   │   └── PHPFullCalendar.js   # application JS (ESM/IIFE)
│   └── styles/
│       └── main.css
├── src/PHPFullCalendar/
│   ├── Controllers/        # un fichier par contrôleur
│   ├── Database/           # couche d'accès aux données (PDO)
│   ├── Views/              # Json, Ok, BadRequest, Ics, Template…
│   ├── Authentications/    # LDAP (implémente AuthenticationInterface)
│   ├── Informations/       # récupération des attributs utilisateur
│   ├── Groups/             # résolution des groupes
│   └── _.php               # classe utilitaire centrale (autoload, config, PDO…)
├── templates/
│   └── index.php           # unique vue HTML
└── translations/
    ├── fr.php
    └── en.php
```

## API HTTP

L'authentification de session se fait via `/Authenticate/login` (POST). Les routes sans session utilisent le préfixe `/$` avec Basic Auth (`source.login:password`).

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/Calendar/catalog` | liste des calendriers |
| POST | `/Calendar/create` | créer un calendrier |
| POST | `/Calendar/update/<id>` | modifier un calendrier |
| GET | `/Calendar/delete/<id>` | supprimer un calendrier |
| GET | `/Event/list/<calendar_id>?start=…&end=…` | événements (format FullCalendar) |
| POST | `/Event/create/<calendar_id>` | créer un événement |
| POST | `/Event/update/<id>` | modifier un événement |
| GET | `/Event/delete/<id>` | supprimer un événement |
| GET | `/Event/ics/<calendar_id>` | export ICS |
| GET | `/Authorization/global` | droits globaux de l'utilisateur courant |
| GET | `/Authorization/calendar/<id>` | niveau d'accès sur un calendrier |
| GET/POST/DELETE | `/Authorization/globalacl` | gestion des droits globaux |
| GET/POST/DELETE | `/Authorization/calendaracl/<id>` | gestion des droits par calendrier |

## Modèle de droits

### Droits globaux (bitmask)

| Valeur | Constante | Description |
|--------|-----------|-------------|
| 1 | `GR_CAL_CREATE` | créer des calendriers |
| 2 | `GR_CAL_DESTROY` | supprimer des calendriers |
| 4 | `GR_RES_ADD` | ajouter des ressources |
| 8 | `GR_RES_DEL` | supprimer des ressources |
| 16 | `GR_ACL` | gérer les droits globaux |

### Droits par calendrier (niveaux)

| Niveau | Constante | Description |
|--------|-----------|-------------|
| 1 | `CAL_FREE_BUSY` | voir les disponibilités |
| 2 | `CAL_READ` | lire les événements |
| 3 | `CAL_WRITE` | créer/modifier/supprimer des événements |
| 4 | `CAL_ACL` | gérer les droits du calendrier |

Les droits sont attribuables à :
- un **utilisateur** (`type=user`, `source=<source_auth>`, `identifier=<login>`)
- un **groupe** (`type=group`)
- une entrée **spéciale** (`type=special`) : `anonymous` (source vide) pour tout le monde, `authenticated` pour les utilisateurs connectés

## Dépendances frontend

- [FullCalendar.js](https://fullcalendar.io/) 6.1.15
- [Bootstrap](https://getbootstrap.com/) 5
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [SweetAlert](https://sweetalert.js.org/)
