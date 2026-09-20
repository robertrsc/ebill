<?php
/**
 * Emissão e Edição de Faturas
 * eBill Mini ERP Web
 */
$page_title = "Emissão de Fatura";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cliente_pre_selecionado = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

$fatura = [
    'id' => 0,
    'numero_fatura' => 'FAT-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
    'cliente_id' => $cliente_pre_selecionado,
    'data_emissao' => date('Y-m-d'),
    'data_vencimento' => date('Y-m-d', strtotime('+15 days')),
    'subtotal' => 0.00,
    'desconto' => 0.00,
    'valor_total' => 0.00,
    'status' => 'Pendente',
    'forma_pagamento' => 'Pix',
    'observacoes' => ''
];
$fatura_itens = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM faturas WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch();
    if ($dados) {
        $fatura = $dados;
        $page_title = "Editar Fatura " . $fatura['numero_fatura'];

        $st_itens = $pdo->prepare("SELECT * FROM fatura_itens WHERE fatura_id = ?");
        $st_itens->execute([$id]);
        $fatura_itens = $st_itens->fetchAll();
    } else {
        set_flash_message('danger', 'Fatura não encontrada.');
        header("Location: faturas.php");
        exit;
    }
}

// Carregar Clientes e Produtos/Serviços
$clientes = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE status = 'Ativo' ORDER BY nome ASC")->fetchAll();
$catalogo_itens = $pdo->query("SELECT id, codigo_sku, nome, tipo, preco, estoque FROM produtos_servicos WHERE status = 'Ativo' ORDER BY nome ASC")->fetchAll();

