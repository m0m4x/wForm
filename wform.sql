-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Dic 14, 2015 alle 21:45
-- Versione del server: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wform`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `form`
--

CREATE TABLE IF NOT EXISTS `form` (
  `id_form` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `created` timestamp NOT NULL,
  PRIMARY KEY (`id_form`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `form`
--

INSERT INTO `form` (`id_form`, `data`, `created`) VALUES
('0tgpD', '[{"name":"optionsRadios","value":"option2"},{"name":"optionsCheckbox","value":"option1"},{"name":"optionsCheckbox","value":"option2"},{"name":"txt1","value":"ghjgfjaaad"},{"name":"placeholder","value":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"},{"name":"readonly","value":""},{"name":"regular1","value":"a"},{"name":"regular2","value":"aaa"},{"name":"regular3","value":""},{"name":"labelfor","value":"asdmhhh"}]', '2015-12-12 21:07:21'),
('12', '21', '2015-12-12 20:36:00'),
('5TxfI', '[{"name":"optionsRadios","value":"option1"},{"name":"optionsCheckbox","value":"option1"},{"name":"txt1","value":""},{"name":"placeholder","value":""},{"name":"readonly","value":"read only"},{"name":"regular1","value":""},{"name":"regular2","value":"http://"},{"name":"regular3","value":"hhh"},{"name":"labelfor","value":""}]', '2015-12-12 21:55:03'),
('7XkMb', '[{"name":"optionsRadios","value":"option1"},{"name":"optionsCheckbox","value":"option1"},{"name":"txt1","value":"sasd"},{"name":"placeholder","value":"saaa"},{"name":"readonly","value":"read only"},{"name":"regular1","value":"asdaaaaa"},{"name":"regular2","value":"http://"},{"name":"regular3","value":"asasas"},{"name":"labelfor","value":""}]', '2015-12-12 22:00:40'),
('fAuLe', '[{"name":"optionsRadios","value":"option1"},{"name":"optionsCheckbox","value":"option1"},{"name":"txt1","value":""},{"name":"placeholder","value":""},{"name":"readonly","value":"read only"},{"name":"regular1","value":""},{"name":"regular2","value":"http://"},{"name":"regular3","value":""},{"name":"labelfor","value":""}]', '2015-12-12 21:53:14'),
('jGEXx', '[{"name":"optionsRadios","value":"option2"},{"name":"optionsCheckbox","value":"option1"},{"name":"optionsCheckbox","value":"option2"},{"name":"optionsCheckbox","value":"option3"},{"name":"txt1","value":"xgfbxb"},{"name":"placeholder","value":"xcvbxb"},{"name":"readonly","value":"read only"},{"name":"regular1","value":"xcvbxc"},{"name":"regular2","value":"http://xcvbvc"},{"name":"regular3","value":"xcvxcv"},{"name":"labelfor","value":"bxcbxcbcb"}]', '2015-12-12 20:52:33'),
('P2MXl', '[{"name":"optionsRadios","value":"option1"},{"name":"optionsCheckbox","value":"option1"},{"name":"txt1","value":""},{"name":"placeholder","value":""},{"name":"readonly","value":"read only"},{"name":"regular1","value":""},{"name":"regular2","value":"http://"},{"name":"regular3","value":""},{"name":"labelfor","value":""}]', '2015-12-12 21:53:42'),
('zrMP6', '[{"name":"optionsRadios","value":"option1"},{"name":"optionsCheckbox","value":"option1"},{"name":"txt1","value":"sdfsdfsdf"},{"name":"placeholder","value":" nnn"},{"name":"readonly","value":"read only"},{"name":"regular1","value":"asdasasds"},{"name":"regular2","value":"http://sdasdasdn"},{"name":"regular3","value":"asdasdat"},{"name":"labelfor","value":"asd"}]', '2015-12-12 21:56:43');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
