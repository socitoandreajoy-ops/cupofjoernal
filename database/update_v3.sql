-- database/update_v3.sql
-- Adds order type (Take Out / To Go / Deliver) + delivery details to orders. Safe to run more than once.
USE `cupofjoernal`;

ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `order_type` enum('Take Out','To Go','Deliver') NOT NULL DEFAULT 'Take Out' AFTER `order_date`,
  ADD COLUMN IF NOT EXISTS `delivery_address` varchar(255) DEFAULT NULL AFTER `order_type`,
  ADD COLUMN IF NOT EXISTS `contact_phone` varchar(20) DEFAULT NULL AFTER `delivery_address`;
