# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/base:latest

# ---------------------------------------------------
# Install Magento Open Source
# ---------------------------------------------------
WORKDIR /var/www/html/
RUN echo $HOME
#RUN composer config --no-plugins allow-plugins.automattic/jetpack-autoloader false
RUN composer config --global http-basic.repo.magento.com ec47b6a9766ce31c2111ee5cef0f3b43 e33cb371f78a3e3b78bf38faff3e98dc
RUN composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition magento/

USER www-data

EXPOSE 443
