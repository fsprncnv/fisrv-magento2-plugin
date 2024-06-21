# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/base:latest

# ---------------------------------------------------
# Configure Database
# ---------------------------------------------------
RUN mysql -V
RUN systemctl start mysql.service
RUN mysql -e "UPDATE mysql.user SET Password = PASSWORD('test') WHERE User = 'root'"
RUN mysql -e "DROP USER ''@'localhost'"
RUN mysql -e "DROP DATABASE test"
RUN mysql -e "FLUSH PRIVILEGES"
RUN mysql -e "CREATE USER 'magento2'@'localhost' IDENTIFIED BY 'test';
RUN mysql -e "CREATE DATABASE magento2';

# ---------------------------------------------------
# Install Magento Open Source
# ---------------------------------------------------
WORKDIR /var/www/html/
RUN composer config --global http-basic.repo.magento.com ec47b6a9766ce31c2111ee5cef0f3b43 e33cb371f78a3e3b78bf38faff3e98dc
RUN composer create-project --quite --repository-url=https://repo.magento.com/ magento/project-community-edition .
RUN php bin/magento setup:install \
  --base-url="https://fisrv-magento.mherrm.de" \
  --db-host="localhost" \
  --db-name="magento2" \
  --db-user="magento2"  \
  --db-password="test" \
  --admin-firstname="Admin" \
  --admin-lastname="Admin" \
  --admin-email="abdul.rehman@cloudways.com" \
  --admin-user="admin" \
  --admin-password="Testing123%567" \
  --use-rewrites="1" \
  --backend-frontname="admin" \
  --db-prefix=mage_