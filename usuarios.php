<?php
/**
 * Módulo de Gestão de Usuários - Listagem
 * eBill Mini ERP Web (Restrito ao Usuário Root Mestre)
 */
$page_title = "Usuários do Sistema";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Garantir acesso exclusivo ao Usuário Root Mestre
checar_perfil_root();

$pdo = get_db_connection();

// Ação de Exclusão de Usuário
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $user_id_del = (int)$_GET['id'];
    try {
        // Bloquear exclusão do próprio root logado
        if ($user_id_del === $_SESSION['usuario_id']) {
            set_flash_message('danger', 'Não é possível excluir o seu próprio usuário enquanto estiver conectado.');
        } else {
            $st_chk = $pdo->prepare("SELECT perfil, usuario FROM usuarios WHERE id = ?");
            $st_chk->execute([$user_id_del]);
            $u_del = $st_chk->fetch();

            if ($u_del && $u_del['usuario'] === 'root') {
                set_flash_message('danger', 'O usuário mestre "root" do sistema não pode ser excluído.');
            } else {
                $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$user_id_del]);
                set_flash_message('success', 'Usuário excluído com sucesso!');
            }
        }
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao excluir usuário: ' . $e->getMessage());
    }
    header("Location: usuarios.php");
    exit;
}

// Filtros
$busca  = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$perfil = isset($_GET['perfil']) ? trim($_GET['perfil']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (usuario LIKE ? OR nome LIKE ? OR email LIKE ?)";
    $term = "%$busca%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($perfil)) {
    $sql .= " AND perfil = ?";
    $params[] = $perfil;
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY CASE WHEN perfil='root' THEN 1 WHEN perfil='admin' THEN 2 ELSE 3 END, nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-users-cog text-primary me-2"></i>Usuários do Sistema</h1>
                    <p class="text-muted small mb-0">Gerencie os acessos, operadores e permissões dos usuários do ERP.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="usuario_form.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-user-plus me-1"></i> Novo Usuário
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Card Filtros -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="usuarios.php" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="busca" class="form-control border-start-0 ps-0" placeholder="Buscar por usuário, nome ou e-mail..." value="<?= htmlspecialchars($busca) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="perfil" class="form-select">
                                <option value="">Todos os Perfis</option>
                                <option value="root" <?= $perfil === 'root' ? 'selected' : '' ?>>Root Mestre</option>
                                <option value="admin" <?= $perfil === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="operador" <?= $perfil === 'operador' ? 'selected' : '' ?>>Operador</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Todos os Status</option>
                                <option value="Ativo" <?= $status === 'Ativo' ? 'selected' : '' ?>>Ativos</option>
                                <option value="Inativo" <?= $status === 'Inativo' ? 'selected' : '' ?>>Inativos</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary px-3"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <?php if (!empty($busca) || !empty($perfil) || !empty($status)): ?>
                                <a href="usuarios.php" class="btn btn-light border"><i class="fas fa-times me-1"></i> Limpar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Usuários -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário (Login)</th>
                                    <th>Nome Completo</th>
                                    <th>E-mail</th>
                                    <th>Perfil</th>
                                    <th>Status</th>
                                    <th>Cadastrado em</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Nenhum usuário localizado.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td>#<?= $u['id'] ?></td>
                                        <td>
                                            <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($u['usuario']) ?></span>
                                        </td>
                                        <td class="fw-semibold"><?= htmlspecialchars($u['nome']) ?></td>
                                        <td><small class="text-muted"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($u['email']) ?></small></td>
                                        <td>
                                            <?php if ($u['perfil'] === 'root'): ?>
                                                <span class="badge bg-dark border text-info px-2 py-1"><i class="fas fa-crown me-1 text-warning"></i> Root Mestre</span>
                                            <?php elseif ($u['perfil'] === 'admin'): ?>
                                                <span class="badge bg-primary px-2 py-1"><i class="fas fa-user-shield me-1"></i> Administrador</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-2 py-1"><i class="fas fa-user me-1"></i> Operador</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $u['status'] === 'Ativo' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $u['status'] ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= format_data($u['criado_em']) ?></small></td>
                                        <td class="text-end">
                                            <a href="usuario_form.php?id=<?= $u['id'] ?>" class="btn btn-light btn-sm text-primary me-1" title="Editar Usuário"><i class="fas fa-edit"></i></a>
                                            <?php if ($u['usuario'] !== 'root' && $u['id'] != $_SESSION['usuario_id']): ?>
                                                <a href="usuarios.php?acao=excluir&id=<?= $u['id'] ?>" class="btn btn-light btn-sm text-danger btn-delete-confirm" data-item="o usuário '<?= htmlspecialchars($u['usuario']) ?>'" title="Excluir"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
