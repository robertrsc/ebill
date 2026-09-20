<?php
/**
 * Tela de Autenticação / Login
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/config/db.php';

// Se já estiver logado, redireciona para o Dashboard
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = get_db_connection();
$empresa = get_empresa_info();
$flash = get_flash_message();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $login_input = trim($_POST['usuario_email'] ?? '');
    $senha_input = $_POST['senha'] ?? '';

    if (empty($login_input) || empty($senha_input)) {
        set_flash_message('danger', 'Preencha o usuário/e-mail e a senha.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (usuario = ? OR email = ?) AND status = 'Ativo' LIMIT 1");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha_input, $user['senha'])) {
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_login']  = $user['usuario'];
            $_SESSION['usuario_nome']   = $user['nome'];
            $_SESSION['usuario_email']  = $user['email'];
            $_SESSION['usuario_perfil'] = $user['perfil'];

            set_flash_message('success', 'Seja bem-vindo, ' . $user['nome'] . '!');
            header("Location: index.php");
            exit;
        } else {
            set_flash_message('danger', 'Usuário/E-mail ou senha incorretos.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eBill Mini ERP</title>

    <!-- Google Font & Font Awesome -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap 5 & AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
        }
        .card-login {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .brand-logo-circle {
            width: 60px;
            height: 60px;
            background-color: #0284c7;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    
    <div class="card card-login bg-white p-4">
        <div class="card-body login-card-body p-2">
            
            <div class="text-center mb-4">
                <div class="brand-logo-circle rounded-circle text-white d-inline-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-file-invoice-dollar fs-2"></i>
                </div>
                <h3 class="fw-bold text-dark mb-0">eBill <span class="text-primary">ERP</span></h3>
                <small class="text-muted"><?= htmlspecialchars($empresa['nome_fantasia']) ?></small>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['tipo'] === 'danger' ? 'danger' : ($flash['tipo'] === 'success' ? 'success' : 'warning') ?> alert-dismissible fade show py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($flash['texto']) ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="usuario_email" class="form-label fw-semibold text-secondary small">Usuário ou E-mail</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-user"></i></span>
                        <input type="text" name="usuario_email" id="usuario_email" class="form-control border-start-0 ps-0" placeholder="Digite seu usuário ou e-mail" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="senha" class="form-label fw-semibold text-secondary small mb-0">Senha</label>
                        <a href="esqueci_senha.php" class="small text-primary text-decoration-none">Esqueceu a senha?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-lock"></i></span>
                        <input type="password" name="senha" id="senha" class="form-control border-start-0 ps-0" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> Acessar Sistema
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top text-center text-muted small">
                <p class="mb-0">Dica: Usuário mestre <strong>root</strong></p>
            </div>

        </div>
    </div>

</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
