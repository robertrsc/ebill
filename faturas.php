<?php
/**
 * Módulo de Emissão e Gestão de Faturas - Listagem
 * eBill Mini ERP Web
 */
$page_title = "Faturas & Cobranças";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

// Atualiza status de faturas vencidas automaticamente para "Atrasada"
$pdo->query("UPDATE faturas SET status = 'Atrasada' WHERE status = 'Pendente' AND data_vencimento < CURDATE()");

// Filtros
$busca      = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$status     = isset($_GET['status']) ? trim($_GET['status']) : '';
$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

$sql = "SELECT f.*, c.nome as cliente_nome, c.cpf_cnpj 
        FROM faturas f 
        JOIN clientes c ON f.cliente_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (f.numero_fatura LIKE ? OR c.nome LIKE ? OR c.cpf_cnpj LIKE ?)";
    $term = "%$busca%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status)) {
    $sql .= " AND f.status = ?";
    $params[] = $status;
}

if ($cliente_id > 0) {
    $sql .= " AND f.cliente_id = ?";
    $params[] = $cliente_id;
}

$sql .= " ORDER BY f.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$faturas = $stmt->fetchAll();

// Obter Lista de Clientes para Filtro
$clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-file-invoice text-primary me-2"></i>Faturas & Cobranças</h1>
                    <p class="text-muted small mb-0">Emita, controle e acompanhe o pagamento de faturas enviadas aos seus clientes.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="fatura_form.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> Nova Fatura
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
                    <form method="GET" action="faturas.php" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="busca" class="form-control border-start-0 ps-0" placeholder="Buscar por número da fatura, cliente ou CPF/CNPJ..." value="<?= htmlspecialchars($busca) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="cliente_id" class="form-select">
                                <option value="">Todos os Clientes</option>
                                <?php foreach ($clientes as $cl): ?>
                                    <option value="<?= $cl['id'] ?>" <?= $cliente_id == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Todos os Status</option>
                                <option value="Pendente" <?= $status === 'Pendente' ? 'selected' : '' ?>>Pendentes</option>
                                <option value="Paga" <?= $status === 'Paga' ? 'selected' : '' ?>>Pagas</option>
                                <option value="Atrasada" <?= $status === 'Atrasada' ? 'selected' : '' ?>>Atrasadas</option>
                                <option value="Cancelada" <?= $status === 'Cancelada' ? 'selected' : '' ?>>Canceladas</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary px-3"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <?php if (!empty($busca) || !empty($status) || $cliente_id > 0): ?>
                                <a href="faturas.php" class="btn btn-light border"><i class="fas fa-times me-1"></i> Limpar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Faturas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Emissão</th>
                                    <th>Vencimento</th>
                                    <th>Forma Pagto</th>
                                    <th>Valor Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($faturas)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-file-invoice-dollar fs-2 mb-2 d-block opacity-50"></i>
                                        Nenhuma fatura encontrada.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($faturas as $f): ?>
                                    <tr>
                                        <td>
                                            <a href="fatura_detalhes.php?id=<?= $f['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                                <?= htmlspecialchars($f['numero_fatura']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($f['cliente_nome']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($f['cpf_cnpj'] ?: '') ?></small>
                                        </td>
                                        <td><?= format_data($f['data_emissao']) ?></td>
                                        <td class="<?= ($f['status'] == 'Atrasada') ? 'text-danger fw-bold' : '' ?>">
                                            <?= format_data($f['data_vencimento']) ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($f['forma_pagamento']) ?></span></td>
                                        <td class="fw-bold text-dark fs-6"><?= format_moeda($f['valor_total']) ?></td>
                                        <td>
                                            <?php
                                                $badge = 'bg-secondary';
                                                if ($f['status'] == 'Paga') $badge = 'badge-status-paga';
                                                elseif ($f['status'] == 'Pendente') $badge = 'badge-status-pendente';
                                                elseif ($f['status'] == 'Atrasada') $badge = 'badge-status-atrasada';
                                                elseif ($f['status'] == 'Cancelada') $badge = 'badge-status-cancelada';
                                            ?>
                                            <span class="badge <?= $badge ?> px-2 py-1"><?= $f['status'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="fatura_detalhes.php?id=<?= $f['id'] ?>" class="btn btn-light btn-sm text-primary me-1" title="Visualizar / Imprimir"><i class="fas fa-eye"></i></a>
                                            
                                            <?php if ($f['status'] == 'Paga'): ?>
                                                <a href="gerar_recibo_pdf.php?fatura_id=<?= $f['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success me-1" title="Imprimir Recibo PDF">
                                                    <i class="fas fa-file-pdf"></i> Recibo
                                                </a>
                                            <?php endif; ?>

                                            <?php if (in_array($f['status'], ['Pendente', 'Atrasada'])): ?>
                                                <a href="fatura_acao.php?acao=marcar_paga&id=<?= $f['id'] ?>" class="btn btn-sm btn-success me-1" title="Marcar como Paga">
                                                    <i class="fas fa-check"></i> Pagar
                                                </a>
                                            <?php endif; ?>

                                            <a href="fatura_form.php?id=<?= $f['id'] ?>" class="btn btn-light btn-sm text-secondary me-1" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="fatura_acao.php?acao=excluir&id=<?= $f['id'] ?>" class="btn btn-light btn-sm text-danger btn-delete-confirm" data-item="a fatura '<?= htmlspecialchars($f['numero_fatura']) ?>'" title="Excluir"><i class="fas fa-trash"></i></a>
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
