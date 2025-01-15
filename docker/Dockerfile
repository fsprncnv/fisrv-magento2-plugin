# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/docker/apache:latest

# ---------------------------------------------------
# Install & Configure Magento2 Community Edition
# ---------------------------------------------------
WORKDIR /var/www/html/
RUN composer config --global http-basic.repo.magento.com ec47b6a9766ce31c2111ee5cef0f3b43 e33cb371f78a3e3b78bf38faff3e98dc
RUN composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition .

COPY docker/auth.json .
CMD php bin/magento module:disable Magento_Elasticsearch Magento_InventoryElasticsearch Magento_OpenSearch Magento_Elasticsearch7 Magento_ElasticsearchCatalogPermissions
CMD php -dmemory_limit=-1 bin/magento setup:install --base-url=https://magento2.fisv.mherrm.de --db-host=fisrv-magento-db --db-name=magento2 --db-user=magento2 --db-password="magento2" --admin-firstname=bob --admin-lastname=smith --admin-email=email@example.com --admin-user=bob --admin-password="admin1234" --language=en_US --currency=EUR --timezone=Europe/Berlin --use-rewrites=1
CMD php bin/magento sampledata:deploy
CMD php bin/magento setup:upgrade
