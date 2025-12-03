-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/12/2025 às 22:26
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
-- Banco de dados: `cond_v1`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `cpf`, `telefone`, `email`, `data_cadastro`) VALUES
(1, 'Mariana Silva', '111.222.333-44', '(34) 99111-1111', 'mariana@email.com', '2025-11-28 18:45:05'),
(2, 'Fernanda Costa', '222.333.444-55', '(34) 99222-2222', 'nanda@email.com', '2025-11-28 18:45:05'),
(3, 'Camila Oliveira', '333.444.555-66', '(34) 99333-3333', 'camila@email.com', '2025-11-28 18:45:05'),
(4, 'Larissa Santos', '444.555.666-77', '(34) 99444-4444', 'larissa@email.com', '2025-11-28 18:45:05'),
(5, 'Juliana Lima', '555.666.777-88', '(34) 99555-5555', 'ju@email.com', '2025-11-28 18:45:05'),
(6, 'Patrícia Rocha', '666.777.888-99', '(34) 99666-6666', 'paty@email.com', '2025-11-28 18:45:05'),
(7, 'Teste', '12902155611', '(34) 92000-1138', 'email@email.com', '2025-12-01 17:39:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `condicionais`
--

CREATE TABLE `condicionais` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `data_saida` datetime DEFAULT current_timestamp(),
  `data_prevista_retorno` datetime NOT NULL,
  `status` enum('ABERTO','FINALIZADO','ATRASADO') DEFAULT 'ABERTO',
  `data_finalizacao` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `condicionais`
--

INSERT INTO `condicionais` (`id`, `cliente_id`, `data_saida`, `data_prevista_retorno`, `status`, `data_finalizacao`, `observacoes`) VALUES
(1, 2, '2025-11-27 00:00:00', '2025-11-29 00:00:00', 'ABERTO', NULL, 'Em andamento.'),
(2, 3, '2025-12-25 00:00:00', '2025-12-27 00:00:00', 'FINALIZADO', '2025-12-01 13:39:39', 'Agendado.'),
(3, 3, '2025-12-24 00:00:00', '2025-12-26 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(4, 4, '2025-12-28 00:00:00', '2025-12-30 00:00:00', 'FINALIZADO', '2025-12-01 13:39:28', 'Agendado.'),
(5, 2, '2025-11-20 00:00:00', '2025-11-22 00:00:00', 'FINALIZADO', '2025-12-01 13:40:06', 'Muito atrasado!'),
(6, 5, '2025-12-30 00:00:00', '2026-01-01 00:00:00', 'FINALIZADO', '2025-12-01 13:39:08', 'Agendado.'),
(7, 6, '2025-11-23 00:00:00', '2025-11-25 00:00:00', 'FINALIZADO', '2025-12-01 13:40:00', 'Cliente não retornou contato.'),
(8, 5, '2025-12-13 00:00:00', '2025-12-15 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(9, 5, '2025-11-26 00:00:00', '2025-11-28 00:00:00', 'FINALIZADO', '2025-12-01 13:40:15', 'Em andamento.'),
(10, 4, '2025-12-13 00:00:00', '2025-12-15 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(11, 6, '2025-12-25 00:00:00', '2025-12-27 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(12, 4, '2025-11-20 00:00:00', '2025-11-22 00:00:00', 'FINALIZADO', '2025-11-23 00:00:00', 'Devolvido com sucesso.'),
(13, 2, '2025-12-20 00:00:00', '2025-12-22 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(14, 3, '2025-12-08 00:00:00', '2025-12-10 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(15, 5, '2025-12-26 00:00:00', '2025-12-28 00:00:00', 'FINALIZADO', '2025-12-01 13:39:34', 'Agendado.'),
(16, 3, '2025-12-22 00:00:00', '2025-12-24 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(17, 2, '2025-12-23 00:00:00', '2025-12-25 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(18, 2, '2025-12-02 00:00:00', '2025-12-04 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(19, 6, '2025-12-20 00:00:00', '2025-12-22 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(20, 1, '2025-12-07 00:00:00', '2025-12-09 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(21, 6, '2025-11-29 00:00:00', '2025-12-01 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(22, 6, '2025-12-25 00:00:00', '2025-12-27 00:00:00', 'FINALIZADO', '2025-12-01 13:39:46', 'Agendado.'),
(23, 4, '2025-12-05 00:00:00', '2025-12-07 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(24, 4, '2025-12-17 00:00:00', '2025-12-19 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(25, 5, '2025-11-29 00:00:00', '2025-12-01 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(26, 4, '2025-12-29 00:00:00', '2025-12-31 00:00:00', 'FINALIZADO', '2025-12-01 13:39:15', 'Agendado.'),
(27, 4, '2025-11-28 00:00:00', '2025-11-30 00:00:00', 'ABERTO', NULL, 'Em andamento.'),
(28, 4, '2025-12-25 00:00:00', '2025-12-27 00:00:00', 'FINALIZADO', '2025-12-01 13:39:51', 'Agendado.'),
(29, 1, '2025-12-19 00:00:00', '2025-12-21 00:00:00', 'ABERTO', NULL, 'Agendado.'),
(30, 6, '2025-11-22 00:00:00', '2025-11-24 00:00:00', 'FINALIZADO', '2025-11-26 00:00:00', 'Devolvido com sucesso.'),
(31, 3, '2025-12-01 09:15:10', '2025-12-01 00:00:00', 'ABERTO', NULL, 'Provar');

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `cep` varchar(9) NOT NULL,
  `logradouro` varchar(150) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `enderecos`
--

INSERT INTO `enderecos` (`id`, `cliente_id`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`) VALUES
(1, 1, '38700-000', 'Rua Major Gote', '100', NULL, 'Centro', 'Patos de Minas', 'MG'),
(2, 2, '38700-001', 'Av. Getúlio Vargas', '200', NULL, 'Centro', 'Patos de Minas', 'MG'),
(3, 3, '38700-002', 'Rua Doutor Marcolino', '300', NULL, 'Rosário', 'Patos de Minas', 'MG'),
(4, 4, '38700-003', 'Rua dos Potiguares', '400', NULL, 'Lagoa Grande', 'Patos de Minas', 'MG'),
(5, 5, '38700-004', 'Av. Fátima Porto', '500', NULL, 'Caiçaras', 'Patos de Minas', 'MG'),
(6, 6, '38700-005', 'Rua Padre Caldeira', '600', NULL, 'Centro', 'Patos de Minas', 'MG'),
(7, 7, '38700001', 'Rua Major Gote', '1', '', 'Centro', 'Patos de Minas', 'MG');

-- --------------------------------------------------------

--
-- Estrutura para tabela `entradas_produto`
--

CREATE TABLE `entradas_produto` (
  `id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `data_entrada` datetime DEFAULT current_timestamp(),
  `data_vencimento` date DEFAULT NULL,
  `numero_nota` varchar(50) DEFAULT NULL,
  `numero_nfe` varchar(20) DEFAULT NULL,
  `serie_nfe` varchar(10) DEFAULT NULL,
  `chave_acesso` varchar(44) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status_pagamento` enum('PENDENTE','PAGO','CANCELADO') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `entradas_produto`
--

INSERT INTO `entradas_produto` (`id`, `fornecedor_id`, `data_entrada`, `data_vencimento`, `numero_nota`, `numero_nfe`, `serie_nfe`, `chave_acesso`, `valor_total`, `status_pagamento`, `observacoes`) VALUES
(1, 1, '2025-11-01 10:00:00', NULL, NULL, NULL, NULL, NULL, 1500.00, 'PAGO', 'Coleção Primavera'),
(2, 2, '2025-11-05 14:00:00', NULL, NULL, NULL, NULL, NULL, 2000.00, 'PAGO', 'Jeans e Jaquetas'),
(3, 3, '2025-11-10 09:00:00', NULL, NULL, NULL, NULL, NULL, 3500.00, 'PAGO', 'Linha Festa'),
(4, 4, '2025-11-15 16:00:00', NULL, NULL, NULL, NULL, NULL, 800.00, 'PAGO', 'Reposição Básicos'),
(5, 3, '2025-12-01 09:47:49', '2025-12-01', '', '', '', '', 829.60, 'PENDENTE', 'Teste'),
(6, 1, '2025-12-01 09:48:27', '2025-12-05', '', '', '', '', 874.70, 'PENDENTE', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cnpj_cpf` varchar(18) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome`, `cnpj_cpf`, `telefone`, `email`, `data_cadastro`) VALUES
(1, 'Fashion Brás Atacado', '12.345.678/0001-90', '(11) 99999-1001', 'contato@fashionbras.com', '2025-11-28 18:44:48'),
(2, 'Jeans Premium Factory', '98.765.432/0001-15', '(34) 98888-2002', 'vendas@jeanspremium.com', '2025-11-28 18:44:48'),
(3, 'Elegance Importados', '45.123.456/0001-88', '(41) 97777-3003', 'sac@eleganceimp.com', '2025-11-28 18:44:48'),
(4, 'Tecidos & Cia', '11.222.333/0001-44', '(31) 96666-4004', 'financeiro@tecidoscia.com', '2025-11-28 18:44:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_condicional`
--

CREATE TABLE `itens_condicional` (
  `id` int(11) NOT NULL,
  `condicional_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `status_item` enum('EM_CONDICIONAL','DEVOLVIDO','VENDIDO') DEFAULT 'EM_CONDICIONAL',
  `preco_momento` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `itens_condicional`
--

INSERT INTO `itens_condicional` (`id`, `condicional_id`, `produto_id`, `quantidade`, `status_item`, `preco_momento`) VALUES
(1, 1, 9, 1, 'EM_CONDICIONAL', 79.90),
(2, 1, 5, 1, 'EM_CONDICIONAL', 139.90),
(3, 1, 4, 1, 'EM_CONDICIONAL', 89.90),
(4, 2, 2, 1, 'DEVOLVIDO', 149.90),
(5, 3, 9, 1, 'EM_CONDICIONAL', 79.90),
(6, 4, 6, 1, 'DEVOLVIDO', 99.90),
(7, 5, 8, 1, 'DEVOLVIDO', 229.90),
(8, 6, 1, 1, 'VENDIDO', 220.00),
(9, 6, 6, 1, 'VENDIDO', 99.90),
(10, 6, 7, 1, 'VENDIDO', 119.90),
(11, 7, 9, 1, 'VENDIDO', 79.90),
(12, 8, 9, 1, 'EM_CONDICIONAL', 79.90),
(13, 9, 4, 1, 'VENDIDO', 89.90),
(14, 10, 10, 1, 'EM_CONDICIONAL', 189.90),
(15, 10, 8, 1, 'EM_CONDICIONAL', 229.90),
(16, 11, 10, 1, 'EM_CONDICIONAL', 189.90),
(17, 12, 10, 1, 'DEVOLVIDO', 189.90),
(18, 12, 9, 1, 'DEVOLVIDO', 79.90),
(19, 12, 1, 1, 'DEVOLVIDO', 199.90),
(20, 13, 5, 1, 'EM_CONDICIONAL', 139.90),
(21, 13, 10, 1, 'EM_CONDICIONAL', 189.90),
(22, 14, 10, 1, 'EM_CONDICIONAL', 189.90),
(23, 15, 10, 1, 'VENDIDO', 189.90),
(24, 15, 8, 1, 'VENDIDO', 229.90),
(25, 16, 10, 1, 'EM_CONDICIONAL', 189.90),
(26, 16, 3, 1, 'EM_CONDICIONAL', 289.90),
(27, 16, 7, 1, 'EM_CONDICIONAL', 119.90),
(28, 17, 7, 1, 'EM_CONDICIONAL', 119.90),
(29, 17, 3, 1, 'EM_CONDICIONAL', 289.90),
(30, 18, 10, 1, 'EM_CONDICIONAL', 189.90),
(31, 18, 2, 1, 'EM_CONDICIONAL', 149.90),
(32, 18, 9, 1, 'EM_CONDICIONAL', 79.90),
(33, 19, 8, 1, 'EM_CONDICIONAL', 229.90),
(34, 20, 9, 1, 'EM_CONDICIONAL', 79.90),
(35, 21, 2, 1, 'EM_CONDICIONAL', 149.90),
(36, 21, 10, 1, 'EM_CONDICIONAL', 189.90),
(37, 21, 3, 1, 'EM_CONDICIONAL', 289.90),
(38, 22, 2, 1, 'VENDIDO', 149.90),
(39, 22, 7, 1, 'VENDIDO', 119.90),
(40, 23, 9, 1, 'EM_CONDICIONAL', 79.90),
(41, 24, 10, 1, 'EM_CONDICIONAL', 189.90),
(42, 24, 4, 1, 'EM_CONDICIONAL', 89.90),
(43, 25, 4, 1, 'EM_CONDICIONAL', 89.90),
(44, 26, 7, 1, 'VENDIDO', 119.90),
(45, 26, 9, 1, 'VENDIDO', 79.90),
(46, 26, 1, 1, 'VENDIDO', 199.90),
(47, 27, 3, 1, 'EM_CONDICIONAL', 289.90),
(48, 27, 7, 1, 'EM_CONDICIONAL', 119.90),
(49, 27, 8, 1, 'EM_CONDICIONAL', 229.90),
(50, 28, 7, 1, 'VENDIDO', 119.90),
(51, 29, 2, 1, 'EM_CONDICIONAL', 149.90),
(52, 29, 1, 1, 'EM_CONDICIONAL', 199.90),
(53, 30, 10, 1, 'DEVOLVIDO', 189.90),
(54, 30, 3, 1, 'DEVOLVIDO', 289.90),
(55, 31, 3, 1, 'EM_CONDICIONAL', 289.90),
(56, 31, 9, 1, 'EM_CONDICIONAL', 79.90),
(57, 31, 1, 1, 'EM_CONDICIONAL', 199.90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_entrada`
--

CREATE TABLE `itens_entrada` (
  `id` int(11) NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_custo_momento` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `itens_entrada`
--

INSERT INTO `itens_entrada` (`id`, `entrada_id`, `produto_id`, `quantidade`, `preco_custo_momento`) VALUES
(1, 1, 1, 10, 80.00),
(2, 1, 4, 20, 35.00),
(3, 2, 2, 15, 60.00),
(4, 2, 6, 10, 45.00),
(5, 2, 10, 8, 85.00),
(6, 3, 3, 10, 120.00),
(7, 3, 5, 12, 55.00),
(8, 3, 8, 10, 90.00),
(9, 4, 7, 10, 40.00),
(10, 4, 9, 15, 30.00),
(11, 5, 3, 5, 35.95),
(12, 5, 1, 10, 49.99),
(13, 5, 10, 5, 29.99),
(14, 6, 7, 15, 19.98),
(15, 6, 4, 20, 28.75);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `tamanho` varchar(10) DEFAULT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `preco_custo` decimal(10,2) DEFAULT 0.00,
  `preco` decimal(10,2) NOT NULL,
  `estoque_loja` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `imagem`, `tamanho`, `cor`, `preco_custo`, `preco`, `estoque_loja`, `created_at`) VALUES
(1, 'Vestido Longo Floral', 'Viscose, estampa primavera', NULL, 'M', 'Estampado', 49.99, 199.90, 19, '2025-11-28 18:44:48'),
(2, 'Calça Jeans Skinny', 'Cintura alta, lavagem escura', NULL, '38', 'Azul Marinho', 60.00, 149.90, 16, '2025-11-28 18:44:48'),
(3, 'Blazer Alfaiataria', 'Corte moderno, botões dourados', NULL, 'P', 'Off-White', 35.95, 289.90, 14, '2025-11-28 18:44:48'),
(4, 'Cropped Renda', 'Detalhes em guipir', NULL, 'U', 'Preto', 28.75, 89.90, 40, '2025-11-28 18:44:48'),
(5, 'Saia Midi Plissada', 'Tecido leve e fluido', NULL, 'G', 'Verde Militar', 55.00, 139.90, 12, '2025-11-28 18:44:48'),
(6, 'Shorts Linho', 'Acompanha cinto de corda', NULL, '40', 'Beige', 45.00, 99.90, 11, '2025-11-28 18:44:48'),
(7, 'Blusa Seda', 'Alça fina básica', NULL, 'P', 'Branco', 19.98, 119.90, 25, '2025-11-28 18:44:48'),
(8, 'Macacão Pantacourt', 'Tecido crepe alfaiataria', NULL, 'M', 'Preto', 90.00, 229.90, 11, '2025-11-28 18:44:48'),
(9, 'Body Estampado', 'Decote V profundo', NULL, 'U', 'Tropical', 30.00, 79.90, 14, '2025-11-28 18:44:48'),
(10, 'Jaqueta Jeans', 'Oversized destroyed', NULL, 'G', 'Jeans Claro', 29.99, 189.90, 13, '2025-11-28 18:44:48'),
(11, 'Produto Teste', 'Teste', NULL, 'M', 'Preto', 49.98, 120.00, 10, '2025-12-01 17:40:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `foto` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `login`, `senha`, `nivel`, `foto`, `data_cadastro`) VALUES
(1, 'GEO Clothing', 'geo', '$2y$10$39cCFjaR/jX5GD39T9CyTeq68jnStKfiasMjr42sgfZaXTrpgG08O', 'usuario', '1_1764622142.jpeg', '2025-11-28 18:42:57'),
(2, 'Administrador', 'admin', '$2y$10$DpvLyqeoUx9R3H0jsA59cOO6Ay5qge6.GftIg4rxyXp28aNdBQ9Be', 'admin', '2_1764621984.jpeg', '2025-11-28 18:44:26'),
(3, 'teste', 'teste', '$2y$10$GMT17RdrXhZT6rKa0TycVO1MQ8MJRoUe/vI3zpGm79KdxCOOk8kPC', 'usuario', '692dfe5b26ac2.png', '2025-12-01 17:45:15');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `condicionais`
--
ALTER TABLE `condicionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `entradas_produto`
--
ALTER TABLE `entradas_produto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj_cpf` (`cnpj_cpf`);

--
-- Índices de tabela `itens_condicional`
--
ALTER TABLE `itens_condicional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `condicional_id` (`condicional_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `itens_entrada`
--
ALTER TABLE `itens_entrada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entrada_id` (`entrada_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `condicionais`
--
ALTER TABLE `condicionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `entradas_produto`
--
ALTER TABLE `entradas_produto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `itens_condicional`
--
ALTER TABLE `itens_condicional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de tabela `itens_entrada`
--
ALTER TABLE `itens_entrada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `condicionais`
--
ALTER TABLE `condicionais`
  ADD CONSTRAINT `condicionais_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `enderecos`
--
ALTER TABLE `enderecos`
  ADD CONSTRAINT `enderecos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `entradas_produto`
--
ALTER TABLE `entradas_produto`
  ADD CONSTRAINT `entradas_produto_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`);

--
-- Restrições para tabelas `itens_condicional`
--
ALTER TABLE `itens_condicional`
  ADD CONSTRAINT `itens_condicional_ibfk_1` FOREIGN KEY (`condicional_id`) REFERENCES `condicionais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_condicional_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `itens_entrada`
--
ALTER TABLE `itens_entrada`
  ADD CONSTRAINT `itens_entrada_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_produto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_entrada_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
