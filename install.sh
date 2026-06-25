#!/bin/bash

# Installe PHPFullCalendar et ses dépendances dans le répertoire passé en
# paramètre, à partir de la version actuellement clonée des dépôts (HEAD).
# Chaque dépôt est exporté avec « git archive » :
#   - PHPFullCalendar       -> <installation>/
#   - php-rrule (src)        -> <installation>/dependencies/php-rrule/
#   - oTools                 -> <installation>/dependencies/oTools/
#   - Twig (src)             -> <installation>/dependencies/Twig/

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

if [ "$missing" -ne 0 ]; then
	echo "Des prérequis sont manquants, installation interrompue." >&2
	exit 1
fi

mkdir -p "$1"
INSTALL=$(cd "$1" && pwd)
DEPENDENCIES="$INSTALL/dependencies"

# archive <dépôt> <destination> [sous-répertoire]
# Exporte HEAD du dépôt (ou de son sous-répertoire) dans la destination.
archive() {
	local repo=$1 dest=$2 subdir=${3:-}
	if [ ! -d "$repo/.git" ]; then
		echo "Dépôt introuvable : $repo" >&2
		exit 1
	fi
	local ref
	ref=$(git -C "$repo" rev-parse --short HEAD)
	mkdir -p "$dest"
	if [ -n "$subdir" ]; then
		echo "  $repo ($subdir) @ $ref -> $dest"
		git -C "$repo" archive HEAD "$subdir" | tar -x --strip-components=1 -C "$dest"
	else
		echo "  $repo @ $ref -> $dest"
		git -C "$repo" archive HEAD | tar -x -C "$dest"
	fi
}

echo "PHPFullCalendar -> $INSTALL"
archive "$SOURCE" "$INSTALL"

echo "Dépendances -> $DEPENDENCIES"
archive "$SOURCE/php-rrule" "$DEPENDENCIES/php-rrule" src
archive "$SOURCE/oTools"    "$DEPENDENCIES/oTools"
archive "$SOURCE/Twig"      "$DEPENDENCIES/Twig" src

# Liens attendus par l'autoloader (_::load -> <installation>/src/<Namespace>)
echo "Liens des dépendances dans src/"
ln -sfn ../dependencies/php-rrule "$INSTALL/src/RRule"
ln -sfn ../dependencies/oTools    "$INSTALL/src/oTools"
ln -sfn ../dependencies/Twig      "$INSTALL/src/Twig"

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
		created=1
		echo "Base créée et schéma chargé depuis schema.sql."
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
