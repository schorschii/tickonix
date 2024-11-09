-- phpMyAdmin SQL Dump
-- version 5.2.1deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 21. Okt 2024 um 13:03
-- Server-Version: 10.11.6-MariaDB-0+deb12u1
-- PHP-Version: 8.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `sandbox_tickets`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event`
--

CREATE TABLE `event` (
  `id` varchar(100) NOT NULL,
  `title` text NOT NULL,
  `max` int(11) DEFAULT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `location` text NOT NULL,
  `voucher_only` tinyint(4) NOT NULL DEFAULT 0,
  `tickets_per_email` int(11) NOT NULL DEFAULT 1,
  `reservation_start` datetime DEFAULT NULL,
  `reservation_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `setting`
--

CREATE TABLE `setting` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ticket`
--

CREATE TABLE `ticket` (
  `code` varchar(100) NOT NULL,
  `event_id` varchar(100) NOT NULL,
  `email` text NOT NULL,
  `revoked` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `token` text NOT NULL,
  `voucher_code` varchar(100) DEFAULT NULL,
  `checked_in` timestamp NULL DEFAULT NULL,
  `checked_out` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `voucher`
--

CREATE TABLE `voucher` (
  `code` varchar(100) NOT NULL,
  `event_id` varchar(100) DEFAULT NULL,
  `valid_amount` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`key`);

--
-- Indizes für die Tabelle `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`code`),
  ADD KEY `fk_ticket_voucher` (`voucher_code`);

--
-- Indizes für die Tabelle `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`code`),
  ADD KEY `fk_voucher_event` (`event_id`);

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `fk_ticket_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ticket_voucher` FOREIGN KEY (`voucher_code`) REFERENCES `voucher` (`code`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints der Tabelle `voucher`
--
ALTER TABLE `voucher`
  ADD CONSTRAINT `fk_voucher_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
