#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

MAGENTO_DIR="${1:-$DIR/magento-install}"
MAGENTO_VERSION="${2:-2.4.8-p3}"
MAGENTO_HOST="${3:-magento.test}"

set -e
set -x

__ensure_line_in_file() {
    local FILEPATH="$1"
    local LINE="$2"

    grep -F -- "$LINE" "$FILEPATH" >/dev/null 2>&1 || echo "$LINE" >> "$FILEPATH"
}

read -p "This will install Magento in ${MAGENTO_DIR}. For this to be successful this directory needs to be empty AND any previous containers/volumes needs to be removed.

This command will also fail to update /etc/hosts and install the SSL CA if ran without sudo. 
For the sudo command to work you must 'request privileges' from your system tray now.

Continue? (y/n) " -n 1 -r

mkdir -vp "${MAGENTO_DIR}" && cd "${MAGENTO_DIR}"

curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup \
    | bash -s -- "${MAGENTO_HOST}" community "${MAGENTO_VERSION}"
echo "Initial install completed"

rm -rf "${MAGENTO_DIR}.git"
__ensure_line_in_file "${DIR}/.gitignore" "${MAGENTO_DIR##*/}/"


"${MAGENTO_DIR}/bin/magento" "deploy:mode:set" "production" 

"${MAGENTO_DIR}/bin/create-user"

echo "Initializing pre-reqs"
chmod +x "${MAGENTO_DIR}/bin/init"
"${MAGENTO_DIR}/bin/init"
"${MAGENTO_DIR}/bin/magento"  module:enable Magento_SampleData
"${MAGENTO_DIR}/bin/magento"  module:enable Magento_BundleSampleData
"${MAGENTO_DIR}/bin/magento"  module:enable Magento_CatalogSampleData
"${MAGENTO_DIR}/bin/magento"  module:enable Magento_CmsSampleData
"${MAGENTO_DIR}/bin/magento"  setup:install
"${MAGENTO_DIR}/bin/magento"  setup:di:compile
"${MAGENTO_DIR}/bin/magento"  setup:static-content:deploy -f
"${MAGENTO_DIR}/bin/magento"  indexer:reindex
"${MAGENTO_DIR}/bin/magento"  cache:flush

echo "Pre-reqs complete"

"${MAGENTO_DIR}/bin/magento" "deploy:mode:set" "developer"



yq -i '.services.app.volumes += ["../PublicSquare/:/var/www/html/app/code/PublicSquare"]'  "${MAGENTO_DIR}/compose.yaml" 
"${MAGENTO_DIR}/bin/restart"  

"${MAGENTO_DIR}/bin/magento" module:enable PublicSquare_Payments
"${MAGENTO_DIR}/bin/magento" setup:install
"${MAGENTO_DIR}/bin/magento" setup:di:compile
"${MAGENTO_DIR}/bin/magento" setup:static-content:deploy -f
"${MAGENTO_DIR}/bin/magento" indexer:reindex
"${MAGENTO_DIR}/bin/magento" cache:flush

cd "${DIR}"
set +e
set +x
echo "Magento installation complete! You man now install the PublicSquare_Payments module."

open https://magento.test

