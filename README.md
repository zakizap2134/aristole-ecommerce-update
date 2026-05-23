# aristole-ecommerce-update
Added some edit of images for admin/managers and images for shop
in order to use this you must do the 1st one and redownload this file and paste this in c\xampp\htdocs\aristotle  so this file you can use the new updates feature
if you finish proceed here...

first open your phpmyadmin...

then go to sql and copy and paste this then select go to run..

USE aristosole_db;

-- Add image column to products table

ALTER TABLE products ADD COLUMN image VARCHAR(200) DEFAULT NULL;

-- Verify column was added

DESCRIBE products;
