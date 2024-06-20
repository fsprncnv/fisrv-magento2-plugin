# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/base:latest

# ---------------------------------------------------
# Install Magento Open Source
# ---------------------------------------------------
WORKDIR /var/www/html/
ADD docker/auth.json .
RUN ls -al
#RUN composer config --no-plugins allow-plugins.automattic/jetpack-autoloader false
RUN composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition magento/

USER www-data

EXPOSE 443
