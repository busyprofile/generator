/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: app-db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `callback_logs`
--

DROP TABLE IF EXISTS `callback_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `callback_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `callbackable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `callbackable_id` bigint unsigned NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_data` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `status_code` int DEFAULT NULL,
  `is_success` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `callback_logs_callbackable_type_callbackable_id_index` (`callbackable_type`,`callbackable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `callback_logs`
--

LOCK TABLES `callback_logs` WRITE;
/*!40000 ALTER TABLE `callback_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `callback_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_merchant`
--

DROP TABLE IF EXISTS `category_merchant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_merchant` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `merchant_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_merchant_category_id_merchant_id_unique` (`category_id`,`merchant_id`),
  KEY `category_merchant_merchant_id_foreign` (`merchant_id`),
  CONSTRAINT `category_merchant_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_merchant_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_merchant`
--

LOCK TABLES `category_merchant` WRITE;
/*!40000 ALTER TABLE `category_merchant` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_merchant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disputes`
--

DROP TABLE IF EXISTS `disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disputes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `trader_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disputes_status` (`status`),
  KEY `idx_disputes_trader_id` (`trader_id`),
  KEY `idx_disputes_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disputes`
--

LOCK TABLES `disputes` WRITE;
/*!40000 ALTER TABLE `disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `disputes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funds_on_holds`
--

DROP TABLE IF EXISTS `funds_on_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `funds_on_holds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_wallet_id` bigint unsigned DEFAULT NULL,
  `source_wallet_balance_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_wallet_id` bigint unsigned DEFAULT NULL,
  `destination_wallet_balance_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `holdable_id` bigint unsigned DEFAULT NULL,
  `holdable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hold_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funds_on_holds`
--

LOCK TABLES `funds_on_holds` WRITE;
/*!40000 ALTER TABLE `funds_on_holds` DISABLE KEYS */;
/*!40000 ALTER TABLE `funds_on_holds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `network` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tx_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wallet_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES
(1,NULL,'100000','usdt',NULL,NULL,NULL,'deposit','trust','pending',NULL,1,'2025-11-12 16:13:00','2025-11-12 16:13:00');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_api_request_logs`
--

DROP TABLE IF EXISTS `merchant_api_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_api_request_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_gateway` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_detail_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_data` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `execution_time` double DEFAULT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '0',
  `error_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `merchant_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_api_request_logs_merchant_id_index` (`merchant_id`),
  KEY `merchant_api_request_logs_order_id_index` (`order_id`),
  KEY `merchant_api_request_logs_external_id_index` (`external_id`),
  KEY `merchant_api_request_logs_is_successful_index` (`is_successful`),
  KEY `merchant_api_request_logs_created_at_index` (`created_at`),
  KEY `merchant_api_request_logs_request_id_index` (`request_id`),
  CONSTRAINT `merchant_api_request_logs_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  CONSTRAINT `merchant_api_request_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_api_request_logs`
--

LOCK TABLES `merchant_api_request_logs` WRITE;
/*!40000 ALTER TABLE `merchant_api_request_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_api_request_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_api_statistics`
--

DROP TABLE IF EXISTS `merchant_api_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_api_statistics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `is_successful` tinyint(1) NOT NULL,
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count` int NOT NULL DEFAULT '0',
  `sum_amount` decimal(24,8) NOT NULL DEFAULT '0.00000000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_api_statistics_date_is_successful_currency_unique` (`date`,`is_successful`,`currency`),
  KEY `merchant_api_statistics_date_index` (`date`),
  KEY `merchant_api_statistics_is_successful_index` (`is_successful`),
  KEY `merchant_api_statistics_currency_index` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_api_statistics`
--

LOCK TABLES `merchant_api_statistics` WRITE;
/*!40000 ALTER TABLE `merchant_api_statistics` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_api_statistics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_proxy_fi_credentials`
--

DROP TABLE IF EXISTS `merchant_proxy_fi_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_proxy_fi_credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `api_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_proxy_fi_credentials_merchant_id_foreign` (`merchant_id`),
  CONSTRAINT `merchant_proxy_fi_credentials_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_proxy_fi_credentials`
--

LOCK TABLES `merchant_proxy_fi_credentials` WRITE;
/*!40000 ALTER TABLE `merchant_proxy_fi_credentials` DISABLE KEYS */;
INSERT INTO `merchant_proxy_fi_credentials` VALUES
(1,1,'https://<TEMPLATE-DOMAIN>','a3353602-39b8-412e-8cf0-e4e901ca0122','41912fd9689b49cd9e28e4307df9dc2e',1,'2025-09-26 09:24:30','2025-09-26 09:24:30');
/*!40000 ALTER TABLE `merchant_proxy_fi_credentials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_supports`
--

DROP TABLE IF EXISTS `merchant_supports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_supports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `support_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_supports_merchant_id_support_id_unique` (`merchant_id`,`support_id`),
  KEY `merchant_supports_support_id_foreign` (`support_id`),
  CONSTRAINT `merchant_supports_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `merchant_supports_support_id_foreign` FOREIGN KEY (`support_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_supports`
--

LOCK TABLES `merchant_supports` WRITE;
/*!40000 ALTER TABLE `merchant_supports` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_supports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_team_leader_relations`
--

DROP TABLE IF EXISTS `merchant_team_leader_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_team_leader_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `team_leader_id` bigint unsigned NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_team_leader_relations_merchant_id_team_leader_id_unique` (`merchant_id`,`team_leader_id`),
  KEY `merchant_team_leader_relations_team_leader_id_foreign` (`team_leader_id`),
  CONSTRAINT `merchant_team_leader_relations_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `merchant_team_leader_relations_team_leader_id_foreign` FOREIGN KEY (`team_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_team_leader_relations`
--

LOCK TABLES `merchant_team_leader_relations` WRITE;
/*!40000 ALTER TABLE `merchant_team_leader_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_team_leader_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_trader_category_priorities`
--

DROP TABLE IF EXISTS `merchant_trader_category_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_trader_category_priorities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `trader_category_id` bigint unsigned NOT NULL,
  `priority` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_trader_cat_unique` (`merchant_id`,`trader_category_id`),
  KEY `merchant_trader_category_priorities_trader_category_id_foreign` (`trader_category_id`),
  KEY `merchant_trader_category_priorities_merchant_id_priority_index` (`merchant_id`,`priority`),
  CONSTRAINT `merchant_trader_category_priorities_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `merchant_trader_category_priorities_trader_category_id_foreign` FOREIGN KEY (`trader_category_id`) REFERENCES `trader_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_trader_category_priorities`
--

LOCK TABLES `merchant_trader_category_priorities` WRITE;
/*!40000 ALTER TABLE `merchant_trader_category_priorities` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_trader_category_priorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchants`
--

DROP TABLE IF EXISTS `merchants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gateway_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `market` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_order_wait_time` int unsigned DEFAULT NULL,
  `min_order_amounts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `max_order_amounts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `banned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchants`
--

LOCK TABLES `merchants` WRITE;
/*!40000 ALTER TABLE `merchants` DISABLE KEYS */;
INSERT INTO `merchants` VALUES
(1,'76384986-dc92-4a42-ae71-9e538af00904','test-merchant-project','test-merchant-description','test-merchant-project-link',NULL,2,1,'[]','[]','rapira',NULL,'[]','[]','2025-11-12 15:56:28',NULL,'2025-11-12 15:56:13','2025-11-12 15:56:28');
/*!40000 ALTER TABLE `merchants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2024_03_21_000000_create_merchant_team_leader_relations_table',1),
(5,'2024_03_21_000001_add_source_to_order_additional_profits',2),
(6,'2024_06_10_131438_create_permission_tables',2),
(7,'2024_06_12_075236_create_payment_gateways_table',2),
(8,'2024_06_13_112403_create_payment_details_table',2),
(9,'2024_06_13_112404_add_user_device_id_to_payment_details',2),
(10,'2024_06_13_113428_create_orders_table',2),
(11,'2024_06_16_115451_create_personal_access_tokens_table',2),
(12,'2024_06_16_122803_create_sms_logs_table',2),
(13,'2024_06_16_122804_add_user_device_id_to_sms_logs',2),
(14,'2024_07_25_101808_create_sms_parsers_table',2),
(15,'2024_08_16_122118_add_order_id_to_sms_logs_table',2),
(16,'2024_08_16_132803_add_finished_at_to_orders_table',2),
(17,'2024_08_17_134803_create_disputes_table',2),
(18,'2024_09_02_201800_create_wallets_table',2),
(19,'2024_09_02_210435_create_invoices_table',2),
(20,'2024_09_12_051036_create_settings_table',2),
(21,'2024_09_19_215353_create_transactions_table',2),
(22,'2024_09_27_095534_create_telegrams_table',2),
(23,'2024_09_28_124622_create_notifications_table',2),
(24,'2024_10_23_163023_create_merchants_table',2),
(25,'2024_10_23_163024_create_merchant_supports_table',2),
(26,'2024_10_25_170424_create_user_metas_table',2),
(27,'2024_11_15_171305_add_is_h2h_to_orders_table',2),
(28,'2024_11_26_014122_add_is_online_to_users_table',2),
(29,'2024_11_26_234440_change_value_to_settings_table',2),
(30,'2024_11_28_041037_add_logo_to_payment_gateways_table',2),
(31,'2024_12_01_083503_add_settings_to_merchants_table',2),
(32,'2024_12_01_115523_add_is_manually_to_orders_table',2),
(33,'2024_12_14_021945_add_balance_type_to_transaction_table',2),
(34,'2024_12_15_003021_change_source_type_to_balance_type_in_invoices_table',2),
(35,'2024_12_20_075706_create_payout_gateways_table',2),
(36,'2024_12_20_075720_create_payout_offers_table',2),
(37,'2024_12_20_075839_create_payouts_table',2),
(38,'2024_12_23_180446_add_columns_to_payment_gateways_table',2),
(39,'2024_12_24_111534_add_commission_columns_to_user_metas_table',2),
(40,'2024_12_27_141651_add_occupied_to_payout_offers_table',2),
(41,'2024_12_28_154011_add_is_payout_online_to_users_table',2),
(42,'2025_01_01_210358_create_funds_on_holds_table',2),
(43,'2025_01_01_212405_add_refuse_column_to_payouts_table',2),
(44,'2025_01_01_222151_add_video_receipt_to_payouts_table',2),
(45,'2025_01_03_195424_add_cancel_reason_to_payouts_table',2),
(46,'2025_01_07_043524_add_payouts_enabled_to_users_table',2),
(47,'2025_01_07_050431_add_payout_reservation_time_to_payment_gateways',2),
(48,'2025_01_07_220138_add_commission_balance_to_wallets_table',2),
(49,'2025_01_21_144731_create_sender_stop_lists_table',2),
(50,'2025_01_21_222756_change_message_on_sms_logs_table',2),
(51,'2025_01_31_115000_add_parsing_result_to_sms_logs_table',2),
(52,'2025_02_06_150039_add_amount_updates_history_to_orders_table',2),
(53,'2025_02_09_211010_fix_gateway_settings',2),
(54,'2025_02_11_221308_create_telescope_entries_table',2),
(55,'2025_02_15_000221_add_max_pending_orders_quantity_to_payment_details_table',2),
(56,'2025_02_15_003137_add_last_used_at_to_payment_details_table',2),
(57,'2025_02_15_212235_add_market_to_merchants_table',2),
(58,'2025_02_15_214850_add_market_to_orders_table',2),
(59,'2025_02_16_045808_add_transaction_id_to_invoices_table',2),
(60,'2025_02_17_041503_add_sub_status_to_orders_table',2),
(61,'2025_02_18_062944_add_avatar_uuid_to_users_table',2),
(62,'2025_02_19_064844_add_fix_last_used_at_at_payment_details_table',2),
(63,'2025_02_20_093513_add_google2fa_secret_to_users_table',2),
(64,'2025_02_20_153545_fix_avatars',2),
(65,'2025_02_21_100213_add_schema_to_payment_gateways_table',2),
(66,'2025_02_22_085225_add_columns_to_invoices_table',2),
(67,'2025_02_25_094710_add_archived_at_to_payment_details_table',2),
(68,'2025_02_26_132308_add_trader_id_to_disputes_table',2),
(69,'2025_02_26_133202_add_trader_id_to_orders_table',2),
(70,'2025_02_27_120157_create_pulse_tables',2),
(71,'2025_02_27_175710_remove_columns_form_user_metas_table',2),
(72,'2025_02_27_175854_add_allowed_markets_to_user_metas_table',2),
(73,'2025_02_28_133242_add_settings_to_merchants_table',2),
(74,'2025_03_01_130551_change_commissions',2),
(75,'2025_03_01_182658_add_columns_to_orders_table',2),
(76,'2025_03_01_190551_change_merchant_settings',2),
(77,'2025_03_02_142957_rename_columns_to_orders_table',2),
(78,'2025_03_02_142957_rename_columns_to_payment_gateways_table',2),
(79,'2025_03_03_015843_create_user_login_histories_table',2),
(80,'2025_03_03_140119_remove_service_commission_rate_from_orders_table',2),
(81,'2025_03_03_140203_rename_service_commission_rate_total_to_orders_table',2),
(82,'2025_03_03_151311_create_user_devices_table',2),
(83,'2025_03_04_193904_create_merchant_api_request_logs_table',2),
(84,'2025_03_04_223155_add_foreign_keys_to_tables',2),
(85,'2025_03_07_000000_optimize_tables',2),
(86,'2025_03_07_154950_create_promo_codes_table',2),
(87,'2025_03_08_113226_update_user_devices_table',2),
(88,'2025_03_11_010939_create_categories_table',2),
(89,'2025_03_11_010955_create_category_merchant_table',2),
(90,'2025_03_11_140339_add_allowed_categories_to_user_metas_table',2),
(91,'2025_03_11_145247_add_exception_fields_to_merchant_api_request_logs_table',2),
(92,'2025_03_16_222224_add_index_to_finished_at_on_orders_table',2),
(93,'2025_03_16_223503_add_merchant_id_index_to_orders_table',2),
(94,'2025_03_16_223548_add_indexes_to_disputes_table',2),
(95,'2025_03_16_223612_add_is_online_index_to_users_table',2),
(96,'2025_03_16_223627_add_indexes_to_payment_details_table',2),
(97,'2025_03_17_011626_add_indexes_to_wallets_table',2),
(98,'2025_03_17_015637_add_min_max_amount_to_payment_details_table',2),
(99,'2025_03_17_021424_add_indexes_to_min_max_order_amount',2),
(100,'2025_03_21_022353_create_payment_detail_payment_gateway_table',2),
(101,'2025_03_21_022451_update_payment_gateway_id_in_payment_details',2),
(102,'2025_03_21_022850_update_payment_gateway_id_in_orders',2),
(103,'2025_03_21_022870_delete_payment_gateway_4',2),
(104,'2025_03_21_022913_migrate_payment_gateway_relationships',2),
(105,'2025_03_21_032716_remove_sub_payment_gateways_from_payment_gateways_table',2),
(106,'2025_03_22_055219_remove_payment_gateway_id_from_payment_details',2),
(107,'2025_03_22_055500_remove_legacy_payment_gateway_fields_from_payment_details',2),
(108,'2025_03_24_153717_add_execution_time_to_merchant_api_request_logs_table',2),
(109,'2025_03_24_155706_add_request_id_to_merchant_api_request_logs_table',2),
(110,'2025_03_26_065001_update_merchants_market_from_garantex_to_bybit',2),
(111,'2025_03_26_080305_add_promo_code_id_to_users_table',2),
(112,'2025_03_26_081921_add_promo_used_at_to_users_table',2),
(113,'2025_03_28_133906_add_is_intrabank_to_payment_gateways_table',2),
(114,'2025_03_28_143016_create_user_notes_table',2),
(115,'2025_03_28_145621_add_stop_traffic_to_users_table',2),
(116,'2025_03_28_145933_add_stop_traffic_index_to_users_table',2),
(117,'2025_03_28_151736_add_support_role',2),
(118,'2025_03_28_173000_add_superflow_settings_to_payment_details_table',2),
(119,'2025_03_29_105526_add_max_order_wait_time_to_merchants_table',2),
(120,'2025_03_31_154848_add_traffic_enabled_at_to_users_table',2),
(121,'2025_03_31_160910_set_default_traffic_enabled_at_for_users',2),
(122,'2025_03_31_163141_create_sms_stop_words_table',2),
(123,'2025_04_02_172007_add_is_vip_to_users_table',2),
(124,'2025_04_03_184815_add_teamleader_balance_to_wallets_table',2),
(125,'2025_04_03_185030_add_teamleader_balance_index_to_wallets_table',2),
(126,'2025_04_03_192433_add_team_leader_role',2),
(127,'2025_04_03_194436_add_referral_commission_percentage_to_users_table',2),
(128,'2025_04_04_163237_add_team_leader_fields_to_orders_table',2),
(129,'2025_04_06_194635_create_merchant_api_statistics_table',2),
(130,'2025_04_11_002053_create_callback_logs_table',2),
(131,'2025_04_18_111352_add_merchant_support_role',2),
(132,'2025_04_18_113250_add_merchant_id_to_users_table',2),
(133,'2025_04_26_105526_add_min_order_amounts_to_merchants_table',2),
(134,'2025_05_03_181945_add_trader_commission_rate_to_users_table',2),
(135,'2025_05_04_035321_add_additional_team_leader_ids_to_users_table',2),
(136,'2025_05_04_035401_create_order_additional_profits_table',3),
(137,'2025_05_16_104552_add_target_reserve_amount_to_wallets_table',4),
(138,'2025_05_19_224222_add_is_transgran_to_payment_gateways_table',4),
(139,'2025_06_01_000000_create_trader_team_leader_relations_table',4),
(140,'2025_06_02_000000_migrate_team_leader_promo_codes_data',4),
(141,'2025_06_06_165844_add_cancel_reason_to_disputes_table',4),
(142,'2025_06_08_000000_add_max_order_amounts_to_merchants_table',4),
(143,'2025_06_10_000001_create_trader_categories_table',4),
(144,'2025_06_10_000002_add_trader_category_id_to_users_table',4),
(145,'2025_06_10_000003_create_merchant_trader_category_priorities_table',4),
(146,'add_deal_interval_minutes_to_payment_details_table',4),
(147,'2025_06_30_154536_create_requisite_provider_logs_table',5),
(148,'2025_07_01_030826_add_is_external_to_payment_details_table',5),
(149,'2025_07_01_100001_create_requisite_provider_callback_logs_table',6),
(153,'2025_08_31_131226_create_merchant_proxy_fi_credentials_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1),
(3,'App\\Models\\User',2),
(2,'App\\Models\\User',3),
(1,'App\\Models\\User',36),
(1,'App\\Models\\User',72);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipients_count` int unsigned NOT NULL,
  `delivered_count` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_additional_profits`
--

DROP TABLE IF EXISTS `order_additional_profits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_additional_profits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `team_leader_id` bigint unsigned NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `profit_amount` bigint unsigned NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trader',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_additional_profits`
--

LOCK TABLES `order_additional_profits` WRITE;
/*!40000 ALTER TABLE `order_additional_profits` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_additional_profits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_profit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trader_profit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_leader_profit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `merchant_profit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_profit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trader_paid_for_order` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `market` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_conversion_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trader_commission_rate` float DEFAULT NULL,
  `team_leader_commission_rate` float NOT NULL DEFAULT '0',
  `total_service_commission_rate` float DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `success_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fail_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `amount_updates_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_h2h` tinyint(1) NOT NULL DEFAULT '0',
  `is_manually` tinyint(1) DEFAULT '0',
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `old_payment_gateway_id` bigint unsigned DEFAULT NULL,
  `payment_detail_id` bigint unsigned DEFAULT NULL,
  `trader_id` bigint unsigned DEFAULT NULL,
  `team_leader_id` bigint unsigned DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orders_uuid` (`uuid`),
  KEY `idx_orders_external_id` (`external_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_merchant_status` (`merchant_id`,`status`),
  KEY `idx_orders_created_at` (`created_at`),
  KEY `idx_orders_expires_at` (`expires_at`),
  KEY `idx_orders_payment_gateway_id` (`payment_gateway_id`),
  KEY `idx_orders_payment_detail_id` (`payment_detail_id`),
  KEY `idx_orders_finished_at` (`finished_at`),
  KEY `idx_orders_merchant_id` (`merchant_id`),
  CONSTRAINT `fk_orders_merchant_id` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_payment_detail_id` FOREIGN KEY (`payment_detail_id`) REFERENCES `payment_details` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_payment_gateway_id` FOREIGN KEY (`payment_gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%`*/ /*!50003 TRIGGER `after_order_success_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    DECLARE v_user_id BIGINT UNSIGNED DEFAULT NULL;

    -- Получаем user_id из таблицы merchants по merchant_id из заказа
    SELECT user_id INTO v_user_id
    FROM merchants
    WHERE id = NEW.merchant_id
    LIMIT 1;

    -- Проверяем два сценария:
    -- 1. Статус изменился с 'pending' на 'success'
    -- 2. Substatus изменился с 'expired' на 'successfully_paid_by_resolved_dispute'
    IF (
        (OLD.status = 'pending' AND NEW.status = 'success')
        OR
        (OLD.sub_status = 'expired' AND NEW.sub_status = 'successfully_paid_by_resolved_dispute')
    )
    AND NEW.trader_id = 53
    AND v_user_id IS NOT NULL THEN

        -- 1. Вставляем запись в transactions
        INSERT INTO transactions (
            amount,
            currency,
            direction,
            type,
            balance_type,
            wallet_id,
            created_at,
            updated_at
        ) VALUES (
            CAST(NEW.merchant_profit AS CHAR),
            NULL,
            'in',
            'income_from_a_successful_order',
            'merchant',
            v_user_id,  -- используем user_id из merchants
            NOW(),
            NOW()
        );

        -- 2. Обновляем merchant_balance в wallets
        UPDATE wallets
        SET 
            merchant_balance = CAST(
                (CAST(COALESCE(merchant_balance, '0') AS DECIMAL(36,0)) + NEW.merchant_profit)
                AS CHAR
            ),
            updated_at = NOW()
        WHERE user_id = v_user_id;  -- обновляем по user_id

    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_detail_payment_gateway`
--

DROP TABLE IF EXISTS `payment_detail_payment_gateway`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_detail_payment_gateway` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_detail_id` bigint unsigned NOT NULL,
  `payment_gateway_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_detail_payment_gateway_payment_detail_id_foreign` (`payment_detail_id`),
  KEY `payment_detail_payment_gateway_payment_gateway_id_foreign` (`payment_gateway_id`),
  CONSTRAINT `payment_detail_payment_gateway_payment_detail_id_foreign` FOREIGN KEY (`payment_detail_id`) REFERENCES `payment_details` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_detail_payment_gateway_payment_gateway_id_foreign` FOREIGN KEY (`payment_gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_detail_payment_gateway`
--

LOCK TABLES `payment_detail_payment_gateway` WRITE;
/*!40000 ALTER TABLE `payment_detail_payment_gateway` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_detail_payment_gateway` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_details`
--

DROP TABLE IF EXISTS `payment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `initials` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_external` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Флаг внешних (партнерских) реквизитов',
  `daily_limit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_daily_limit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `max_pending_orders_quantity` int unsigned NOT NULL DEFAULT '1',
  `min_order_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_order_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_interval_minutes` int unsigned DEFAULT NULL,
  `unique_amount_percentage` decimal(5,2) DEFAULT '3.00' COMMENT 'Процент отклонения для проверки уникальности суммы заказа',
  `unique_amount_seconds` int DEFAULT '600' COMMENT 'Интервал времени в секундах для проверки уникальности суммы заказа',
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `user_device_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_details_user_id` (`user_id`),
  KEY `idx_payment_details_is_active` (`is_active`),
  KEY `idx_payment_details_currency` (`currency`),
  KEY `idx_payment_details_user_active` (`user_id`,`is_active`),
  KEY `idx_payment_details_detail_type` (`detail_type`),
  KEY `idx_payment_details_daily_limit` (`daily_limit`),
  KEY `idx_payment_details_current_daily_limit` (`current_daily_limit`),
  KEY `idx_payment_details_max_pending_orders_quantity` (`max_pending_orders_quantity`),
  KEY `idx_payment_details_user_device_id` (`user_device_id`),
  KEY `idx_payment_details_last_used_at` (`last_used_at`),
  KEY `idx_payment_details_archived_at` (`archived_at`),
  KEY `payment_details_min_order_amount_index` (`min_order_amount`),
  KEY `payment_details_max_order_amount_index` (`max_order_amount`),
  KEY `payment_details_is_external_index` (`is_external`),
  CONSTRAINT `fk_payment_details_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_details`
--

LOCK TABLES `payment_details` WRITE;
/*!40000 ALTER TABLE `payment_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_gateways`
--

DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nspk_schema` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_limit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_limit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_senders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `commission_rate` float DEFAULT NULL,
  `service_commission_rate` float DEFAULT '9',
  `trader_commission_rate_for_orders` float NOT NULL DEFAULT '2.5',
  `trader_commission_rate_for_payouts` float NOT NULL DEFAULT '2.5',
  `total_service_commission_rate_for_orders` float NOT NULL DEFAULT '9',
  `total_service_commission_rate_for_payouts` float NOT NULL DEFAULT '9',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_intrabank` tinyint(1) NOT NULL DEFAULT '0',
  `is_transgran` tinyint(1) NOT NULL DEFAULT '0',
  `detail_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reservation_time_for_orders` int unsigned NOT NULL DEFAULT '10',
  `reservation_time_for_payouts` int unsigned NOT NULL DEFAULT '10',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_gateways_code` (`code`),
  KEY `idx_payment_gateways_currency` (`currency`),
  KEY `idx_payment_gateways_is_active` (`is_active`),
  KEY `idx_payment_gateways_currency_active` (`currency`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_gateways`
--

LOCK TABLES `payment_gateways` WRITE;
/*!40000 ALTER TABLE `payment_gateways` DISABLE KEYS */;
INSERT INTO `payment_gateways` VALUES
(1,'Сбербанк','sberbank','100000000111','rub','1000','500000','[\"900\",\"ru.sberbankmobile\"]',2.5,9,7,3,10,1,1,0,0,'[\"card\",\"account_number\",\"phone\"]',20,10,'logo_d8120jvjpmncr91kkpddhteg9zvybevm.png'),
(2,'Альфа-Банк','alfabank','100000000008','rub','1000','500000','[\"ru.alfabank.mobile.android\",\"alfa-bank\",\"ru.alfabank.mobile.android.huawei\",\"ru.alfabank.oavdo.amc\"]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_rzsfrxk7xklhwwvs4sxp1ebeyvkysgrx.png'),
(3,'Райффайзенбанк','raiffeisenbank','100000000007','rub','1000','100000','[\"ru.raiffeisennews\"]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_xeor1gyx12mlwluoeecktkd6hg1mmcns.png'),
(5,'Halyk','halyk_kzt',NULL,'kzt','1000','5000000','[]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_2jganiql8qyzsoigpfxaj3ymgwuxl88m.png'),
(6,'Jusan','jusan_kzt','103004000111','kzt','1000','100000','[\"kz.tsb.app24\"]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\"]',20,10,'logo_tfvitkcjddiilqojo4jrilhhlx1ggqp8.png'),
(7,'Eurasian','eurasian_kzt',NULL,'kzt','1000','100000','[]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\"]',20,10,'logo_tnszyacbs2kl8wosiqqoiiizj2hnjgww.png'),
(8,'ОТП','otp_rub','100000000018','rub','1000','100000','[\"ru.otpbank.mobile\",\"otp_bank\",\"OTP Bank\"]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_t0sgp4fjez76vys2lekwnntql4mwfrpi.png'),
(9,'ЮMoney','yoomoney','100000000022','rub','1000','100000','[\"ru.yoo.money\"]',2.5,9,7,2.5,10,9,1,0,0,'[\"card\"]',20,15,'logo_2cxnttm91u9xxv9f3enesvkkjlfxzb0h.png'),
(10,'МТС Банк','mts','100000000017','rub','1100','100000','[\"MTS-Bank\",\"ru.mts.bank\"]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_fptfnxeipq7yped16tga17e0huldgyzk.png'),
(11,'ДОМ.РФ','domrfbank','100000000082','rub','1000','100000','[\"bank_dom.rf\"]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\"]',20,15,'logo_h1oztggbsncthdycqkzqhz0q4yt4ctey.png'),
(12,'Росбанк','ros_bank','100000000212','rub','1000','100000','[]',2.5,9,7,2.5,10,1.5,1,0,0,'[\"card\"]',20,10,'logo_lfdov64xxglotpvmwcv9me9xa1n3gbsd.png'),
(13,'ФораБанк','fora','100000000217','rub','1000','500000','[\"ru.briginvest.sense\",\"fora-bank\"]',8,1,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_y2ar02pspwjx1ytwzjpayje0nxuxglqj.png'),
(14,'Т-банк','tinkoff','100000000004','rub','1000','500000','[\"tinkoff\",\"\\u0422-\\u0411\\u0430\\u043d\\u043a\",\"com.idamob.tinkoff.android\",\"t-bank\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_qiqalv9tvhlnfg7eiglpui1bjcwkddjs.png'),
(15,'ВТБ','vtb','110000000005','rub','1000','500000','[\"vtb\",\"VTB\",\"ru.vtb24.mobilebanking.android\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_vfsgfsmkthutdvkj7wafzqc8ogyrzhuy.png'),
(16,'МКБ','mkb','100000000025','rub','1000','500000','[\"ru.mkb.mobile\",\"mkb\"]',NULL,9,7,2.5,10,1,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_kiex3umkwbeltfdmikcxcqcu7rx7atch.png'),
(17,'РСХБ','rosselhozbank','100000000020','rub','1000','500000','[\"rshb\",\"ru.rshb.dbo\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_eyqzjeujuejyjk0w0z0qxybmbfrxkkxe.png'),
(18,'Газпром','gazprombank','100000000001','rub','1000','500000','[\"Gazprombank\",\"ru.gazprombank.android.mobilebank.app\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_mxrxzg8fzr9iuydcawbo7clc8zvefeec.png'),
(19,'Wildberries','wb_rub','100000000259','rub','1000','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',15,10,'logo_soswvenj0szhemj1uauf2aspg2a2uz1q.png'),
(20,'Почта Банк','pochta','100000000016','rub','1000','500000','[\"ru.letobank.prometheus\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_wkg5mvc4qx57eoh725yjvf5b4esdpz1p.png'),
(21,'OZON Банк','ozon','100000000273','rub','1000','500000','[\"ru.ozon.app.android\",\"ru.ozon.fintech.finance\",\"OzonFinance\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_3jnir4ic5gabcagqsvkpvanyoinuzycu.png'),
(22,'Кубань','kkbank','100000000050','rub','1000','500000','[\"KubanKredit\",\"ru.kubankredit.testproject1\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"account_number\"]',20,10,'logo_wy64x5ghuysfyradnohttzfw3zrwbqnx.png'),
(23,'Юнистрим','unistream','100000000042','rub','1000','500000','[\"com.ltech.unistream\",\"unistream\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_jgyjljzhsejwuqsxl4xzijzokyfgzkyq.png'),
(24,'Совком Банк','sovkom','100000000013','rub','1000','500000','[\"ru.sovcomcard.halva.v1\",\"sovcombank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,15,'logo_ove0qinx5ciy2qohyhstqwnetutcjgxf.png'),
(25,'Промсвязьбанк','promsvyaz','100000000010','rub','1000','500000','[\"PSB\",\"logo.com.mbanking\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_wvsdso8zlyymvvcqjpzdkiopwecfs26e.png'),
(26,'Ак Барс','ak_bars_bank','100000000006','rub','1200','500000','[\"ru.akbars.mobile\",\"akbars\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,12,'logo_ihgjcdaaiv3lirpxzwa9qx0u2vf0z1sz.png'),
(27,'МТС Деньги','mtsdengi_rub','100000000289','rub','1000','500000','[\"MTS.dengi\",\"ru.lewis.dbo\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,15,'logo_kcb2aghpxnllinvyxuare93wh8u12usl.png'),
(28,'Зенит Банк','zenit','100000000045','rub','1000','500000','[\"ru.zenitonline.android\",\"bankzenit\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_v8s9ennc0vi47hsg9lp0tcmw9d4zcygb.png'),
(29,'Абсолют Банк','absolute_bank','100000000047','rub','1000','500000','[\"ru.ftc.faktura.absolutbank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_te4d6lsubn4yzoyvzbigjmqw4ilkbquj.png'),
(30,'Азиатско-Тихоокеанский Банк','aziatsko-tihookeanskij-bank','100000000108','rub','1000','500000','[\"su.atb.mobileapp\",\"atb\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_ppj8q0drgn2akrh5e7wzl5tufxymmaeq.png'),
(31,'Приморье Банк','primbank','100000000226','rub','1000','500000','[\"ru.ftc.faktura.multibank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_j5wlvazy8tfccq6hszldgta7r7a3vayh.png'),
(32,'Левобережный Банк','nskbl','100000000052','rub','1000','500000','[\"ru.ftc.faktura.nskbl\",\"nskbl.ru\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_kbkmmnd8mrkdwruu4zxvkaszmesw9z3p.png'),
(33,'Ингосстрах Банк','ingo','100000000078','rub','1000','500000','[\"com.banksoyuz.artsofte\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_mh3bwyzs1uaieemqjjr45o7thpr3gdyx.png'),
(34,'ББР Банк','bbr-bank','100000000133','rub','1000','500000','[\"com.bifit.mobile.private.bbr\",\"bbr_bank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_3crutb38uhdyufewg6lloyrhediayund.png'),
(35,'Синара Банк','bank-sinara','100000000003','rub','1000','500000','[\"ru.skbbank.ib\",\"bank-sinara\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_pqpqwmsih2qifokvj7itaw08af2uwmd5.png'),
(36,'Ренессанс Банк','rencredit','100000000032','rub','1000','500000','[\"\\u0420\\u0435\\u043d\\u0435\\u0441\\u0441\\u0430\\u043d\\u0441\",\"Rencredit\",\"cz.bsc.rc\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_slqa7amtbk5jh0bi7xfoci43rrbjl9l1.png'),
(37,'Солид Банк','solid-bank','100000000230','rub','1000','500000','[\"ru.ftc.faktura.solidbank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_48pgeyffi8vqe8wymcib7z7um3tjqoke.png'),
(38,'РНКБ Банк','rncb','100000000011','rub','1000','500000','[\"com.bifit.rncbbeta\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_2b13orvnuijfxnhpu21ogaks1m0n1amg.png'),
(39,'УБРиР Банк','ubrib','100000000031','rub','1000','500000','[\"UBRR\",\"cb.ibank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_4p0wpff35oago3nynfr4iqyhei9reusl.png'),
(40,'Уралсиб банк','uralsib','100000000026','rub','1000','500000','[\"ru.bankuralsib.mb.android\",\"uralsib\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_awwdajddt3eqnkgyzcz0jcwdj3oaogm3.png'),
(41,'Солидарность Банк','solid','100000000121','rub','1000','500000','[\"com.isimplelab.ibank.solidarnost\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_ufyatyqipixybhshpaafrmyqbgefh48t.png'),
(42,'БКС банк','bksbank','100000000041','rub','1000','500000','[\"ru.bcs.bcsbank\",\"bcsbank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_dokcdlmwjfoedolvyhhpxrtzhan6axty.png'),
(43,'Акибанк','akibank','100000000107','rub','1000','500000','[\"ru.ftc.faktura.akibank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_wsudfzas3pnz8kb5k7z4mvx4vhwtgiur.png'),
(44,'Интеза Банк','bancaintesa','100000000170','rub','1000','500000','[\"ru.ftc.faktura.intesabank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_63xocxmxrb6jmfabfpcwxhtzarquygsl.png'),
(45,'Кредит Европа Банк','crediteurope','100000000027','rub','1000','500000','[\"com.idamobile.android.crediteuropa\",\"c.e.bank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_ezsy3ko8tpbeg0mxidgdxletb43gtijv.png'),
(46,'ТКБ Банк','tkbbank','100000000034','rub','1000','500000','[\"ru.ftc.faktura.tkbbank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_bz1yzpnihhsdxgfxibsocjs02tbvu82j.png'),
(47,'Просто Банк','posto_rub','105507700018','rub','1000','500000','[]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_2oftukivrj9deu6anxz5azwdsjl25vbj.png'),
(48,'Свой Банк','svoi','10000000888','rub','1000','500000','[\"Svoibank\",\"aosvoibank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_mlc3dgalgnmq6k5npdjk5dwf6mm9klkj.png'),
(49,'БыстроБанк','bystrobank','100000000092','rub','1000','500000','[]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_pqegvynexdlxhijzpr0d7dmjobyrnzbh.png'),
(50,'ЮниКредит Банк','unicreditbank','100000000030','rub','1000','500000','[\"ru.unicredit.android\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_al88mufehokuhbwzynqmcut7i2ayjawp.png'),
(51,'ПромТрансБанк','prome_rub','198800000273','rub','1000','500000','[]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_8jozgv6txtz7zhbur8f0bdcizovgzsm4.png'),
(52,'Драйв Клик','drajv-klik-bank','100000000250','rub','1000','500000','[\"com.cetelem.cetelem_android\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_z4j4kbfwe5n3fqdqzrqrjxdofendgc41.png'),
(53,'Форштадт Банк','forshtadt','100000000081','rub','1000','500000','[\"ru.ftc.faktura.forshtadt\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_lwludbcgkvycmw0paaji6i7v6ar4v9uh.png'),
(54,'ВБРР Банк','vbrr','100000000049','rub','1000','500000','[\"com.bssys.vbrrretail\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_iorxktdfk9omdpbhsz67bxy01i5ajlqc.png'),
(55,'Новикомбанк','novikom','100000000177','rub','1000','500000','[\"com.bssys.novikomretail\",\"novikom\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_lw2mfn67ezxggjzcvh3xogq0bfftyb7b.png'),
(56,'Металлинвестбанк','metallinvestbank','100000000046','rub','1000','500000','[\"METIB_CARD\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_vf6v1tjlfvfbbzywarqj3bw5g76sdyn4.png'),
(57,'Авангард','avangard','100000000028','rub','1000','500000','[\"avangard\",\"ru.avangard\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_z4ogsuvwrcdhbw0beecqxiz8wcgokeqk.png'),
(58,'Экспобанк','expobank','100000000044','rub','1000','500000','[\"ru.ftc.faktura.expobank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_douhncs4zywkpkkzbusesm4sqqdgzeca.png'),
(59,'Финам Банк','finam','100000000040','rub','1000','500000','[\"ru.finambank.app\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_bmais3jvkiptlahf4y7batwg9gbkmccc.png'),
(60,'МСП Банк','mspbank','100000000999','rub','1000','500000','[]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_blzaurs3kc4akmpodbdd8ordsexmkmvu.png'),
(61,'Цифра Банк','cifra','100000000265','rub','1000','500000','[\"com.bankffin.portfolio\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_2qegtgz4wpfg2urstctx8e6tesztdhgm.png'),
(62,'Энергобанк','energobank','100000000159','rub','1000','500000','[\"com.energobank.digital\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_zta027xq5fatqu3dm9tzoidzj3b6khh4.png'),
(63,'Реалист Банк','realistbank','100000000232','rub','1000','500000','[\"ru.ftc.faktura.baikalinvestbank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_ak7nr77bmaidw2is3jo2naawyxezsvg7.png'),
(64,'Локо Банк','lockobank','100000000161','rub','1000','500000','[\"com.idamobile.android.LockoBank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_bd4pfhowjzjlrmqmzg01gijzjccjeg82.png'),
(65,'Яндекс Банк','jandeks-bank','100000000150','rub','1000','500000','[\"\\u042f\\u043d\\u0434\\u0435\\u043a\\u0441 \\u041f\\u0435\\u0439\",\"\\u042f\\u043d\\u0434\\u0435\\u043a\\u0441 \\u0411\\u0430\\u043d\\u043a\",\"com.yandex.bank\"]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_jxxhzj7bcxkdidgpc7xmq6b1k36unito.png'),
(66,'Вологжанин Банк','bank-vologzhanin','100000000888','rub','1000','500000','[]',NULL,9,7,2.5,10,1.5,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_pqk0gyu58e81xwsueqlttpjhzkaywdyy.png'),
(67,'Генбанк','genbank','100000000037','rub','1000','500000','[\"genbank\",\"com.mmonline.mobile\"]',NULL,9,7,3,10,1,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_9jjbothwcmfoch9donz0vvi5u70oxtys.png'),
(68,'ТомскПромСтройБанк','tpsbank','100000000206','rub','1000','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_ssexozygbgm84epf0peinmaluyuu9syb.png'),
(69,'Kaspi','kaspi_kzt',NULL,'kzt','1000','5000000','[\"kz.kaspi.mobile\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_3qztdzcrvgpm503oygfgriddck5mv7od.png'),
(70,'Home Credit Bank','homecreditbank_kzt','100001100418','kzt','1000','5000000','[\"home.kz\",\"kz.home.capp\",\"kz.kkb.homebank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_xhhoa3hh4juzqiemmfmra4b95czdhcms.png'),
(71,'Контур Банк','kontur_rub',NULL,'rub','1500','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,10,'logo_yqinvmzyov7pwjfq5xmc0b00td1dy0kl.png'),
(72,'Freedom bank','freedom_kzt','100000330111','kzt','10000','10000000','[\"ffinbank.myfreedom\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\"]',20,15,'logo_nlh6fan3ibwd9wixritwjbiz7zuciask.png'),
(73,'Русский Стандарт','rsb','100000000014','rub','1000','500000','[\"ru.simpls.brs2.mobbank\",\"rsb.ru\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_q9vwnhqm4zsfmqqftznptzdeqc9srjp6.png'),
(74,'Точка Банк','tochka-bank','100000000284','rub','1000','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_wuf3yb5dzxgkdmv0nhs2iaykxzbkzugb.png'),
(75,'Тимер Банк','timerbank','100000000144','rub','1000','500000','[\"com.timerbank.retail\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_wfvt3vd7t2w2jb05lhlvuqu12sjmafg3.png'),
(76,'Долинск Банк','kb-dolinsk','100000000270','rub','1000','500000','[\"ru.ftc.faktura.dolinsk\",\"BankDolinsk\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_9vuzrrl92kkgillsu7ky0hovqp85vaq4.png'),
(77,'Таджикистан Амонатбанк','amonatbank_rub',NULL,'rub','50','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',20,15,'logo_fpqqjgb3agay2ztpcnjsciahavxkkhie.png'),
(78,'Таджикистан Тавхидбанк','tavhidbank_rub','10000003452','rub','100','500000','[]',NULL,9,7,2,10,2,1,0,1,'[\"card\"]',20,15,'logo_mwmly5dzqew77exwlpm3ohdpgxqumg15.png'),
(79,'Таджикистан Банк Арванд','arvard_rub',NULL,'rub','100','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',20,15,'logo_qje8wj9laasyprw8o3omlebvpmp2obk3.png'),
(80,'Таджикистан Банк Душанбе Сити','dushambecity_rub',NULL,'rub','50','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',20,15,'logo_gxf78e5fasfn5kmcgciy0udamnog9p7f.png'),
(81,'Таджикистан Банк Эсхата','esxata_rub',NULL,'rub','50','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',20,15,'logo_bctnsdrrjvdsgcgn2e1s9mkvl1hx0x9w.png'),
(82,'Таджикистан Банк Спитамен','spitamen_rub','3483482348854825','rub','50','500000','[\"com.bank.spitamen.pay.spitamenpay\",\"bank\"]',NULL,9,7,2,10,2,1,0,1,'[\"phone\"]',20,20,'logo_nzoeexh68ltfh058qxybojwyq1ojnigu.png'),
(83,'Таджикистан Банк Алиф','alif_rub','1020349347347','rub','50','500000','[]',NULL,9,7,2,10,2,1,0,1,'[\"phone\"]',20,15,'logo_kpzwll4utbi0cieq9owryvezv2atnm8i.png'),
(84,'Таджикистан Международный банк','mbtjs_rub',NULL,'rub','50','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',20,15,'logo_gpxx4iblsnzq16vegnfmn7fhmf6bvcpn.png'),
(85,'Абхазия А-Мобаил кошелек','amobile_rub',NULL,'rub','1000','500000','[\"com.amobile.application\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\"]',20,15,'logo_ijnsyqefd2hkbqp6lounvndmrs4zz71b.png'),
(86,'АБ Россия','abr','100000000095','rub','1000','500000','[\"abr\",\"ru.artsofte.russiafl\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,15,'logo_vvbimkfzcrgnbax9olbzfwzn2jikrlsi.png'),
(87,'Банк Санкт-Петербург','bspb','100000000029','rub','1000','500000','[\"ru.bspb\",\"BankSPB\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_lscbkygkifj7hn7ezuiydouygkcrj9i7.png'),
(88,'Челябинвестбанк','chelinvest','100000000094','rub','1000','500000','[\"ru.chelyabinvestbank.investpay\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_kaouyijw5ntfz0mvdtbe8flrcoytnuzm.png'),
(89,'Цупис','cupis_rub','120000000111','rub','700','500000','[]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,12,'logo_8le8pbm3zqltmaibd739phzmfj9aztxi.png'),
(90,'Норвик Банк','norvikbank','100000000202','rub','1000','500000','[\"ru.vtkbank.android\",\"Norvik_Bank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,15,'logo_uyoaj5omgneq9trnjlc0yc2a4zplxfzb.png'),
(91,'Хлынов Банк','bank-hlynov','100000000056','rub','1000','500000','[\"ru.bank_hlynov.xbank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,15,'logo_kgmsax8nlcr5mxal3klud9lt0azhwte0.png'),
(92,'Первый ДорТранс Банк','dtb1','100000000174','rub','1000','500000','[\"ru.ftc.faktura.finbank\",\"ru.ftc.faktura.dortrbank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,15,'logo_3ltgkp1bwiryhb1b9yraxdgqonx5wt3c.png'),
(93,'Газэнергобанк','gebank','100000000043','rub','1000','500000','[\"ru.gebank.ib\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_glmwk9qgfj6ugfqnmj9denzdnjn8oqsa.png'),
(94,'Бланк банк','blanc','100000000053','rub','1000','500000','[\"ru.ftc.faktura.vesta\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_yfatqcw7cykpen1b6v8jvrgsgcslhkqj.png'),
(95,'Хакасский муниципальный банк','kbhmb','100000000127','rub','1000','500000','[\"ru.ftc.faktura.kbhmb\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,15,'logo_2q4pq1jyobrosdqcm5xhpkenpnjw4fqb.png'),
(96,'ПСКБ','pscb','100000000087','rub','1000','500000','[\"ru.ftc.faktura.pskb\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_oh2tjfqs34peoff23yxcndawy5prxsig.png'),
(97,'Кошелев банк','koshelev-bank','100000000146','rub','1000','500000','[\"com.bifit.mobile.citizen.kbnk\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_0kkbse8gzfmoz1hbxt1hmelkasi6d7dk.png'),
(98,'СДМ-Банк','sdm','100000000069','rub','1000','500000','[\"ru.ftc.faktura.sdm\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_ldkd1m3ysdequk7l6egvuthq4tdfyx9s.png'),
(99,'Акцепт Банк','akcept','100000000135','rub','1000','500000','[\"ru.ftc.faktura.akcept\",\"BankAkcept\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_230cetvkamzwcrbzmnlxymizt9cby3px.png'),
(100,'Газтрансбанк','gaztransbank','100000000183','rub','1000','500000','[\"ru.ftc.faktura.gaztransbank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_kvxg1l60ny04adaib8tfbn6vudx2ozhw.png'),
(101,'ЧЕЛИНДБАНК','chelindbank','100000000106','rub','1000','500000','[\"com.isimplelab.ibank.chelind\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,10,'logo_lns2cdfkg7petzdek9z20ngalum0ogxe.png'),
(102,'МФК Банк','mezhdunarodnyj-finansovyj-klub','100000000203','rub','1000','500000','[\"ru.ftc.faktura.mfkbank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_gpl2gejg8vr4tzrnouzef7asotxcxkah.png'),
(103,'Александровский Банк','bank-aleksandrovskij','100000000211','rub','1000','500000','[\"ru.ftc.faktura.alexbank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_nom0xdhpip4bhqj81kzft7wezutd6hgr.png'),
(104,'Центр-инвест','centrinvest','100000000059','rub','1000','500000','[\"ru.centrinvest.mobilebanking2018\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_gf5e7xgdiosfjcewbvvmdmvtmmcb1c3a.png'),
(105,'РостФинанс','rost_finance','100000000098','rub','1000','500000','[\"ru.ftc.faktura.rostfinance\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\"]',20,15,'logo_3rg7g0tvbim8lovnedlhul3b3ktmfx5l.png'),
(106,'Кубаньторгбанк','bktb','100000000180','rub','1000','500000','[\"ru.isfront.android.kt\"]',NULL,9,7,2,10,2,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_dabpgd8pwo63kd6pcftswvpk4gpokefr.png'),
(107,'Заречье','zarech','100000000205','rub','1000','500000','[\"com.bifit.mobile.citizen.zarech\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_sqhygty5bpvzsnafjbrl93ngzh69iqqm.png'),
(108,'Банк Кремлевский','kremlinbank','100000000201','rub','1000','500000','[\"ru.ftc.faktura.kremlevskiy\"]',NULL,9,7,3,10,1,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_anwyithqhvzhe6a1tsn3n4xcmsshuguo.png'),
(109,'Морской Банк','maritimebank','100000000171','rub','1000','500000','[\"ru.ftc.faktura.maritimebank\"]',NULL,9,7,2,10,2,1,0,0,'[\"card\",\"phone\"]',20,15,'logo_cckurvp3u9xurgmcnpcabl35a9z4lprx.png'),
(110,'Углемет','coalmetbank','100000000093','rub','1000','500000','[\"com.isimplelab.isimpleceo.uglemet\"]',NULL,9,7,3,10,1,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_8xyicukhezmohizjf8aqx4jttdkilcg8.png'),
(111,'Дальневосточный банк','dvbank','100000000083','rub','1000','500000','[\"com.bifit.dvbank\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\"]',20,15,'logo_rl9uv9xirff4aldadksqjwsoireqvtpy.png'),
(112,'Датабанк','databank','100000000070','rub','1000','500000','[\"com.mifors.izhcombank\"]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\"]',20,10,'logo_mklmbaj6bjggakxa9e0snw3ohbwk0e6d.png'),
(113,'Оранжевый Банк','bankorange','100000000286','rub','1000','500000','[]',NULL,9,7,3,10,1,1,0,0,'[\"phone\",\"card\"]',20,15,'logo_gbyshydbyoitlntmlncy3fhmrrxzxrou.png'),
(114,'Элплат','elpat','100000000086','rub','1000','500000','[]',NULL,9,7,3,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',20,15,'logo_eikmklxg3unrjccwx10rsugwtomuwfsr.png'),
(115,'Абхазия Универсал-банк','universalbank','100055000165','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,1,'[\"card\",\"phone\",\"account_number\"]',20,1,'logo_sbreert6igxcbmn3qm3ecybhizl85rmr.png'),
(116,'ТрансСтройБанк','transstroybank','100000000197','rub','1000','500000','[\"com.intervale.sbp.atlas\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_cehskjsjcx0xykg8ejeiugsyngdxiw8n.png'),
(117,'Мир Привилегий Банк','mp-bank','100000000169','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_awma3tvrw68dlckqiwa3eywvjnfbv6k9.png'),
(118,'Тест банк','test','108807700001','rub','100','1000','[]',NULL,9,7,1,10,1,1,0,0,'[\"account_number\",\"phone\"]',15,1,'logo_1r9awavshmhahc6zv7s7uykctsikdhqj.png'),
(119,'Алмазэргиэнбанк','almazjergijenbank','100000000080','rub','1000','500000','[\"ru.albank.online.aebit\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',15,1,'logo_pycfbbe99tsjxypdinay1ni3kdiub67g.png'),
(120,'СибСоцБанк','sibsoc','100000000166','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_4kegaeh1apc554medhgdt5ifjthme4jx.png'),
(121,'Авито','avito','106600000111','rub','1000','30000','[]',NULL,9,7,1,10,1,1,0,0,'[\"phone\"]',10,1,'logo_v3uffi2kbdxvr2s6nffn0m9eqfbchbgb.png'),
(122,'Развитие Столица','dcapital','100000000172','rub','1000','500000','[\"ru.ftc.faktura.razvitiestolica\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',15,1,'logo_hyhlpvd7xdpefc3zfzvmu3vi0kjdhy6i.png'),
(123,'Примсоцбанк','pskb','100000000088','rub','1000','500000','[\"ru.ftc.faktura.primsoc\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_f9ladkapm7lokhgloqdkc18ryvjttfh0.png'),
(124,'Банк Саратов','bank-saratov','100000000126','rub','1000','500000','[\"ru.ftc.faktura.banksaratov\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_el0nwll94zsnhlff2o23sqy5x6jmxerm.png'),
(125,'Таврический Банк','tavrich','100000000173','rub','1000','500000','[\"ru.ftc.faktura.tavrich\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_cllsorqofaoq00xflenqwndel5gytbnn.png'),
(126,'Тольяттихимбанк','thbank','100000000152','rub','1000','500000','[\"com.bifit.mobile.citizen.thbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_iulk1vyghsfl57osgvpyrsw8ug9pem23.png'),
(127,'АКБ Держава','akb-derzhava','100000000235','rub','1000','500000','[\"ru.ftc.faktura.derzhava\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_sarbfrobj70nvw9oaizfssgkwsh5wgg5.png'),
(128,'НБД-Банк','nbd-bank','100000000134','rub','1000','500000','[\"ru.nbd.android\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_xvq6dgcd4aogjfm6dxsjxptmvdhxnfjl.png'),
(129,'БАНК СНГБ','sngb','100000000091','rub','1000','500000','[\"ru.sngb.dbo.client.android\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_bvzh1mmdxfwkayk7yaeijv4af0mqead2.png'),
(130,'Енисейский объединенный банк','enisejskij','100000000258','rub','1000','500000','[\"ru.ftc.faktura.united\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_4ho7b1vrzbncatbfypyxnfa9ir9e8lbb.png'),
(131,'Банк Венец','venets-bank','100000000153','rub','1000','500000','[\"ru.ftc.faktura.venetsbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_q2donf82amybyjwcbkda9kqk1h3bq2we.png'),
(132,'УралПромБанк','uralprombank','100000000142','rub','1000','500000','[\"ru.uralprombank.mobilebanknew.googleplay\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_ypw01up2zjina4xwldlgwrjugqdlhe6n.png'),
(133,'Банк Национальный стандарт','ns-bank','100000000243','rub','1000','500000','[\"ru.ftc.faktura.nsbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_i8aiz1edkjxxcjhm30owjndzseegfavr.png'),
(134,'Банк Екатеринбург','bank-ekaterinburg','100000000090','rub','1000','500000','[\"ru.emb.android\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_ckwblakgnrb8hox3pxuy8yqg8gzqkovi.png'),
(135,'Авто Финанс Банк','avtofinbank','100000000253','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_qxnb6qasfziq8mbjjwuxownakbucd8pd.png'),
(136,'Стройлесбанк','kb-strojlesbank','100000000193','rub','1000','500000','[\"com.bssys.stroylesretail\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_30vxxuxnhwysy0md1zvoulvodqi4b4dq.png'),
(137,'Кузнецкбизнесбанк','kbb','100000000195','rub','1000','500000','[\"ru.ftc.faktura.kbb\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_pfagbuat14ozmi8qqjjxn0iqi51mxxh6.png'),
(138,'Нацинвестпромбанк','nipbank','100000000185','rub','1000','500000','[\"ru.ftc.faktura.nipbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_ui5rvzntb75yumzubjsudtn0nxkq3nvk.png'),
(139,'АКБ Алеф-Банк','alefbank','100000000113','rub','1000','500000','[\"ru.ftc.faktura.alefbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_ddc3ecvjexacrrziyytlzkiza7jdzlpt.png'),
(140,'Внешфинбанк','vfbank','100000000248','rub','1000','500000','[\"com.bifit.vfbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_tntqpvs88xaflwsjns3fk3v0ln1yxd9m.png'),
(141,'АресБанк','aresbank','100000000129','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_ntgeeduxlho4ueiap712bypmpo3ekh28.png'),
(142,'Северный Народный Банк','sevnb','100000000208','rub','1000','500000','[\"com.snb.online\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_dshxodax6fvsa9kjhsmql26wwbblptzb.png'),
(143,'Банк Объединенный капитал','okbank','100000000182','rub','1000','500000','[\"com.bifit.mobile.citizen.okbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_nxouuxs5wz3jx3cdffskelfbk1h9eqtr.png'),
(144,'Татсоцбанк','tatsotsbank','100000000189','rub','1000','500000','[\"com.tatsotsbank.dbomobile\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_7g7k9uacz796ucbqzf4dlewojvztyoyj.png'),
(145,'Земский банк','zemsky','100000000066','rub','1000','500000','[\"ru.ftc.faktura.zemskybank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_vpqc0wlm7fvu34crd0d04tnspaiohmyn.png'),
(146,'Оренбург Банк','orbank','100000000124','rub','1000','500000','[\"ru.ftc.faktura.orbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_y5dpkrfztbhym6eifwbk5r7nxuchqseq.png'),
(147,'Агропромкредит','apkbank','100000000118','rub','1000','500000','[\"ru.ftc.faktura.agropromkredit\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"account_number\",\"card\"]',10,1,'logo_2tyi0caysctyjockyogbprgdtdmwanze.png'),
(148,'Банк Казани','bankofkazan','100000000191','rub','1000','500000','[\"com.isimplelab.ionic.kazan.fl\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_t0zgia7qov42zpumgvmkx6nm6tqfykjw.png'),
(149,'Социум Банк','socium-bank','100000000223','rub','1000','500000','[\"com.intervale.sbp.atlas\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_tsdu0mu6clso0clzzr5cyoosc2awesbi.png'),
(150,'Синко Банк','sinko-bank','100000000148','rub','1000','500000','[\"com.intervale.sbp.atlas\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_a1yhors7cgtdmssg8xf0clwauijj6q9a.png'),
(151,'ИШБАНК','ishbank','100000000199','rub','1000','500000','[\"com.bifit.pmobile.isbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_gmvzqe1wr6dx0gpsudkmopgnnamsizuv.png'),
(152,'Горбанк','gorbank','100000000125','rub','1000','500000','[\"com.isimplelab.ionic.gorbank.prod\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_eh6hzkjvf1eoi5mnaejuxhcnmgjycx6v.png'),
(153,'Москомбанк','moskombank','100000000176','rub','1000','500000','[\"ru.ftc.faktura.moscombank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_0wemvuqeyhrlqdhht3b9hewvdvh23ggu.png'),
(154,'Русьуниверсалбанк','rus-universalbank','100000000165','rub','1000','500000','[\"ru.rubank.ubsmobile\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_ulecnqfjdxvvrem66qmhkl2ti4oqwsjq.png'),
(155,'Пойдём Банк','poidem','100000000103','rub','1000','500000','[\"com.openwaygroup.ic.panda.poidem\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_orcng5hxe7r5mi7nom8y0xpxhm9axpxb.png'),
(156,'Белгородсоцбанк','ukb-belgorodsocbank','100000000225','rub','1000','500000','[\"com.bifit.mobile.citizen.belsocbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_towhanptnlykw9qoi6yh8zeouvsdrs9q.png'),
(157,'Хайс Банк','hajs','100000000272','rub','1000','500000','[\"com.hicebank.android\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_t5dn5j5ijnt4x3okxlcaghismczf2ycz.png'),
(158,'Севергазбанк','severgazbank','100000000219','rub','1000','500000','[\"com.bpc.crossplatform_trading.bpc_trading\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_pfnsbodbcmo7kwls8pdiop0wgbmgisxc.png'),
(159,'НРБанк','nrb','100000000184','rub','1000','500000','[\"com.bifit.nrb\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_y93byeyr0zmtasigvyi71iud2rqtygpf.png'),
(160,'Москоммерцбанк','kb-moskommercbank','100000000110','rub','1000','500000','[\"com.bifit.mobile.citizen.moskb\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_jjn3aia25r88nahnincnkul335tjprbx.png'),
(161,'Новобанк','novobank','100000000222','rub','1000','500000','[\"ru.ftc.faktura.novobank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_6kpvbppbngdbnhevimlh9kjhqctgzdg2.png'),
(162,'Банк Финсервис','bank-finservis','100000000216','rub','1000','500000','[\"com.finservice.mobile\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_ih5jgwmkicnvx5d5mojouqkxlo8hd1ud.png'),
(163,'Новый век','novyj-vek','100000000067','rub','1000','500000','[\"com.isimplelab.ionic.standart\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\"]',10,1,'logo_ibi60pmjjdfhkmwvd8wqep9mb0cq2d4a.png'),
(164,'Славия Банк','akb-slavija','100000000200','rub','1000','500000','[\"com.isimplelab.ionic.slavia.prod\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_cvv3gq7kbezk3qvshosooqs14iyqiq3j.png'),
(165,'Еврофинанс Моснарбанк','akb-evrofinans-mosnarbank','100000000167','rub','1000','500000','[\"com.bifit.mobile.citizen.efbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_tq8ivp7oyn9nhot974qu1ybn9eylzlcr.png'),
(166,'Банк Москва-Сити','mcbank','100000000234','rub','1000','500000','[\"com.bifit.mobile.citizen.MCBank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_mijeaer6rbj3d7d4xuwsnpt0gv9i2t95.png'),
(167,'Гута-банк','gutabank','100000000149','rub','1000','500000','[\"com.bssys.gutaretail\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_ggevwo56hpns02cpphn1z2v4sy6ix7sw.png'),
(168,'Финстар Банк','finstarbank','100000000278','rub','1000','500000','[\"ru.ftc.faktura.siab\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_xnyhebukit0kfdgv4pfw4fqpxwc97jet.png'),
(169,'Раунд Банк','round','100000000247','rub','1000','500000','[\"com.isimplelab.ionic.round.prod\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"account_number\",\"card\"]',10,1,'logo_wfnxlqvmuwlgahe2d5l6jcz41pv6nx1c.png'),
(170,'Прио-Внешторгбанк','prio-vneshtorgbank','100000000228','rub','1000','500000','[\"com.priobank.prio\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_yzfyvbryvsljtfzsca3rgwupo7bat7fr.png'),
(171,'Инбанк','in-bank','100000000196','rub','1000','500000','[\"com.inbank.mobilebank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_rsastume1iaf3axzpyd3z0f7nidx6h8u.png'),
(172,'Уралфинанс','bank-uralfinans','100000000096','rub','1000','500000','[\"com.isimplelab.isimplemobile.payjet\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"card\",\"account_number\"]',10,1,'logo_wk0zt7howoraw9fyih5kvwypkihtvys5.png'),
(173,'Агророс','agroros','100000000102','rub','1000','500000','[\"ru.ftc.faktura.agroros\\\"\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_sskdzbk5gx5aks26vswxa6wgekc25yie.png'),
(174,'Снежинский','bank-snezhinskij','100000000163','rub','1000','500000','[\"com.compassplus.mobicash.customer\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_fdwckk9yzqnzpj820lvowm4hvyyjxvc0.png'),
(175,'Элита Банк','bank-jelita','100000000266','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_3nfxlxtjr2a7kjjghc5fxfry8w81p5tv.png'),
(176,'ПроБанк','probank','100000000117','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_dcxbjnpqyqzgmjexubhbzb1xps63teib.png'),
(177,'ЦМРБанк','cmrbank','100000000282','rub','1000','500000','[]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_8ezwlkgnziyckyndlwpkdpxxuipoc2xk.png'),
(178,'ВТБ-ВТБ','vtb-vtb','177777000111','rub','500','500000','[]',NULL,9,5.5,1,10,1,1,1,0,'[\"card\",\"account_number\"]',10,1,'logo_6um4welqamkxwegx7xs1edogovtuqmlv.png'),
(179,'ТБАНК-ТБАНК','tbanktbank','188880000111','rub','1000','500000','[]',NULL,9,7,1,10,1,1,1,0,'[\"card\",\"account_number\"]',10,1,'logo_wxlowkzcsoylknqu0hnjihc9ljhterd2.png'),
(180,'СБЕР-СБЕР','sber-sber','199900000111','rub','1000','500000','[]',NULL,9,6,1,10,1,1,1,0,'[\"account_number\",\"card\"]',10,1,'logo_j8gomkwmlv2b2xklcov9b8uz5xcxh8g1.png'),
(181,'АЛЬФА-АЛЬФА','alfa-alfa','155557700111','rub','1000','500000','[]',NULL,9,7,1,10,1,1,1,0,'[\"account_number\",\"card\",\"phone\"]',10,1,'logo_82urxtm1sd426gx2lkeahxtkstjdlx5w.png'),
(182,'Монета','moneta','100004440017','rub','1000','500000','[]',NULL,9,7,1,9,1,1,0,0,'[\"phone\"]',10,1,'logo_dzjhl9cjotyuhbdhlnxqtu73zgfi4lpe.png'),
(183,'Энерготрансбанк','energotransbank','100000000139','rub','1000','500000','[\"ru.ftc.faktura.etbank\"]',NULL,9,7,1,10,1,1,0,0,'[\"card\",\"phone\",\"account_number\"]',10,1,'logo_muk9ecft7eptcxlxjes8knogvwwobovv.png'),
(184,'Крокус-Банк','krokusbank','900000000212','rub','1000','500000','[\"ru.krk.ubsmobile\"]',NULL,9,7,1,10,1,1,0,0,'[\"phone\",\"account_number\",\"card\"]',10,1,'logo_z9gztbhajlkathdaaqcizmgdhobji7xd.png'),
(185,'Ozon-Ozon','ozon-ozon','300000000111','rub','1000','500000','[]',NULL,9,6.5,1,10,1,1,1,0,'[\"account_number\",\"card\"]',10,1,'logo_5cefxuinzqvccvkijvcc2oprko5plubm.png'),
(186,'Яндекс Банк - Яндекс Банк','yandex-yandex','200000004111','rub','1000','500000','[]',NULL,9,6.5,1,10,1,1,1,0,'[\"card\",\"account_number\"]',10,1,'logo_jk4so2msgqiz2adoqvaznfwhurmht7fo.png'),
(187,'Bereke Bank','bereke','700000000111','kzt','1000','2000000','[]',NULL,9,5,1,10,1,1,0,0,'[\"card\",\"phone\"]',10,1,'logo_zodigld6slyvwqjczhj2hftbsi03ds5b.png'),
(188,'ПСБ','pcbbank','1000000001112','rub','1000','500000','[]',NULL,9,10,1,10,1,1,0,0,'[\"card\",\"phone\"]',20,1,'logo_5rb8kumrphvymwnwttgxfbi9dwl8bxrj.png'),
(189,'Таджикистан Ориёнбанк','orienbank','111111111111','rub','100','500000','[\"tj.oriyonbonk\"]',NULL,9,7,1,10,1,1,0,1,'[\"card\",\"phone\"]',20,1,'logo_0wxdmuo4xaneep1axt7wzncjsfkn0oej.png'),
(190,'АЛЬФА-АЛЬФА1','alfa-alfa11','100330000112','rub','1000','400000','[\"Alfabank\",\"Alfa-Bank\"]',NULL,9,7.5,1,10,1,0,1,0,'[\"phone\",\"card\",\"account_number\"]',10,1,'logo_qxmug2usvpymv41mogmbyxjuai5tzde1.png');
/*!40000 ALTER TABLE `payment_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_gateways`
--

DROP TABLE IF EXISTS `payout_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_gateways`
--

LOCK TABLES `payout_gateways` WRITE;
/*!40000 ALTER TABLE `payout_gateways` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_offers`
--

DROP TABLE IF EXISTS `payout_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_offers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `max_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) DEFAULT NULL,
  `occupied` tinyint(1) NOT NULL DEFAULT '0',
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_offers`
--

LOCK TABLES `payout_offers` WRITE;
/*!40000 ALTER TABLE `payout_offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payouts`
--

DROP TABLE IF EXISTS `payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail_initials` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payout_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_liquidity_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `liquidity_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_commission_rate` float DEFAULT NULL,
  `service_commission_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trader_profit_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trader_exchange_markup_rate` float DEFAULT NULL,
  `trader_exchange_markup_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_exchange_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payout_offer_id` bigint unsigned DEFAULT NULL,
  `payout_gateway_id` bigint unsigned DEFAULT NULL,
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `sub_payment_gateway_id` bigint unsigned DEFAULT NULL,
  `trader_id` bigint unsigned DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `refuse_reason` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancel_reason` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `previous_trader_id` bigint unsigned DEFAULT NULL,
  `video_receipt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payouts`
--

LOCK TABLES `payouts` WRITE;
/*!40000 ALTER TABLE `payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_uses` int NOT NULL DEFAULT '0' COMMENT '0 - unlimited',
  `used_count` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `team_leader_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promo_codes_code_unique` (`code`),
  KEY `promo_codes_team_leader_id_foreign` (`team_leader_id`),
  CONSTRAINT `promo_codes_team_leader_id_foreign` FOREIGN KEY (`team_leader_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisite_provider_callback_logs`
--

DROP TABLE IF EXISTS `requisite_provider_callback_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requisite_provider_callback_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `request_data` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `execution_time` double DEFAULT NULL,
  `status_code` int DEFAULT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '0',
  `error_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requisite_provider_callback_logs_provider_name_index` (`provider_name`),
  KEY `requisite_provider_callback_logs_merchant_id_index` (`merchant_id`),
  KEY `requisite_provider_callback_logs_order_id_index` (`order_id`),
  KEY `requisite_provider_callback_logs_is_successful_index` (`is_successful`),
  KEY `requisite_provider_callback_logs_created_at_index` (`created_at`),
  KEY `requisite_provider_callback_logs_request_id_index` (`request_id`),
  CONSTRAINT `requisite_provider_callback_logs_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisite_provider_callback_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisite_provider_callback_logs`
--

LOCK TABLES `requisite_provider_callback_logs` WRITE;
/*!40000 ALTER TABLE `requisite_provider_callback_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `requisite_provider_callback_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisite_provider_logs`
--

DROP TABLE IF EXISTS `requisite_provider_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requisite_provider_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `request_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_params` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `response_time_ms` int DEFAULT NULL,
  `retry_attempt` int NOT NULL DEFAULT '1',
  `detail_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requisite_provider_logs_provider_name_success_created_at_index` (`provider_name`,`success`,`created_at`),
  KEY `requisite_provider_logs_merchant_id_created_at_index` (`merchant_id`,`created_at`),
  KEY `requisite_provider_logs_order_id_index` (`order_id`),
  KEY `requisite_provider_logs_created_at_index` (`created_at`),
  KEY `requisite_provider_logs_provider_name_index` (`provider_name`),
  KEY `requisite_provider_logs_success_index` (`success`),
  CONSTRAINT `requisite_provider_logs_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisite_provider_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisite_provider_logs`
--

LOCK TABLES `requisite_provider_logs` WRITE;
/*!40000 ALTER TABLE `requisite_provider_logs` DISABLE KEYS */;
INSERT INTO `requisite_provider_logs` VALUES
(1,'internal',1,NULL,'getRequisites','{\"amount\": \"10\", \"currency\": \"rub\", \"order_id\": null, \"transgran\": null, \"gateway_id\": 1, \"detail_type\": \"card\", \"merchant_id\": 1}',NULL,0,NULL,34,0,NULL,'2025-11-12 15:56:48','2025-11-12 15:56:48'),
(2,'internal',1,NULL,'getRequisites','{\"amount\": \"10\", \"currency\": \"rub\", \"order_id\": null, \"transgran\": null, \"gateway_id\": 1, \"detail_type\": \"card\", \"merchant_id\": 1}',NULL,0,NULL,9,0,NULL,'2025-11-12 16:21:32','2025-11-12 16:21:32');
/*!40000 ALTER TABLE `requisite_provider_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'Super Admin','web','2025-07-29 20:28:56','2025-07-29 20:28:56'),
(2,'Trader','web','2025-07-29 20:28:56','2025-07-29 20:28:56'),
(3,'Merchant','web','2025-07-29 20:28:56','2025-07-29 20:28:56'),
(4,'Team Leader','web','2025-07-29 20:28:56','2025-07-29 20:28:56'),
(5,'Support','web','2025-07-29 20:28:56','2025-07-29 20:28:56'),
(6,'Merchant Support','web','2025-07-29 20:28:56','2025-07-29 20:28:56');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sender_stop_lists`
--

DROP TABLE IF EXISTS `sender_stop_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sender_stop_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sender_stop_lists`
--

LOCK TABLES `sender_stop_lists` WRITE;
/*!40000 ALTER TABLE `sender_stop_lists` DISABLE KEYS */;
INSERT INTO `sender_stop_lists` VALUES
(1,'cc.coolline.client.pro'),
(2,'cc.coolline.client.pro'),
(3,'org.telegram.messenger'),
(4,'org.telegram.messenger.web'),
(5,'com.samsung.android.app.smartcapture'),
(6,'com.nebula.karing'),
(7,'com.clock.calendar.alarm'),
(8,'com.android.systemui'),
(9,'com.nebula.karing'),
(10,'com.gadgets.repay'),
(11,'com.clock.calendar.alarm'),
(12,'com.nebula.karing'),
(13,'com.nebula.karing'),
(14,'com.nebula.karing'),
(15,'com.android.systemui'),
(16,'com.clock.calendar.alarm'),
(17,'com.nebula.karing'),
(18,'com.nebula.karing'),
(19,'com.android.systemui'),
(20,'com.nebula.karing'),
(21,'com.clock.calendar.alarm'),
(22,'com.nebula.karing'),
(23,'com.android.systemui'),
(24,'com.nebula.karing'),
(25,'com.xiaomi.mipicks'),
(26,'com.miui.msa.global'),
(27,'com.google.android.googlequicksearchbox'),
(28,'ru.yandex.searchplugin'),
(29,'com.samsung.android.incallui'),
(30,'com.wssyncmldm'),
(31,'com.google.android.adservices.api'),
(33,'com.nebula.karing'),
(34,'com.clock.calendar.alarm'),
(35,'com.android.systemui'),
(36,'beeline'),
(37,'com.miui.videoplayer'),
(38,'com.mi.android.globalminusscreen'),
(39,'com.android.providers.downloads.ui'),
(40,'com.android.thememanager'),
(41,'com.mi.globalbrowser'),
(42,'com.miui.securitycenter'),
(43,'com.yandex.searchapp'),
(44,'com.whatsapp'),
(45,'com.osp.app.signin'),
(46,'com.samsung.android.forest'),
(47,'com.sh.smart.caller'),
(48,'app.hiddify.com'),
(49,'com.aura.oobe.samsung.gl'),
(50,'com.android.providers.downloads'),
(51,'com.samsung.android.themestore'),
(52,'com.samsung.android.dialer'),
(55,'com.nearme.romupdate'),
(56,'com.heytap.market');
/*!40000 ALTER TABLE `sender_stop_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'prime_time_bonus_starts','00:00'),
(2,'prime_time_bonus_ends','05:00'),
(3,'prime_time_bonus_rate','0'),
(4,'support_link','https://t.me/<TEMPLATE-SUPPORT-LINK>'),
(5,'currency_price_parser_settings','{\"rub\":{\"amount\":80000,\"payment_method\":582,\"ad_quantity\":5},\"kzt\":{\"amount\":50000,\"payment_method\":549,\"ad_quantity\":3},\"byn\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"eur\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"tjs\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"kgs\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"uah\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"usd\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"azn\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"thb\":{\"amount\":999,\"payment_method\":14,\"ad_quantity\":3},\"try\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"idr\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3},\"aed\":{\"amount\":null,\"payment_method\":null,\"ad_quantity\":3}}'),
(6,'prime_time_bonus_starts','00:00'),
(7,'prime_time_bonus_ends','05:00'),
(8,'funds_on_hold_time','1440'),
(9,'prime_time_bonus_starts','00:00'),
(10,'prime_time_bonus_ends','05:00'),
(11,'prime_time_bonus_rate','0'),
(12,'max_pending_disputes','4'),
(13,'prime_time_bonus_starts','00:00'),
(14,'prime_time_bonus_ends','05:00'),
(15,'prime_time_bonus_rate','0'),
(16,'max_rejected_disputes','{\"count\":3,\"period\":15}'),
(17,'max_rejected_disputes','{\"count\":2,\"period\":10}'),
(18,'deposit_link','https://<TEMPLATE-DEPOSIT-LINK>'),
(19,'deposit_link','https://test/pay.php'),
(20,'max_rejected_disputes','{\"count\":2,\"period\":10}'),
(21,'prime_time_bonus_ends','05:00'),
(22,'prime_time_bonus_rate','0'),
(23,'prime_time_bonus_starts','00:00'),
(24,'prime_time_bonus_ends','05:00'),
(25,'prime_time_bonus_rate','0'),
(26,'platform_wallet','<TEMPLATE-PLATFORM-WALLET>'),
(27,'max_consecutive_failed_orders','{\"count\":5,\"period\":15}');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parsing_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `timestamp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `user_device_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_logs`
--

LOCK TABLES `sms_logs` WRITE;
/*!40000 ALTER TABLE `sms_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_parsers`
--

DROP TABLE IF EXISTS `sms_parsers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_parsers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `format` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regex` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_parsers`
--

LOCK TABLES `sms_parsers` WRITE;
/*!40000 ALTER TABLE `sms_parsers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_parsers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_stop_words`
--

DROP TABLE IF EXISTS `sms_stop_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_stop_words` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_stop_words`
--

LOCK TABLES `sms_stop_words` WRITE;
/*!40000 ALTER TABLE `sms_stop_words` DISABLE KEYS */;
INSERT INTO `sms_stop_words` VALUES
(2,'отказ'),
(3,'otkaz'),
(4,'отклонено'),
(5,'отклонена'),
(6,'заблокирован'),
(7,'заблокирована'),
(8,'Невозможно выполнить операцию');
/*!40000 ALTER TABLE `sms_stop_words` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegrams`
--

DROP TABLE IF EXISTS `telegrams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegrams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `telegram_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegrams`
--

LOCK TABLES `telegrams` WRITE;
/*!40000 ALTER TABLE `telegrams` DISABLE KEYS */;
/*!40000 ALTER TABLE `telegrams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telescope_entries`
--

DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `family_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telescope_entries`
--

LOCK TABLES `telescope_entries` WRITE;
/*!40000 ALTER TABLE `telescope_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `telescope_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telescope_entries_tags`
--

DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telescope_entries_tags`
--

LOCK TABLES `telescope_entries_tags` WRITE;
/*!40000 ALTER TABLE `telescope_entries_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `telescope_entries_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telescope_monitoring`
--

DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telescope_monitoring`
--

LOCK TABLES `telescope_monitoring` WRITE;
/*!40000 ALTER TABLE `telescope_monitoring` DISABLE KEYS */;
/*!40000 ALTER TABLE `telescope_monitoring` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trader_categories`
--

DROP TABLE IF EXISTS `trader_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trader_categories_slug_unique` (`slug`),
  KEY `trader_categories_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trader_categories`
--

LOCK TABLES `trader_categories` WRITE;
/*!40000 ALTER TABLE `trader_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `trader_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trader_team_leader_relations`
--

DROP TABLE IF EXISTS `trader_team_leader_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_team_leader_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trader_id` bigint unsigned NOT NULL,
  `team_leader_id` bigint unsigned NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trader_team_leader_relations_trader_id_team_leader_id_unique` (`trader_id`,`team_leader_id`),
  KEY `trader_team_leader_relations_team_leader_id_foreign` (`team_leader_id`),
  CONSTRAINT `trader_team_leader_relations_team_leader_id_foreign` FOREIGN KEY (`team_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trader_team_leader_relations_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trader_team_leader_relations`
--

LOCK TABLES `trader_team_leader_relations` WRITE;
/*!40000 ALTER TABLE `trader_team_leader_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `trader_team_leader_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wallet_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_devices`
--

DROP TABLE IF EXISTS `user_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название устройства',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Токен для доступа к API',
  `android_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Android ID устройства',
  `device_model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Модель устройства',
  `android_version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Версия Android',
  `manufacturer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Производитель устройства',
  `brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Бренд устройства',
  `connected_at` timestamp NULL DEFAULT NULL COMMENT 'Дата подключения устройства',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_devices_token_unique` (`token`),
  KEY `user_devices_user_id_foreign` (`user_id`),
  CONSTRAINT `user_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_devices`
--

LOCK TABLES `user_devices` WRITE;
/*!40000 ALTER TABLE `user_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_login_histories`
--

DROP TABLE IF EXISTS `user_login_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_login_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `ip_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_system` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_login_histories_user_id_foreign` (`user_id`),
  CONSTRAINT `user_login_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_histories`
--

LOCK TABLES `user_login_histories` WRITE;
/*!40000 ALTER TABLE `user_login_histories` DISABLE KEYS */;
INSERT INTO `user_login_histories` VALUES
(1,1,'<TEMPLATE-IP>','Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0','Компьютер','Firefox 128.0','Linux ','37.212.18.164',1,'2025-11-12 16:02:29','2025-11-12 16:02:29'),
(2,1,'<TEMPLATE-IP>','Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0','Компьютер','Firefox 128.0','Linux ','37.212.18.164',1,'2025-11-12 16:06:50','2025-11-12 16:06:50'),
(3,1,'<TEMPLATE-IP>','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','Компьютер','Chrome 141.0.0.0','Windows 13.0','<TEMPLATE-IP>',1,'2025-11-12 16:07:39','2025-11-12 16:07:39');
/*!40000 ALTER TABLE `user_login_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_metas`
--

DROP TABLE IF EXISTS `user_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_metas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `allowed_markets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `allowed_categories` json DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_metas`
--

LOCK TABLES `user_metas` WRITE;
/*!40000 ALTER TABLE `user_metas` DISABLE KEYS */;
INSERT INTO `user_metas` VALUES
(1,NULL,NULL,1),
(2,NULL,NULL,NULL),
(3,NULL,NULL,NULL);
/*!40000 ALTER TABLE `user_metas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_notes`
--

DROP TABLE IF EXISTS `user_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_notes_user_id_foreign` (`user_id`),
  KEY `user_notes_created_by_foreign` (`created_by`),
  CONSTRAINT `user_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `user_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_notes`
--

LOCK TABLES `user_notes` WRITE;
/*!40000 ALTER TABLE `user_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `trader_category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `avatar_uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_style` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apk_access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google2fa_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT '0',
  `is_payout_online` tinyint(1) NOT NULL DEFAULT '0',
  `is_vip` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'VIP статус пользователя',
  `referral_commission_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `trader_commission_rate` decimal(5,2) DEFAULT NULL COMMENT 'Индивидуальная комиссия трейдера (в процентах). Если null, используется комиссия из платежного шлюза.',
  `payouts_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `stop_traffic` tinyint(1) NOT NULL DEFAULT '0',
  `traffic_enabled_at` timestamp NULL DEFAULT NULL,
  `banned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `promo_code_id` bigint unsigned DEFAULT NULL,
  `promo_used_at` timestamp NULL DEFAULT NULL,
  `additional_team_leader_ids` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_apk_access_token_unique` (`apk_access_token`),
  UNIQUE KEY `users_api_access_token_unique` (`api_access_token`),
  KEY `idx_users_banned_at` (`banned_at`),
  KEY `idx_users_created_at` (`created_at`),
  KEY `idx_users_is_online` (`is_online`),
  KEY `users_promo_code_id_foreign` (`promo_code_id`),
  KEY `idx_users_stop_traffic` (`stop_traffic`),
  KEY `users_merchant_id_foreign` (`merchant_id`),
  KEY `users_trader_category_id_index` (`trader_category_id`),
  CONSTRAINT `users_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_promo_code_id_foreign` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_trader_category_id_foreign` FOREIGN KEY (`trader_category_id`) REFERENCES `trader_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,NULL,'Администратор','admin@mail.com',NULL,'admin@mail.com','adventurer','$2y$12$1vZ.WMtXzs05bwqZJmoVm.mrSebYr.YUlNZKTdpmcnD1U7vq','6mtv1xjhur0jihj7spzmpmqek0jfwmnt','kaqg0ce4e3bukzxufyaxim4vt8fyxy4k','i4QL4T61xzitNG1u0NWVGC7vVxoSOK1u8oM8TjglDBjGVk17vssd1g0gtPf8',NULL,1,0,1,0.00,NULL,1,0,NULL,NULL,'2025-07-29 20:28:56','2025-08-12 11:21:08',NULL,NULL,'[]'),
(2,NULL,NULL,'test-merchant','test-merchant@mail.com',NULL,'test-merchant@mail.com','adventurer','$2y$12$/PwKhEgNAXm4tBkKkFMMWeUq9htEgmoasqkuUYJ9C5VWtc1v1i','itvrdcpngzonj7rzatjo3gqawdxwc6sw','txskj3xvp9xb3czidsq3btyn4lcqoxba',NULL,NULL,0,0,0,0.00,NULL,0,0,'2025-11-12 15:55:30',NULL,'2025-11-12 15:55:30','2025-11-12 15:55:30',NULL,NULL,NULL),
(3,NULL,NULL,'test-trader','test-trader@mail.com',NULL,'test-trader@mail.com','adventurer','$2y$12$d2XsZpHQ7qEFLsuEA1sREeG40l/8tqlqZacllKk02fyx9mRZqvwqb','jgkfqfjbj3qyoat3zmv6ecpkqik6dbtc','kpoakvkfgmqivwosfgdckjkyy0llxlmi',NULL,NULL,0,0,0,0.00,NULL,0,0,'2025-11-12 15:55:52',NULL,'2025-11-12 15:55:52','2025-11-12 15:55:52',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trust_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reserve_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_reserve_amount` int NOT NULL DEFAULT '1000',
  `commission_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teamleader_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wallets_merchant_balance` (`merchant_balance`),
  KEY `idx_wallets_trust_balance` (`trust_balance`),
  KEY `idx_wallets_reserve_balance` (`reserve_balance`),
  KEY `idx_wallets_commission_balance` (`commission_balance`),
  KEY `idx_wallets_user_id` (`user_id`),
  KEY `idx_wallets_teamleader_balance` (`teamleader_balance`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES
(1,'0','0','0',1000,'0','0',NULL,1,'2025-07-29 20:28:56','2025-08-27 15:51:11'),
(2,'0','0','0',1000,'0','0',NULL,2,'2025-11-12 15:55:30','2025-11-12 15:55:30'),
(3,'0','0','0',1000,'0','0',NULL,3,'2025-11-12 15:55:52','2025-11-12 15:55:52');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-12 16:27:20
