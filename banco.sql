-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/10/2026 às 20:04
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `banco`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_tentativas`
--

CREATE TABLE `login_tentativas` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `bairro` varchar(120) NOT NULL,
  `endereco` varchar(200) NOT NULL,
  `descricao` text NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `status` enum('pendente','em_analise','resolvido') NOT NULL DEFAULT 'pendente',
  `recado_adm` text DEFAULT NULL,
  `recado_atualizado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ocorrencias`
--

INSERT INTO `ocorrencias` (`id`, `usuario_id`, `categoria`, `bairro`, `endereco`, `descricao`, `foto_path`, `status`, `recado_adm`, `recado_atualizado_em`, `criado_em`) VALUES
(4, 1, 'Iluminação pública', 'Vila Norte', 'rua bertasso, 31', 'cncnwjnjwenjnc', NULL, 'pendente', NULL, NULL, '2026-10-16 14:35:47'),
(5, 1, 'Descarte irregular de lixo', 'centro', 'rua roberto, 33', 'wdqdDD3D2Dsss', NULL, 'pendente', NULL, NULL, '2026-10-16 14:36:21'),
(6, 1, 'Iluminação pública', 'vila sul', 'rua alberto, 211', 'cwececec3ec', NULL, 'pendente', NULL, NULL, '2026-10-16 14:36:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencia_excluida`
--

CREATE TABLE `ocorrencia_excluida` (
  `id` int(11) NOT NULL,
  `ocorrencia_id_original` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `usuario_nome` varchar(150) NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `bairro` varchar(120) NOT NULL,
  `endereco` varchar(200) NOT NULL,
  `descricao` text NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `status_no_momento` enum('pendente','em_analise','resolvido') NOT NULL,
  `criado_em_original` datetime NOT NULL,
  `excluido_por_id` int(11) NOT NULL,
  `excluido_por_nome` varchar(150) NOT NULL,
  `excluido_por_tipo` enum('cidadao','admin') NOT NULL,
  `excluido_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ocorrencia_excluida`
--

INSERT INTO `ocorrencia_excluida` (`id`, `ocorrencia_id_original`, `usuario_id`, `usuario_nome`, `categoria`, `bairro`, `endereco`, `descricao`, `foto_path`, `status_no_momento`, `criado_em_original`, `excluido_por_id`, `excluido_por_nome`, `excluido_por_tipo`, `excluido_em`) VALUES
(1, 1, 1, 'Katatau', 'Vazamento de água', 'centro', 'pinheiro, 302', 'Torneira com goteira', NULL, 'resolvido', '2026-08-21 14:00:30', 2, 'Administrador', 'admin', '2026-09-28 17:45:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencia_historico`
--

CREATE TABLE `ocorrencia_historico` (
  `id` int(11) NOT NULL,
  `ocorrencia_id` int(11) NOT NULL,
  `ocorrencia_categoria` varchar(80) NOT NULL,
  `status_anterior` enum('pendente','em_analise','resolvido') DEFAULT NULL,
  `status_novo` enum('pendente','em_analise','resolvido') NOT NULL,
  `recado` text DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_nome` varchar(150) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ocorrencia_historico`
--

INSERT INTO `ocorrencia_historico` (`id`, `ocorrencia_id`, `ocorrencia_categoria`, `status_anterior`, `status_novo`, `recado`, `admin_id`, `admin_nome`, `criado_em`) VALUES
(1, 1, 'Vazamento de água', 'pendente', 'resolvido', 'Equipe de manutenção já está indo ao local.', 2, 'Administrador', '2026-09-24 18:35:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `aceite_lgpd` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('cidadao','admin') NOT NULL DEFAULT 'cidadao'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `aceite_lgpd`, `criado_em`, `tipo`) VALUES
(1, 'Katatau', 'a@gmail.com', '$2y$10$dFq/vQxTjxw3nIt5IGt0F.Vhqla1v10XWyVRFYoYuadnkxSRK361G', 1, '2026-08-16 18:05:19', 'cidadao'),
(2, 'Administrador', 'adm@gmail.com', '$2y$10$WvXbNEbIsvI1YkL22xFiAOCrS1q0R5EQIG84ATV4ywQoACc9vPaUu', 1, '2026-08-23 18:11:15', 'admin');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `login_tentativas`
--
ALTER TABLE `login_tentativas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_data` (`email`,`criado_em`),
  ADD KEY `idx_ip_data` (`ip`,`criado_em`);

--
-- Índices de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `ocorrencia_excluida`
--
ALTER TABLE `ocorrencia_excluida`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ocorrencia_id_original` (`ocorrencia_id_original`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `ocorrencia_historico`
--
ALTER TABLE `ocorrencia_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ocorrencia_id` (`ocorrencia_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `login_tentativas`
--
ALTER TABLE `login_tentativas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `ocorrencia_excluida`
--
ALTER TABLE `ocorrencia_excluida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `ocorrencia_historico`
--
ALTER TABLE `ocorrencia_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD CONSTRAINT `ocorrencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
