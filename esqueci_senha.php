<?php
/**
 * Recuperação de Senha - Solicitação
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/config/db.php';

$pdo = get_db_connection();
$empresa = get_empresa_info();
$flash = get_flash_message();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = trim($_POST['usuario_email'] ?? '');

    if (empty($input)) {
        set_flash_message('danger', 'Por favor, informe seu usuário ou e-mail.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (usuario = ? OR email = ?) AND status = 'Ativo' LIMIT 1");
        $stmt->execute([$input, $input]);
        $user = $stmt->fetch();

        if ($user && !empty($user['email'])) {
            // Gerar Token de Recuperação Seguro
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $up = $pdo->prepare("UPDATE usuarios SET token_recuperacao = ?, expiracao_token = ? WHERE id = ?");
            $up->execute([$token, $expiracao, $user['id']]);

            // Construir Link de Redefinição
            $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $uri_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $link_redefinicao = "{$protocolo}://{$host}{$uri_dir}/redefinir_senha.php?token={$token}";

            // E-mail HTML Transacional
            $corpo_email = "
            <div style='font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff;'>
                <div style='text-align: center; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;'>
                    <h2 style='color: #0284c7; margin: 0;'>eBill <span style='color: #0f172a;'>ERP</span></h2>
                    <p style='color: #64748b; font-size: 14px; margin-top: 5px;'>Recuperação de Senha do Sistema</p>
                </div>
                <div style='padding: 20px 0;'>
                    <p>Olá, <strong>" . htmlspecialchars($user['nome']) . "</strong>!</p>
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta de acesso ao eBill ERP (Usuário: <code>" . htmlspecialchars($user['usuario']) . "</code>).</p>
                    <p>Para cadastrar uma nova senha, clique no botão abaixo:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$link_redefinicao}' style='background-color: #0284c7; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Redefinir Minha Senha</a>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>Ou copie e cole o seguinte link no seu navegador:<br><a href='{$link_redefinicao}' style='color: #0284c7;'>{$link_redefinicao}</a></p>
                    <p style='color: #ef4444; font-size: 12px;'>Este link é válido por 1 hora. Caso não tenha solicitado esta alteração, desconsidere este e-mail.</p>
                </div>
                <div style='border-top: 1px solid #f1f5f9; padding-top: 15px; text-align: center; font-size: 12px; color: #94a3b8;'>
                    &copy; " . date('Y') . " " . htmlspecialchars($empresa['nome_fantasia']) . ". Todos os direitos reservados.
                </div>
            </div>";

            $enviado = enviar_email_smtp($user['email'], $user['nome'], "Recuperação de Senha - eBill ERP", $corpo_email);
        }

        // Mensagem genérica por segurança
        set_flash_message('success', 'Se as informações conferem com o cadastro, enviamos um e-mail com as instruções de redefinição de senha.');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha Senha - eBill Mini ERP</title>
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
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                    <i class="fas fa-key fs-3"></i>
                </div>
                <h4 class="fw-bold text-dark mb-0">Esqueceu a Senha?</h4>
                <p class="text-muted small">Digite seu usuário ou e-mail para receber o link de redefinição.</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['tipo'] === 'danger' ? 'danger' : 'success' ?> alert-dismissible fade show py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($flash['texto']) ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="esqueci_senha.php" method="POST">
                <div class="mb-3">
                    <label for="usuario_email" class="form-label fw-semibold text-secondary small">Usuário ou E-mail Cadastrado</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-envelope"></i></span>
                        <input type="text" name="usuario_email" id="usuario_email" class="form-control border-start-0 ps-0" placeholder="Ex: root ou seu@email.com" required autofocus>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">
                        <i class="fas fa-paper-plane me-1"></i> Enviar Link de Recuperação
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="login.php" class="text-primary text-decoration-none fw-semibold small">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para a Tela de Login
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
