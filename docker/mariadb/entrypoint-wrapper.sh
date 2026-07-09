#!/bin/bash
set -euo pipefail

/scripts/prepare-encryption-keys.sh

exec docker-entrypoint.sh "$@"
