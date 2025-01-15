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
CMD php bin/magento sampledata:deploy
CMD php bin/magento setup:upgrade
