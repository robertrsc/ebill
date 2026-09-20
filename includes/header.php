<?php
/**
 * Cabeçalho e Layout Topbar - AdminLTE 3 / Bootstrap 5
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/../config/db.php';

// Exige autenticação para todas as páginas internas
checar_login();

$empresa = get_empresa_info();
$usuario_atual = usuario_logado();
$flash = get_flash_message();

$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>eBill Mini ERP</title>

    <!-- Google Font: Source Sans Pro & Outfit -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Source+Sans+Pro:wght@300;400;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE 3.2 Theme CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            font-family: 'Outfit', 'Source Sans Pro', sans-serif;
            background-color: #f4f6f9;
        }
        .main-header {
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .brand-link {
            background-color: #1e293b !important;
            border-bottom: 1px solid #334155 !important;
            padding: 0.8125rem 1rem !important;
        }
        .brand-text {
            font-weight: 700 !important;
            letter-spacing: 0.5px;
            color: #38bdf8 !important;
        }
        .main-sidebar {
            background-color: #0f172a !important;
        }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            border-radius: 8px;
        }
        .nav-sidebar .nav-link {
            border-radius: 8px;
            margin-bottom: 4px;
            color: #94a3b8;
        }
        .nav-sidebar .nav-link:hover {
            color: #f8fafc;
            background-color: rgba(255, 255, 255, 0.05);
        }
        .card-primary.card-outline {
            border-top: 3px solid #0284c7;
        }
        .small-box {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .small-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .small-box .icon {
            font-size: 65px;
            top: 5px;
            right: 15px;
            opacity: 0.2;
        }
        .badge-status-paga, .badge-status-pago {
            background-color: #10b981;
            color: #fff;
        }
        .badge-status-pendente {
            background-color: #f59e0b;
            color: #fff;
        }
        .badge-status-atrasada {
            background-color: #ef4444;
            color: #fff;
        }
        .badge-status-cancelada, .badge-status-cancelado {
            background-color: #6b7280;
            color: #fff;
        }
        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar Topo -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Esquerda navbar -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link"><i class="fas fa-chart-line text-primary me-1"></i> Dashboard</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="fatura_form.php" class="nav-link text-success fw-bold"><i class="fas fa-plus-circle me-1"></i> Nova Fatura</a>
            </li>
        </ul>

        <!-- Direita navbar -->
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown me-3">
                <a class="nav-link d-flex align-items-center" data-bs-toggle="dropdown" href="#">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-weight: bold;">
                        <i class="fas fa-user-circle fs-5"></i>
                    </div>
                    <span class="d-none d-md-inline fw-semibold text-secondary me-1"><?= htmlspecialchars($usuario_atual['nome']) ?></span>
                    <span class="badge bg-dark text-info text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($usuario_atual['perfil']) ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 240px;">
                    <div class="dropdown-header text-center py-3 bg-light border-bottom">
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($usuario_atual['nome']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($usuario_atual['email']) ?></small><br>
                        <span class="badge bg-secondary text-uppercase mt-1"><?= htmlspecialchars($usuario_atual['perfil']) ?></span>
                    </div>
                    <?php if (is_root()): ?>
                        <a href="usuarios.php" class="dropdown-item py-2"><i class="fas fa-users-cog me-2 text-primary"></i> Usuários do Sistema</a>
                    <?php endif; ?>
                    <a href="configuracoes.php" class="dropdown-item py-2"><i class="fas fa-cog me-2 text-primary"></i> Configurações da Empresa</a>
                    <div class="dropdown-divider my-0"></div>
                    <a href="logout.php" class="dropdown-item py-2 text-danger fw-bold"><i class="fas fa-sign-out-alt me-2"></i> Sair do Sistema</a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Mensagens Flash -->
    <?php if ($flash): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $flash['tipo'] === 'danger' ? 'error' : $flash['tipo'] ?>',
                title: '<?= $flash['tipo'] === 'success' ? 'Sucesso!' : ($flash['tipo'] === 'danger' ? 'Atenção' : 'Aviso') ?>',
                text: '<?= htmlspecialchars($flash['texto']) ?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000
            });
        });
    </script>
    <?php endif; ?>
