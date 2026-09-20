-- Esquema de Banco de Dados para ebill (Mini ERP)
CREATE DATABASE IF NOT EXISTS `ebill` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ebill`;

-- 1. Tabela de Configuração da Empresa & SMTP
CREATE TABLE IF NOT EXISTS `configuracao_empresa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_fantasia` VARCHAR(255) NOT NULL DEFAULT 'eBill Soluções ERP',
  `razao_social` VARCHAR(255) NOT NULL DEFAULT 'eBill Sistemas Ltda',
  `cnpj` VARCHAR(20) NOT NULL DEFAULT '12.345.678/0001-90',
  `email` VARCHAR(100) NOT NULL DEFAULT 'contato@ebill.com.br',
  `telefone` VARCHAR(30) DEFAULT '(11) 3333-4444',
  `celular` VARCHAR(30) DEFAULT '(11) 99999-8888',
  `cep` VARCHAR(10) DEFAULT '01001-000',
  `endereco` VARCHAR(255) DEFAULT 'Praça da Sé',
  `numero` VARCHAR(20) DEFAULT '100',
  `complemento` VARCHAR(100) DEFAULT 'Bloco A - Sala 301',
  `bairro` VARCHAR(100) DEFAULT 'Centro',
  `cidade` VARCHAR(100) DEFAULT 'São Paulo',
  `uf` VARCHAR(2) DEFAULT 'SP',
  `chave_pix` VARCHAR(100) DEFAULT '12.345.678/0001-90',
  `observacoes_padrao` TEXT NULL,
  `smtp_host` VARCHAR(100) DEFAULT 'localhost',
  `smtp_porta` INT DEFAULT 587,
  `smtp_usuario` VARCHAR(100) DEFAULT NULL,
  `smtp_senha` VARCHAR(255) DEFAULT NULL,
  `smtp_seguranca` ENUM('none', 'tls', 'ssl') DEFAULT 'tls',
  `email_remetente` VARCHAR(100) DEFAULT 'nao-responda@ebill.com.br',
  `nome_remetente` VARCHAR(100) DEFAULT 'eBill ERP System'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert empresa inicial se vazia
INSERT INTO `configuracao_empresa` (`id`, `nome_fantasia`, `razao_social`, `cnpj`, `email`, `telefone`, `celular`, `cep`, `endereco`, `numero`, `bairro`, `cidade`, `uf`, `chave_pix`, `observacoes_padrao`)
SELECT 1, 'eBill Soluções ERP', 'eBill Tecnologia & Sistemas Ltda', '12.345.678/0001-90', 'contato@ebill.com.br', '(11) 3333-4444', '(11) 98888-7777', '01001-000', 'Av. Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', '12.345.678/0001-90', 'Obrigado pela preferência! Em caso de dúvidas sobre este documento, entre em contato conosco.'
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 2. Tabela de Usuários do Sistema
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('root', 'admin', 'operador') NOT NULL DEFAULT 'admin',
  `token_recuperacao` VARCHAR(64) DEFAULT NULL,
  `expiracao_token` DATETIME DEFAULT NULL,
  `status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir / Garantir Usuário Root Mestre (root / Nv32125)
INSERT INTO `usuarios` (`id`, `usuario`, `nome`, `email`, `senha`, `perfil`, `status`)
VALUES (1, 'root', 'Administrador Root', 'root@ebill.com.br', '$2y$10$sPpnN8Uc89HGjm15xzTg1O0V1qmZgg36kENw4NAlk5jkwTcliSoeq', 'root', 'Ativo')
ON DUPLICATE KEY UPDATE 
  `usuario` = 'root',
  `senha` = '$2y$10$sPpnN8Uc89HGjm15xzTg1O0V1qmZgg36kENw4NAlk5jkwTcliSoeq',
  `perfil` = 'root',
  `status` = 'Ativo';

