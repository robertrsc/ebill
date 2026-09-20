<?php
/**
 * Dashboard Financeiro e Painel Inicial
 * eBill Mini ERP Web
 */
$page_title = "Dashboard Financeiro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

// 1. Cálculos dos KPIs
$mes_atual = date('Y-m');

// Faturamento do Mês (Faturas Pagas neste mês)
$stmt = $pdo->prepare("SELECT SUM(valor_total) FROM faturas WHERE status = 'Paga' AND DATE_FORMAT(data_pagamento, '%Y-%m') = ?");
$stmt->execute([$mes_atual]);
$faturamento_mes = $stmt->fetchColumn() ?: 0.00;

// Faturas Pendentes (Valor e quantidade)
$stmt = $pdo->query("SELECT COUNT(*) as qtd, SUM(valor_total) as total FROM faturas WHERE status = 'Pendente'");
$res_pendentes = $stmt->fetch();
$qtd_pendentes = $res_pendentes['qtd'] ?: 0;
$total_pendentes = $res_pendentes['total'] ?: 0.00;

// Faturas Atrasadas
$stmt = $pdo->query("SELECT COUNT(*) as qtd, SUM(valor_total) as total FROM faturas WHERE status = 'Atrasada' OR (status = 'Pendente' AND data_vencimento < CURDATE())");
$res_atrasadas = $stmt->fetch();
$total_atrasadas = $res_atrasadas['total'] ?: 0.00;

// Despesas do Mês
$stmt = $pdo->prepare("SELECT SUM(valor) FROM lancamentos_financeiros WHERE tipo = 'despesa' AND status = 'Pago' AND DATE_FORMAT(data_pagamento, '%Y-%m') = ?");
$stmt->execute([$mes_atual]);
$despesas_mes = $stmt->fetchColumn() ?: 0.00;

// Total de Clientes Ativos
$total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes WHERE status = 'Ativo'")->fetchColumn() ?: 0;

// Recibos emitidos no mês
$total_recibos = $pdo->prepare("SELECT COUNT(*) FROM recibos WHERE DATE_FORMAT(data_recibo, '%Y-%m') = ?");
$total_recibos->execute([$mes_atual]);
$qtd_recibos = $total_recibos->fetchColumn() ?: 0;

// 2. Dados para o Gráfico de Evolução Financeira (Últimos 6 meses)
$meses_labels = [];
$receitas_grafico = [];
$despesas_grafico = [];

for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $nome_mes = date('M/Y', strtotime("-$i months"));
    $meses_labels[] = $nome_mes;

    // Receitas no mês m
    $st_rec = $pdo->prepare("SELECT SUM(valor) FROM lancamentos_financeiros WHERE tipo = 'receita' AND status = 'Pago' AND DATE_FORMAT(data_pagamento, '%Y-%m') = ?");
    $st_rec->execute([$m]);
    $receitas_grafico[] = (float)($st_rec->fetchColumn() ?: 0.00);

    // Despesas no mês m
    $st_des = $pdo->prepare("SELECT SUM(valor) FROM lancamentos_financeiros WHERE tipo = 'despesa' AND status = 'Pago' AND DATE_FORMAT(data_pagamento, '%Y-%m') = ?");
    $st_des->execute([$m]);
    $despesas_grafico[] = (float)($st_des->fetchColumn() ?: 0.00);
}

// 3. Status das Faturas para Gráfico Donut
$st_status = $pdo->query("SELECT status, COUNT(*) as qtd FROM faturas GROUP BY status");
$status_counts = ['Paga' => 0, 'Pendente' => 0, 'Atrasada' => 0, 'Cancelada' => 0];
while ($row = $st_status->fetch()) {
    $status_counts[$row['status']] = (int)$row['qtd'];
}

