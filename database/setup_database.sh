#!/usr/bin/env bash
# One-shot database setup for the Morgan Legacy Scholarship site.
#
#   sudo bash database/setup_database.sh
#
# Creates the PostgreSQL role + database, applies database/schema.sql, and
# seeds the first admin as "pending setup" (you choose the password through
# the normal set-password page -- no password ever lives in this script).
# Safe to re-run: anything that already exists is left alone.
#
# Optional overrides (environment variables):
#   DB_NAME, DB_USER, DB_PASS, ADMIN_EMAIL, ADMIN_NAME, SITE_URL

set -euo pipefail

DB_NAME="${DB_NAME:-morgan_legacy_scholarship}"
DB_USER="${DB_USER:-dbadmin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-garrett.morgan.pro@gmail.com}"
ADMIN_NAME="${ADMIN_NAME:-Garrett Morgan}"
SITE_URL="${SITE_URL:-https://themorganlegacy.com}"

SCHEMA_FILE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/schema.sql"
[ -f "$SCHEMA_FILE" ] || { echo "Can't find $SCHEMA_FILE" >&2; exit 1; }

command -v psql    >/dev/null || { echo "psql not found -- install PostgreSQL first: sudo apt install postgresql" >&2; exit 1; }
command -v openssl >/dev/null || { echo "openssl not found -- sudo apt install openssl" >&2; exit 1; }

# The postgres OS user can't read the caller's current directory, which makes
# psql print a harmless-but-confusing warning.
cd /tmp

pg() { sudo -u postgres psql -X -q -v ON_ERROR_STOP=1 "$@"; }

ROLE_CREATED=0
if [ "$(pg -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'")" != "1" ]; then
    DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"
    pg -v dbuser="$DB_USER" -v dbpass="$DB_PASS" <<'SQL'
CREATE ROLE :"dbuser" LOGIN PASSWORD :'dbpass';
SQL
    ROLE_CREATED=1
    echo "Created database role '${DB_USER}'."
else
    echo "Database role '${DB_USER}' already exists -- leaving its password alone."
fi

if [ "$(pg -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'")" != "1" ]; then
    sudo -u postgres createdb -O "$DB_USER" "$DB_NAME"
    echo "Created database '${DB_NAME}'."
else
    echo "Database '${DB_NAME}' already exists -- applying schema on top (safe, idempotent)."
fi

# gen_random_uuid() is built in from PostgreSQL 13; older servers need pgcrypto.
if [ "$(pg -tAc "SHOW server_version_num")" -lt 130000 ]; then
    pg -d "$DB_NAME" -c "CREATE EXTENSION IF NOT EXISTS pgcrypto"
fi

# SET ROLE first so every table ends up owned by the app's role -- no
# separate GRANT step needed. -c and -f share one session, in order.
pg -d "$DB_NAME" -c "SET ROLE \"${DB_USER}\"" -c "SET client_min_messages = warning" -f "$SCHEMA_FILE"
echo "Schema applied."

ADMIN_LINK=""
if [ "$(pg -d "$DB_NAME" -tAc "SELECT COUNT(*) FROM admin_users")" = "0" ]; then
    ADMIN_TOKEN="$(openssl rand -hex 32)"
    # password_hash '!' can never match a real password, so the account is
    # unusable until the invite link below is used to set one.
    pg -d "$DB_NAME" -v name="$ADMIN_NAME" -v email="$ADMIN_EMAIL" -v token="$ADMIN_TOKEN" <<'SQL'
INSERT INTO admin_users (name, email, password_hash, is_active, unlock_token, unlock_token_expires)
VALUES (:'name', :'email', '!', FALSE, :'token', NOW() + INTERVAL '7 days');
SQL
    ADMIN_LINK="${SITE_URL}/admin/auth/set_password.php?token=${ADMIN_TOKEN}"
fi

echo
echo "================ DONE ================"
echo
echo "1) Put this in app/config.local.php (db section):"
echo
echo "    'db' => ["
echo "        'host' => 'localhost',"
echo "        'port' => 5432,"
echo "        'name' => '${DB_NAME}',"
echo "        'user' => '${DB_USER}',"
if [ "$ROLE_CREATED" = "1" ]; then
    echo "        'pass' => '${DB_PASS}',"
else
    echo "        'pass' => '(unchanged -- the password this role already had)',"
fi
echo "    ],"
echo
if [ -n "$ADMIN_LINK" ]; then
    echo "2) Set your admin password (link valid 7 days, 12+ character password):"
    echo
    echo "    ${ADMIN_LINK}"
else
    echo "2) An admin account already exists, so none was created."
fi
if [ "$ROLE_CREATED" = "1" ]; then
    echo
    echo "Save the database password above somewhere safe -- it isn't stored anywhere else."
fi
