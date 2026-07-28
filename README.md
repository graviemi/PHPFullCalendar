# PHPFullCalendar

Web application for shared calendar management. Frontend based on [FullCalendar.js](https://fullcalendar.io/), PHP backend with a MySQL database.

## What is it for ?

PHPFullCalendar lets a group of people share and manage calendars from a web browser. It is meant for teams and organisations that need a common view of events and want to control precisely who can see or edit what.

Each calendar can be shared with individual users, groups or everyone, and access is granted at several levels: from simply seeing whether a slot is free or busy, through reading and editing events, up to administering the calendar's rights. A separate set of global rights controls who may create or delete calendars and manage resources.

The application does not manage user accounts or passwords itself: authentication, group membership and user information come from your existing directory (LDAP for now), so people sign in with the credentials they already have.

Calendars are not locked inside the application. Any calendar can be exported as a standard ICS feed, so events can be read and displayed from other tools — desktop and mobile calendar clients (Thunderbird, Apple Calendar, Google Calendar…) or any software that understands the iCalendar format. Events are also available as JSON in the format expected by FullCalendar.js, which makes it straightforward to embed a calendar's events in another web application or feed them to a custom frontend. Each request is authenticated, which means read access from external applications still obeys the same per-calendar rights as the web interface.

Typical use cases: booking shared resources, coordinating a team's schedule, or publishing a read-only agenda while letting a few people edit it.

## Install

You must have terminal access to the hosting server.

- Get install.sh :
	- wget https://github.com/graviemi/PHPFullCalendar/blob/master/install.sh
	or
	- curl https://github.com/graviemi/PHPFullCalendar/blob/master/install.sh
- 

## Features

- Calendar creation and management
- Events: create, edit, delete, ICS export
- Multi-source authentication / groups / user informations (LDAP for now)
- Two-level access control:
  - **global rights** (bitmask): create/delete calendars, manage resources, administer rights
  - **per-calendar rights** (levels 1–4): free/busy, read, write, admin
- Rights assignable to users, groups or special entries (`anonymous`, `authenticated`)
- Multilingual interface (French, English)

## Planned evolutions

- recuring events
- resources managment (rooms, material...)

## Requirements

- PHP 8.1+
- MySQL / MariaDB
- Apache / nginx (not tested)
- Memcached (session storage) (other session storage planned)

## Installation

### 1. Database

```sql
mysql -u root -p < schema.sql
```

### 2. Configuration

Copy `config.php.example` to `config.php` and adjust:

```php
return [
    'secure' => true,                   // enforce HTTPS
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
            // optional configuration for LDAP group resolution
        ]
    ]
];
```

### 3. Apache

```apache
# Static assets
Alias /public/ /var/www/phpfullcalendar/public/

# Everything else goes through http.php
AliasMatch ^(?!/public/).*$ /var/www/phpfullcalendar/http.php

<Directory /var/www/phpfullcalendar>
    Options -Indexes
    AllowOverride None
    Require all granted
</Directory>
```

`http.php` handles routing itself: the URL `/Controller/method/parameters` is translated into a call to `_<verb>_<method>()` on the class `Controllers\<Controller>`.

## Structure

```
.
├── config.php              # configuration (not versioned)
├── config.php.example      # configuration template
├── http.php                # single entry point + router
├── schema.sql              # MySQL schema
├── public/
│   ├── scripts/
│   │   └── PHPFullCalendar.js   # JS application (IIFE)
│   └── styles/
│       └── main.css
├── src/PHPFullCalendar/
│   ├── Controllers/        # one file per controller
│   ├── Database/           # data access layer (PDO)
│   ├── Views/              # Json, Ok, BadRequest, Ics, Template…
│   ├── Authentications/    # LDAP (implements AuthenticationInterface)
│   ├── Informations/       # user attribute retrieval
│   ├── Groups/             # group resolution
│   └── _.php               # central utility class (autoload, config, PDO…)
├── templates/
│   └── index.php           # single HTML view
└── translations/
    ├── fr.php
    └── en.php
```

## HTTP API

Session authentication is done via `/Authenticate/login` (POST). Sessionless routes use the `/$` prefix with Basic Auth (`source.login:password`).

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/Calendar/catalog` | list calendars |
| POST | `/Calendar/create` | create a calendar |
| POST | `/Calendar/update/<id>` | update a calendar |
| GET | `/Calendar/delete/<id>` | delete a calendar |
| GET | `/Event/list/<calendar_id>?start=…&end=…` | events (FullCalendar format) |
| POST | `/Event/create/<calendar_id>` | create an event |
| POST | `/Event/update/<id>` | update an event |
| GET | `/Event/delete/<id>` | delete an event |
| GET | `/Event/ics/<calendar_id>` | ICS export |
| GET | `/Authorization/global` | current user's global rights |
| GET | `/Authorization/calendar/<id>` | access level on a calendar |
| GET/POST/DELETE | `/Authorization/globalacl` | manage global rights |
| GET/POST/DELETE | `/Authorization/calendaracl/<id>` | manage per-calendar rights |

## Access control model

### Global rights (bitmask)

| Value | Constant | Description |
|-------|----------|-------------|
| 1 | `GR_CAL_CREATE` | create calendars |
| 2 | `GR_CAL_DESTROY` | delete calendars |
| 4 | `GR_RES_ADD` | add resources |
| 8 | `GR_RES_DEL` | delete resources |
| 16 | `GR_ACL` | manage global rights |

### Per-calendar rights (levels)

| Level | Constant | Description |
|-------|----------|-------------|
| 1 | `CAL_FREE_BUSY` | view free/busy information |
| 2 | `CAL_READ` | read events |
| 3 | `CAL_WRITE` | create/edit/delete events |
| 4 | `CAL_ACL` | manage calendar rights |

Rights can be assigned to:
- a **user** (`type=user`, `source=<auth_source>`, `identifier=<login>`)
- a **group** (`type=group`)
- a **special** entry (`type=special`): `anonymous` (empty source) for everyone, `authenticated` for logged-in users

## Frontend dependencies

- [FullCalendar.js](https://fullcalendar.io/) 6.1.15
- [Bootstrap](https://getbootstrap.com/) 5
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [SweetAlert](https://sweetalert.js.org/)