-- 3. Tabela de Clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_pessoa` ENUM('Física', 'Jurídica') DEFAULT 'Física',
  `nome` VARCHAR(255) NOT NULL,
  `cpf_cnpj` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `telefone` VARCHAR(30) DEFAULT NULL,
  `celular` VARCHAR(30) DEFAULT NULL,
  `cep` VARCHAR(10) DEFAULT NULL,
  `endereco` VARCHAR(255) DEFAULT NULL,
  `numero` VARCHAR(20) DEFAULT NULL,
  `complemento` VARCHAR(100) DEFAULT NULL,
  `bairro` VARCHAR(100) DEFAULT NULL,
  `cidade` VARCHAR(100) DEFAULT NULL,
  `uf` VARCHAR(2) DEFAULT NULL,
  `status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
  `observacoes` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Produtos e Serviços
CREATE TABLE IF NOT EXISTS `produtos_servicos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_sku` VARCHAR(50) NOT NULL UNIQUE,
  `nome` VARCHAR(255) NOT NULL,
  `tipo` ENUM('produto', 'servico') DEFAULT 'produto',
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estoque` INT NOT NULL DEFAULT 0,
  `estoque_minimo` INT NOT NULL DEFAULT 5,
  `descricao` TEXT NULL,
  `status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabela de Faturas
CREATE TABLE IF NOT EXISTS `faturas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_fatura` VARCHAR(50) NOT NULL UNIQUE,
  `cliente_id` INT NOT NULL,
  `data_emissao` DATE NOT NULL,
  `data_vencimento` DATE NOT NULL,
  `data_pagamento` DATE DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pendente', 'Paga', 'Atrasada', 'Cancelada') DEFAULT 'Pendente',
  `forma_pagamento` VARCHAR(50) DEFAULT 'Pix',
  `observacoes` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de Itens da Fatura
CREATE TABLE IF NOT EXISTS `fatura_itens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fatura_id` INT NOT NULL,
  `item_id` INT DEFAULT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `tipo` ENUM('produto', 'servico') DEFAULT 'produto',
  `quantidade` INT NOT NULL DEFAULT 1,
  `preco_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`fatura_id`) REFERENCES `faturas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `produtos_servicos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabela de Recibos
CREATE TABLE IF NOT EXISTS `recibos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_recibo` VARCHAR(50) NOT NULL UNIQUE,
  `fatura_id` INT DEFAULT NULL,
  `cliente_id` INT NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_recibo` DATE NOT NULL,
  `referente_a` TEXT NOT NULL,
  `forma_pagamento` VARCHAR(50) DEFAULT 'Pix',
  `hash_autenticacao` VARCHAR(64) DEFAULT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`fatura_id`) REFERENCES `faturas`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabela de Lançamentos Financeiros (Livro Razão / Gestão Financeira)
