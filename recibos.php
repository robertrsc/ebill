<?php
/**
 * Módulo de Recibos PDF - Listagem
 * eBill Mini ERP Web
 */
$page_title = "Recibos Emitidos";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

// Exclusão de recibo
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM recibos WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Recibo excluído com sucesso!');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao excluir recibo: ' . $e->getMessage());
    }
    header("Location: recibos.php");
    exit;
}

// Filtros
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

$sql = "SELECT r.*, c.nome as cliente_nome, c.cpf_cnpj, f.numero_fatura 
        FROM recibos r 
        JOIN clientes c ON r.cliente_id = c.id 
        LEFT JOIN faturas f ON r.fatura_id = f.id 
        WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (r.numero_recibo LIKE ? OR c.nome LIKE ? OR r.referente_a LIKE ? OR f.numero_fatura LIKE ?)";
    $term = "%$busca%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recibos = $stmt->fetchAll();
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-receipt text-primary me-2"></i>Recibos Emitidos (PDF)</h1>
                    <p class="text-muted small mb-0">Consulte, reimprima em PDF ou emita recibos de pagamento avulsos.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="recibo_form.php" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> Emitir Recibo Avulso
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
                    <form method="GET" action="recibos.php" class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="busca" class="form-control border-start-0 ps-0" placeholder="Buscar por número do recibo, cliente, fatura ou descrição..." value="<?= htmlspecialchars($busca) ?>">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary px-3"><i class="fas fa-filter me-1"></i> Buscar</button>
                            <?php if (!empty($busca)): ?>
                                <a href="recibos.php" class="btn btn-light border"><i class="fas fa-times me-1"></i> Limpar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Recibos -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Data</th>
                                    <th>Cliente / Pagador</th>
                                    <th>Referente a</th>
                                    <th>Forma Pagto</th>
                                    <th>Valor (R$)</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recibos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-file-pdf fs-2 mb-2 d-block opacity-50"></i>
                                        Nenhum recibo emitido até o momento.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recibos as $r): ?>
                                    <tr>
                                        <td>
                                            <a href="gerar_recibo_pdf.php?id=<?= $r['id'] ?>" target="_blank" class="fw-bold text-success text-decoration-none">
                                                <i class="fas fa-file-pdf me-1"></i> <?= htmlspecialchars($r['numero_recibo']) ?>
                                            </a>
                                            <?php if ($r['numero_fatura']): ?>
                                                <br><small class="badge bg-light text-secondary border">Fatura: <?= htmlspecialchars($r['numero_fatura']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= format_data($r['data_recibo']) ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($r['cliente_nome']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($r['cpf_cnpj'] ?: '') ?></small>
                                        </td>
                                        <td><small class="text-secondary"><?= htmlspecialchars(mb_strimwidth($r['referente_a'], 0, 50, '...')) ?></small></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['forma_pagamento']) ?></span></td>
                                        <td class="fw-bold text-success fs-6"><?= format_moeda($r['valor']) ?></td>
                                        <td class="text-end">
                                            <a href="gerar_recibo_pdf.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-success btn-sm me-1" title="Gerar / Baixar PDF">
                                                <i class="fas fa-download"></i> PDF
                                            </a>
                                            <a href="recibos.php?acao=excluir&id=<?= $r['id'] ?>" class="btn btn-light btn-sm text-danger btn-delete-confirm" data-item="o recibo '<?= htmlspecialchars($r['numero_recibo']) ?>'" title="Excluir"><i class="fas fa-trash"></i></a>
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
