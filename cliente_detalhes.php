<?php
/**
 * Detalhes do Cliente e Histórico Financeiro
 * eBill Mini ERP Web
 */
$page_title = "Detalhes do Cliente";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: clientes.php");
    exit;
}

// Dados do Cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    set_flash_message('danger', 'Cliente não encontrado.');
    header("Location: clientes.php");
    exit;
}

// Faturas do Cliente
$st_faturas = $pdo->prepare("SELECT * FROM faturas WHERE cliente_id = ? ORDER BY id DESC");
$st_faturas->execute([$id]);
$faturas = $st_faturas->fetchAll();

// Total Faturado e Total Pago
$st_totais = $pdo->prepare("
    SELECT 
        SUM(valor_total) as total_geral,
        SUM(CASE WHEN status = 'Paga' THEN valor_total ELSE 0 END) as total_pago,
        SUM(CASE WHEN status = 'Pendente' OR status = 'Atrasada' THEN valor_total ELSE 0 END) as total_pendente
    FROM faturas WHERE cliente_id = ?
");
$st_totais->execute([$id]);
$totais = $st_totais->fetch();
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-user-tag text-primary me-2"></i><?= htmlspecialchars($cliente['nome']) ?></h1>
                    <p class="text-muted small mb-0">Visualização de cadastro e histórico de cobranças do cliente.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="fatura_form.php?cliente_id=<?= $cliente['id'] ?>" class="btn btn-success btn-sm rounded-pill px-3 me-1">
                        <i class="fas fa-plus me-1"></i> Nova Fatura para este Cliente
                    </a>
                    <a href="cliente_form.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <a href="clientes.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <div class="row">
                <!-- Dados Cadastrais -->
                <div class="col-lg-4">
                    <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 64px; height: 64px; font-size: 1.8rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($cliente['nome']) ?></h5>
                                <span class="badge bg-secondary mb-2"><?= $cliente['tipo_pessoa'] ?></span>
                                <div>
                                    <span class="badge <?= $cliente['status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $cliente['status'] ?>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <ul class="list-group list-group-flush text-sm">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="fas fa-id-card me-2"></i>CPF/CNPJ:</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($cliente['cpf_cnpj'] ?: '-') ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="fas fa-envelope me-2"></i>E-mail:</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($cliente['email'] ?: '-') ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="fas fa-phone me-2"></i>Telefone:</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($cliente['telefone'] ?: '-') ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="fab fa-whatsapp me-2 text-success"></i>Celular:</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($cliente['celular'] ?: '-') ?></span>
                                </li>
                                <li class="list-group-item px-0">
                                    <span class="text-muted d-block mb-1"><i class="fas fa-map-marker-alt me-2"></i>Endereço:</span>
                                    <span class="fw-semibold text-dark">
                                        <?= htmlspecialchars($cliente['endereco'] ?: '-') ?>, <?= htmlspecialchars($cliente['numero'] ?: 'S/N') ?>
                                        <?= $cliente['complemento'] ? ' ('.htmlspecialchars($cliente['complemento']).')' : '' ?><br>
                                        <?= htmlspecialchars($cliente['bairro'] ?: '') ?> - <?= htmlspecialchars($cliente['cidade'] ?: '') ?>/<?= htmlspecialchars($cliente['uf'] ?: '') ?><br>
                                        CEP: <?= htmlspecialchars($cliente['cep'] ?: '-') ?>
                                    </span>
                                </li>
                            </ul>
                            <?php if ($cliente['observacoes']): ?>
                                <div class="mt-3 p-2 bg-light rounded border text-muted small">
                                    <strong><i class="fas fa-info-circle me-1"></i>Obs:</strong> <?= nl2br(htmlspecialchars($cliente['observacoes'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Resumo & Histórico de Faturas -->
                <div class="col-lg-8">
                    
                    <!-- Cards Resumo Financeiro -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm bg-white border-start border-4 border-primary">
                                <div class="card-body p-3">
                                    <small class="text-muted uppercase fw-bold">Total Faturado</small>
                                    <h4 class="fw-bold text-primary mb-0 mt-1"><?= format_moeda($totais['total_geral'] ?: 0) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm bg-white border-start border-4 border-success">
                                <div class="card-body p-3">
                                    <small class="text-muted uppercase fw-bold">Total Pago</small>
                                    <h4 class="fw-bold text-success mb-0 mt-1"><?= format_moeda($totais['total_pago'] ?: 0) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm bg-white border-start border-4 border-warning">
                                <div class="card-body p-3">
                                    <small class="text-muted uppercase fw-bold">Pendente / Atrasado</small>
                                    <h4 class="fw-bold text-warning mb-0 mt-1"><?= format_moeda($totais['total_pendente'] ?: 0) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Faturas -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title fw-bold m-0"><i class="fas fa-file-invoice text-secondary me-2"></i>Histórico de Faturas</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-custom align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Número</th>
                                            <th>Emissão</th>
                                            <th>Vencimento</th>
                                            <th>Valor Total</th>
                                            <th>Status</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($faturas)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                Nenhuma fatura encontrada para este cliente.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($faturas as $f): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($f['numero_fatura']) ?></td>
                                                <td><?= format_data($f['data_emissao']) ?></td>
                                                <td><?= format_data($f['data_vencimento']) ?></td>
                                                <td class="fw-bold"><?= format_moeda($f['valor_total']) ?></td>
                                                <td>
                                                    <?php
                                                        $badge = 'bg-secondary';
                                                        if ($f['status'] == 'Paga') $badge = 'badge-status-paga';
                                                        elseif ($f['status'] == 'Pendente') $badge = 'badge-status-pendente';
                                                        elseif ($f['status'] == 'Atrasada') $badge = 'badge-status-atrasada';
                                                    ?>
                                                    <span class="badge <?= $badge ?> px-2 py-1"><?= $f['status'] ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="fatura_detalhes.php?id=<?= $f['id'] ?>" class="btn btn-light btn-sm text-primary" title="Ver Fatura"><i class="fas fa-eye"></i></a>
                                                    <?php if ($f['status'] == 'Paga'): ?>
                                                        <a href="gerar_recibo_pdf.php?fatura_id=<?= $f['id'] ?>" target="_blank" class="btn btn-light btn-sm text-success" title="Recibo PDF"><i class="fas fa-file-pdf"></i> Recibo</a>
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
            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
