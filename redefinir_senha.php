<?php
/**
 * Redefinição de Senha via Token
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/config/db.php';

$pdo = get_db_connection();
$empresa = get_empresa_info();
$flash = get_flash_message();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$token_valido = false;
$usuario_token = null;

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token_recuperacao = ? AND expiracao_token > NOW() AND status = 'Ativo' LIMIT 1");
    $stmt->execute([$token]);
    $usuario_token = $stmt->fetch();

    if ($usuario_token) {
        $token_valido = true;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $token_valido) {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (empty($nova_senha) || strlen($nova_senha) < 6) {
        set_flash_message('danger', 'A nova senha deve ter no mínimo 6 caracteres.');
    } elseif ($nova_senha !== $confirma_senha) {
        set_flash_message('danger', 'As senhas digitadas não coincidem.');
    } else {
        try {
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            $up = $pdo->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, expiracao_token = NULL WHERE id = ?");
            $up->execute([$nova_senha_hash, $usuario_token['id']]);

            set_flash_message('success', 'Sua senha foi redefinida com sucesso! Efetue login com sua nova senha.');
            header("Location: login.php");
            exit;

        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao atualizar senha: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - eBill Mini ERP</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box { width: 420px; }
        .card-login { border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card card-login bg-white p-4">
        <div class="card-body login-card-body p-2">
            
            <div class="text-center mb-4">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                    <i class="fas fa-lock-open fs-3"></i>
                </div>
                <h4 class="fw-bold text-dark mb-0">Redefinir Senha</h4>
                <?php if ($token_valido): ?>
                    <p class="text-muted small">Defina a nova senha para o usuário <strong><?= htmlspecialchars($usuario_token['usuario']) ?></strong></p>
                <?php endif; ?>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['tipo'] === 'danger' ? 'danger' : 'success' ?> alert-dismissible fade show py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($flash['texto']) ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$token_valido): ?>
                <div class="alert alert-danger py-3 px-3 text-center small">
                    <i class="fas fa-exclamation-triangle fs-4 d-block mb-2 text-danger"></i>
                    <strong>Link Inválido ou Expirado!</strong><br>
                    O token de recuperação informado não é válido ou já expirou. Por favor, solicite um novo link de redefinição.
                </div>
                <div class="d-grid mt-3">
                    <a href="esqueci_senha.php" class="btn btn-outline-primary rounded-pill">Solicitar Novo Link</a>
                </div>
            <?php else: ?>

                <form action="redefinir_senha.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label for="nova_senha" class="form-label fw-semibold text-secondary small">Nova Senha</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-key"></i></span>
                            <input type="password" name="nova_senha" id="nova_senha" class="form-control border-start-0 ps-0" placeholder="Mínimo 6 caracteres" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirma_senha" class="form-label fw-semibold text-secondary small">Confirme a Nova Senha</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-check-double"></i></span>
                            <input type="password" name="confirma_senha" id="confirma_senha" class="form-control border-start-0 ps-0" placeholder="Repita a nova senha" required>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success rounded-pill py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> Salvar Nova Senha
                        </button>
                    </div>
                </form>

            <?php endif; ?>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="login.php" class="text-primary text-decoration-none fw-semibold small">
                    <i class="fas fa-arrow-left me-1"></i> Ir para o Login
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
