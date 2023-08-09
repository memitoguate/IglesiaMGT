#!/bin/bash

# we install the special config file for docker env
#cp docker/Config.php src/Include/

# first : we shutdown the container
docker-compose down

# now it's time to start it
docker-compose up -d

# and to install npm to work
docker-compose exec --index=1 webserver  sh -c "curl -fsSL https://deb.nodesource.com/setup_16.x | bash - && apt-get install -y nodejs && npm install node-sass && npm install -g github:phili67/i18next-extract-gettext && npm install i18next-conv -g && npm install grunt -g && npm install --global strip-json-comments-cli && apt -y install vim && apt -y install gettext && apt -y install jq && apt-get install -y locales locales-all && apt -y install htop && npm rebuild node-sass && cd src && composer install && cd .. && npm run orm-gen && cp -f BuildConfig.json.example BuildConfig.json && bash install.sh && cp node_modules/bootstrap-datepicker/dist/locales/bootstrap-datepicker.no.min.js node_modules/bootstrap-datepicker/dist/locales/bootstrap-datepicker.nb.min.js && grunt generateSignatures && grunt genPluginsSignatures && service apache2 reload"

# now we log in
docker-compose exec webserver bash
