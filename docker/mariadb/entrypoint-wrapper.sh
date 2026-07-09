#!/bin/bash
set -euo pipefail

/bin/sh /scripts/prepare-encryption-keys.sh

exec docker-entrypoint.sh "$@"