// Processamento POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $numero_fatura   = trim($_POST['numero_fatura'] ?? '');
    $cliente_id      = (int)($_POST['cliente_id'] ?? 0);
    $data_emissao    = $_POST['data_emissao'] ?? date('Y-m-d');
    $data_vencimento = $_POST['data_vencimento'] ?? date('Y-m-d');
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'Pix';
    $status          = $_POST['status'] ?? 'Pendente';
    $observacoes     = trim($_POST['observacoes'] ?? '');

    $desconto_raw    = str_replace('.', '', $_POST['desconto'] ?? '0');
    $desconto_raw    = str_replace(',', '.', $desconto_raw);
    $desconto        = (float)$desconto_raw;

    $itens_post = $_POST['itens'] ?? [];

    if (empty($numero_fatura) || $cliente_id <= 0 || empty($itens_post)) {
        set_flash_message('danger', 'Preencha o cliente e inclua pelo menos 1 item na fatura.');
    } else {
        try {
            $pdo->beginTransaction();

            // Calcular Subtotal e Total
            $subtotal_calculado = 0.00;
            $itens_processados = [];

            foreach ($itens_post as $item_raw) {
                $desc = trim($item_raw['descricao'] ?? '');
                $qtd  = (int)($item_raw['quantidade'] ?? 1);
                
                $pr_raw = str_replace('.', '', $item_raw['preco_unitario'] ?? '0');
                $pr_raw = str_replace(',', '.', $pr_raw);
                $preco_unit = (float)$pr_raw;

                if (!empty($desc) && $qtd > 0) {
                    $item_id = !empty($item_raw['item_id']) ? (int)$item_raw['item_id'] : null;
                    $tipo = $item_raw['tipo'] ?? 'produto';
                    $sub = $qtd * $preco_unit;
                    $subtotal_calculado += $sub;

                    $itens_processados[] = [
                        'item_id' => $item_id,
                        'descricao' => $desc,
                        'tipo' => $tipo,
                        'quantidade' => $qtd,
                        'preco_unitario' => $preco_unit,
                        'subtotal' => $sub
                    ];
                }
            }

            $valor_total_calculado = max(0, $subtotal_calculado - $desconto);

            if ($id > 0) {
                // UPDATE Fatura
                $sql_fat = "UPDATE faturas SET 
                            numero_fatura = ?, cliente_id = ?, data_emissao = ?, data_vencimento = ?, 
                            subtotal = ?, desconto = ?, valor_total = ?, status = ?, forma_pagamento = ?, observacoes = ? 
                            WHERE id = ?";
                $stmt = $pdo->prepare($sql_fat);
                $stmt->execute([
                    $numero_fatura, $cliente_id, $data_emissao, $data_vencimento,
                    $subtotal_calculado, $desconto, $valor_total_calculado, $status, $forma_pagamento, $observacoes, $id
                ]);

                // Remover itens antigos e reinserir
                $pdo->prepare("DELETE FROM fatura_itens WHERE fatura_id = ?")->execute([$id]);
                $fatura_id_salvo = $id;
            } else {
                // INSERT Fatura
                $sql_fat = "INSERT INTO faturas (numero_fatura, cliente_id, data_emissao, data_vencimento, subtotal, desconto, valor_total, status, forma_pagamento, observacoes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql_fat);
                $stmt->execute([
                    $numero_fatura, $cliente_id, $data_emissao, $data_vencimento,
                    $subtotal_calculado, $desconto, $valor_total_calculado, $status, $forma_pagamento, $observacoes
                ]);
                $fatura_id_salvo = $pdo->lastInsertId();
            }

            // Inserir itens
            $stmt_item = $pdo->prepare("INSERT INTO fatura_itens (fatura_id, item_id, descricao, tipo, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($itens_processados as $it) {
                $stmt_item->execute([
                    $fatura_id_salvo, $it['item_id'], $it['descricao'], $it['tipo'], $it['quantidade'], $it['preco_unitario'], $it['subtotal']
                ]);
            }

            $pdo->commit();
            set_flash_message('success', 'Fatura salva com sucesso!');
            header("Location: fatura_detalhes.php?id=" . $fatura_id_salvo);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('danger', 'Erro ao salvar fatura: ' . $e->getMessage());
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
                        <i class="fas fa-file-invoice text-primary me-2"></i><?= $id > 0 ? 'Editar Fatura' : 'Nova Fatura' ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="faturas.php" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar / Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="POST" action="fatura_form.php<?= $id > 0 ? '?id='.$id : '' ?>" id="formFatura">
                
                <!-- Dados Gerais da Fatura -->
                <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-info-circle text-primary me-2"></i>Dados Gerais da Fatura</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Número Fatura -->
                            <div class="col-md-3">
                                <label for="numero_fatura" class="form-label fw-semibold">Número da Fatura <span class="text-danger">*</span></label>
                                <input type="text" name="numero_fatura" id="numero_fatura" class="form-control fw-bold" required value="<?= htmlspecialchars($fatura['numero_fatura']) ?>">
                            </div>

                            <!-- Cliente -->
                            <div class="col-md-5">
                                <label for="cliente_id" class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                                <select name="cliente_id" id="cliente_id" class="form-select" required>
                                    <option value="">-- Selecione o Cliente --</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $fatura['cliente_id'] == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nome']) ?> <?= $c['cpf_cnpj'] ? '('.$c['cpf_cnpj'].')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Data Emissão -->
                            <div class="col-md-2">
                                <label for="data_emissao" class="form-label fw-semibold">Data Emissão</label>
                                <input type="date" name="data_emissao" id="data_emissao" class="form-control" value="<?= $fatura['data_emissao'] ?>">
                            </div>

                            <!-- Data Vencimento -->
                            <div class="col-md-2">
                                <label for="data_vencimento" class="form-label fw-semibold">Data Vencimento</label>
                                <input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?= $fatura['data_vencimento'] ?>">
                            </div>

                            <!-- Forma de Pagamento -->
                            <div class="col-md-3">
                                <label for="forma_pagamento" class="form-label fw-semibold">Forma de Pagamento</label>
                                <select name="forma_pagamento" id="forma_pagamento" class="form-select">
                                    <option value="Pix" <?= $fatura['forma_pagamento'] === 'Pix' ? 'selected' : '' ?>>Pix</option>
                                    <option value="Boleto" <?= $fatura['forma_pagamento'] === 'Boleto' ? 'selected' : '' ?>>Boleto Bancário</option>
                                    <option value="Cartao_Credito" <?= $fatura['forma_pagamento'] === 'Cartao_Credito' ? 'selected' : '' ?>>Cartão de Crédito</option>
                                    <option value="Cartao_Debito" <?= $fatura['forma_pagamento'] === 'Cartao_Debito' ? 'selected' : '' ?>>Cartão de Débito</option>
                                    <option value="Dinheiro" <?= $fatura['forma_pagamento'] === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                                    <option value="Transferencia" <?= $fatura['forma_pagamento'] === 'Transferencia' ? 'selected' : '' ?>>Transferência Bancária (TED/DOC)</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3">
                                <label for="status" class="form-label fw-semibold">Status da Fatura</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Pendente" <?= $fatura['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="Paga" <?= $fatura['status'] === 'Paga' ? 'selected' : '' ?>>Paga</option>
                                    <option value="Atrasada" <?= $fatura['status'] === 'Atrasada' ? 'selected' : '' ?>>Atrasada</option>
                                    <option value="Cancelada" <?= $fatura['status'] === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Itens da Fatura -->
                <div class="card card-outline card-info shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-list text-info me-2"></i>Itens da Fatura (Produtos / Serviços)</h5>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" id="btnAdicionarItem">
                            <i class="fas fa-plus me-1"></i> Adicionar Item
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-custom align-middle mb-0" id="tabelaItens">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Item / Descrição</th>
                                        <th style="width: 15%;">Tipo</th>
                                        <th style="width: 15%;">Qtd</th>
                                        <th style="width: 15%;">Preço Unit. (R$)</th>
                                        <th style="width: 15%;">Subtotal (R$)</th>
                                        <th style="width: 5%;" class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyItens">
                                    <?php if (!empty($fatura_itens)): ?>
                                        <?php foreach ($fatura_itens as $idx => $it): ?>
                                        <tr class="linha-item">
                                            <td>
                                                <input type="hidden" name="itens[<?= $idx ?>][item_id]" value="<?= $it['item_id'] ?>">
                                                <input type="text" name="itens[<?= $idx ?>][descricao]" class="form-control form-control-sm input-descricao" required value="<?= htmlspecialchars($it['descricao']) ?>">
                                            </td>
                                            <td>
                                                <select name="itens[<?= $idx ?>][tipo]" class="form-select form-select-sm input-tipo">
                                                    <option value="produto" <?= $it['tipo'] === 'produto' ? 'selected' : '' ?>>Produto</option>
                                                    <option value="servico" <?= $it['tipo'] === 'servico' ? 'selected' : '' ?>>Serviço</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="itens[<?= $idx ?>][quantidade]" class="form-control form-control-sm input-qtd" min="1" value="<?= $it['quantidade'] ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="itens[<?= $idx ?>][preco_unitario]" class="form-control form-control-sm mask-money input-preco" value="<?= number_format($it['preco_unitario'], 2, ',', '.') ?>">
                                            </td>
                                            <td class="fw-bold text-dark text-subtotal">
                                                <?= format_moeda($it['subtotal']) ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-light text-danger btn-remover-item"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rodapé com Seletor Rápido do Catálogo e Totais -->
                    <div class="card-footer bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <label class="input-group-text bg-light text-secondary"><i class="fas fa-box me-1"></i> Catálogo:</label>
                                    <select id="selectCatalogo" class="form-select">
                                        <option value="">-- Escolher item do catálogo para inserir --</option>
                                        <?php foreach ($catalogo_itens as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                    data-nome="<?= htmlspecialchars($cat['nome']) ?>" 
                                                    data-tipo="<?= $cat['tipo'] ?>" 
                                                    data-preco="<?= number_format($cat['preco'], 2, ',', '.') ?>">
                                                [<?= $cat['tipo'] == 'produto' ? 'PROD' : 'SERV' ?>] <?= htmlspecialchars($cat['nome']) ?> - R$ <?= number_format($cat['preco'], 2, ',', '.') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="btnInserirCatalogo"><i class="fas fa-plus"></i> Inserir</button>
                                </div>
                            </div>
                            
                            <!-- Resumo Financeiro da Fatura -->
                            <div class="col-md-6 text-end">
                                <div class="d-inline-block text-start" style="min-width: 250px;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Subtotal:</span>
                                        <span class="fw-bold" id="lblSubtotal">R$ 0,00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted me-2">Desconto (R$):</span>
                                        <input type="text" name="desconto" id="inputDesconto" class="form-control form-control-sm mask-money text-end" style="width: 100px;" value="<?= number_format($fatura['desconto'], 2, ',', '.') ?>">
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fs-5 fw-bold text-primary">
                                        <span>Total:</span>
                                        <span id="lblTotal">R$ 0,00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observações da Fatura -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-comment-alt text-secondary me-2"></i>Observações da Fatura</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="observacoes" class="form-control" rows="3" placeholder="Informações de pagamento, instruções bancárias ou nota sobre o serviço..."><?= htmlspecialchars($fatura['observacoes']) ?></textarea>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <a href="faturas.php" class="btn btn-light border px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Salvar Fatura</button>
                    </div>
                </div>

            </form>
        </div>
    </section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let itemIndex = <?= count($fatura_itens) ?>;

    // Se a tabela estiver vazia na abertura, adiciona 1 linha por padrão
    if (itemIndex === 0) {
        adicionarLinhaItem('', 'servico', 1, '0,00');
    } else {
        calcularTotais();
    }

    // Botão Adicionar Item Vazio
    document.getElementById('btnAdicionarItem').addEventListener('click', function() {
        adicionarLinhaItem('', 'produto', 1, '0,00');
    });

    // Inserir Item Selecionado do Catálogo
    document.getElementById('btnInserirCatalogo').addEventListener('click', function() {
        const select = document.getElementById('selectCatalogo');
        const option = select.options[select.selectedIndex];
        if (option && option.value) {
            const nome = option.getAttribute('data-nome');
            const tipo = option.getAttribute('data-tipo');
            const preco = option.getAttribute('data-preco');
            const itemId = option.value;
            adicionarLinhaItem(nome, tipo, 1, preco, itemId);
            select.selectedIndex = 0;
        }
    });

    function adicionarLinhaItem(desc, tipo, qtd, preco, itemId = '') {
        const tbody = document.getElementById('bodyItens');
        const tr = document.createElement('tr');
        tr.className = 'linha-item';

        tr.innerHTML = `
            <td>
                <input type="hidden" name="itens[${itemIndex}][item_id]" value="${itemId}">
                <input type="text" name="itens[${itemIndex}][descricao]" class="form-control form-control-sm input-descricao" required value="${desc}" placeholder="Descrição do produto ou serviço">
            </td>
            <td>
                <select name="itens[${itemIndex}][tipo]" class="form-select form-select-sm input-tipo">
                    <option value="produto" ${tipo === 'produto' ? 'selected' : ''}>Produto</option>
                    <option value="servico" ${tipo === 'servico' ? 'selected' : ''}>Serviço</option>
                </select>
            </td>
            <td>
                <input type="number" name="itens[${itemIndex}][quantidade]" class="form-control form-control-sm input-qtd" min="1" value="${qtd}">
            </td>
            <td>
                <input type="text" name="itens[${itemIndex}][preco_unitario]" class="form-control form-control-sm mask-money input-preco" value="${preco}">
            </td>
            <td class="fw-bold text-dark text-subtotal">R$ 0,00</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light text-danger btn-remover-item"><i class="fas fa-trash"></i></button>
            </td>
        `;

        tbody.appendChild(tr);
        itemIndex++;

        // Aplicar máscara no novo input de preço
        $(tr).find('.mask-money').mask('#.##0,00', {reverse: true});
        vincularEventosLinha(tr);
        calcularTotais();
    }

    function vincularEventosLinha(tr) {
        $(tr).find('.input-qtd, .input-preco').on('input change blur', function() {
            calcularTotais();
        });

        $(tr).find('.btn-remover-item').on('click', function() {
            tr.remove();
            calcularTotais();
        });
    }

    // Vincular eventos nas linhas pré-existentes
    document.querySelectorAll('.linha-item').forEach(tr => vincularEventosLinha(tr));

    $('#inputDesconto').on('input change blur', function() {
        calcularTotais();
    });

    function parseMoedaBR(val) {
        if (!val) return 0;
        val = val.toString().replace(/\./g, '').replace(',', '.');
        return parseFloat(val) || 0;
    }

    function formatMoedaBR(val) {
        return 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calcularTotais() {
        let subtotalGeral = 0;

        document.querySelectorAll('.linha-item').forEach(tr => {
            const qtd = parseInt(tr.querySelector('.input-qtd').value) || 0;
            const preco = parseMoedaBR(tr.querySelector('.input-preco').value);
            const sub = qtd * preco;
            subtotalGeral += sub;

            tr.querySelector('.text-subtotal').textContent = formatMoedaBR(sub);
        });

        const desconto = parseMoedaBR(document.getElementById('inputDesconto').value);
        const totalFinal = Math.max(0, subtotalGeral - desconto);

        document.getElementById('lblSubtotal').textContent = formatMoedaBR(subtotalGeral);
        document.getElementById('lblTotal').textContent = formatMoedaBR(totalFinal);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
