# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/base:latest

# ---------------------------------------------------
# Install Magento Open Source
# ---------------------------------------------------
RUN pwd
WORKDIR /var/www/html/
RUN composer config --global --quite http-basic.repo.magento.com ec47b6a9766ce31c2111ee5cef0f3b43 e33cb371f78a3e3b78bf38faff3e98dc
RUN composer create-project --quite --repository-url=https://repo.magento.com/ magento/project-community-edition .
RUN php bin/magento setup:install \
  --base-url="https://fisrv-magento.mherrm.de" \
  --db-host="localhost" \
  --db-name="magento2" \
  --db-user="yrzhqayjyq"  \
  --db-password="VA2r8RdxAe" \
  --admin-firstname="Admin" \
  --admin-lastname="Admin" \
  --admin-email="abdul.rehman@cloudways.com" \
  --admin-user="admin" \
  --admin-password="Testing123%567" \
  --use-rewrites="1" \
  --backend-frontname="admin" \
  --db-prefix=mage_