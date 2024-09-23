-- Insert sample categories
INSERT INTO `catalog_category_entity` (`entity_id`, `attribute_set_id`, `parent_id`, `created_at`, `updated_at`, `path`, `position`, `level`, `children_count`) VALUES
(1, 3, 0, '2023-05-24 00:00:00', '2023-05-24 00:00:00', '1', 0, 0, 2),
(2, 3, 1, '2023-05-24 00:00:00', '2023-05-24 00:00:00', '1/2', 1, 1, 0),
(3, 3, 1, '2023-05-24 00:00:00', '2023-05-24 00:00:00', '1/3', 2, 1, 0);

INSERT INTO `catalog_category_entity_varchar` (`value_id`, `attribute_id`, `store_id`, `entity_id`, `value`) VALUES
(1, 45, 0, 1, 'Root Catalog'),
(2, 45, 0, 2, 'Sample Category 1'),
(3, 45, 0, 3, 'Sample Category 2');

-- Insert sample products
INSERT INTO `catalog_product_entity` (`entity_id`, `attribute_set_id`, `type_id`, `sku`, `has_options`, `required_options`, `created_at`, `updated_at`) VALUES
(1, 4, 'simple', 'product1', 0, 0, '2023-05-24 00:00:00', '2023-05-24 00:00:00'),
(2, 4, 'simple', 'product2', 0, 0, '2023-05-24 00:00:00', '2023-05-24 00:00:00'),
(3, 4, 'simple', 'product3', 0, 0, '2023-05-24 00:00:00', '2023-05-24 00:00:00');

INSERT INTO `catalog_product_entity_varchar` (`value_id`, `attribute_id`, `store_id`, `entity_id`, `value`) VALUES
(1, 71, 0, 1, 'Product 1'),
(2, 71, 0, 2, 'Product 2'),
(3, 71, 0, 3, 'Product 3'),
(4, 72, 0, 1, 'Short description for Product 1'),
(5, 72, 0, 2, 'Short description for Product 2'),
(6, 72, 0, 3, 'Short description for Product 3');

INSERT INTO `catalog_product_entity_text` (`value_id`, `attribute_id`, `store_id`, `entity_id`, `value`) VALUES
(1, 73, 0, 1, 'Long description for Product 1'),
(2, 73, 0, 2, 'Long description for Product 2'),
(3, 73, 0, 3, 'Long description for Product 3');

INSERT INTO `catalog_product_entity_decimal` (`value_id`, `attribute_id`, `store_id`, `entity_id`, `value`) VALUES
(1, 75, 0, 1, 10.99),
(2, 75, 0, 2, 19.99),
(3, 75, 0, 3, 5.99);

-- Insert sample product-category associations
INSERT INTO `catalog_category_product` (`category_id`, `product_id`, `position`) VALUES
(2, 1, 0),
(2, 2, 1),
(3, 3, 0);