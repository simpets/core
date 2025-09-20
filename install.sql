-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 06, 2025 at 02:41 PM
-- Server version: 8.0.42
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simpets_SS_INSTALL`
--

-- --------------------------------------------------------

--
-- Table structure for table `adopts`
--

CREATE TABLE `adopts` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `cost` int NOT NULL DEFAULT '0',
  `available` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `adopts`
--

INSERT INTO `adopts` (`id`, `name`, `image`, `type`, `cost`, `available`) VALUES
(1, 'Feenee', 'images/Feenee.png', 'Feenee', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `adventure_locations`
--

CREATE TABLE `adventure_locations` (
  `id` int UNSIGNED NOT NULL,
  `location_id` int UNSIGNED NOT NULL,
  `drop_type` enum('simbucks','item','nothing') NOT NULL,
  `itemname` varchar(100) DEFAULT NULL,
  `min_amount` int DEFAULT '0',
  `max_amount` int DEFAULT '0',
  `weight` int UNSIGNED NOT NULL DEFAULT '1',
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adventure_runs`
--

CREATE TABLE `adventure_runs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `pet_id` int UNSIGNED NOT NULL,
  `zone_id` int UNSIGNED DEFAULT NULL,
  `awarded_item_id` int UNSIGNED DEFAULT NULL,
  `simbucks` int UNSIGNED NOT NULL DEFAULT '0',
  `pet_name` varchar(100) DEFAULT NULL,
  `location_id` int UNSIGNED DEFAULT NULL,
  `result_type` enum('simbucks','item','nothing') NOT NULL,
  `result_item` varchar(100) DEFAULT NULL,
  `amount` int DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `seed` bigint DEFAULT NULL,
  `rarity_awarded` enum('common','uncommon','rare','epic','legendary') DEFAULT NULL,
  `is_crit` tinyint(1) NOT NULL DEFAULT '0',
  `rewards_json` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adventure_zones`
--

CREATE TABLE `adventure_zones` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `min_level` int UNSIGNED NOT NULL DEFAULT '1',
  `stamina_cost` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `adventure_zones`
--

INSERT INTO `adventure_zones` (`id`, `name`, `min_level`, `stamina_cost`, `is_active`, `sort_order`) VALUES
(1, 'Sunny Meadow', 3, 1, 1, 10),
(2, 'Moonlit Beach', 3, 1, 1, 20),
(3, 'Crystal Caves', 3, 2, 1, 30),
(4, 'Whispering Woods', 3, 1, 1, 25);

-- --------------------------------------------------------

--
-- Table structure for table `adventure_zone_drops`
--