// 4. Faturas Recentes
$faturas_recentes = $pdo->query("
    SELECT f.*, c.nome as cliente_nome 
    FROM faturas f 
    JOIN clientes c ON f.cliente_id = c.id 
    ORDER BY f.id DESC LIMIT 6
")->fetchAll();
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper bg-light">
    <!-- Content Header (Page header) -->
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-chart-line text-primary me-2"></i>Dashboard Financeiro</h1>
                    <p class="text-muted small mb-0">Visão geral do faturamento, cobranças e gestão do seu negócio.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="fatura_form.php" class="btn btn-primary btn-sm shadow-sm rounded-pill px-3">
                        <i class="fas fa-plus me-1"></i> Nova Fatura
                    </a>
                    <a href="recibo_form.php" class="btn btn-outline-success btn-sm shadow-sm rounded-pill px-3 ms-1">
                        <i class="fas fa-receipt me-1"></i> Novo Recibo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Cards KPI -->
            <div class="row">
                <!-- Faturamento no Mês -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-white p-3 border-0 border-start border-4 border-success">
                        <div class="inner">
                            <span class="text-uppercase text-muted fw-bold small">Faturamento (<?= date('m/Y') ?>)</span>
                            <h3 class="fw-bold text-success mt-1 mb-0"><?= format_moeda($faturamento_mes) ?></h3>
                            <p class="text-muted small mb-0 mt-2"><i class="fas fa-check-circle me-1"></i> Faturas quitadas</p>
                        </div>
                        <div class="icon text-success">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>

                <!-- Faturas Pendentes -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-white p-3 border-0 border-start border-4 border-warning">
                        <div class="inner">
                            <span class="text-uppercase text-muted fw-bold small">Pendentes a Receber</span>
                            <h3 class="fw-bold text-warning mt-1 mb-0"><?= format_moeda($total_pendentes) ?></h3>
                            <p class="text-muted small mb-0 mt-2"><i class="fas fa-clock me-1"></i> <?= $qtd_pendentes ?> fatura(s) pendente(s)</p>
                        </div>
                        <div class="icon text-warning">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>

                <!-- Despesas do Mês -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-white p-3 border-0 border-start border-4 border-danger">
                        <div class="inner">
                            <span class="text-uppercase text-muted fw-bold small">Despesas Pagas (<?= date('m/Y') ?>)</span>
                            <h3 class="fw-bold text-danger mt-1 mb-0"><?= format_moeda($despesas_mes) ?></h3>
                            <p class="text-muted small mb-0 mt-2"><i class="fas fa-arrow-down-long me-1"></i> Livro caixa</p>
                        </div>
                        <div class="icon text-danger">
                            <i class="fas fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                </div>

                <!-- Clientes Ativos -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-white p-3 border-0 border-start border-4 border-info">
                        <div class="inner">
                            <span class="text-uppercase text-muted fw-bold small">Clientes Cadastrados</span>
                            <h3 class="fw-bold text-info mt-1 mb-0"><?= $total_clientes ?></h3>
                            <p class="text-muted small mb-0 mt-2"><i class="fas fa-users me-1"></i> Total de clientes ativos</p>
                        </div>
                        <div class="icon text-info">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->

            <!-- Gráficos -->
            <div class="row">
                <!-- Evolução de Receitas vs Despesas -->
                <div class="col-lg-8">
                    <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h5 class="card-title m-0 fw-bold text-dark"><i class="fas fa-chart-area text-primary me-2"></i>Fluxo de Caixa (Últimos 6 Meses)</h5>
                            <span class="badge bg-light text-secondary border">Receitas x Despesas</span>
                        </div>
                        <div class="card-body">
                            <canvas id="chartFluxoCaixa" height="110"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Donut Chart Status Faturas -->
                <div class="col-lg-4">
                    <div class="card card-outline card-info shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title m-0 fw-bold text-dark"><i class="fas fa-chart-pie text-info me-2"></i>Status das Faturas</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartStatusFaturas" height="230"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Faturas Recentes -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h5 class="card-title m-0 fw-bold text-dark"><i class="fas fa-file-invoice text-secondary me-2"></i>Faturas Recentes</h5>
                            <a href="faturas.php" class="btn btn-outline-primary btn-sm">Ver Todas <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-custom align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Número</th>
                                            <th>Cliente</th>
                                            <th>Vencimento</th>
                                            <th>Valor Total</th>
                                            <th>Status</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($faturas_recentes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-folder-open fs-3 mb-2 d-block opacity-50"></i>
                                                Nenhuma fatura cadastrada até o momento.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($faturas_recentes as $f): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($f['numero_fatura']) ?></td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($f['cliente_nome']) ?></td>
                                                <td><?= format_data($f['data_vencimento']) ?></td>
                                                <td class="fw-bold"><?= format_moeda($f['valor_total']) ?></td>
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
                                                    <a href="fatura_detalhes.php?id=<?= $f['id'] ?>" class="btn btn-light btn-sm me-1" title="Visualizar Fatura"><i class="fas fa-eye text-primary"></i></a>
                                                    <?php if ($f['status'] == 'Paga'): ?>
                                                        <a href="gerar_recibo_pdf.php?fatura_id=<?= $f['id'] ?>" target="_blank" class="btn btn-light btn-sm text-success" title="Imprimir Recibo PDF"><i class="fas fa-file-pdf"></i></a>
                                                    <?php elseif ($f['status'] == 'Pendente'): ?>
                                                        <a href="fatura_acao.php?acao=marcar_paga&id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-success" title="Marcar como Paga"><i class="fas fa-check"></i> Pagar</a>
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

<!-- Script de Gráficos -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Gráfico de Fluxo de Caixa
    const ctxFluxo = document.getElementById('chartFluxoCaixa').getContext('2d');
    new Chart(ctxFluxo, {
        type: 'bar',
        data: {
            labels: <?= json_encode($meses_labels) ?>,
            datasets: [
                {
                    label: 'Receitas (R$)',
                    data: <?= json_encode($receitas_grafico) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: 'Despesas (R$)',
                    data: <?= json_encode($despesas_grafico) ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }
                    }
                }
            }
        }
    });

    // 2. Gráfico Donut Status
    const ctxStatus = document.getElementById('chartStatusFaturas').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Pagas', 'Pendentes', 'Atrasadas', 'Canceladas'],
            datasets: [{
                data: [
                    <?= $status_counts['Paga'] ?>,
                    <?= $status_counts['Pendente'] ?>,
                    <?= $status_counts['Atrasada'] ?>,
                    <?= $status_counts['Cancelada'] ?>
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
