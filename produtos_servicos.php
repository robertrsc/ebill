<?php
/**
 * Módulo de Produtos e Serviços - Listagem
 * eBill Mini ERP Web
 */
$page_title = "Produtos e Serviços";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

// Exclusão
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM produtos_servicos WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Item excluído com sucesso!');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao excluir item: ' . $e->getMessage());
    }
    header("Location: produtos_servicos.php");
    exit;
}

// Filtros
$busca  = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$tipo   = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql = "SELECT * FROM produtos_servicos WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (nome LIKE ? OR codigo_sku LIKE ? OR descricao LIKE ?)";
    $term = "%$busca%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($tipo)) {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll();
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-boxes-packing text-primary me-2"></i>Produtos e Serviços</h1>
                    <p class="text-muted small mb-0">Gerencie seu catálogo de produtos físicos e serviços prestados.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="produto_form.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-plus me-1"></i> Cadastrar Item
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
                    <form method="GET" action="produtos_servicos.php" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="busca" class="form-control border-start-0 ps-0" placeholder="Buscar por código SKU, nome ou descrição..." value="<?= htmlspecialchars($busca) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="tipo" class="form-select">
                                <option value="">Todos os Tipos</option>
                                <option value="produto" <?= $tipo === 'produto' ? 'selected' : '' ?>>Produtos</option>
                                <option value="servico" <?= $tipo === 'servico' ? 'selected' : '' ?>>Serviços</option>
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
                            <?php if (!empty($busca) || !empty($tipo) || !empty($status)): ?>
                                <a href="produtos_servicos.php" class="btn btn-light border"><i class="fas fa-times me-1"></i> Limpar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Tipo</th>
                                    <th>Nome do Item</th>
                                    <th>Preço Unitário</th>
                                    <th>Estoque</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($itens)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open fs-2 mb-2 d-block opacity-50"></i>
                                        Nenhum item cadastrado no catálogo.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($itens as $i): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary"><?= htmlspecialchars($i['codigo_sku']) ?></td>
                                        <td>
                                            <?php if ($i['tipo'] === 'produto'): ?>
                                                <span class="badge bg-primary"><i class="fas fa-box me-1"></i> Produto</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark"><i class="fas fa-concierge-bell me-1"></i> Serviço</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($i['nome']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($i['descricao'] ?: 'Sem descrição') ?></small>
                                        </td>
                                        <td class="fw-bold text-dark"><?= format_moeda($i['preco']) ?></td>
                                        <td>
                                            <?php if ($i['tipo'] === 'produto'): ?>
                                                <?php if ($i['estoque'] <= $i['estoque_minimo']): ?>
                                                    <span class="badge bg-danger" title="Estoque Mínimo: <?= $i['estoque_minimo'] ?>"><i class="fas fa-exclamation-triangle me-1"></i> <?= $i['estoque'] ?> un (Baixo)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> <?= $i['estoque'] ?> un</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A (Serviço)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $i['status'] === 'Ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $i['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="produto_form.php?id=<?= $i['id'] ?>" class="btn btn-light btn-sm text-primary me-1" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="produtos_servicos.php?acao=excluir&id=<?= $i['id'] ?>" class="btn btn-light btn-sm text-danger btn-delete-confirm" data-item="o item '<?= htmlspecialchars($i['nome']) ?>'" title="Excluir"><i class="fas fa-trash"></i></a>
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