CREATE TABLE `adventure_zone_drops` (
  `zone_id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `rarity` enum('common','uncommon','rare','epic','legendary') NOT NULL DEFAULT 'common',
  `weight` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `adventure_zone_drops`
--

INSERT INTO `adventure_zone_drops` (`zone_id`, `item_id`, `rarity`, `weight`) VALUES
(1, 501, 'common', 10),
(1, 502, 'common', 10),
(1, 503, 'common', 10),
(1, 504, 'common', 10),
(2, 501, 'common', 10),
(2, 502, 'common', 10),
(2, 503, 'common', 10),
(2, 504, 'common', 10),
(2, 601, 'uncommon', 6),
(2, 602, 'uncommon', 6),
(2, 603, 'uncommon', 6),
(2, 604, 'uncommon', 6),
(3, 501, 'common', 10),
(3, 502, 'common', 10),
(3, 503, 'common', 10),
(3, 504, 'common', 10),
(3, 601, 'uncommon', 6),
(3, 602, 'uncommon', 6),
(3, 603, 'uncommon', 6),
(3, 604, 'uncommon', 6),
(3, 701, 'rare', 2);

-- --------------------------------------------------------

--
-- Table structure for table `battle_logs`
--

CREATE TABLE `battle_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `game` varchar(50) DEFAULT NULL,
  `won` tinyint(1) DEFAULT NULL,
  `reward` int DEFAULT '0',
  `battle_time` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `blocker_id` int NOT NULL,
  `blocked_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boost_log`
--

CREATE TABLE `boost_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `pet_id` int NOT NULL,
  `boost_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `breeding_log`
--

CREATE TABLE `breeding_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `parent1_id` int NOT NULL,
  `parent2_id` int NOT NULL,
  `offspring_name` varchar(100) NOT NULL,
  `offspring_image` varchar(255) NOT NULL,
  `bred_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `broadcast_log`
--

CREATE TABLE `broadcast_log` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `sent_count` int NOT NULL DEFAULT '0',
  `method` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `broadcast_log`
--

INSERT INTO `broadcast_log` (`id`, `admin_id`, `subject`, `body`, `sent_count`, `method`, `created_at`) VALUES
(1, 1, 'Test', 'testy Mc Test', 1, 'mail()', '2025-08-28 16:05:03'),
(2, 1, 'Test', 'testy Mc Test', 1, 'mail()', '2025-08-28 19:22:40');

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE `forums` (
  `id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `is_container` tinyint(1) NOT NULL DEFAULT '0',
  `admin_only` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `access_level` varchar(20) DEFAULT 'All'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `forums`
--

INSERT INTO `forums` (`id`, `parent_id`, `is_container`, `admin_only`, `name`, `description`, `is_locked`, `created_at`, `access_level`) VALUES
(1, NULL, 0, 0, 'Test', NULL, 0, '2025-08-28 19:38:55', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

CREATE TABLE `forum_categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `forum_categories`
--

INSERT INTO `forum_categories` (`id`, `name`) VALUES
(1, 'Test 1'),
(2, 'Test 2');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` int NOT NULL,
  `topic_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text NOT NULL,
  `posted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE `forum_topics` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `function_type` varchar(50) DEFAULT NULL,
  `shop` varchar(50) DEFAULT NULL,
  `price` int NOT NULL DEFAULT '0',
  `shop_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `description`, `image`, `function_type`, `shop`, `price`, `shop_id`) VALUES
(1, 'Wasteland', NULL, 'Wasteland.png', 'set_background', 'Background Bin', 25, 1),
(2, 'Teddy Toy', 'A soft teddy bear toy.', 'Teddy.png', 'add_toy1', 'General Store', 25, 2),
(3, 'Mature Potion', 'Instantly levels your pet to 3.', 'Mature.png', 'level3', 'Potions', 50, 3),
(4, 'Dreamscape', NULL, 'Dreamscape.png', 'set_background', 'Background Bin', 25, 1),
(5, 'Dreamland', NULL, 'Dreamland.png', 'set_background', 'Background Bin', 25, 1),
(6, 'Spooky', NULL, 'Spooky.png', 'set_background', 'Background Bin', 25, 1),
(7, 'Archbow', NULL, 'Archbow.png', 'set_background', 'Background Bin', 25, 1),
(8, 'Celtic', NULL, 'Celtic.png', 'set_background', 'Background Bin', 25, 1),
(9, 'Epiphany', NULL, 'Epiphany.png', 'set_background', 'Background Bin', 25, 1),
(10, 'Flower Lake', NULL, 'Flower_Lake.png', 'set_background', 'Background Bin', 25, 1),
(11, 'Flower Power', NULL, 'Flower_Power.png', 'set_background', 'Background Bin', 25, 1),
(12, 'Gothica', NULL, 'Gothica.png', 'set_background', 'Background Bin', 25, 1),
(13, 'Hamlet', NULL, 'Hamlet.png', 'set_background', 'Background Bin', 25, 1),
(14, 'Heartwood', NULL, 'Heartwood.png', 'set_background', 'Background Bin', 25, 1),
(15, 'Lake', NULL, 'Lake.png', 'set_background', 'Background Bin', 25, 1),
(16, 'Midnight', NULL, 'Midnight.png', 'set_background', 'Background Bin', 25, 1),
(17, 'Sweets', NULL, 'Sweets.png', 'set_background', 'Background Bin', 25, 1),
(19, 'Base Token', 'A Token to make a Base Custom', 'Base_Token.png', NULL, 'Tokens', 100, 4),
(20, 'Custom Token', NULL, 'Custom_Token.png', NULL, 'Tokens', 100, 4),
(21, 'Custom Token 2', NULL, 'Custom_Token2.png', NULL, 'Tokens', 250, 4),
(22, 'Custom Token 3', NULL, 'Custom_Token3.png', NULL, 'Tokens', 500, 4),
(23, 'Special Token', NULL, NULL, NULL, NULL, 25, NULL),
(24, 'Tusky', NULL, 'Tusky.png', 'add_toy2', 'General Store', 25, 2),
(25, 'Puppy Plush', NULL, 'Puppy_Plush.png', 'add_toy3', 'General Store', 25, 2),
(26, 'Kitty Plush', NULL, 'Kitty_Plush.png', 'add_toy3', 'General Store', 25, 2),
(27, 'Bunny Plush', NULL, 'Bunny_Plush.png', 'add_toy3', 'General Store', 25, 2),
(28, 'Raven', NULL, 'Raven.png', 'add_toy3', 'General Store', 5000, 2),
(29, 'Sloth', NULL, 'Sloth.png', 'add_toy3', 'General Store', 25, 2),
(30, 'Stars And Stripes', NULL, 'Stars_And_Stripes.png', 'set_background', 'Background Bin', 25, 1),
(31, 'Deluxe Custom Token', 'Allows Deluxe custom creation with base and 2 markings.', 'Deluxe_Custom_Token.png', NULL, 'Tokens', 1000, 4),
(32, 'Grand Custom Token', 'Allows Grand custom creation with base and 3 markings.', 'Grand_Custom_Token.png', NULL, 'Tokens', 1000, 4),
(52, 'Rainbowed', 'Instantly gives your pet a rainbow effect!', 'Rainbowed.png', 'rainbow_fur', 'Potions', 50, 3),
(53, 'Mini Me', 'Instantly changes your pet to Mini Size!', 'Mini_Me.png', 'mini_size', 'Potions', 50, 3),
(54, 'Glow', 'Instantly gives your pet a glow effect', 'Glow.png', 'glow', 'Potions', 50, 3),
(55, 'No Bow ~ Rainbow Remover', 'Removes the Rainbow!', 'No_Bow.png', 'normal_fur', 'Potions', 50, 3),
(56, 'Grow ~ Normal Sizer', 'Instantly changes your pet to normal Size!', 'Grow.png', 'normal_size', 'Potions', 50, 3),
(57, 'De Glow ~ Glow Be Gone!', 'Instantly removes your pet\'s glow effect', 'De_Glow.png', 'remove_glow', 'Potions', 50, 3);

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `id` int NOT NULL,
  `pet_type` varchar(50) NOT NULL,
  `level` int NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `pet_type`, `level`, `image`) VALUES
