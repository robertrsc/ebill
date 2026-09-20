<?php
/**
 * Módulo de Gestão de Clientes - Listagem
 * eBill Mini ERP Web
 */
$page_title = "Clientes";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

// Ação de exclusão de cliente
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $cliente_id = (int)$_GET['id'];
    try {
        // Verifica se possui faturas vinculadas
        $check = $pdo->prepare("SELECT COUNT(*) FROM faturas WHERE cliente_id = ?");
        $check->execute([$cliente_id]);
        if ($check->fetchColumn() > 0) {
            set_flash_message('danger', 'Não é possível excluir o cliente pois ele possui faturas cadastradas no sistema.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            set_flash_message('success', 'Cliente excluído com sucesso!');
        }
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao excluir cliente: ' . $e->getMessage());
    }
    header("Location: clientes.php");
    exit;
}

// Filtros de Busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM faturas f WHERE f.cliente_id = c.id) as total_faturas
        FROM clientes c WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (c.nome LIKE ? OR c.cpf_cnpj LIKE ? OR c.email LIKE ? OR c.cidade LIKE ?)";
    $term = "%$busca%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY c.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();
?>

<!-- Content Wrapper -->
<div class="content-wrapper bg-light">
    <!-- Header -->
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-users text-primary me-2"></i>Gestão de Clientes</h1>
                    <p class="text-muted small mb-0">Cadastre e gerencie os clientes e contatos da sua empresa.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="cliente_form.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-user-plus me-1"></i> Novo Cliente
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Card Filtros -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="clientes.php" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="busca" class="form-control border-start-0 ps-0" placeholder="Buscar por nome, CPF/CNPJ, e-mail ou cidade..." value="<?= htmlspecialchars($busca) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Todos os Status</option>
                                <option value="Ativo" <?= $status === 'Ativo' ? 'selected' : '' ?>>Ativos</option>
                                <option value="Inativo" <?= $status === 'Inativo' ? 'selected' : '' ?>>Inativos</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary px-3"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <?php if (!empty($busca) || !empty($status)): ?>
                                <a href="clientes.php" class="btn btn-light border"><i class="fas fa-times me-1"></i> Limpar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Clientes -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome / Razão Social</th>
                                    <th>CPF / CNPJ</th>
                                    <th>Contato</th>
                                    <th>Cidade / UF</th>
                                    <th>Faturas</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clientes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-user-slash fs-2 mb-2 d-block opacity-50"></i>
                                        Nenhum cliente encontrado.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($clientes as $c): ?>
                                    <tr>
                                        <td>#<?= $c['id'] ?></td>
                                        <td>
                                            <a href="cliente_detalhes.php?id=<?= $c['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($c['nome']) ?>
                                            </a>
                                            <br>
                                            <small class="badge bg-light text-secondary border"><?= $c['tipo_pessoa'] ?></small>
                                        </td>
                                        <td class="fw-semibold text-secondary"><?= htmlspecialchars($c['cpf_cnpj'] ?: '-') ?></td>
                                        <td>
                                            <?php if ($c['email']): ?>
                                                <small class="d-block"><i class="fas fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($c['email']) ?></small>
                                            <?php endif; ?>
                                            <?php if ($c['celular'] || $c['telefone']): ?>
                                                <small class="d-block"><i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($c['celular'] ?: $c['telefone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(($c['cidade'] ?: '-') . ($c['uf'] ? '/' . $c['uf'] : '')) ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark rounded-pill px-2"><?= $c['total_faturas'] ?> fatura(s)</span>
                                        </td>
                                        <td>
                                            <?php if ($c['status'] === 'Ativo'): ?>
                                                <span class="badge bg-success px-2 py-1">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-2 py-1">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="cliente_detalhes.php?id=<?= $c['id'] ?>" class="btn btn-light btn-sm text-info me-1" title="Ver Histórico"><i class="fas fa-eye"></i></a>
                                            <a href="cliente_form.php?id=<?= $c['id'] ?>" class="btn btn-light btn-sm text-primary me-1" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="clientes.php?acao=excluir&id=<?= $c['id'] ?>" class="btn btn-light btn-sm text-danger btn-delete-confirm" data-item="o cliente '<?= htmlspecialchars($c['nome']) ?>'" title="Excluir"><i class="fas fa-trash"></i></a>
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
