<?php
/**
 * Sidebar Lateral - AdminLTE 3 / Bootstrap 5
 * eBill Mini ERP Web
 */
$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');
$usuario_sidebar = usuario_logado();
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link d-flex align-items-center">
        <div class="bg-primary text-white rounded me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fas fa-file-invoice-dollar fs-5"></i>
        </div>
        <span class="brand-text font-weight-bold fs-4">eBill <small class="fs-6 text-white-50">ERP</small></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- User Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center border-bottom border-secondary">
            <div class="image">
                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
            <div class="info ms-2">
                <a href="#" class="d-block text-light fw-semibold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($usuario_sidebar['nome'] ?? 'Usuário') ?></a>
                <span class="badge bg-primary text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($usuario_sidebar['perfil'] ?? 'admin') ?></span>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-header text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Visão Geral</li>
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= $pagina_atual == 'index' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard Financeiro</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-muted fw-bold mt-2" style="font-size: 0.7rem; letter-spacing: 1px;">Gestão Operacional</li>
                <li class="nav-item">
                    <a href="clientes.php" class="nav-link <?= in_array($pagina_atual, ['clientes', 'cliente_form', 'cliente_detalhes']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Clientes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="produtos_servicos.php" class="nav-link <?= in_array($pagina_atual, ['produtos_servicos', 'produto_form']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-boxes-packing"></i>
                        <p>Produtos e Serviços</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-muted fw-bold mt-2" style="font-size: 0.7rem; letter-spacing: 1px;">Vendas & Faturamento</li>
                <li class="nav-item">
                    <a href="faturas.php" class="nav-link <?= in_array($pagina_atual, ['faturas', 'fatura_form', 'fatura_detalhes']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>Faturas & Cobranças</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="recibos.php" class="nav-link <?= in_array($pagina_atual, ['recibos', 'recibo_form']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-receipt"></i>
                        <p>Recibos PDF</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-muted fw-bold mt-2" style="font-size: 0.7rem; letter-spacing: 1px;">Financeiro</li>
                <li class="nav-item">
                    <a href="financeiro.php" class="nav-link <?= $pagina_atual == 'financeiro' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cash-register"></i>
                        <p>Livro Caixa / Fluxo</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-muted fw-bold mt-2" style="font-size: 0.7rem; letter-spacing: 1px;">Sistema</li>
                <?php if (is_root() || ($usuario_sidebar['perfil'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a href="usuarios.php" class="nav-link <?= in_array($pagina_atual, ['usuarios', 'usuario_form']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Usuários do Sistema</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="configuracoes.php" class="nav-link <?= $pagina_atual == 'configuracoes' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Configurações Empresa</p>
                    </a>
                </li>

                <li class="nav-item mt-3">
                    <a href="logout.php" class="nav-link text-danger border border-danger border-opacity-25 rounded">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Sair do Sistema</p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
