# Use the Bitnami Magento image as the base
FROM bitnami/magento:latest

# Switch to root user to perform installations
USER root

# Install any necessary dependencies (if required)
# RUN apt-get update && apt-get install -y <any-required-packages>

# Copy the PublicSquare Payments plugin to the Magento installation directory
COPY ./PublicSquare/Payments /opt/bitnami/magento/app/code/PublicSquare/Payments

# Set proper permissions for the plugin files
RUN chown -R daemon:daemon /opt/bitnami/magento/app/code/PublicSquare/Payments

# Configure Magento database connection
ENV MAGENTO_DATABASE_HOST=mariadb \
    MAGENTO_DATABASE_PORT_NUMBER=3306 \
    MAGENTO_DATABASE_USER=root \
    MAGENTO_DATABASE_PASSWORD=your_db_password \
    MAGENTO_DATABASE_NAME=bitnami_magento

# Switch back to the non-root user
USER 1001

# Enable the PublicSquare Payments module
RUN php /opt/bitnami/magento/bin/magento module:enable PublicSquare_Payments
RUN php /opt/bitnami/magento/bin/magento setup:upgrade
RUN php /opt/bitnami/magento/bin/magento setup:di:compile
RUN php /opt/bitnami/magento/bin/magento setup:static-content:deploy -f

# Create admin user with predetermined credentials
RUN php /opt/bitnami/magento/bin/magento admin:user:create \
    --admin-firstname=John \
    --admin-lastname=Doe \
    --admin-email=admin@example.com \
    --admin-user=admin \
    --admin-password=admin123

# Create customer user with predetermined credentials  
RUN php /opt/bitnami/magento/bin/magento customer:create \
    --firstname=Jane \
    --lastname=Smith \
    --email=customer@example.com \
    --password=customer123

# Seed the database with sample product data
COPY ./seed/products.sql /tmp/products.sql
RUN mysql -h mariadb -u root bitnami_magento < /tmp/products.sql

# Clear the cache
RUN php /opt/bitnami/magento/bin/magento cache:flush