(1, 'Feenee', 1, 'images/levels/Feenee_Egg.png'),
(2, 'Feenee', 2, 'images/levels/Feenee_Egg.png'),
(3, 'Feenee', 3, 'images/levels/Feenee.png');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `body` text,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `subject`, `body`, `sent_at`, `is_read`) VALUES
(2, 1, 7, 'Welcome to Simpets!', 'Hi there and welcome to Simpets! We hope you enjoy your time here. Feel free to ask questions anytime. For more fun and info, check out our FAQ and forum. Please make sure you read and follow the rules and Terms of Service!<br><br>Have a wonderful day!<br><br>~Admin', '2025-07-14 15:14:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_posted` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `message`, `date_posted`) VALUES
(1, 'Test', 'Test News!\nGlow, Rainbow and Mini Me!\n\nFind them in the Potions Shop at The Market!', '2025-08-04 23:24:50');

-- --------------------------------------------------------

--
-- Table structure for table `online`
--

CREATE TABLE `online` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip` varchar(45) DEFAULT '',
  `session` varchar(255) DEFAULT NULL,
  `time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `online`
--

INSERT INTO `online` (`id`, `username`, `ip`, `session`, `time`) VALUES
(5, 'Admin', '72.212.22.68', NULL, 1757179797);

-- --------------------------------------------------------

--
-- Table structure for table `online_users`
--

CREATE TABLE `online_users` (
  `id` int NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(120) DEFAULT NULL,
  `last_seen` datetime NOT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_adventure_state`
--

CREATE TABLE `pet_adventure_state` (
  `pet_id` int UNSIGNED NOT NULL,
  `stamina_current` int UNSIGNED NOT NULL DEFAULT '5',
  `stamina_max` int UNSIGNED NOT NULL DEFAULT '5',
  `stamina_refill` date NOT NULL DEFAULT (curdate()),
  `pity_rare_count` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_boosts`
--

CREATE TABLE `pet_boosts` (
  `id` int NOT NULL,
  `pet_id` int NOT NULL,
  `user_id` int NOT NULL,
  `boost_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_market`
--

CREATE TABLE `pet_market` (
  `id` int NOT NULL,
  `pet_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `price` int NOT NULL,
  `listed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `topic_id` int DEFAULT NULL,
  `author` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `posted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `thread_id` int NOT NULL,
  `user_id` int NOT NULL,
  `body` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `posts`
--
DELIMITER $$
CREATE TRIGGER `bi_posts_fill_topic_id` BEFORE INSERT ON `posts` FOR EACH ROW BEGIN
  IF NEW.topic_id IS NULL AND NEW.thread_id IS NOT NULL THEN
    SET NEW.topic_id = NEW.thread_id;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_log`
--

CREATE TABLE `quiz_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `game_name` varchar(50) NOT NULL,
  `played_on` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `name`, `description`, `is_active`) VALUES
(1, 'Background Bin', NULL, 1),
(2, 'General Store', NULL, 1),
(3, 'Potions', NULL, 1),
(4, 'Tokens', 'Buy Tokens Here!', 1);

-- --------------------------------------------------------

--
-- Table structure for table `shop_items`
--

CREATE TABLE `shop_items` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` int NOT NULL,
  `type` varchar(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sparring_logs`
--

CREATE TABLE `sparring_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `game` varchar(50) DEFAULT NULL,
  `won` tinyint(1) DEFAULT NULL,
  `reward` int DEFAULT '0',
  `battle_time` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `id` int NOT NULL,
  `forum_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `last_post_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int NOT NULL,
  `forum_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'assets/default-avatar.png',
  `bio` text,
  `simbucks` int DEFAULT '10000',
  `nickname` varchar(50) DEFAULT NULL,
  `usergroup` varchar(20) NOT NULL DEFAULT 'Member',
  `profile_theme` varchar(20) DEFAULT 'theme-default',
  `custom_background` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `last_news_seen` int DEFAULT '0',
  `display_name` text,
  `unsubscribe_emails` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `avatar`, `bio`, `simbucks`, `nickname`, `usergroup`, `profile_theme`, `custom_background`, `reset_token`, `reset_expires`, `email`, `last_news_seen`, `display_name`, `unsubscribe_emails`) VALUES
(1, 'Admin', '$2y$10$tCLux2rXmi0KckrxqmvpIO82O.ytuNS5JNymHpjzZ0sa1Te3GJWja', 'assets/default-avatar.png', NULL, 120311, 'Milly', 'Admin', 'theme-custom', 'http://wallpapercave.com/wp/yHOliZp.jpg', NULL, NULL, 'milly.money@aol.com', 11, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_friends`
--

CREATE TABLE `user_friends` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `addressee_id` int NOT NULL,
  `status` enum('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_items`
--

CREATE TABLE `user_items` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_pets`
--

CREATE TABLE `user_pets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `pet_name` varchar(100) NOT NULL,
  `pet_image` varchar(255) NOT NULL,
  `adopted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `background` int DEFAULT NULL,
  `accessory` int DEFAULT NULL,
  `mother` varchar(255) DEFAULT NULL,
  `father` varchar(255) DEFAULT NULL,
  `offspring` int DEFAULT '0',
  `gender` enum('Male','Female') DEFAULT 'Female',
  `clicks` int DEFAULT '0',
  `level` int DEFAULT '1',
  `skill` int NOT NULL DEFAULT '0',
  `boosts` int DEFAULT '0',
  `description` text,
  `type` varchar(50) DEFAULT 'Feenee',
  `price` int DEFAULT NULL,
  `background_url` varchar(255) DEFAULT NULL,
  `toy1` varchar(255) DEFAULT NULL,
  `toy2` varchar(255) DEFAULT NULL,
  `toy3` varchar(255) DEFAULT NULL,
  `deco` varchar(255) DEFAULT NULL,
  `marking1` varchar(255) DEFAULT NULL,
  `marking2` varchar(255) DEFAULT NULL,
  `marking3` varchar(255) DEFAULT NULL,
  `toy1_x` int DEFAULT NULL,
  `toy1_y` int DEFAULT NULL,
  `toy2_x` int DEFAULT NULL,
  `toy2_y` int DEFAULT NULL,
  `toy3_x` int DEFAULT NULL,
  `toy3_y` int DEFAULT NULL,
  `deco_x` int DEFAULT NULL,
  `deco_y` int DEFAULT NULL,
  `base` varchar(255) DEFAULT '',
  `sparring_wins` int DEFAULT '0',
  `marking4` varchar(255) DEFAULT NULL,
  `marking5` varchar(255) DEFAULT NULL,
  `sale` varchar(3) NOT NULL DEFAULT 'No',
  `mate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'None',
  `mate_id` int NOT NULL DEFAULT '0',
  `last_quest` datetime DEFAULT NULL,
  `appearance_effect` varchar(32) DEFAULT NULL,
  `appearance_size` varchar(16) DEFAULT NULL,
  `appearance_pattern` varchar(32) DEFAULT NULL,
  `temp_species` varchar(32) DEFAULT NULL,
  `appearance_fluff` tinyint(1) DEFAULT '0',
  `has_wings` tinyint(1) DEFAULT '0',
  `appearance_glow` tinyint(1) DEFAULT '0',
  `appearance_elemental` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_pets`
--

INSERT INTO `user_pets` (`id`, `user_id`, `pet_name`, `pet_image`, `adopted_at`, `background`, `accessory`, `mother`, `father`, `offspring`, `gender`, `clicks`, `level`, `skill`, `boosts`, `description`, `type`, `price`, `background_url`, `toy1`, `toy2`, `toy3`, `deco`, `marking1`, `marking2`, `marking3`, `toy1_x`, `toy1_y`, `toy2_x`, `toy2_y`, `toy3_x`, `toy3_y`, `deco_x`, `deco_y`, `base`, `sparring_wins`, `marking4`, `marking5`, `sale`, `mate`, `mate_id`, `last_quest`, `appearance_effect`, `appearance_size`, `appearance_pattern`, `temp_species`, `appearance_fluff`, `has_wings`, `appearance_glow`, `appearance_elemental`) VALUES
(2, 1, 'Ziggy', 'images/generated/pet_1755659882.png', '2025-07-04 21:48:38', NULL, NULL, NULL, NULL, 4, 'Male', 0, 3, 10, 1, NULL, 'Farra', NULL, 'Dreamscape.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Farra_Rainbow.png', 3, NULL, NULL, 'No', 'None', 0, '2025-07-15 17:32:32', NULL, NULL, NULL, NULL, 0, 0, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_quiz_log`
--

CREATE TABLE `user_quiz_log` (
  `user_id` int NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_shops`
--

CREATE TABLE `user_shops` (
  `user_id` int NOT NULL,
  `shop_name` varchar(100) NOT NULL DEFAULT 'My Shop',
  `shop_image` varchar(255) DEFAULT NULL,
  `shop_description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_shop_listings`
--

CREATE TABLE `user_shop_listings` (
  `id` int NOT NULL,
  `seller_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` int NOT NULL,
  `currency` varchar(20) NOT NULL DEFAULT 'canicash',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adopts`
--
ALTER TABLE `adopts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `adventure_locations`
--
ALTER TABLE `adventure_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `drop_type` (`drop_type`),
  ADD KEY `itemname` (`itemname`);

--
-- Indexes for table `adventure_runs`
--
ALTER TABLE `adventure_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `username` (`username`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_runs_zone` (`zone_id`),
  ADD KEY `idx_runs_pet` (`pet_id`),
  ADD KEY `idx_runs_item` (`awarded_item_id`);

--
-- Indexes for table `adventure_zones`
--
ALTER TABLE `adventure_zones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `adventure_zone_drops`
--
ALTER TABLE `adventure_zone_drops`
  ADD PRIMARY KEY (`zone_id`,`item_id`),
  ADD KEY `idx_zone_rarity` (`zone_id`,`rarity`);

--
-- Indexes for table `battle_logs`
--
ALTER TABLE `battle_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`blocker_id`,`blocked_id`);

--
-- Indexes for table `boost_log`
--
ALTER TABLE `boost_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_pet_date` (`user_id`,`pet_id`,`boost_date`);

--
-- Indexes for table `breeding_log`
--
ALTER TABLE `breeding_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent1_id` (`parent1_id`),
  ADD KEY `parent2_id` (`parent2_id`);

--
-- Indexes for table `broadcast_log`
--
ALTER TABLE `broadcast_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_forums_parent` (`parent_id`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online`
--
ALTER TABLE `online`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`);

--
-- Indexes for table `online_users`
--
ALTER TABLE `online_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_last_seen` (`last_seen`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `pet_adventure_state`
--
ALTER TABLE `pet_adventure_state`
  ADD PRIMARY KEY (`pet_id`);

--
-- Indexes for table `pet_boosts`
--
ALTER TABLE `pet_boosts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `pet_market`
--
ALTER TABLE `pet_market`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `idx_thread` (`thread_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `quiz_log`
--
ALTER TABLE `quiz_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`game_name`,`played_on`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `shop_items`
--
ALTER TABLE `shop_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sparring_logs`
--
ALTER TABLE `sparring_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forum` (`forum_id`),
  ADD KEY `idx_last` (`last_post_at`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `forum_id` (`forum_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_friends`
--
ALTER TABLE `user_friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pair` (`requester_id`,`addressee_id`),
  ADD KEY `idx_addressee_status` (`addressee_id`,`status`),
  ADD KEY `idx_requester_status` (`requester_id`,`status`);

--
-- Indexes for table `user_items`
--
ALTER TABLE `user_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_items_ibfk_2` (`item_id`);

--
-- Indexes for table `user_pets`
--
ALTER TABLE `user_pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_quiz_log`
--
ALTER TABLE `user_quiz_log`
  ADD PRIMARY KEY (`user_id`,`date`);

--
-- Indexes for table `user_shops`
--
ALTER TABLE `user_shops`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_shop_listings`
--
ALTER TABLE `user_shop_listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `item_id` (`item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adopts`
--
ALTER TABLE `adopts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adventure_locations`
--
ALTER TABLE `adventure_locations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adventure_runs`
--
ALTER TABLE `adventure_runs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adventure_zones`
--
ALTER TABLE `adventure_zones`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `battle_logs`
--
ALTER TABLE `battle_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boost_log`
--
ALTER TABLE `boost_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `breeding_log`
--
ALTER TABLE `breeding_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `broadcast_log`
--
ALTER TABLE `broadcast_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forums`
--
ALTER TABLE `forums`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `online`
--
ALTER TABLE `online`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `online_users`
--
ALTER TABLE `online_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_boosts`
--
ALTER TABLE `pet_boosts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_market`
--
ALTER TABLE `pet_market`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_log`
--
ALTER TABLE `quiz_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `shop_items`
--
ALTER TABLE `shop_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sparring_logs`
--
ALTER TABLE `sparring_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_friends`
--
ALTER TABLE `user_friends`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_items`
--
ALTER TABLE `user_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_pets`
--
ALTER TABLE `user_pets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_shop_listings`
--
ALTER TABLE `user_shop_listings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adventure_locations`
--
ALTER TABLE `adventure_locations`
  ADD CONSTRAINT `adventure_locations_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `adventure_locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `adventure_runs`
--
ALTER TABLE `adventure_runs`
  ADD CONSTRAINT `adventure_runs_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `adventure_locations` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `adventure_zone_drops`
--
ALTER TABLE `adventure_zone_drops`
  ADD CONSTRAINT `fk_azd_zone` FOREIGN KEY (`zone_id`) REFERENCES `adventure_zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `breeding_log`
--
ALTER TABLE `breeding_log`
  ADD CONSTRAINT `breeding_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `breeding_log_ibfk_2` FOREIGN KEY (`parent1_id`) REFERENCES `user_pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `breeding_log_ibfk_3` FOREIGN KEY (`parent2_id`) REFERENCES `user_pets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `fk_forums_parent` FOREIGN KEY (`parent_id`) REFERENCES `forums` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pet_market`
--
ALTER TABLE `pet_market`
  ADD CONSTRAINT `pet_market_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `user_pets` (`id`),
  ADD CONSTRAINT `pet_market_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `fk_threads_forum` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`);

--
-- Constraints for table `user_friends`
--
ALTER TABLE `user_friends`
  ADD CONSTRAINT `fk_uf_add` FOREIGN KEY (`addressee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uf_req` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_items`
--
ALTER TABLE `user_items`
  ADD CONSTRAINT `user_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_pets`
--
ALTER TABLE `user_pets`
  ADD CONSTRAINT `user_pets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_shops`
--
ALTER TABLE `user_shops`
  ADD CONSTRAINT `user_shops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_shop_listings`
--
ALTER TABLE `user_shop_listings`
  ADD CONSTRAINT `user_shop_listings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_shop_listings_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
