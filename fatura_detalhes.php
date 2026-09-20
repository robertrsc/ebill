<?php
/**
 * Visualização da Fatura (Espelho / Documento Impresso)
 * eBill Mini ERP Web
 */
$page_title = "Detalhes da Fatura";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();
$empresa = get_empresa_info();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: faturas.php");
    exit;
}

// Carregar Fatura e Cliente
$stmt = $pdo->prepare("
    SELECT f.*, c.nome as cliente_nome, c.cpf_cnpj, c.email, c.telefone, c.celular, c.endereco, c.numero, c.complemento, c.bairro, c.cidade, c.uf, c.cep 
    FROM faturas f 
    JOIN clientes c ON f.cliente_id = c.id 
    WHERE f.id = ?
");
$stmt->execute([$id]);
$fatura = $stmt->fetch();

if (!$fatura) {
    set_flash_message('danger', 'Fatura não encontrada.');
    header("Location: faturas.php");
    exit;
}

// Carregar Itens da Fatura
$st_itens = $pdo->prepare("SELECT * FROM fatura_itens WHERE fatura_id = ?");
$st_itens->execute([$id]);
$itens = $st_itens->fetchAll();
?>

<!-- Estilos específicos para Impressão -->
<style>
@media print {
    .main-sidebar, .main-header, .main-footer, .btn-print-hide {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        background: #fff !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<div class="content-wrapper bg-light">
    <!-- Header com barra de ações -->
    <div class="content-header py-3 btn-print-hide">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3">
                        <i class="fas fa-file-invoice text-primary me-2"></i>Fatura <?= htmlspecialchars($fatura['numero_fatura']) ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 me-1">
                        <i class="fas fa-print me-1"></i> Imprimir Fatura
                    </button>
                    <?php if ($fatura['status'] === 'Paga'): ?>
                        <a href="gerar_recibo_pdf.php?fatura_id=<?= $fatura['id'] ?>" target="_blank" class="btn btn-success rounded-pill px-3 me-1">
                            <i class="fas fa-file-pdf me-1"></i> Recibo PDF
                        </a>
                    <?php elseif (in_array($fatura['status'], ['Pendente', 'Atrasada'])): ?>
                        <a href="fatura_acao.php?acao=marcar_paga&id=<?= $fatura['id'] ?>" class="btn btn-success rounded-pill px-3 me-1">
                            <i class="fas fa-check me-1"></i> Registrar Pagamento
                        </a>
                    <?php endif; ?>
                    <a href="faturas.php" class="btn btn-light border rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Documento Fatura -->
    <section class="content">
        <div class="container-fluid">
            <div class="card shadow-lg border-0 p-4 mb-4 bg-white" style="border-radius: 12px;">
                
                <!-- Cabeçalho do Documento: Empresa & Dados da Fatura -->
                <div class="row pb-4 mb-4 border-bottom align-items-center">
                    <div class="col-md-7">
                        <h3 class="fw-bold text-primary mb-1"><?= htmlspecialchars($empresa['nome_fantasia']) ?></h3>
                        <p class="text-muted small mb-0">
                            <strong>Razão Social:</strong> <?= htmlspecialchars($empresa['razao_social']) ?><br>
                            <strong>CNPJ:</strong> <?= htmlspecialchars($empresa['cnpj']) ?> | <strong>Tel:</strong> <?= htmlspecialchars($empresa['telefone']) ?><br>
                            <?= htmlspecialchars($empresa['endereco']) ?>, <?= htmlspecialchars($empresa['numero']) ?> - <?= htmlspecialchars($empresa['bairro']) ?>, <?= htmlspecialchars($empresa['cidade']) ?>/<?= htmlspecialchars($empresa['uf']) ?> - CEP: <?= htmlspecialchars($empresa['cep']) ?><br>
                            <strong>E-mail:</strong> <?= htmlspecialchars($empresa['email']) ?>
                        </p>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="bg-light p-3 rounded border d-inline-block text-start" style="min-width: 240px;">
                            <h4 class="fw-bold text-dark mb-1">FATURA</h4>
                            <span class="fs-5 fw-bold text-primary d-block mb-2"><?= htmlspecialchars($fatura['numero_fatura']) ?></span>
                            
                            <small class="text-muted d-block"><strong>Emissão:</strong> <?= format_data($fatura['data_emissao']) ?></small>
                            <small class="text-muted d-block"><strong>Vencimento:</strong> <?= format_data($fatura['data_vencimento']) ?></small>
                            
                            <?php if ($fatura['data_pagamento']): ?>
                                <small class="text-success fw-bold d-block"><strong>Pagamento:</strong> <?= format_data($fatura['data_pagamento']) ?></small>
                            <?php endif; ?>

                            <div class="mt-2">
                                <?php
                                    $badge = 'bg-secondary';
                                    if ($fatura['status'] == 'Paga') $badge = 'badge-status-paga';
                                    elseif ($fatura['status'] == 'Pendente') $badge = 'badge-status-pendente';
                                    elseif ($fatura['status'] == 'Atrasada') $badge = 'badge-status-atrasada';
                                ?>
                                <span class="badge <?= $badge ?> px-3 py-2 fs-6"><?= strtoupper($fatura['status']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados do Cliente -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="p-3 bg-light rounded border">
                            <h6 class="fw-bold text-uppercase text-secondary mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">Cobrado de:</h6>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fatura['cliente_nome']) ?></h5>
                            <p class="text-muted small mb-0">
                                <strong>CPF/CNPJ:</strong> <?= htmlspecialchars($fatura['cpf_cnpj'] ?: '-') ?> | <strong>E-mail:</strong> <?= htmlspecialchars($fatura['email'] ?: '-') ?> | <strong>Tel:</strong> <?= htmlspecialchars($fatura['celular'] ?: $fatura['telefone'] ?: '-') ?><br>
                                <strong>Endereço:</strong> <?= htmlspecialchars($fatura['endereco'] ?: '-') ?>, <?= htmlspecialchars($fatura['numero'] ?: 'S/N') ?> <?= $fatura['complemento'] ? '('.htmlspecialchars($fatura['complemento']).')' : '' ?> - <?= htmlspecialchars($fatura['bairro'] ?: '') ?>, <?= htmlspecialchars($fatura['cidade'] ?: '') ?>/<?= htmlspecialchars($fatura['uf'] ?: '') ?> - CEP: <?= htmlspecialchars($fatura['cep'] ?: '-') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Itens -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Descrição do Produto / Serviço</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Preço Unitário</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $idx => $it): ?>
                            <tr>
                                <td class="text-muted"><?= $idx + 1 ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($it['descricao']) ?></strong></td>
                                <td class="text-center">
                                    <span class="badge <?= $it['tipo'] == 'produto' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                        <?= ucfirst($it['tipo']) ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold"><?= $it['quantidade'] ?></td>
                                <td class="text-end"><?= format_moeda($it['preco_unitario']) ?></td>
                                <td class="text-end fw-bold text-dark"><?= format_moeda($it['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumo de Valores e Forma de Pagamento -->
                <div class="row align-items-start mb-4">
                    <div class="col-md-7">
                        <div class="p-3 border rounded bg-light mb-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-credit-card me-1 text-primary"></i> Informações para Pagamento</h6>
                            <p class="small text-muted mb-1"><strong>Forma de Pagamento:</strong> <?= htmlspecialchars($fatura['forma_pagamento']) ?></p>
                            <?php if ($empresa['chave_pix']): ?>
                                <p class="small text-muted mb-1"><strong>Chave PIX:</strong> <span class="badge bg-white text-dark border font-monospace"><?= htmlspecialchars($empresa['chave_pix']) ?></span></p>
                            <?php endif; ?>
                            <?php if ($fatura['observacoes']): ?>
                                <div class="mt-2 pt-2 border-top">
                                    <strong class="small text-dark">Observações:</strong>
                                    <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($fatura['observacoes'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <ul class="list-group list-group-flush fs-6 border rounded">
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Subtotal</span>
                                <span><?= format_moeda($fatura['subtotal']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2 text-danger">
                                <span>Desconto</span>
                                <span>- <?= format_moeda($fatura['desconto']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3 bg-light fw-bold fs-4 text-primary">
                                <span>VALOR TOTAL</span>
                                <span><?= format_moeda($fatura['valor_total']) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Rodapé de Autenticação -->
                <div class="pt-4 border-top text-center text-muted small">
                    <p class="mb-0"><?= htmlspecialchars($empresa['observacoes_padrao'] ?: 'Obrigado pela preferência!') ?></p>
                    <small>Documento gerado eletronicamente por eBill Mini ERP em <?= date('d/m/Y H:i') ?></small>
                </div>

            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
