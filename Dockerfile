# Use the Bitnami Magento image as the base
FROM bitnami/magento:latest

# Switch to root user to perform installations
USER root

# Install any necessary dependencies (if required)
# RUN apt-get update && apt-get install -y <any-required-packages>

# Copy the Credova Payments plugin to the Magento installation directory
COPY ./Credova/Payments /opt/bitnami/magento/app/code/Credova/Payments

# Set proper permissions for the plugin files
RUN chown -R daemon:daemon /opt/bitnami/magento/app/code/Credova/Payments

# Switch back to the non-root user
USER 1001

# Enable the Credova Payments module
RUN php /opt/bitnami/magento/bin/magento module:enable Credova_Payments
RUN php /opt/bitnami/magento/bin/magento setup:upgrade
RUN php /opt/bitnami/magento/bin/magento setup:di:compile
RUN php /opt/bitnami/magento/bin/magento setup:static-content:deploy -f

# Clear the cache
RUN php /opt/bitnami/magento/bin/magento cache:flush
