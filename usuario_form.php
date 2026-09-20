<?php
/**
 * Cadastro e Edição de Usuários do Sistema
 * eBill Mini ERP Web
 */
$page_title = "Cadastro de Usuário";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Acesso restrito ao root / administradores
checar_perfil_root();

$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_edit = [
    'id' => 0,
    'usuario' => '',
    'nome' => '',
    'email' => '',
    'perfil' => 'admin',
    'status' => 'Ativo'
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch();
    if ($dados) {
        $user_edit = $dados;
        $page_title = "Editar Usuário #" . $id;
    } else {
        set_flash_message('danger', 'Usuário não encontrado.');
        header("Location: usuarios.php");
        exit;
    }
}

// Processamento POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $usuario = strtolower(trim($_POST['usuario'] ?? ''));
    $nome    = trim($_POST['nome'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $perfil  = $_POST['perfil'] ?? 'admin';
    $status  = $_POST['status'] ?? 'Ativo';
    $senha   = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($nome) || empty($email)) {
        set_flash_message('danger', 'Preencha todos os campos obrigatórios (Usuário, Nome e E-mail).');
    } elseif ($id === 0 && empty($senha)) {
        set_flash_message('danger', 'Informe uma senha para o novo usuário.');
    } else {
        try {
            // Verificar unicidade de usuário e e-mail
            $st_chk = $pdo->prepare("SELECT id FROM usuarios WHERE (usuario = ? OR email = ?) AND id != ?");
            $st_chk->execute([$usuario, $email, $id]);
            if ($st_chk->fetch()) {
                set_flash_message('danger', 'O nome de usuário ou e-mail já está em uso por outra conta.');
            } else {
                if ($id > 0) {
                    // UPDATE
                    if (!empty($senha)) {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $sql = "UPDATE usuarios SET usuario = ?, nome = ?, email = ?, perfil = ?, status = ?, senha = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$usuario, $nome, $email, $perfil, $status, $senha_hash, $id]);
                    } else {
                        $sql = "UPDATE usuarios SET usuario = ?, nome = ?, email = ?, perfil = ?, status = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$usuario, $nome, $email, $perfil, $status, $id]);
                    }
                    set_flash_message('success', 'Usuário atualizado com sucesso!');
                } else {
                    // INSERT
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO usuarios (usuario, nome, email, senha, perfil, status) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$usuario, $nome, $email, $senha_hash, $perfil, $status]);
                    set_flash_message('success', 'Usuário criado com sucesso!');
                }
                header("Location: usuarios.php");
                exit;
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao salvar usuário: ' . $e->getMessage());
        }
    }
}
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3">
                        <i class="fas fa-user-shield text-primary me-2"></i><?= $id > 0 ? 'Editar Usuário' : 'Novo Usuário do Sistema' ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="usuarios.php" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="POST" action="usuario_form.php<?= $id > 0 ? '?id='.$id : '' ?>">
                
                <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-user-cog text-primary me-2"></i>Credenciais e Permissões</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Usuário (Login) -->
                            <div class="col-md-3">
                                <label for="usuario" class="form-label fw-semibold">Login / Usuário <span class="text-danger">*</span></label>
                                <input type="text" name="usuario" id="usuario" class="form-control font-monospace" required value="<?= sanitizar($user_edit['usuario']) ?>" placeholder="ex: joaosilva" <?= $user_edit['usuario'] === 'root' ? 'readonly' : '' ?>>
                                <small class="text-muted">Apenas letras e números (sem espaços)</small>
                            </div>

                            <!-- Nome Completo -->
                            <div class="col-md-5">
                                <label for="nome" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="nome" class="form-control" required value="<?= sanitizar($user_edit['nome']) ?>" placeholder="Ex: João da Silva">
                            </div>

                            <!-- Email -->
                            <div class="col-md-4">
                                <label for="email" class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" required value="<?= sanitizar($user_edit['email']) ?>" placeholder="joao@empresa.com.br">
                                <small class="text-muted">Usado para recuperação de senha</small>
                            </div>

                            <!-- Perfil -->
                            <div class="col-md-3">
                                <label for="perfil" class="form-label fw-semibold">Nível de Permissão (Perfil)</label>
                                <select name="perfil" id="perfil" class="form-select" <?= $user_edit['usuario'] === 'root' ? 'disabled' : '' ?>>
                                    <option value="root" <?= $user_edit['perfil'] === 'root' ? 'selected' : '' ?>>Root Mestre</option>
                                    <option value="admin" <?= $user_edit['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    <option value="operador" <?= $user_edit['perfil'] === 'operador' ? 'selected' : '' ?>>Operador</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3">
                                <label for="status" class="form-label fw-semibold">Status do Acesso</label>
                                <select name="status" id="status" class="form-select" <?= $user_edit['usuario'] === 'root' ? 'disabled' : '' ?>>
                                    <option value="Ativo" <?= $user_edit['status'] === 'Ativo' ? 'selected' : '' ?>>Ativo (Permite Login)</option>
                                    <option value="Inativo" <?= $user_edit['status'] === 'Inativo' ? 'selected' : '' ?>>Inativo (Bloqueado)</option>
                                </select>
                            </div>

                            <!-- Senha -->
                            <div class="col-md-3">
                                <label for="senha" class="form-label fw-semibold">Senha <?= $id > 0 ? '<small class="text-muted">(Deixe em branco p/ não alterar)</small>' : '<span class="text-danger">*</span>' ?></label>
                                <input type="password" name="senha" id="senha" class="form-control" <?= $id === 0 ? 'required' : '' ?> placeholder="••••••••">
                            </div>

                        </div>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <a href="usuarios.php" class="btn btn-light border px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Salvar Usuário</button>
                    </div>
                </div>

            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
