#!/bin/bash
set -e

current_directory="$PWD"

cd $(dirname $0)/..

docker cp PublicSquare magento-app-1:/var/www/html/app/code/

echo "ADDITIONAL INSTALLATION INSTRUCTIONS:"

echo "1. Run \"bin/magento module:enable PublicSquare_Payments\" from the root of your Magento directory"
# docker exec magento-app-1 "cd /var/www/html && bin/magento module:enable PublicSquare_Payments"
echo "2. Then run \"bin/magento setup:upgrade\" to finish installation"
# docker exec magento-app-1 "cd /var/www/html && bin/magento setup:upgrade"

cd "$current_directory"
