#!/bin/sh
# Provision MariaDB file_key_management keys before mariadbd starts.
# Keys persist on the mariadb-encryption volume across container restarts.
set -eu

ENCRYPTION_DIR="/etc/mysql/encryption"
KEYFILE="${ENCRYPTION_DIR}/keyfile"

mkdir -p "${ENCRYPTION_DIR}"
chown mysql:mysql "${ENCRYPTION_DIR}"
chmod 700 "${ENCRYPTION_DIR}"

if [ ! -f "${KEYFILE}" ]; then
    umask 077

    {
        printf '1;%s\n' "$(openssl rand -hex 32)"
        printf '2;%s\n' "$(openssl rand -hex 32)"
    } > "${KEYFILE}"
fi

chown mysql:mysql "${KEYFILE}"
chmod 600 "${KEYFILE}"
