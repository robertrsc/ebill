<?php
/**
 * Módulo de Gestão Financeira / Livro Caixa
 * eBill Mini ERP Web
 */
$page_title = "Gestão Financeira";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

// Processamento de Novo Lançamento POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['acao_financeiro']) && $_POST['acao_financeiro'] === 'salvar') {
    $tipo            = $_POST['tipo'] ?? 'despesa';
    $descricao       = trim($_POST['descricao'] ?? '');
    $categoria       = trim($_POST['categoria'] ?? 'Geral');
    $data_vencimento = $_POST['data_vencimento'] ?? date('Y-m-d');
    $data_pagamento  = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
    $status          = $_POST['status'] ?? 'Pago';
    $observacoes     = trim($_POST['observacoes'] ?? '');

    $valor_raw       = str_replace('.', '', $_POST['valor'] ?? '0');
    $valor_raw       = str_replace(',', '.', $valor_raw);
    $valor           = (float)$valor_raw;

    if (empty($descricao) || $valor <= 0) {
        set_flash_message('danger', 'Informe a descrição e um valor válido.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO lancamentos_financeiros (tipo, descricao, categoria, valor, data_vencimento, data_pagamento, status, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tipo, $descricao, $categoria, $valor, $data_vencimento, $data_pagamento, $status, $observacoes]);
            set_flash_message('success', 'Lançamento financeiro registrado com sucesso!');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao salvar lançamento: ' . $e->getMessage());
        }
    }
    header("Location: financeiro.php");
    exit;
}

// Exclusão
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM lancamentos_financeiros WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Lançamento excluído com sucesso!');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao excluir lançamento: ' . $e->getMessage());
    }
    header("Location: financeiro.php");
    exit;
}

// Filtros
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$sql = "SELECT l.*, c.nome as cliente_nome 
        FROM lancamentos_financeiros l 
        LEFT JOIN clientes c ON l.cliente_id = c.id 
        WHERE DATE_FORMAT(l.data_vencimento, '%Y-%m') = ?";
$params = [$mes_filtro];

if (!empty($tipo_filtro)) {
    $sql .= " AND l.tipo = ?";
    $params[] = $tipo_filtro;
}

$sql .= " ORDER BY l.data_vencimento DESC, l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();

// Cômputo dos Totais do Mês
$st_totais = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'receita' AND status = 'Pago' THEN valor ELSE 0 END) as total_receita,
        SUM(CASE WHEN tipo = 'despesa' AND status = 'Pago' THEN valor ELSE 0 END) as total_despesa,
        SUM(CASE WHEN status = 'Pendente' THEN valor ELSE 0 END) as total_pendente
    FROM lancamentos_financeiros 
    WHERE DATE_FORMAT(data_vencimento, '%Y-%m') = ?
");
$st_totais->execute([$mes_filtro]);
$totais = $st_totais->fetch();