CREATE TABLE IF NOT EXISTS `lancamentos_financeiros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` ENUM('receita', 'despesa') NOT NULL DEFAULT 'receita',
  `descricao` VARCHAR(255) NOT NULL,
  `categoria` VARCHAR(100) NOT NULL DEFAULT 'Geral',
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_vencimento` DATE NOT NULL,
  `data_pagamento` DATE DEFAULT NULL,
  `status` ENUM('Pendente', 'Pago', 'Cancelado') DEFAULT 'Pago',
  `fatura_id` INT DEFAULT NULL,
  `cliente_id` INT DEFAULT NULL,
  `observacoes` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`fatura_id`) REFERENCES `faturas`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados demonstrativos iniciais
INSERT INTO `clientes` (`id`, `tipo_pessoa`, `nome`, `cpf_cnpj`, `email`, `celular`, `cidade`, `uf`, `status`) VALUES
(1, 'Jurídica', 'Tech Brasil Distribuidora Ltda', '11.222.333/0001-44', 'compras@techbrasil.com.br', '(11) 97777-6666', 'São Paulo', 'SP', 'Ativo'),
(2, 'Física', 'Carlos Eduardo Silva', '123.456.789-00', 'carlos.silva@email.com', '(21) 98888-5555', 'Rio de Janeiro', 'RJ', 'Ativo'),
(3, 'Jurídica', 'Mercado & Cia Comercio S.A.', '22.333.444/0001-55', 'financeiro@mercadocia.com.br', '(31) 99111-2222', 'Belo Horizonte', 'MG', 'Ativo')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `produtos_servicos` (`id`, `codigo_sku`, `nome`, `tipo`, `preco`, `estoque`, `estoque_minimo`, `descricao`) VALUES
(1, 'PROD-001', 'Monitor LED 24 Full HD', 'produto', 750.00, 15, 3, 'Monitor 24 polegadas HDMI/DisplayPort'),
(2, 'PROD-002', 'Teclado Mecanico RGB', 'produto', 280.00, 25, 5, 'Teclado mecânico com switches azuis'),
(3, 'SERV-001', 'Consultoria e Suporte TI (Hora)', 'servico', 150.00, 0, 0, 'Serviço de suporte técnico especializado por hora'),
(4, 'SERV-002', 'Desenvolvimento de Website Responsivo', 'servico', 2500.00, 0, 0, 'Desenvolvimento web completo com painel administrativo')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `faturas` (`id`, `numero_fatura`, `cliente_id`, `data_emissao`, `data_vencimento`, `data_pagamento`, `subtotal`, `desconto`, `valor_total`, `status`, `forma_pagamento`, `observacoes`) VALUES
(1, 'FAT-2026-0001', 1, CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 2 DAY, CURDATE() - INTERVAL 2 DAY, 1500.00, 50.00, 1450.00, 'Paga', 'Pix', 'Fatura referente a serviços de consultoria e equipamento'),
(2, 'FAT-2026-0002', 2, CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 10 DAY, NULL, 750.00, 0.00, 750.00, 'Pendente', 'Boleto', 'Compra de monitor'),
(3, 'FAT-2026-0003', 3, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 5 DAY, NULL, 2500.00, 100.00, 2400.00, 'Atrasada', 'Transferencia', 'Projeto web')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `fatura_itens` (`id`, `fatura_id`, `item_id`, `descricao`, `tipo`, `quantidade`, `preco_unitario`, `subtotal`) VALUES
(1, 1, 3, 'Consultoria e Suporte TI (Hora)', 'servico', 10, 150.00, 1500.00),
(2, 2, 1, 'Monitor LED 24 Full HD', 'produto', 1, 750.00, 750.00),
(3, 3, 4, 'Desenvolvimento de Website Responsivo', 'servico', 1, 2500.00, 2500.00)
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `recibos` (`id`, `numero_recibo`, `fatura_id`, `cliente_id`, `valor`, `data_recibo`, `referente_a`, `forma_pagamento`, `hash_autenticacao`) VALUES
(1, 'REC-2026-0001', 1, 1, 1450.00, CURDATE() - INTERVAL 2 DAY, 'Quitação da Fatura FAT-2026-0001 referente a 10h de Consultoria TI', 'Pix', SHA2(CONCAT('REC-2026-0001', NOW()), 256))
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `lancamentos_financeiros` (`id`, `tipo`, `descricao`, `categoria`, `valor`, `data_vencimento`, `data_pagamento`, `status`, `fatura_id`, `cliente_id`) VALUES
(1, 'receita', 'Recebimento Fatura FAT-2026-0001', 'Vendas', 1450.00, CURDATE() - INTERVAL 2 DAY, CURDATE() - INTERVAL 2 DAY, 'Pago', 1, 1),
(2, 'despesa', 'Aluguel do Escritório', 'Infraestrutura', 1200.00, CURDATE() - INTERVAL 15 DAY, CURDATE() - INTERVAL 15 DAY, 'Pago', NULL, NULL),
(3, 'despesa', 'Internet Fibra Óptica', 'Serviços', 250.00, CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 5 DAY, 'Pago', NULL, NULL),
(4, 'receita', 'Previsão de Recebimento FAT-2026-0002', 'Vendas', 750.00, CURDATE() + INTERVAL 10 DAY, NULL, 'Pendente', 2, 2)
ON DUPLICATE KEY UPDATE `id`=`id`;
