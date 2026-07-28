#!/bin/bash

# Installe PHPFullCalendar et ses dépendances dans le répertoire passé en
# paramètre, à partir de la version actuellement clonée des dépôts (HEAD).
# Chaque dépôt est exporté avec « git archive » :
#   - PHPFullCalendar       -> <installation>/
#   - php-rrule (src)        -> <installation>/dependencies/php-rrule/
#   - oTools                 -> <installation>/dependencies/oTools/

PHPFullCalendarURL="https://github.com/graviemi/PHPFullCalendar/archive/refs/tags/v1.0.0-beta.tar.gz"
PHPRRuleURL="https://github.com/rlanvin/php-rrule/archive/refs/tags/v2.6.0.tar.gz"
OTOOLSURL="https://github.com/graviemi/oTools/archive/refs/tags/v0.1.0-beta.tar.gz"

set -euo pipefail

SOURCE=$(cd "$(dirname "$0")" && pwd)

if [ $# -ne 1 ]; then
	echo "Usage: $0 <chemin_installation>" >&2
	exit 1
fi

# Vérifications préalables
echo "Vérifications préalables"
missing=0
fail() { echo "  ✗ $1" >&2; missing=1; }

# Support des liens symboliques
tmp=$(mktemp -d)
if ln -s . "$tmp/lien" 2>/dev/null; then
	echo "  ✓ liens symboliques"
else
	fail "le système de fichiers ne supporte pas les liens symboliques"
fi
rm -rf "$tmp"

# Outils requis par l'installation
for tool in git tar; do
	command -v "$tool" >/dev/null && echo "  ✓ $tool" || fail "$tool introuvable"
done

# Client MySQL / MariaDB
if command -v mysql >/dev/null || command -v mariadb >/dev/null; then
	echo "  ✓ client mysql/mariadb"
else
	fail "client mysql ou mariadb introuvable"
fi

# Serveur memcached
command -v memcached >/dev/null && echo "  ✓ serveur memcached" || fail "serveur memcached introuvable"

# PHP >= 8.3 et extensions
if ! command -v php >/dev/null; then
	fail "php introuvable"
elif [ "$(php -r 'echo PHP_VERSION_ID;')" -lt 80300 ]; then
	fail "PHP >= 8.3 requis (version actuelle : $(php -r 'echo PHP_VERSION;'))"
else
	echo "  ✓ PHP $(php -r 'echo PHP_VERSION;')"
	for ext in pdo_mysql memcached fileinfo; do
		php -m | grep -qix "$ext" && echo "  ✓ extension php $ext" || fail "extension php $ext manquante"
	done
fi

if command -v curl >/dev/null 2>&1
then
	DOWNLOAD=(curl -L)
	echo "  ✓ curl"
elif command -v wget >/dev/null 2>&1
then
	DOWNLOAD=(wget -O -)
	echo "  ✓ wget"
else
	fail "curl ou wget introuvables"
fi

if [ "$missing" -ne 0 ]; then
	echo "Des prérequis sont manquants, installation interrompue." >&2
	exit 1
fi

mkdir -p "$1"
INSTALL=$(cd "$1" && pwd)
DEPENDENCIES="$INSTALL/dependencies"

echo "PHPFullCalendar -> $INSTALL"
"${DOWNLOAD[@]}" $PHPFullCalendarURL | tar xvz -C "$INSTALL" --strip-components=1

echo "Dépendances -> $DEPENDENCIES"
mkdir -p "$DEPENDENCIES/RRule"
"${DOWNLOAD[@]}" $PHPRRuleURL | tar xvz -C "$DEPENDENCIES/RRule" --strip-components=1
mkdir -p "$DEPENDENCIES/oTools"
"${DOWNLOAD[@]}" $OTOOLSURL | tar xvz -C "$DEPENDENCIES/oTools" --strip-components=1

# Liens attendus par l'autoloader (_::load -> <installation>/src/<Namespace>)
echo "Liens des dépendances dans src/"
ln -sfn ../dependencies/RRule/src "$INSTALL/src/RRule"
ln -sfn ../dependencies/oTools "$INSTALL/src/oTools"

exit 0

# Base de données
echo
echo "Base de données"

read -rp "Utiliser sudo pour les connexions MySQL ? [o/N] " reply
if [ "$reply" = o ] || [ "$reply" = O ]; then
	MYSQL=(sudo mysql)
else
	MYSQL=(mysql)
fi

read -rp "Nom de la base de données : " dbname

created=0
if [ -n "$(echo "SHOW DATABASES LIKE '$dbname';" | "${MYSQL[@]}" --batch --skip-column-names)" ]; then
	echo "La base « $dbname » existe déjà."
else
	read -rp "Créer la base de données « $dbname » ? [o/N] " reply
	if [ "$reply" = o ] || [ "$reply" = O ]; then
		echo "CREATE DATABASE \`$dbname\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" | "${MYSQL[@]}"
		"${MYSQL[@]}" "$dbname" < "$INSTALL/schema.sql"
		"${MYSQL[@]}" "$dbname" < "$INSTALL/triggers.sql"
		created=1
		echo "Base créée, schéma chargé depuis schema.sql et déclencheurs depuis triggers.sql."
	fi
fi

# Droits de l'utilisateur applicatif, uniquement si la base vient d'être créée
if [ "$created" -eq 1 ]; then
	read -rp "Identifiant de l'utilisateur MySQL : " dbuser
	read -rsp "Mot de passe : " dbpass
	echo
	{
		echo "CREATE USER IF NOT EXISTS '$dbuser'@'localhost' IDENTIFIED BY '$dbpass';"
		echo "GRANT ALL PRIVILEGES ON \`$dbname\`.* TO '$dbuser'@'localhost';"
		echo "FLUSH PRIVILEGES;"
	} | "${MYSQL[@]}"
	echo "Droits accordés à « $dbuser » sur « $dbname »."
fi

echo "Terminé."