$receitas = $totais['total_receita'] ?: 0.00;
$despesas = $totais['total_despesa'] ?: 0.00;
$saldo = $receitas - $despesas;
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-cash-register text-primary me-2"></i>Gestão Financeira & Livro Caixa</h1>
                    <p class="text-muted small mb-0">Fluxo de caixa, controle de entradas e pagamento de despesas operacionais.</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoLancamento">
                        <i class="fas fa-plus me-1"></i> Novo Lançamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Cards de Resumo Financeiro -->
            <div class="row g-3 mb-4">
                <!-- Entradas -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-white border-start border-4 border-success">
                        <div class="card-body p-3">
                            <span class="text-uppercase text-muted fw-bold small">Entradas (Receitas Pagas)</span>
                            <h3 class="fw-bold text-success mb-0 mt-1"><?= format_moeda($receitas) ?></h3>
                        </div>
                    </div>
                </div>
                <!-- Saídas -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-white border-start border-4 border-danger">
                        <div class="card-body p-3">
                            <span class="text-uppercase text-muted fw-bold small">Saídas (Despesas Pagas)</span>
                            <h3 class="fw-bold text-danger mb-0 mt-1"><?= format_moeda($despesas) ?></h3>
                        </div>
                    </div>
                </div>
                <!-- Saldo Líquido -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-white border-start border-4 <?= $saldo >= 0 ? 'border-primary' : 'border-warning' ?>">
                        <div class="card-body p-3">
                            <span class="text-uppercase text-muted fw-bold small">Saldo Líquido do Mês</span>
                            <h3 class="fw-bold <?= $saldo >= 0 ? 'text-primary' : 'text-danger' ?> mb-0 mt-1"><?= format_moeda($saldo) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Filtros -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="financeiro.php" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Mês de Referência:</label>
                            <input type="month" name="mes" class="form-control" value="<?= $mes_filtro ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Tipo de Lançamento:</label>
                            <select name="tipo" class="form-select" onchange="this.form.submit()">
                                <option value="">Todos (Receitas e Despesas)</option>
                                <option value="receita" <?= $tipo_filtro === 'receita' ? 'selected' : '' ?>>Apenas Receitas (+)</option>
                                <option value="despesa" <?= $tipo_filtro === 'despesa' ? 'selected' : '' ?>>Apenas Despesas (-)</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <a href="financeiro.php" class="btn btn-light border w-100"><i class="fas fa-calendar-day me-1"></i> Mês Atual</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela Extrato Financeiro -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold m-0"><i class="fas fa-list-check text-secondary me-2"></i>Extrato de Lançamentos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Vencimento</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Status</th>
                                    <th>Valor (R$)</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lancamentos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-receipt fs-2 mb-2 d-block opacity-50"></i>
                                        Nenhum lançamento registrado neste mês.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($lancamentos as $l): ?>
                                    <tr>
                                        <td>
                                            <?php if ($l['tipo'] === 'receita'): ?>
                                                <span class="badge bg-success"><i class="fas fa-arrow-up me-1"></i> Receita</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-arrow-down me-1"></i> Despesa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= format_data($l['data_vencimento']) ?></td>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($l['descricao']) ?></span>
                                            <?php if ($l['cliente_nome']): ?>
                                                <small class="text-muted"><i class="fas fa-user me-1"></i> <?= htmlspecialchars($l['cliente_nome']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['categoria']) ?></span></td>
                                        <td>
                                            <span class="badge <?= $l['status'] === 'Pago' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= $l['status'] ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold fs-6 <?= $l['tipo'] === 'receita' ? 'text-success' : 'text-danger' ?>">
                                            <?= $l['tipo'] === 'receita' ? '+' : '-' ?> <?= format_moeda($l['valor']) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="financeiro.php?acao=excluir&id=<?= $l['id'] ?>" class="btn btn-light btn-sm text-danger btn-delete-confirm" data-item="o lançamento '<?= htmlspecialchars($l['descricao']) ?>'" title="Excluir"><i class="fas fa-trash"></i></a>
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

<!-- Modal Novo Lançamento -->
<div class="modal fade" id="modalNovoLancamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Novo Lançamento Financeiro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="financeiro.php">
                <input type="hidden" name="acao_financeiro" value="salvar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Lançamento</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_despesa" value="despesa" checked>
                                <label class="form-check-label text-danger fw-bold" for="tipo_despesa"><i class="fas fa-arrow-down me-1"></i> Despesa (Saída)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_receita" value="receita">
                                <label class="form-check-label text-success fw-bold" for="tipo_receita"><i class="fas fa-arrow-up me-1"></i> Receita (Entrada)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                        <input type="text" name="descricao" id="descricao" class="form-control" required placeholder="Ex: Aluguel do mês, Conta de Energia, Venda Avulsa">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="categoria" class="form-label fw-semibold">Categoria</label>
                            <select name="categoria" id="categoria" class="form-select">
                                <option value="Vendas">Vendas / Serviços</option>
                                <option value="Infraestrutura">Infraestrutura / Aluguel</option>
                                <option value="Serviços">Serviços (Luz/Água/Internet)</option>
                                <option value="Salários">Salários / Pró-labore</option>
                                <option value="Impostos">Impostos / Taxas</option>
                                <option value="Fornecedores">Fornecedores</option>
                                <option value="Geral" selected>Geral / Diversos</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="valor" class="form-label fw-semibold">Valor (R$) <span class="text-danger">*</span></label>
                            <input type="text" name="valor" id="valor" class="form-control mask-money fw-bold" required placeholder="0,00">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="data_vencimento" class="form-label fw-semibold">Data Vencimento</label>
                            <input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Pago" selected>Pago / Liquidado</option>
                                <option value="Pendente">Pendente</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label fw-semibold">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2" placeholder="Detalhes adicionais..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Registar Lançamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
