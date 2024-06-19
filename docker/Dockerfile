# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/base:latest

# ---------------------------------------------------
# Install Magento Open Source
# ---------------------------------------------------
WORKDIR /var/www/html/
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition <install-directory-name>

USER www-data

EXPOSE 443
