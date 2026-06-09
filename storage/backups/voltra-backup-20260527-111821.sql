-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: voltra
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `voltra`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `voltra` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `voltra`;

--
-- Table structure for table `detail_pemeliharaan`
--

DROP TABLE IF EXISTS `detail_pemeliharaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_pemeliharaan` (
  `id_pemeliharaan` bigint(20) unsigned NOT NULL,
  `id_part` bigint(20) unsigned NOT NULL,
  `qty_digunakan` int(11) NOT NULL,
  `subtotal_harga_part` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id_pemeliharaan`,`id_part`),
  KEY `detail_pemeliharaan_id_part_foreign` (`id_part`),
  CONSTRAINT `detail_pemeliharaan_id_part_foreign` FOREIGN KEY (`id_part`) REFERENCES `suku_cadang` (`id_part`),
  CONSTRAINT `detail_pemeliharaan_id_pemeliharaan_foreign` FOREIGN KEY (`id_pemeliharaan`) REFERENCES `pemeliharaan` (`id_pemeliharaan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_pemeliharaan`
--

LOCK TABLES `detail_pemeliharaan` WRITE;
/*!40000 ALTER TABLE `detail_pemeliharaan` DISABLE KEYS */;
INSERT INTO `detail_pemeliharaan` VALUES (1,1,2,2500000.00),(1,2,2,850000.00),(1,3,2,770000.00),(2,1,1,1250000.00),(2,2,1,425000.00),(3,4,1,540000.00),(4,1,1,1250000.00),(4,6,2,570000.00);
/*!40000 ALTER TABLE `detail_pemeliharaan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_sewa`
--

DROP TABLE IF EXISTS `detail_sewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_sewa` (
  `id_sewa` bigint(20) unsigned NOT NULL,
  `id_genset` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `alamat_proyek` text DEFAULT NULL,
  `harga_sewa_unit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `biaya_operator` decimal(15,2) NOT NULL DEFAULT 0.00,
  `biaya_mobdemob` decimal(15,2) NOT NULL DEFAULT 0.00,
  `biaya_bbm` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_sewa`,`id_genset`),
  KEY `detail_sewa_id_genset_foreign` (`id_genset`),
  CONSTRAINT `detail_sewa_id_genset_foreign` FOREIGN KEY (`id_genset`) REFERENCES `genset` (`id_genset`),
  CONSTRAINT `detail_sewa_id_sewa_foreign` FOREIGN KEY (`id_sewa`) REFERENCES `transaksi_sewa` (`id_sewa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_sewa`
--

LOCK TABLES `detail_sewa` WRITE;
/*!40000 ALTER TABLE `detail_sewa` DISABLE KEYS */;
INSERT INTO `detail_sewa` VALUES (1001,1,'2026-04-05','2026-04-30','Proyek MRT Fase 2, Thamrin',2500000.00,350000.00,4500000.00,1200000.00),(1002,3,'2026-04-08','2026-05-08','Site Tenggarong Block-C',4800000.00,450000.00,12000000.00,3500000.00),(1003,4,'2026-04-22','2026-04-24','Event Wedding Kemang',1500000.00,300000.00,1500000.00,500000.00),(1004,6,'2026-04-15','2026-04-29','Ballroom Hotel Grand Melati',7500000.00,500000.00,6000000.00,2100000.00),(1005,2,'2026-03-20','2026-03-30','BSD Sky Tower',2500000.00,350000.00,4000000.00,900000.00),(1006,8,'2026-04-28','2026-05-12','Proyek MRT Fase 2, Dukuh Atas',2500000.00,350000.00,4500000.00,1000000.00),(1008,2,'2026-04-25','2026-05-05',NULL,2500000.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `detail_sewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genset`
--

DROP TABLE IF EXISTS `genset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `genset` (
  `id_genset` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_kategori` bigint(20) unsigned DEFAULT NULL,
  `id_merek` bigint(20) unsigned DEFAULT NULL,
  `id_supplier` bigint(20) unsigned DEFAULT NULL,
  `nomor_seri` varchar(100) NOT NULL,
  `tgl_perolehan` date NOT NULL,
  `harga_perolehan` decimal(15,2) NOT NULL,
  `nilai_residu_aktual` decimal(15,2) NOT NULL DEFAULT 0.00,
  `umur_ekonomis_aktual` int(11) NOT NULL DEFAULT 96,
  `status` enum('di_perusahaan','di_proyek','di_gudang','terjual','rusak') NOT NULL DEFAULT 'di_gudang',
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `lokasi_terkini` text DEFAULT NULL,
  PRIMARY KEY (`id_genset`),
  KEY `genset_id_perusahaan_foreign` (`id_perusahaan`),
  KEY `genset_id_kategori_foreign` (`id_kategori`),
  KEY `genset_id_merek_foreign` (`id_merek`),
  KEY `genset_id_supplier_foreign` (`id_supplier`),
  CONSTRAINT `genset_id_kategori_foreign` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_genset` (`id_kategori`) ON DELETE SET NULL,
  CONSTRAINT `genset_id_merek_foreign` FOREIGN KEY (`id_merek`) REFERENCES `merek` (`id_merek`) ON DELETE SET NULL,
  CONSTRAINT `genset_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE,
  CONSTRAINT `genset_id_supplier_foreign` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genset`
--

LOCK TABLES `genset` WRITE;
/*!40000 ALTER TABLE `genset` DISABLE KEYS */;
INSERT INTO `genset` VALUES (1,1,2,1,1,'CMN-250-0231','2023-02-14',480000000.00,60000000.00,96,'di_proyek','Genset diesel silenced canopy',NULL,'Proyek MRT Fase 2, Thamrin – Jakarta Pusat'),(2,1,2,1,1,'CMN-250-0232','2023-02-14',480000000.00,60000000.00,96,'di_gudang','Genset diesel silenced canopy',NULL,'Gudang utama – Cakung'),(3,1,3,2,2,'PKI-500-0117','2022-07-30',920000000.00,110000000.00,120,'di_proyek','Heavy duty open frame',NULL,'Site Tenggarong Block-C – Kaltim'),(4,1,1,2,2,'PKI-100-0058','2024-05-02',265000000.00,30000000.00,96,'di_gudang','Soundproof rental unit',NULL,'Gudang utama – Cakung'),(5,1,3,1,1,'CMN-500-0303','2021-11-11',880000000.00,100000000.00,120,'rusak','Overhaul jadwal Q2',NULL,'Workshop – Cakung'),(6,1,4,1,1,'CMN-1K-0012','2020-03-20',1850000000.00,220000000.00,120,'di_proyek','Containerized 1000kVA',NULL,'Ballroom Hotel Grand Melati – Sudirman'),(7,1,1,2,2,'PKI-100-0059','2024-05-02',265000000.00,30000000.00,96,'di_gudang','Soundproof rental unit',NULL,'Gudang utama – Cakung'),(8,1,2,2,2,'PKI-250-0140','2023-08-08',470000000.00,58000000.00,96,'di_gudang','Rental fleet 250kVA',NULL,'Gudang utama – Cakung');
/*!40000 ALTER TABLE `genset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jadwal_ketersediaan`
--

DROP TABLE IF EXISTS `jadwal_ketersediaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jadwal_ketersediaan` (
  `id_jadwal` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_genset` bigint(20) unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('tersedia','disewa','maintenance','tidak_tersedia') NOT NULL DEFAULT 'tersedia',
  PRIMARY KEY (`id_jadwal`),
  KEY `jadwal_ketersediaan_id_genset_tanggal_index` (`id_genset`,`tanggal`),
  CONSTRAINT `jadwal_ketersediaan_id_genset_foreign` FOREIGN KEY (`id_genset`) REFERENCES `genset` (`id_genset`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_ketersediaan`
--

LOCK TABLES `jadwal_ketersediaan` WRITE;
/*!40000 ALTER TABLE `jadwal_ketersediaan` DISABLE KEYS */;
INSERT INTO `jadwal_ketersediaan` VALUES (1,1,'2026-04-01','tersedia'),(2,1,'2026-04-02','tersedia'),(3,1,'2026-04-03','tersedia'),(4,1,'2026-04-04','tersedia'),(5,1,'2026-04-05','disewa'),(6,1,'2026-04-06','disewa'),(7,1,'2026-04-07','disewa'),(8,1,'2026-04-08','disewa'),(9,1,'2026-04-09','disewa'),(10,1,'2026-04-10','disewa'),(11,1,'2026-04-11','disewa'),(12,1,'2026-04-12','disewa'),(13,1,'2026-04-13','disewa'),(14,1,'2026-04-14','disewa'),(15,1,'2026-04-15','disewa'),(16,1,'2026-04-16','disewa'),(17,1,'2026-04-17','disewa'),(18,1,'2026-04-18','disewa'),(19,1,'2026-04-19','disewa'),(20,1,'2026-04-20','disewa'),(21,1,'2026-04-21','disewa'),(22,1,'2026-04-22','disewa'),(23,1,'2026-04-23','disewa'),(24,1,'2026-04-24','disewa'),(25,1,'2026-04-25','disewa'),(26,1,'2026-04-26','disewa'),(27,1,'2026-04-27','disewa'),(28,1,'2026-04-28','disewa'),(29,1,'2026-04-29','disewa'),(30,1,'2026-04-30','disewa'),(31,2,'2026-04-01','maintenance'),(32,2,'2026-04-02','maintenance'),(33,2,'2026-04-03','tersedia'),(34,2,'2026-04-04','tersedia'),(35,2,'2026-04-05','tersedia'),(36,2,'2026-04-06','tersedia'),(37,2,'2026-04-07','tersedia'),(38,2,'2026-04-08','tersedia'),(39,2,'2026-04-09','tersedia'),(40,2,'2026-04-10','tersedia'),(41,2,'2026-04-11','tersedia'),(42,2,'2026-04-12','tersedia'),(43,2,'2026-04-13','tersedia'),(44,2,'2026-04-14','tersedia'),(45,2,'2026-04-15','tersedia'),(46,2,'2026-04-16','tersedia'),(47,2,'2026-04-17','tersedia'),(48,2,'2026-04-18','tersedia'),(49,2,'2026-04-19','tersedia'),(50,2,'2026-04-20','tersedia'),(51,2,'2026-04-21','tersedia'),(52,2,'2026-04-22','tersedia'),(53,2,'2026-04-23','tersedia'),(54,2,'2026-04-24','tersedia'),(55,2,'2026-04-25','disewa'),(56,2,'2026-04-26','disewa'),(57,2,'2026-04-27','disewa'),(58,2,'2026-04-28','disewa'),(59,2,'2026-04-29','disewa'),(60,2,'2026-04-30','disewa'),(61,3,'2026-04-01','tersedia'),(62,3,'2026-04-02','tersedia'),(63,3,'2026-04-03','tersedia'),(64,3,'2026-04-04','tersedia'),(65,3,'2026-04-05','tersedia'),(66,3,'2026-04-06','tersedia'),(67,3,'2026-04-07','tersedia'),(68,3,'2026-04-08','disewa'),(69,3,'2026-04-09','disewa'),(70,3,'2026-04-10','disewa'),(71,3,'2026-04-11','disewa'),(72,3,'2026-04-12','disewa'),(73,3,'2026-04-13','disewa'),(74,3,'2026-04-14','disewa'),(75,3,'2026-04-15','disewa'),(76,3,'2026-04-16','disewa'),(77,3,'2026-04-17','disewa'),(78,3,'2026-04-18','disewa'),(79,3,'2026-04-19','disewa'),(80,3,'2026-04-20','disewa'),(81,3,'2026-04-21','disewa'),(82,3,'2026-04-22','disewa'),(83,3,'2026-04-23','disewa'),(84,3,'2026-04-24','disewa'),(85,3,'2026-04-25','disewa'),(86,3,'2026-04-26','disewa'),(87,3,'2026-04-27','disewa'),(88,3,'2026-04-28','disewa'),(89,3,'2026-04-29','disewa'),(90,3,'2026-04-30','disewa'),(91,4,'2026-04-01','tersedia'),(92,4,'2026-04-02','tersedia'),(93,4,'2026-04-03','tersedia'),(94,4,'2026-04-04','tersedia'),(95,4,'2026-04-05','tersedia'),(96,4,'2026-04-06','tersedia'),(97,4,'2026-04-07','tersedia'),(98,4,'2026-04-08','tersedia'),(99,4,'2026-04-09','tersedia'),(100,4,'2026-04-10','tersedia'),(101,4,'2026-04-11','tersedia'),(102,4,'2026-04-12','tersedia'),(103,4,'2026-04-13','tersedia'),(104,4,'2026-04-14','tersedia'),(105,4,'2026-04-15','tersedia'),(106,4,'2026-04-16','tersedia'),(107,4,'2026-04-17','tersedia'),(108,4,'2026-04-18','tersedia'),(109,4,'2026-04-19','tersedia'),(110,4,'2026-04-20','tersedia'),(111,4,'2026-04-21','tersedia'),(112,4,'2026-04-22','disewa'),(113,4,'2026-04-23','disewa'),(114,4,'2026-04-24','disewa'),(115,4,'2026-04-25','tersedia'),(116,4,'2026-04-26','tersedia'),(117,4,'2026-04-27','tersedia'),(118,4,'2026-04-28','tersedia'),(119,4,'2026-04-29','tersedia'),(120,4,'2026-04-30','tersedia'),(121,5,'2026-04-01','tidak_tersedia'),(122,5,'2026-04-02','tidak_tersedia'),(123,5,'2026-04-03','tidak_tersedia'),(124,5,'2026-04-04','tidak_tersedia'),(125,5,'2026-04-05','tidak_tersedia'),(126,5,'2026-04-06','tidak_tersedia'),(127,5,'2026-04-07','tidak_tersedia'),(128,5,'2026-04-08','tidak_tersedia'),(129,5,'2026-04-09','tidak_tersedia'),(130,5,'2026-04-10','maintenance'),(131,5,'2026-04-11','maintenance'),(132,5,'2026-04-12','maintenance'),(133,5,'2026-04-13','maintenance'),(134,5,'2026-04-14','maintenance'),(135,5,'2026-04-15','maintenance'),(136,5,'2026-04-16','maintenance'),(137,5,'2026-04-17','maintenance'),(138,5,'2026-04-18','maintenance'),(139,5,'2026-04-19','maintenance'),(140,5,'2026-04-20','maintenance'),(141,5,'2026-04-21','maintenance'),(142,5,'2026-04-22','maintenance'),(143,5,'2026-04-23','maintenance'),(144,5,'2026-04-24','maintenance'),(145,5,'2026-04-25','maintenance'),(146,5,'2026-04-26','maintenance'),(147,5,'2026-04-27','maintenance'),(148,5,'2026-04-28','maintenance'),(149,5,'2026-04-29','maintenance'),(150,5,'2026-04-30','maintenance'),(151,6,'2026-04-01','tersedia'),(152,6,'2026-04-02','tersedia'),(153,6,'2026-04-03','tersedia'),(154,6,'2026-04-04','tersedia'),(155,6,'2026-04-05','tersedia'),(156,6,'2026-04-06','tersedia'),(157,6,'2026-04-07','tersedia'),(158,6,'2026-04-08','tersedia'),(159,6,'2026-04-09','tersedia'),(160,6,'2026-04-10','tersedia'),(161,6,'2026-04-11','tersedia'),(162,6,'2026-04-12','tersedia'),(163,6,'2026-04-13','tersedia'),(164,6,'2026-04-14','tersedia'),(165,6,'2026-04-15','disewa'),(166,6,'2026-04-16','disewa'),(167,6,'2026-04-17','disewa'),(168,6,'2026-04-18','disewa'),(169,6,'2026-04-19','disewa'),(170,6,'2026-04-20','disewa'),(171,6,'2026-04-21','disewa'),(172,6,'2026-04-22','disewa'),(173,6,'2026-04-23','disewa'),(174,6,'2026-04-24','disewa'),(175,6,'2026-04-25','disewa'),(176,6,'2026-04-26','disewa'),(177,6,'2026-04-27','disewa'),(178,6,'2026-04-28','disewa'),(179,6,'2026-04-29','disewa'),(180,6,'2026-04-30','tersedia'),(181,7,'2026-04-01','tersedia'),(182,7,'2026-04-02','tersedia'),(183,7,'2026-04-03','tersedia'),(184,7,'2026-04-04','tersedia'),(185,7,'2026-04-05','tersedia'),(186,7,'2026-04-06','tersedia'),(187,7,'2026-04-07','tersedia'),(188,7,'2026-04-08','tersedia'),(189,7,'2026-04-09','tersedia'),(190,7,'2026-04-10','tersedia'),(191,7,'2026-04-11','tersedia'),(192,7,'2026-04-12','tersedia'),(193,7,'2026-04-13','tersedia'),(194,7,'2026-04-14','tersedia'),(195,7,'2026-04-15','tersedia'),(196,7,'2026-04-16','tersedia'),(197,7,'2026-04-17','tersedia'),(198,7,'2026-04-18','tersedia'),(199,7,'2026-04-19','tersedia'),(200,7,'2026-04-20','maintenance'),(201,7,'2026-04-21','maintenance'),(202,7,'2026-04-22','maintenance'),(203,7,'2026-04-23','maintenance'),(204,7,'2026-04-24','maintenance'),(205,7,'2026-04-25','maintenance'),(206,7,'2026-04-26','maintenance'),(207,7,'2026-04-27','maintenance'),(208,7,'2026-04-28','maintenance'),(209,7,'2026-04-29','maintenance'),(210,7,'2026-04-30','maintenance'),(211,8,'2026-04-01','tersedia'),(212,8,'2026-04-02','tersedia'),(213,8,'2026-04-03','tersedia'),(214,8,'2026-04-04','tersedia'),(215,8,'2026-04-05','tersedia'),(216,8,'2026-04-06','tersedia'),(217,8,'2026-04-07','tersedia'),(218,8,'2026-04-08','tersedia'),(219,8,'2026-04-09','tersedia'),(220,8,'2026-04-10','tersedia'),(221,8,'2026-04-11','tersedia'),(222,8,'2026-04-12','tersedia'),(223,8,'2026-04-13','tersedia'),(224,8,'2026-04-14','tersedia'),(225,8,'2026-04-15','tersedia'),(226,8,'2026-04-16','tersedia'),(227,8,'2026-04-17','tersedia'),(228,8,'2026-04-18','tersedia'),(229,8,'2026-04-19','tersedia'),(230,8,'2026-04-20','tersedia'),(231,8,'2026-04-21','tersedia'),(232,8,'2026-04-22','tersedia'),(233,8,'2026-04-23','tersedia'),(234,8,'2026-04-24','tersedia'),(235,8,'2026-04-25','tersedia'),(236,8,'2026-04-26','tersedia'),(237,8,'2026-04-27','tersedia'),(238,8,'2026-04-28','disewa'),(239,8,'2026-04-29','disewa'),(240,8,'2026-04-30','disewa'),(241,2,'2026-05-01','disewa'),(242,2,'2026-05-02','disewa'),(243,2,'2026-05-03','disewa'),(244,2,'2026-05-04','disewa'),(245,2,'2026-05-05','disewa');
/*!40000 ALTER TABLE `jadwal_ketersediaan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_genset`
--

DROP TABLE IF EXISTS `kategori_genset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori_genset` (
  `id_kategori` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `kapasitas` varchar(50) NOT NULL,
  `umur_ekonomis_default` int(11) NOT NULL,
  `estimasi_nilai_residu` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_kategori`),
  KEY `kategori_genset_id_perusahaan_foreign` (`id_perusahaan`),
  CONSTRAINT `kategori_genset_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_genset`
--

LOCK TABLES `kategori_genset` WRITE;
/*!40000 ALTER TABLE `kategori_genset` DISABLE KEYS */;
INSERT INTO `kategori_genset` VALUES (1,1,'100 kVA',96,30000000.00),(2,1,'250 kVA',96,60000000.00),(3,1,'500 kVA',120,110000000.00),(4,1,'1000 kVA',120,220000000.00);
/*!40000 ALTER TABLE `kategori_genset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merek`
--

DROP TABLE IF EXISTS `merek`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merek` (
  `id_merek` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_merek` varchar(100) NOT NULL,
  `negara_asal` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id_merek`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merek`
--

LOCK TABLES `merek` WRITE;
/*!40000 ALTER TABLE `merek` DISABLE KEYS */;
INSERT INTO `merek` VALUES (1,'Cummins','USA','Heavy-duty diesel genset'),(2,'Perkins','UK','Industrial & rental grade'),(3,'Caterpillar','USA','High capacity diesel'),(4,'Mitsubishi','Japan','Compact unit'),(5,'Volvo Penta','Sweden','Marine & industrial');
/*!40000 ALTER TABLE `merek` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_05_21_000001_create_voltra_operational_schema',1),(2,'2026_05_21_000002_create_voltra_akuntansi_schema',1),(3,'2026_05_21_114005_create_personal_access_tokens_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pelanggan`
--

DROP TABLE IF EXISTS `pelanggan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pelanggan` (
  `id_pelanggan` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `nama_perusahaan` varchar(150) NOT NULL,
  `pic_kontak` varchar(100) DEFAULT NULL,
  `alamat_lengkap` text DEFAULT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_pelanggan`),
  KEY `pelanggan_id_perusahaan_foreign` (`id_perusahaan`),
  CONSTRAINT `pelanggan_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pelanggan`
--

LOCK TABLES `pelanggan` WRITE;
/*!40000 ALTER TABLE `pelanggan` DISABLE KEYS */;
INSERT INTO `pelanggan` VALUES (1,1,'PT Adhi Konstruksi','Pak Hartono','Jl. MH Thamrin No.5, Jakpus','01.234.567.8-001.000','021-5550101','proc@adhi.co.id'),(2,1,'CV Mitra Event','Ibu Lestari','Jl. Kemang Raya No.22, Jaksel','02.345.678.9-002.000','021-5550202','sales@mitraevent.id'),(3,1,'PT Borneo Mining','Bpk. Rizki','Site Tenggarong, Kaltim','03.456.789.0-003.000','0541-555030','logistik@borneomining.com'),(4,1,'PT Sahabat Properti','Ibu Yanti','BSD City, Tangerang','04.567.890.1-004.000','021-5550404','pm@sahabatproperti.com'),(5,1,'Hotel Grand Melati','Mgr. Operasi','Jl. Sudirman No.88, Jakpus','05.678.901.2-005.000','021-5550505','gm@grandmelati.com');
/*!40000 ALTER TABLE `pelanggan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembayaran`
--

DROP TABLE IF EXISTS `pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pembayaran` (
  `id_pembayaran` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_sewa` bigint(20) unsigned NOT NULL,
  `no_kuitansi` varchar(50) DEFAULT NULL,
  `tgl_bayar` date NOT NULL,
  `nominal_bayar` decimal(15,2) NOT NULL,
  `metode_bayar` enum('transfer','tunai','giro','kartu_kredit') NOT NULL DEFAULT 'transfer',
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran`),
  KEY `pembayaran_id_perusahaan_foreign` (`id_perusahaan`),
  KEY `pembayaran_id_sewa_foreign` (`id_sewa`),
  CONSTRAINT `pembayaran_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_id_sewa_foreign` FOREIGN KEY (`id_sewa`) REFERENCES `transaksi_sewa` (`id_sewa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembayaran`
--

LOCK TABLES `pembayaran` WRITE;
/*!40000 ALTER TABLE `pembayaran` DISABLE KEYS */;
INSERT INTO `pembayaran` VALUES (1,1,1001,'KWT-26040-001','2026-04-12',83250000.00,'transfer','Pembayaran penuh'),(2,1,1002,'KWT-26040-002','2026-04-15',100000000.00,'giro','DP 50%'),(3,1,1004,'KWT-26040-003','2026-04-18',126096000.00,'transfer','Pembayaran penuh'),(4,1,1005,'KWT-26030-010','2026-04-01',33466500.00,'giro','Pelunasan'),(5,1,1002,'KWT-260521-002','2026-05-21',109235000.00,'transfer',NULL),(6,1,1003,'KWT-260521-001','2026-05-21',6438000.00,'transfer',NULL);
/*!40000 ALTER TABLE `pembayaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pemeliharaan`
--

DROP TABLE IF EXISTS `pemeliharaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pemeliharaan` (
  `id_pemeliharaan` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_genset` bigint(20) unsigned NOT NULL,
  `id_pengguna` bigint(20) unsigned DEFAULT NULL,
  `tgl_mulai_servis` date NOT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `jenis_servis` enum('rutin','perbaikan','overhaul') NOT NULL,
  `biaya_jasa_eksternal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id_pemeliharaan`),
  KEY `pemeliharaan_id_perusahaan_foreign` (`id_perusahaan`),
  KEY `pemeliharaan_id_genset_foreign` (`id_genset`),
  KEY `pemeliharaan_id_pengguna_foreign` (`id_pengguna`),
  CONSTRAINT `pemeliharaan_id_genset_foreign` FOREIGN KEY (`id_genset`) REFERENCES `genset` (`id_genset`),
  CONSTRAINT `pemeliharaan_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL,
  CONSTRAINT `pemeliharaan_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemeliharaan`
--

LOCK TABLES `pemeliharaan` WRITE;
/*!40000 ALTER TABLE `pemeliharaan` DISABLE KEYS */;
INSERT INTO `pemeliharaan` VALUES (1,1,5,2,'2026-04-10',NULL,'overhaul',12500000.00,'Turun mesin – piston ring'),(2,1,2,2,'2026-04-01','2026-04-02','rutin',0.00,'Ganti oli & filter berkala'),(3,1,4,4,'2026-03-28','2026-03-29','rutin',0.00,'Servis 250 jam'),(4,1,7,4,'2026-04-20',NULL,'perbaikan',750000.00,'Perbaikan starter motor');
/*!40000 ALTER TABLE `pemeliharaan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengembalian`
--

DROP TABLE IF EXISTS `pengembalian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengembalian` (
  `id_pengembalian` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_sewa` bigint(20) unsigned NOT NULL,
  `id_genset` bigint(20) unsigned NOT NULL,
  `jenis_aktivitas` enum('pengambilan','pengembalian') NOT NULL,
  `tanggal` datetime NOT NULL,
  `pic_dari_pelanggan` varchar(100) DEFAULT NULL,
  `pic_dari_rental` varchar(100) DEFAULT NULL,
  `kondisi_genset` text DEFAULT NULL,
  `foto_kondisi` varchar(255) DEFAULT NULL,
  `dicatat_oleh` bigint(20) unsigned DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id_pengembalian`),
  KEY `pengembalian_id_sewa_id_genset_index` (`id_sewa`,`id_genset`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengembalian`
--

LOCK TABLES `pengembalian` WRITE;
/*!40000 ALTER TABLE `pengembalian` DISABLE KEYS */;
INSERT INTO `pengembalian` VALUES (1,1001,1,'pengambilan','2026-04-05 08:30:00','Bpk. Hartono','Faisal Rahman','Unit prima, semua indikator normal, BBM penuh.','foto_h001.jpg',6,'Pemasangan di lantai dasar gedung MRT'),(2,1002,3,'pengambilan','2026-04-08 06:00:00','Bpk. Rizki','Bima Setiawan','Cat sedikit baret samping kiri, fungsi normal.','foto_h002.jpg',1,'Kirim via truk angkutan, ETA 2 hari'),(3,1004,6,'pengambilan','2026-04-15 14:00:00','Mgr. Operasi','Faisal Rahman','Unit baru servis rutin, kondisi excellent.','foto_h003.jpg',6,''),(4,1005,2,'pengambilan','2026-03-20 07:00:00','Ibu Yanti','Faisal Rahman','Kondisi baik.','foto_h004.jpg',1,''),(5,1005,2,'pengembalian','2026-03-30 16:30:00','Ibu Yanti','Bima Setiawan','Body baret minor, oli perlu diganti, BBM 1/4 tangki.','foto_h005.jpg',2,'Perlu servis rutin sebelum unit re-deploy');
/*!40000 ALTER TABLE `pengembalian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengguna`
--

DROP TABLE IF EXISTS `pengguna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengguna` (
  `id_pengguna` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','teknisi','akuntan','owner') NOT NULL,
  `avatar` varchar(5) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `pengguna_email_id_perusahaan_unique` (`email`,`id_perusahaan`),
  KEY `pengguna_id_perusahaan_foreign` (`id_perusahaan`),
  CONSTRAINT `pengguna_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengguna`
--

LOCK TABLES `pengguna` WRITE;
/*!40000 ALTER TABLE `pengguna` DISABLE KEYS */;
INSERT INTO `pengguna` VALUES (1,1,'Andi Pratama','andi@voltra.id','$2y$12$ziWwFc.YJ8IdQ7sbsrh6getSxaS.M05z2TurQbdCLN9zRA.2bO1qK','admin','AP',NULL),(2,1,'Bima Setiawan','bima@voltra.id','$2y$12$snvZ8SZJNwrRHLZn5BvuuevjoUVhcKBY52qva4C2o0E8wPNOmtxTO','teknisi','BS',NULL),(3,1,'Citra Wulandari','citra@voltra.id','$2y$12$tZAtLAP7CUyXYJ/HTrN5uutXzn9uwyp9s3rMweyjI23/HKcAhshDO','owner','CW',NULL),(4,1,'Dedi Kurniawan','dedi@voltra.id','$2y$12$KGmdsdovDv7X1Wj6QW98demZOdKASy67fjgEEpjkexGRcbm2OArFC','teknisi','DK',NULL),(5,1,'Eka Sari','eka@voltra.id','$2y$12$Xa5ZJq/h9gVa/Tg91.wiQe/PtwLMa7HptKuj72J7/woDCn6.S7PGK','akuntan','ES',NULL),(6,1,'Faisal Rahman','faisal@voltra.id','$2y$12$MIfACrGytwgSB536TEWfCOm4zf.Owmn9M225srjFNqx/fXw.lXnBi','operator','FR',NULL),(7,2,'yeda','ekagaul91@gmail.com','$2y$12$ARkaSZaAYUcLHQnVF3sEquJcyOx0USayVyx7O.oi/mnITX.hVyAwq','operator','YE',NULL);
/*!40000 ALTER TABLE `pengguna` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penjualan_genset`
--

DROP TABLE IF EXISTS `penjualan_genset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `penjualan_genset` (
  `id_penjualan` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_genset` bigint(20) unsigned NOT NULL,
  `id_pengguna` bigint(20) unsigned DEFAULT NULL,
  `tgl_jual` date NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `nilai_buku_saat_jual` decimal(15,2) NOT NULL,
  `gain_loss` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id_penjualan`),
  KEY `penjualan_genset_id_perusahaan_foreign` (`id_perusahaan`),
  CONSTRAINT `penjualan_genset_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penjualan_genset`
--

LOCK TABLES `penjualan_genset` WRITE;
/*!40000 ALTER TABLE `penjualan_genset` DISABLE KEYS */;
INSERT INTO `penjualan_genset` VALUES (1,1,99,1,'2026-02-20',250000000.00,180000000.00,70000000.00,'Unit lama CMN-250-0077, laba penjualan');
/*!40000 ALTER TABLE `penjualan_genset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\Pengguna',1,'voltra-api','c57508614db20024353c04950902430ae3cd8c99f912b6981c3f57f6c96d5d8b','[\"*\"]','2026-05-21 04:58:59',NULL,'2026-05-21 04:58:59','2026-05-21 04:58:59'),(2,'App\\Models\\Pengguna',1,'voltra-api','510cbf03b341bf2a00bd38dd0754487b6d377e28e4b2d3a9dbeeb6d68a6c8ad5','[\"*\"]','2026-05-21 04:59:21',NULL,'2026-05-21 04:59:20','2026-05-21 04:59:21');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perusahaan`
--

DROP TABLE IF EXISTS `perusahaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perusahaan` (
  `id_perusahaan` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_perusahaan` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `tgl_bergabung` date DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_perusahaan`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perusahaan`
--

LOCK TABLES `perusahaan` WRITE;
/*!40000 ALTER TABLE `perusahaan` DISABLE KEYS */;
INSERT INTO `perusahaan` VALUES (1,'PT Sinar Daya Nusantara','SDN','Jl. Industri Raya No.12, Jakarta','021-5551200','admin@sdn.co.id','01.234.567.8-001.000','2025-01-15',1),(2,'CV Multi Genset Borneo','MGB','Balikpapan, Kaltim','0542-555088','ops@mgb.id','02.345.678.9-002.000','2025-06-10',1);
/*!40000 ALTER TABLE `perusahaan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suku_cadang`
--

DROP TABLE IF EXISTS `suku_cadang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suku_cadang` (
  `id_part` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `nama_part` varchar(150) NOT NULL,
  `kode_sku` varchar(50) NOT NULL,
  `stok_tersedia` int(11) NOT NULL DEFAULT 0,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_part`),
  UNIQUE KEY `suku_cadang_kode_sku_id_perusahaan_unique` (`kode_sku`,`id_perusahaan`),
  KEY `suku_cadang_id_perusahaan_foreign` (`id_perusahaan`),
  CONSTRAINT `suku_cadang_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suku_cadang`
--

LOCK TABLES `suku_cadang` WRITE;
/*!40000 ALTER TABLE `suku_cadang` DISABLE KEYS */;
INSERT INTO `suku_cadang` VALUES (1,1,'Oli Mesin SAE 15W-40 (20L)','OLI-1540-20',42,1250000.00),(2,1,'Filter Oli Cummins','FLT-OIL-CMN',18,425000.00),(3,1,'Filter Bahan Bakar','FLT-FUEL-01',8,385000.00),(4,1,'Filter Udara','FLT-AIR-02',24,540000.00),(5,1,'Aki Kering 100Ah','AKI-100AH',3,1850000.00),(6,1,'Radiator Coolant (5L)','COL-RAD-5',15,285000.00),(7,1,'V-Belt Alternator','BLT-ALT-11',11,345000.00),(8,1,'Busi / Glow Plug','PLG-GLW-03',2,225000.00);
/*!40000 ALTER TABLE `suku_cadang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier` (
  `id_supplier` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `nama_supplier` varchar(150) NOT NULL,
  `pic_kontak` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  PRIMARY KEY (`id_supplier`),
  KEY `supplier_id_perusahaan_foreign` (`id_perusahaan`),
  CONSTRAINT `supplier_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier`
--

LOCK TABLES `supplier` WRITE;
/*!40000 ALTER TABLE `supplier` DISABLE KEYS */;
INSERT INTO `supplier` VALUES (1,1,'PT Cummins Sales IDN','Arif','021-8889001','sales@cummins.id','Cikarang Barat'),(2,1,'PT Perkasa Diesel','Hendra','021-8889002','info@perkasa.id','Bekasi Utara'),(3,1,'CV Sparepart Jaya','Wati','021-8889003','order@spjjaya.id','Kelapa Gading');
/*!40000 ALTER TABLE `supplier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi_sewa`
--

DROP TABLE IF EXISTS `transaksi_sewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaksi_sewa` (
  `id_sewa` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_pelanggan` bigint(20) unsigned NOT NULL,
  `id_pengguna` bigint(20) unsigned NOT NULL,
  `no_referensi_kontrak` varchar(50) DEFAULT NULL,
  `no_invoice` varchar(50) DEFAULT NULL,
  `tgl_pemesanan` date NOT NULL,
  `tgl_terbit_invoice` date DEFAULT NULL,
  `tgl_jatuh_tempo` date DEFAULT NULL,
  `total_tagihan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pajak` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status_pesanan` enum('pesan','deal','dibatalkan','selesai') NOT NULL DEFAULT 'pesan',
  `status_pembayaran` enum('belum_bayar','dp','lunas','overdue') NOT NULL DEFAULT 'belum_bayar',
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id_sewa`),
  KEY `transaksi_sewa_id_perusahaan_foreign` (`id_perusahaan`),
  KEY `transaksi_sewa_id_pelanggan_foreign` (`id_pelanggan`),
  KEY `transaksi_sewa_id_pengguna_foreign` (`id_pengguna`),
  CONSTRAINT `transaksi_sewa_id_pelanggan_foreign` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`),
  CONSTRAINT `transaksi_sewa_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `transaksi_sewa_id_perusahaan_foreign` FOREIGN KEY (`id_perusahaan`) REFERENCES `perusahaan` (`id_perusahaan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi_sewa`
--

LOCK TABLES `transaksi_sewa` WRITE;
/*!40000 ALTER TABLE `transaksi_sewa` DISABLE KEYS */;
INSERT INTO `transaksi_sewa` VALUES (1001,1,1,1,'KTR-2026-041','INV/2026/04/001','2026-04-01','2026-04-05','2026-04-20',75000000.00,8250000.00,'deal','lunas','Sewa periode April'),(1002,1,3,1,'KTR-2026-042','INV/2026/04/002','2026-04-03','2026-04-08','2026-04-23',188500000.00,20735000.00,'deal','lunas','DP 50% via giro'),(1003,1,2,1,'KTR-2026-043','INV/2026/04/003','2026-04-10','2026-04-22','2026-05-07',5800000.00,638000.00,'pesan','lunas','Event wedding 3 hari'),(1004,1,5,1,'KTR-2026-044','INV/2026/04/004','2026-04-12','2026-04-15','2026-04-30',113600000.00,12496000.00,'deal','lunas','Sewa ballroom'),(1005,1,4,1,'KTR-2026-040','INV/2026/03/012','2026-03-18','2026-03-20','2026-04-04',30150000.00,3316500.00,'selesai','lunas','BSD Sky Tower'),(1006,1,1,1,'KTR-2026-045','INV/2026/04/005','2026-04-18','2026-04-18','2026-05-03',41000000.00,4510000.00,'pesan','belum_bayar','Lanjutan proyek MRT'),(1007,1,2,1,'KTR-2026-039','INV/2026/03/008','2026-03-01','2026-03-02','2026-03-17',8200000.00,902000.00,'dibatalkan','belum_bayar','Dibatalkan karena unit double booking'),(1008,1,2,1,'KTR-2026-008','INV/2026/05/008','2026-05-27','2026-05-27','2026-06-11',25000000.00,2750000.00,'deal','belum_bayar',NULL);
/*!40000 ALTER TABLE `transaksi_sewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `voltra_akuntansi`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `voltra_akuntansi` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `voltra_akuntansi`;

--
-- Table structure for table `akun_perkiraan`
--

DROP TABLE IF EXISTS `akun_perkiraan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `akun_perkiraan` (
  `kode_akun` varchar(20) NOT NULL,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `nama_akun` varchar(150) NOT NULL,
  `kategori_akun` enum('aset','kewajiban','ekuitas','pendapatan','beban') NOT NULL,
  `sub_kategori` varchar(100) DEFAULT NULL,
  `saldo_normal` enum('debit','kredit') NOT NULL,
  `kode_parent` varchar(20) DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`kode_akun`,`id_perusahaan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `akun_perkiraan`
--

LOCK TABLES `akun_perkiraan` WRITE;
/*!40000 ALTER TABLE `akun_perkiraan` DISABLE KEYS */;
INSERT INTO `akun_perkiraan` VALUES ('1',1,'ASET','aset','header','debit',NULL,1),('1-1',1,'Aset Lancar','aset','header','debit','1',1),('1-1001',1,'Kas & Bank','aset','lancar','debit','1-1',1),('1-1101',1,'Piutang Usaha','aset','lancar','debit','1-1',1),('1-1301',1,'Persediaan Suku Cadang','aset','lancar','debit','1-1',1),('1-2',1,'Aset Tetap','aset','header','debit','1',1),('1-2001',1,'Aset Tetap - Genset','aset','tetap','debit','1-2',1),('1-2002',1,'Akumulasi Penyusutan','aset','kontra','kredit','1-2',1),('2',1,'KEWAJIBAN','kewajiban','header','kredit',NULL,1),('2-1001',1,'Utang Usaha','kewajiban','jangka_pendek','kredit','2',1),('2-2001',1,'PPN Keluaran','kewajiban','pajak','kredit','2',1),('3',1,'EKUITAS','ekuitas','header','kredit',NULL,1),('3-1001',1,'Modal Disetor','ekuitas','modal','kredit','3',1),('4',1,'PENDAPATAN','pendapatan','header','kredit',NULL,1),('4-1001',1,'Pendapatan Sewa Genset','pendapatan','operasional','kredit','4',1),('4-1002',1,'Pendapatan Operator & BBM','pendapatan','operasional','kredit','4',1),('5',1,'BEBAN','beban','header','debit',NULL,1),('5-1001',1,'Beban Penyusutan','beban','non_kas','debit','5',1),('5-2001',1,'Beban Servis & Pemeliharaan','beban','operasional','debit','5',1),('5-3001',1,'Beban BBM & Operasional','beban','operasional','debit','5',1),('5-3002',1,'Beban Transport & Mobilisasi','beban','operasional','debit','5',1),('7-1001',1,'Laba/Rugi Pelepasan Aset','pendapatan','non_operasional','kredit','4',1);
/*!40000 ALTER TABLE `akun_perkiraan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_jurnal`
--

DROP TABLE IF EXISTS `detail_jurnal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_jurnal` (
  `id_detail_jurnal` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_jurnal` bigint(20) unsigned NOT NULL,
  `kode_akun` varchar(20) NOT NULL,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kredit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detail_jurnal`),
  KEY `detail_jurnal_id_jurnal_foreign` (`id_jurnal`),
  KEY `detail_jurnal_kode_akun_id_perusahaan_foreign` (`kode_akun`,`id_perusahaan`),
  CONSTRAINT `detail_jurnal_id_jurnal_foreign` FOREIGN KEY (`id_jurnal`) REFERENCES `jurnal_akuntansi` (`id_jurnal`) ON DELETE CASCADE,
  CONSTRAINT `detail_jurnal_kode_akun_id_perusahaan_foreign` FOREIGN KEY (`kode_akun`, `id_perusahaan`) REFERENCES `akun_perkiraan` (`kode_akun`, `id_perusahaan`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_jurnal`
--

LOCK TABLES `detail_jurnal` WRITE;
/*!40000 ALTER TABLE `detail_jurnal` DISABLE KEYS */;
INSERT INTO `detail_jurnal` VALUES (1,501,'1-1101',1,83250000.00,0.00,'Piutang INV/2026/04/001',1),(2,501,'4-1001',1,0.00,62500000.00,'Pendapatan sewa unit',2),(3,501,'4-1002',1,0.00,12500000.00,'Operator + mobdemob + BBM',3),(4,501,'2-2001',1,0.00,8250000.00,'PPN 11% keluaran',4),(5,502,'1-1001',1,83250000.00,0.00,'Penerimaan transfer BCA',1),(6,502,'1-1101',1,0.00,83250000.00,'Pelunasan piutang',2),(7,503,'5-2001',1,1675000.00,0.00,'Pemakaian suku cadang',1),(8,503,'1-1301',1,0.00,1675000.00,'Pengurangan persediaan',2),(9,504,'5-1001',1,32450000.00,0.00,'Beban penyusutan April',1),(10,504,'1-2002',1,0.00,32450000.00,'Akumulasi penyusutan',2),(11,505,'1-1101',1,209235000.00,0.00,'Piutang INV/2026/04/002',1),(12,505,'4-1001',1,0.00,144000000.00,'Pendapatan sewa unit',2),(13,505,'4-1002',1,0.00,44500000.00,'Operator + mobdemob + BBM',3),(14,505,'2-2001',1,0.00,20735000.00,'PPN 11% keluaran',4),(15,506,'5-3001',1,4200000.00,0.00,'BBM tambahan',1),(16,506,'1-1001',1,0.00,4200000.00,'Pengeluaran kas',2),(17,507,'1-1001',1,109235000.00,0.00,'Penerimaan KWT-260521-002',1),(18,507,'1-1101',1,0.00,109235000.00,'Pelunasan piutang',2),(19,508,'1-1001',1,6438000.00,0.00,'Penerimaan KWT-260521-001',1),(20,508,'1-1101',1,0.00,6438000.00,'Pelunasan piutang',2),(21,509,'1-1101',1,27750000.00,0.00,'Piutang INV/2026/05/008',1),(22,509,'4-1001',1,0.00,25000000.00,'Pendapatan sewa',2),(23,509,'4-1002',1,0.00,0.00,'Pendapatan operator & BBM',3),(24,509,'2-2001',1,0.00,2750000.00,'PPN 11% keluaran',4);
/*!40000 ALTER TABLE `detail_jurnal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jadwal_penyusutan`
--

DROP TABLE IF EXISTS `jadwal_penyusutan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jadwal_penyusutan` (
  `id_penyusutan` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_genset` bigint(20) unsigned NOT NULL,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_periode` bigint(20) unsigned DEFAULT NULL,
  `id_jurnal` bigint(20) unsigned DEFAULT NULL,
  `periode_bulan` date NOT NULL,
  `harga_perolehan` decimal(15,2) NOT NULL,
  `nilai_residu` decimal(15,2) NOT NULL,
  `umur_ekonomis_bulan` int(11) NOT NULL,
  `beban_penyusutan` decimal(15,2) NOT NULL,
  `akumulasi_penyusutan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nilai_buku` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status_jurnal` enum('pending','posted') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id_penyusutan`),
  KEY `jadwal_penyusutan_id_genset_periode_bulan_index` (`id_genset`,`periode_bulan`)
) ENGINE=InnoDB AUTO_INCREMENT=908 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_penyusutan`
--

LOCK TABLES `jadwal_penyusutan` WRITE;
/*!40000 ALTER TABLE `jadwal_penyusutan` DISABLE KEYS */;
INSERT INTO `jadwal_penyusutan` VALUES (900,1,1,4,504,'2026-04-01',480000000.00,60000000.00,96,4375000.00,166250000.00,313750000.00,'posted'),(901,2,1,4,504,'2026-04-01',480000000.00,60000000.00,96,4375000.00,166250000.00,313750000.00,'posted'),(902,3,1,4,504,'2026-04-01',920000000.00,110000000.00,120,6750000.00,303750000.00,616250000.00,'posted'),(903,4,1,4,504,'2026-04-01',265000000.00,30000000.00,96,2447917.00,56302091.00,208697909.00,'posted'),(904,5,1,4,504,'2026-04-01',880000000.00,100000000.00,120,6500000.00,344500000.00,535500000.00,'posted'),(905,6,1,4,504,'2026-04-01',1850000000.00,220000000.00,120,13583333.00,991583309.00,858416691.00,'posted'),(906,7,1,4,504,'2026-04-01',265000000.00,30000000.00,96,2447917.00,56302091.00,208697909.00,'posted'),(907,8,1,4,504,'2026-04-01',470000000.00,58000000.00,96,4291667.00,137333344.00,332666656.00,'posted');
/*!40000 ALTER TABLE `jadwal_penyusutan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jurnal_akuntansi`
--

DROP TABLE IF EXISTS `jurnal_akuntansi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jurnal_akuntansi` (
  `id_jurnal` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `id_periode` bigint(20) unsigned NOT NULL,
  `no_bukti` varchar(50) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jenis_jurnal` enum('pembelian_aset','sewa','pembayaran','pemeliharaan','beban_operasional','penyusutan','penjualan_aset','penyesuaian','penutup','manual') NOT NULL,
  `referensi_tipe` varchar(50) DEFAULT NULL,
  `referensi_id` bigint(20) unsigned DEFAULT NULL,
  `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_kredit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `dibuat_oleh` bigint(20) unsigned DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_jurnal`),
  KEY `jurnal_akuntansi_id_periode_foreign` (`id_periode`),
  CONSTRAINT `jurnal_akuntansi_id_periode_foreign` FOREIGN KEY (`id_periode`) REFERENCES `periode_akuntansi` (`id_periode`)
) ENGINE=InnoDB AUTO_INCREMENT=510 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jurnal_akuntansi`
--

LOCK TABLES `jurnal_akuntansi` WRITE;
/*!40000 ALTER TABLE `jurnal_akuntansi` DISABLE KEYS */;
INSERT INTO `jurnal_akuntansi` VALUES (501,1,4,'JRN-26040-001','2026-04-05','sewa','transaksi_sewa',1001,83250000.00,83250000.00,'Terbit Invoice INV/2026/04/001 — PT Adhi Konstruksi',1,'2026-04-05 02:14:00'),(502,1,4,'JRN-26040-002','2026-04-12','pembayaran','pembayaran',1,83250000.00,83250000.00,'Pembayaran INV/2026/04/001',1,'2026-04-12 06:02:00'),(503,1,4,'JRN-26040-003','2026-04-02','pemeliharaan','pemeliharaan',2,1675000.00,1675000.00,'Beban servis rutin Genset CMN-250-0232',2,'2026-04-02 09:30:00'),(504,1,4,'JRN-26040-004','2026-04-30','penyusutan','scheduler',NULL,32450000.00,32450000.00,'Depresiasi bulanan April 2026 (8 unit)',0,'2026-04-30 16:59:00'),(505,1,4,'JRN-26040-005','2026-04-08','sewa','transaksi_sewa',1002,209235000.00,209235000.00,'Terbit Invoice INV/2026/04/002 — PT Borneo Mining',1,'2026-04-08 03:00:00'),(506,1,4,'JRN-26040-006','2026-04-22','beban_operasional','transaksi_sewa',1002,4200000.00,4200000.00,'Tambahan BBM site Tenggarong',1,'2026-04-22 07:20:00'),(507,1,5,'JRN-26050-001','2026-05-21','pembayaran','pembayaran',5,109235000.00,109235000.00,'Pembayaran INV/2026/04/002',5,'2026-05-21 06:21:35'),(508,1,5,'JRN-26050-002','2026-05-21','pembayaran','pembayaran',6,6438000.00,6438000.00,'Pembayaran INV/2026/04/003',5,'2026-05-21 06:21:51'),(509,1,5,'JRN-26050-003','2026-05-27','sewa','transaksi_sewa',1008,27750000.00,27750000.00,'Terbit Invoice INV/2026/05/008',1,'2026-05-26 20:54:00');
/*!40000 ALTER TABLE `jurnal_akuntansi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periode_akuntansi`
--

DROP TABLE IF EXISTS `periode_akuntansi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `periode_akuntansi` (
  `id_periode` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_perusahaan` bigint(20) unsigned NOT NULL,
  `tahun` int(11) NOT NULL,
  `bulan` tinyint(4) NOT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `status` enum('aktif','ditutup') NOT NULL DEFAULT 'aktif',
  `tgl_tutup_buku` datetime DEFAULT NULL,
  `ditutup_oleh` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_periode`),
  KEY `periode_akuntansi_id_perusahaan_tahun_bulan_index` (`id_perusahaan`,`tahun`,`bulan`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periode_akuntansi`
--

LOCK TABLES `periode_akuntansi` WRITE;
/*!40000 ALTER TABLE `periode_akuntansi` DISABLE KEYS */;
INSERT INTO `periode_akuntansi` VALUES (1,1,2026,1,'2026-01-01','2026-01-31','ditutup','2026-02-03 00:00:00',5),(2,1,2026,2,'2026-02-01','2026-02-28','ditutup','2026-03-02 00:00:00',5),(3,1,2026,3,'2026-03-01','2026-03-31','ditutup','2026-04-02 00:00:00',5),(4,1,2026,4,'2026-04-01','2026-04-30','aktif',NULL,NULL),(5,1,2026,5,'2026-05-01','2026-05-31','aktif',NULL,NULL);
/*!40000 ALTER TABLE `periode_akuntansi` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-27 11:18:21
