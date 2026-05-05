-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 28/04/2026 às 04:22
-- Versão do servidor: 11.8.6-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u401260415_forapix`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `model`, `model_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin_login', NULL, NULL, '{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 23:11:32', '2026-04-13 23:11:32'),
(2, 1, 'admin_login', NULL, NULL, '{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/147.0.0.0 Safari\\/537.36\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 20:32:28', '2026-04-17 20:32:28'),
(3, 1, 'admin_login', NULL, NULL, '{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/147.0.0.0 Safari\\/537.36\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-27 21:26:55', '2026-04-27 21:26:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `bets`
--

CREATE TABLE `bets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bet_id` varchar(20) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `bet_type` enum('first_player','second_player','draw','par','impar') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `odds` decimal(5,2) NOT NULL,
  `potential_win` decimal(10,2) NOT NULL,
  `status` enum('pending','won','lost','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `result_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cancellation_reason` text DEFAULT NULL,
  `placed_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `games`
--

CREATE TABLE `games` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sport_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('head_to_head','casino','bingo','sinuca','par_impar') NOT NULL DEFAULT 'head_to_head',
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `min_bet` decimal(10,2) NOT NULL DEFAULT 1.00,
  `max_bet` decimal(10,2) NOT NULL DEFAULT 10000.00,
  `house_edge` decimal(5,4) NOT NULL DEFAULT 0.0500,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `games`
--

INSERT INTO `games` (`id`, `sport_id`, `name`, `slug`, `type`, `image`, `description`, `min_bet`, `max_bet`, `house_edge`, `status`, `settings`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 1, 'UFC Head to Head', 'ufc-head-to-head', 'head_to_head', NULL, 'Apostas em confrontos diretos do UFC', 1.00, 10000.00, 0.0500, 'active', '{\"allow_draw\":false,\"live_betting\":true}', '2026-04-13 19:08:28', '2026-04-13 19:08:28', NULL, NULL),
(2, 6, 'Sinuca Par ou Ímpar', 'sinuca-par-impar', 'par_impar', NULL, 'Aposte se o resultado será par ou ímpar', 1.00, 5000.00, 0.0300, 'active', '{\"auto_resolve\":true,\"max_duration\":30}', '2026-04-13 19:08:28', '2026-04-13 19:08:28', NULL, NULL),
(3, 6, 'Sinuca Head to Head', 'sinuca-head-to-head', 'head_to_head', NULL, 'Apostas em confrontos diretos de sinuca', 1.00, 5000.00, 0.0400, 'active', '{\"allow_draw\":false,\"live_betting\":true}', '2026-04-13 19:08:28', '2026-04-13 19:08:28', NULL, NULL),
(4, NULL, 'Cassino Online', 'cassino-online', 'casino', NULL, 'Jogos de cassino variados', 0.50, 50000.00, 0.0200, 'active', '{\"games\":[\"slots\",\"blackjack\",\"roulette\"],\"instant_play\":true}', '2026-04-13 19:08:28', '2026-04-13 19:08:28', NULL, NULL),
(5, NULL, 'Bingo Online', 'bingo-online', 'bingo', NULL, 'Bingo com prêmios em dinheiro', 1.00, 1000.00, 0.1000, 'active', '{\"max_players\":100,\"draw_interval\":5}', '2026-04-13 19:08:28', '2026-04-13 19:08:28', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `matches`
--

CREATE TABLE `matches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `first_player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `second_player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_player_odds` decimal(5,2) NOT NULL DEFAULT 1.00,
  `second_player_odds` decimal(5,2) NOT NULL DEFAULT 1.00,
  `draw_odds` decimal(5,2) DEFAULT NULL,
  `par_odds` decimal(5,2) DEFAULT NULL,
  `impar_odds` decimal(5,2) DEFAULT NULL,
  `betting_deadline` timestamp NOT NULL,
  `match_start` timestamp NOT NULL,
  `match_end` timestamp NULL DEFAULT NULL,
  `status` enum('scheduled','live','finished','cancelled','postponed') NOT NULL DEFAULT 'scheduled',
  `result` enum('first_player','second_player','draw','par','impar') DEFAULT NULL,
  `winner_player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_player_score` int(11) NOT NULL DEFAULT 0,
  `second_player_score` int(11) NOT NULL DEFAULT 0,
  `external_id` varchar(255) DEFAULT NULL,
  `external_source` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `total_bets_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_bets_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `matches`
--

INSERT INTO `matches` (`id`, `game_id`, `title`, `description`, `first_player_id`, `second_player_id`, `first_player_odds`, `second_player_odds`, `draw_odds`, `par_odds`, `impar_odds`, `betting_deadline`, `match_start`, `match_end`, `status`, `result`, `winner_player_id`, `first_player_score`, `second_player_score`, `external_id`, `external_source`, `metadata`, `featured`, `total_bets_amount`, `total_bets_count`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 3, 'sinuca', NULL, 2, 1, 1.80, 1.90, NULL, NULL, NULL, '2026-04-14 22:00:00', '2026-04-14 22:26:00', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"banner_image\":\"matches\\/banners\\/6b0z8T0MQaoG4SVQ4B9MOiw4rvhqWUf3aCquHoGn.png\"}', 0, 0.00, 0, '2026-04-14 01:27:34', '2026-04-14 01:27:34', NULL, NULL),
(2, 3, 'Sinuca Head to Head', 'Confronto emocionante entre Igão Parceiro e Maycon de Teixeira!', 3, 4, 1.80, 1.90, 3.50, 1.85, 1.95, '2026-04-27 18:38:46', '2026-04-27 19:38:46', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"banner_image\":\"http:\\/\\/localhost:3000\\/assets\\/images\\/sinuca-game.png\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/2\"}', 1, 0.00, 0, '2026-04-27 16:38:46', '2026-04-27 17:59:52', NULL, NULL),
(3, 2, 'Sinuca Par ou Ímpar', 'Fábio Cabeludo vs Diego Sinuqueiro - Ao vivo!', 5, 6, 2.10, 1.75, 4.00, 1.90, 1.90, '2026-04-27 16:08:46', '2026-04-27 15:38:46', NULL, 'live', NULL, NULL, 3, 2, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/live-example\",\"banner_image\":\"http:\\/\\/localhost:3000\\/assets\\/images\\/sinuca-game.png\",\"banner_button_label\":\"Assistir Agora\",\"banner_button_link\":\"https:\\/\\/youtube.com\"}', 1, 0.00, 0, '2026-04-27 16:38:46', '2026-04-27 17:59:52', NULL, NULL),
(4, 3, 'Sinuca Head to Head', 'Carlos venceu com autoridade!', 7, 8, 1.65, 2.20, 3.80, 1.85, 1.95, '2026-04-26 15:38:46', '2026-04-26 16:38:46', '2026-04-26 18:38:46', 'finished', NULL, 7, 5, 3, NULL, NULL, '{\"banner_image\":\"http:\\/\\/localhost:3000\\/assets\\/images\\/sinuca-game.png\"}', 0, 1500.00, 12, '2026-04-27 16:38:46', '2026-04-27 17:59:52', NULL, NULL),
(5, 1, 'UFC Head to Head', 'Jon Jones vs Stipe Miocic - O grande confronto!', 9, 10, 1.55, 2.40, NULL, NULL, NULL, '2026-04-29 15:38:46', '2026-04-29 16:38:46', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/7c3aed\\/ffffff?text=Sinuca+Head+to+Head\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/5\"}', 1, 0.00, 0, '2026-04-27 16:38:46', '2026-04-27 17:19:12', NULL, NULL),
(6, 1, 'UFC Head to Head', 'Poatan nocauteou Adesanya no 2º round!', 11, 12, 1.70, 2.10, NULL, NULL, NULL, '2026-04-24 15:38:46', '2026-04-24 16:38:46', '2026-04-24 17:38:46', 'finished', NULL, 11, 2, 0, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/6b7280\\/ffffff?text=Sinuca+Finished\"}', 0, 8500.00, 45, '2026-04-27 16:38:46', '2026-04-27 17:19:12', NULL, NULL),
(7, 3, 'Sinuca Head to Head', 'Confronto emocionante entre Igão Parceiro e Maycon de Teixeira!', 3, 4, 1.80, 1.90, 3.50, 1.85, 1.95, '2026-04-27 18:58:06', '2026-04-27 19:58:06', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"banner_image\":\"matches\\/banners\\/sinuca-match-1.jpg\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/2\"}', 1, 0.00, 0, '2026-04-27 16:58:06', '2026-04-27 16:58:06', NULL, NULL),
(8, 2, 'Sinuca Par ou Ímpar', 'Fábio Cabeludo vs Diego Sinuqueiro - Ao vivo!', 5, 6, 2.10, 1.75, 4.00, 1.90, 1.90, '2026-04-27 16:28:06', '2026-04-27 15:58:06', NULL, 'live', NULL, NULL, 3, 2, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/live-example\",\"banner_image\":\"matches\\/banners\\/sinuca-live.jpg\",\"banner_button_label\":\"Assistir Agora\",\"banner_button_link\":\"https:\\/\\/youtube.com\"}', 1, 0.00, 0, '2026-04-27 16:58:06', '2026-04-27 16:58:06', NULL, NULL),
(9, 3, 'Sinuca Head to Head', 'Carlos venceu com autoridade!', 7, 8, 1.65, 2.20, 3.80, 1.85, 1.95, '2026-04-26 15:58:06', '2026-04-26 16:58:06', '2026-04-26 18:58:06', 'finished', NULL, 7, 5, 3, NULL, NULL, '{\"banner_image\":\"matches\\/banners\\/sinuca-finished.jpg\"}', 0, 1500.00, 12, '2026-04-27 16:58:06', '2026-04-27 16:58:06', NULL, NULL),
(10, 1, 'UFC Head to Head', 'Jon Jones vs Stipe Miocic - O grande confronto!', 9, 10, 1.55, 2.40, NULL, NULL, NULL, '2026-04-29 15:58:06', '2026-04-29 16:58:06', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"banner_image\":\"matches\\/banners\\/ufc-scheduled.jpg\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/5\"}', 1, 0.00, 0, '2026-04-27 16:58:06', '2026-04-27 16:58:06', NULL, NULL),
(11, 1, 'UFC Head to Head', 'Poatan nocauteou Adesanya no 2º round!', 11, 12, 1.70, 2.10, NULL, NULL, NULL, '2026-04-24 15:58:06', '2026-04-24 16:58:06', '2026-04-24 17:58:06', 'finished', NULL, 11, 2, 0, NULL, NULL, '{\"banner_image\":\"matches\\/banners\\/ufc-finished.jpg\"}', 0, 8500.00, 45, '2026-04-27 16:58:06', '2026-04-27 16:58:06', NULL, NULL),
(12, 3, 'Sinuca Head to Head', 'Confronto emocionante entre Igão Parceiro e Maycon de Teixeira!', 3, 4, 1.80, 1.90, 3.50, 1.85, 1.95, '2026-04-27 19:45:44', '2026-04-27 20:45:44', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/7c3aed\\/ffffff?text=Sinuca+Head+to+Head\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/2\"}', 1, 0.00, 0, '2026-04-27 17:45:44', '2026-04-27 17:45:44', NULL, NULL),
(13, 2, 'Sinuca Par ou Ímpar', 'Fábio Cabeludo vs Diego Sinuqueiro - Ao vivo!', 5, 6, 2.10, 1.75, 4.00, 1.90, 1.90, '2026-04-27 17:15:44', '2026-04-27 16:45:44', NULL, 'live', NULL, NULL, 3, 2, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/live-example\",\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/22c55e\\/ffffff?text=Sinuca+Live\",\"banner_button_label\":\"Assistir Agora\",\"banner_button_link\":\"https:\\/\\/youtube.com\"}', 1, 0.00, 0, '2026-04-27 17:45:44', '2026-04-27 17:45:44', NULL, NULL),
(14, 3, 'Sinuca Head to Head', 'Carlos venceu com autoridade!', 7, 8, 1.65, 2.20, 3.80, 1.85, 1.95, '2026-04-26 16:45:44', '2026-04-26 17:45:44', '2026-04-26 19:45:44', 'finished', NULL, 7, 5, 3, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/6b7280\\/ffffff?text=Sinuca+Finished\"}', 0, 1500.00, 12, '2026-04-27 17:45:44', '2026-04-27 17:45:44', NULL, NULL),
(15, 1, 'UFC Head to Head', 'Jon Jones vs Stipe Miocic - O grande confronto!', 9, 10, 1.55, 2.40, NULL, NULL, NULL, '2026-04-29 16:45:44', '2026-04-29 17:45:44', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/dc2626\\/ffffff?text=UFC+Scheduled\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/5\"}', 1, 0.00, 0, '2026-04-27 17:45:44', '2026-04-27 17:45:44', NULL, NULL),
(16, 1, 'UFC Head to Head', 'Poatan nocauteou Adesanya no 2º round!', 11, 12, 1.70, 2.10, NULL, NULL, NULL, '2026-04-24 16:45:44', '2026-04-24 17:45:44', '2026-04-24 18:45:44', 'finished', NULL, 11, 2, 0, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/4b5563\\/ffffff?text=UFC+Finished\"}', 0, 8500.00, 45, '2026-04-27 17:45:44', '2026-04-27 17:45:44', NULL, NULL),
(17, 3, 'Sinuca Head to Head', 'Confronto emocionante entre Igão Parceiro e Maycon de Teixeira!', 3, 4, 1.80, 1.90, 3.50, 1.85, 1.95, '2026-04-27 19:57:38', '2026-04-27 20:57:38', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"banner_image\":\"http:\\/\\/localhost:3000\\/assets\\/images\\/sinuca-game.png\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/2\"}', 1, 0.00, 0, '2026-04-27 17:57:38', '2026-04-27 17:57:38', NULL, NULL),
(18, 2, 'Sinuca Par ou Ímpar', 'Fábio Cabeludo vs Diego Sinuqueiro - Ao vivo!', 5, 6, 2.10, 1.75, 4.00, 1.90, 1.90, '2026-04-27 17:27:38', '2026-04-27 16:57:38', NULL, 'live', NULL, NULL, 3, 2, NULL, NULL, '{\"stream_url\":\"https:\\/\\/www.youtube.com\\/embed\\/live-example\",\"banner_image\":\"http:\\/\\/localhost:3000\\/assets\\/images\\/sinuca-game.png\",\"banner_button_label\":\"Assistir Agora\",\"banner_button_link\":\"https:\\/\\/youtube.com\"}', 1, 0.00, 0, '2026-04-27 17:57:38', '2026-04-27 17:57:38', NULL, NULL),
(19, 3, 'Sinuca Head to Head', 'Carlos venceu com autoridade!', 7, 8, 1.65, 2.20, 3.80, 1.85, 1.95, '2026-04-26 16:57:38', '2026-04-26 17:57:38', '2026-04-26 19:57:38', 'finished', NULL, 7, 5, 3, NULL, NULL, '{\"banner_image\":\"http:\\/\\/localhost:3000\\/assets\\/images\\/sinuca-game.png\"}', 0, 1500.00, 12, '2026-04-27 17:57:38', '2026-04-27 17:57:38', NULL, NULL),
(20, 1, 'UFC Head to Head', 'Jon Jones vs Stipe Miocic - O grande confronto!', 9, 10, 1.55, 2.40, NULL, NULL, NULL, '2026-04-29 16:57:38', '2026-04-29 17:57:38', NULL, 'scheduled', NULL, NULL, 0, 0, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/dc2626\\/ffffff?text=UFC+Scheduled\",\"banner_button_label\":\"Apostar Agora\",\"banner_button_link\":\"\\/matches\\/5\"}', 1, 0.00, 0, '2026-04-27 17:57:38', '2026-04-27 17:57:38', NULL, NULL),
(21, 1, 'UFC Head to Head', 'Poatan nocauteou Adesanya no 2º round!', 11, 12, 1.70, 2.10, NULL, NULL, NULL, '2026-04-24 16:57:38', '2026-04-24 17:57:38', '2026-04-24 18:57:38', 'finished', NULL, 11, 2, 0, NULL, NULL, '{\"banner_image\":\"https:\\/\\/placehold.co\\/600x300\\/4b5563\\/ffffff?text=UFC+Finished\"}', 0, 8500.00, 45, '2026-04-27 17:57:38', '2026-04-27 17:57:38', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2024_01_01_000001_create_sports_table', 1),
(6, '2024_01_01_000002_create_games_table', 1),
(7, '2024_01_01_000003_create_players_table', 1),
(8, '2024_01_01_000004_create_matches_table', 1),
(9, '2024_01_01_000005_create_bets_table', 1),
(10, '2024_01_01_000006_create_transactions_table', 1),
(11, '2024_01_01_000007_add_balance_to_users_table', 1),
(12, '2024_01_01_000008_create_admin_system_tables', 2),
(13, '2024_01_01_000009_add_admin_fields_to_users', 3),
(14, '2024_01_01_000010_add_winner_player_id_to_matches_table', 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `players`
--

CREATE TABLE `players` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `sport_id` bigint(20) UNSIGNED NOT NULL,
  `bio` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `nationality` varchar(3) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stats`)),
  `rating` decimal(6,2) NOT NULL DEFAULT 1000.00,
  `status` enum('active','inactive','retired') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `players`
--

INSERT INTO `players` (`id`, `name`, `slug`, `photo_url`, `sport_id`, `bio`, `birth_date`, `nationality`, `weight`, `height`, `stats`, `rating`, `status`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'mike moreno', 'mike-moreno-69dd93ed3f9ee', 'players/BlgYi7q9rhYaeFSVbmvDQHFit2u0mlmdHHNNxPLH.jpg', 6, 'bom jogador', '1999-12-12', 'BRA', NULL, NULL, NULL, 10.00, 'active', '2026-04-14 01:10:05', '2026-04-14 01:10:05', NULL, NULL),
(2, 'batista', 'batista-69dd94190bf95', NULL, 6, 'bom jogador de bico', '1995-02-25', 'bra', NULL, NULL, NULL, 20.00, 'active', '2026-04-14 01:10:49', '2026-04-14 01:10:49', NULL, NULL),
(3, 'Igão Parceiro', 'igao-parceiro', 'http://localhost:3000/assets/images/jogador1.png', 6, 'Jogador profissional de sinuca há 10 anos, especialista em jogadas de defesa.', NULL, 'BRA', 75.50, NULL, NULL, 4.50, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:43', NULL, NULL),
(4, 'Maycon de Teixeira', 'maycon-teixeira', 'http://localhost:3000/assets/images/jogador2.png', 6, 'Campeão brasileiro de sinuca 2023, estilo agressivo e preciso.', NULL, 'BRA', 72.00, NULL, NULL, 4.80, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:43', NULL, NULL),
(5, 'Fábio Cabeludo', 'fabio-cabeludo', 'http://localhost:3000/assets/images/jogador1.png', 6, 'Conhecido por sua técnica no jogo de par ou ímpar.', NULL, 'BRA', 78.00, NULL, NULL, 4.20, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:43', NULL, NULL),
(6, 'Diego Sinuqueiro', 'diego-sinuqueiro', 'http://localhost:3000/assets/images/jogador2.png', 6, 'Jogador versátil, competiu em campeonatos internacionais.', NULL, 'BRA', 76.00, NULL, NULL, 4.00, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:43', NULL, NULL),
(7, 'Carlos Taco de Ouro', 'carlos-taco-ouro', 'http://localhost:3000/assets/images/jogador1.png', 6, 'Apelidado de Taco de Ouro pela precisão de suas tacadas.', NULL, 'BRA', 74.50, NULL, NULL, 4.70, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:43', NULL, NULL),
(8, 'Rafael Bilhar Master', 'rafael-bilhar', 'http://localhost:3000/assets/images/jogador2.png', 6, 'Especialista em jogos de bilhar, transitando para sinuca.', NULL, 'BRA', 73.00, NULL, NULL, 4.30, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:43', NULL, NULL),
(9, 'Jon Jones', 'jon-jones', 'http://localhost:3000/assets/images/jogador1.png', 1, 'Considerado um dos maiores lutadores da história do MMA.', NULL, 'USA', 120.20, NULL, NULL, 5.00, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:44', NULL, NULL),
(10, 'Stipe Miocic', 'stipe-miocic', 'http://localhost:3000/assets/images/jogador2.png', 1, 'Ex-campeão peso-pesado do UFC com background em boxe.', NULL, 'USA', 110.50, NULL, NULL, 4.60, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:44', NULL, NULL),
(11, 'Alex Poatan', 'alex-poatan', 'http://localhost:3000/assets/images/jogador1.png', 1, 'Campeão peso-pesado e meio-pesado do UFC, poderoso nocauteador.', NULL, 'BRA', 120.50, NULL, NULL, 4.90, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:44', NULL, NULL),
(12, 'Israel Adesanya', 'israel-adesanya', 'http://localhost:3000/assets/images/jogador2.png', 1, 'Ex-campeão meio-pesado do UFC, estilo técnico e preciso.', NULL, 'NGA', 84.00, NULL, NULL, 4.70, 'active', '2026-04-27 16:38:46', '2026-04-27 17:45:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `promotional_banners`
--

CREATE TABLE `promotional_banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `position` enum('home_top','home_middle','games_top','sidebar') NOT NULL DEFAULT 'home_top',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sports`
--

CREATE TABLE `sports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sports`
--

INSERT INTO `sports` (`id`, `name`, `slug`, `icon`, `image`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 'MMA/UFC', 'mma-ufc', 'fa-hand-fist', NULL, 'active', '{\"description\":\"Mixed Martial Arts e Ultimate Fighting Championship\",\"popular\":true}', '2026-04-13 19:08:28', '2026-04-13 19:08:28'),
(2, 'Futebol', 'futebol', 'fa-futbol', NULL, 'active', '{\"description\":\"Futebol nacional e internacional\",\"popular\":true}', '2026-04-13 19:08:28', '2026-04-13 19:08:28'),
(3, 'Basquete', 'basquete', 'fa-basketball', NULL, 'active', '{\"description\":\"NBA, NBB e outras ligas\",\"popular\":false}', '2026-04-13 19:08:28', '2026-04-13 19:08:28'),
(4, 'Tênis', 'tenis', 'fa-baseball', NULL, 'active', '{\"description\":\"ATP, WTA e Grand Slams\",\"popular\":false}', '2026-04-13 19:08:28', '2026-04-13 19:08:28'),
(5, 'Boxe', 'boxe', 'fa-hand-fist', NULL, 'active', '{\"description\":\"Boxe profissional mundial\",\"popular\":false}', '2026-04-13 19:08:28', '2026-04-13 19:08:28'),
(6, 'Sinuca', 'sinuca', 'fa-8ball', NULL, 'active', '{\"description\":\"Jogos de sinuca e bilhar\",\"popular\":true}', '2026-04-13 19:08:28', '2026-04-13 19:08:28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_notifications`
--

CREATE TABLE `system_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `target` enum('all','admins','users') NOT NULL DEFAULT 'all',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_settings`
--

CREATE TABLE `system_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(30) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('deposit','withdraw','bet','win','refund','bonus','commission') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `failure_reason` text DEFAULT NULL,
  `payment_method` enum('pix','bank_transfer','credit_card','system') DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `balance_before` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `document` varchar(20) DEFAULT NULL,
  `pix_key` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','suspended','pending_verification') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deposited` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_bet` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_won` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remember_token` varchar(100) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `birth_date`, `document`, `pix_key`, `is_admin`, `status`, `last_login_at`, `last_login_ip`, `metadata`, `email_verified_at`, `password`, `balance`, `total_deposited`, `total_withdrawn`, `total_bet`, `total_won`, `remember_token`, `two_factor_secret`, `two_factor_enabled`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@forapix.com', '+55 11 99999-9999', NULL, NULL, NULL, 1, 'active', '2026-04-27 21:26:55', '127.0.0.1', '{\"created_by\":\"system\",\"role\":\"super_admin\"}', '2026-04-13 19:08:28', '$2y$12$2IEf0ev7wKoaz2UobQrvb.Nbsv0VpC16cELBEFzumZcBD3D4yy6A.', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, NULL, 0, '2026-04-13 19:08:28', '2026-04-27 21:26:55'),
(2, 'Carlos Silva', 'carlos@demo.com', '+55 11 98888-8888', NULL, NULL, 'carlos@demo.com', 0, 'active', NULL, NULL, '{\"created_by\":\"system\",\"demo_account\":true}', '2026-04-13 19:08:29', '$2y$12$sC33S2.ddEdklOzqMK51K.efNtj7l9O2QD4KwYMDXJ29QbZBXKT.i', 100.00, 100.00, 0.00, 0.00, 0.00, NULL, NULL, 0, '2026-04-13 19:08:29', '2026-04-13 19:08:29'),
(3, 'Maria Santos', 'maria@test.com', '+55 11 98711-1927', NULL, NULL, 'maria@test.com', 0, 'active', NULL, NULL, '{\"created_by\":\"system\",\"test_account\":true}', '2026-04-13 19:08:29', '$2y$12$qD0KEl4jHD.giv3NRKzwDOdDNCSqFbQLHLcuYfOAEfTs589z3UxO.', 50.00, 50.00, 0.00, 0.00, 0.00, NULL, NULL, 0, '2026-04-13 19:08:29', '2026-04-13 19:08:29'),
(4, 'João Oliveira', 'joao@test.com', '+55 11 98336-3084', NULL, NULL, 'joao@test.com', 0, 'active', NULL, NULL, '{\"created_by\":\"system\",\"test_account\":true}', '2026-04-13 19:08:29', '$2y$12$EyC.cyTF5Rivhe7jG9bsGexTc17plfCzFnGiZftG9PqA.TeToQXBy', 200.00, 200.00, 0.00, 0.00, 0.00, NULL, NULL, 0, '2026-04-13 19:08:29', '2026-04-13 19:08:29'),
(5, 'Ana Costa', 'ana@test.com', '+55 11 98612-6617', NULL, NULL, 'ana@test.com', 0, 'active', NULL, NULL, '{\"created_by\":\"system\",\"test_account\":true}', '2026-04-13 19:08:30', '$2y$12$3UqpKJsgx1in2W0428HzpukovbLVhcxlwKgz2ufoGhoRX.g5c1pni', 75.00, 75.00, 0.00, 0.00, 0.00, NULL, NULL, 0, '2026-04-13 19:08:30', '2026-04-13 19:08:30'),
(6, 'Usuário Demo', 'demo@forapix.com', NULL, NULL, NULL, NULL, 0, 'active', NULL, NULL, NULL, NULL, '$2y$12$CCylAHsF19NKxEpNheK10eISDQ6uW6zJ6F4pL5Q4CnN3iEUOGVndi', 500.00, 0.00, 0.00, 0.00, 0.00, NULL, NULL, 0, '2026-04-27 16:38:47', '2026-04-27 16:38:47');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_logs_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `admin_logs_action_created_at_index` (`action`,`created_at`);

--
-- Índices de tabela `bets`
--
ALTER TABLE `bets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bets_bet_id_unique` (`bet_id`),
  ADD KEY `bets_user_id_status_index` (`user_id`,`status`),
  ADD KEY `bets_match_id_status_index` (`match_id`,`status`),
  ADD KEY `bets_status_placed_at_index` (`status`,`placed_at`),
  ADD KEY `bets_bet_id_index` (`bet_id`);

--
-- Índices de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Índices de tabela `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `games_slug_unique` (`slug`),
  ADD KEY `games_sport_id_status_index` (`sport_id`,`status`),
  ADD KEY `games_type_status_index` (`type`,`status`),
  ADD KEY `games_slug_index` (`slug`),
  ADD KEY `games_created_by_foreign` (`created_by`),
  ADD KEY `games_updated_by_foreign` (`updated_by`);

--
-- Índices de tabela `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matches_first_player_id_foreign` (`first_player_id`),
  ADD KEY `matches_second_player_id_foreign` (`second_player_id`),
  ADD KEY `matches_game_id_status_index` (`game_id`,`status`),
  ADD KEY `matches_status_betting_deadline_index` (`status`,`betting_deadline`),
  ADD KEY `matches_featured_status_index` (`featured`,`status`),
  ADD KEY `matches_external_id_external_source_index` (`external_id`,`external_source`),
  ADD KEY `matches_created_by_foreign` (`created_by`),
  ADD KEY `matches_updated_by_foreign` (`updated_by`),
  ADD KEY `matches_winner_player_id_foreign` (`winner_player_id`);

--
-- Índices de tabela `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Índices de tabela `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Índices de tabela `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `players_slug_unique` (`slug`),
  ADD KEY `players_sport_id_status_index` (`sport_id`,`status`),
  ADD KEY `players_rating_index` (`rating`),
  ADD KEY `players_slug_index` (`slug`),
  ADD KEY `players_created_by_foreign` (`created_by`),
  ADD KEY `players_updated_by_foreign` (`updated_by`);

--
-- Índices de tabela `promotional_banners`
--
ALTER TABLE `promotional_banners`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sports_slug_unique` (`slug`),
  ADD KEY `sports_status_index` (`status`),
  ADD KEY `sports_slug_index` (`slug`);

--
-- Índices de tabela `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `system_settings_key_unique` (`key`);

--
-- Índices de tabela `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transactions_transaction_id_unique` (`transaction_id`),
  ADD KEY `transactions_user_id_type_index` (`user_id`,`type`),
  ADD KEY `transactions_status_created_at_index` (`status`,`created_at`),
  ADD KEY `transactions_reference_id_reference_type_index` (`reference_id`,`reference_type`),
  ADD KEY `transactions_transaction_id_index` (`transaction_id`),
  ADD KEY `transactions_external_transaction_id_index` (`external_transaction_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `bets`
--
ALTER TABLE `bets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `games`
--
ALTER TABLE `games`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `players`
--
ALTER TABLE `players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `promotional_banners`
--
ALTER TABLE `promotional_banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sports`
--
ALTER TABLE `sports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `bets`
--
ALTER TABLE `bets`
  ADD CONSTRAINT `bets_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `games_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `games_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_first_player_id_foreign` FOREIGN KEY (`first_player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_second_player_id_foreign` FOREIGN KEY (`second_player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_winner_player_id_foreign` FOREIGN KEY (`winner_player_id`) REFERENCES `players` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `players_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `players_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
