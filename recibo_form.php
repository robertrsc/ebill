<?php
/**
 * Emissão de Recibo Avulso
 * eBill Mini ERP Web
 */
$page_title = "Emissão de Recibo Avulso";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

$clientes = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE status = 'Ativo' ORDER BY nome ASC")->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $cliente_id      = (int)($_POST['cliente_id'] ?? 0);
    $data_recibo     = $_POST['data_recibo'] ?? date('Y-m-d');
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'Pix';
    $referente_a     = trim($_POST['referente_a'] ?? '');

    $valor_raw       = str_replace('.', '', $_POST['valor'] ?? '0');
    $valor_raw       = str_replace(',', '.', $valor_raw);
    $valor           = (float)$valor_raw;

    if ($cliente_id <= 0 || $valor <= 0 || empty($referente_a)) {
        set_flash_message('danger', 'Preencha todos os campos obrigatórios (Cliente, Valor e Referente a).');
    } else {
        try {
            $num_recibo = 'REC-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $hash_autenticacao = hash('sha256', $num_recibo . microtime());

            $stmt = $pdo->prepare("
                INSERT INTO recibos (numero_recibo, cliente_id, valor, data_recibo, referente_a, forma_pagamento, hash_autenticacao) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$num_recibo, $cliente_id, $valor, $data_recibo, $referente_a, $forma_pagamento, $hash_autenticacao]);
            $recibo_id = $pdo->lastInsertId();

            set_flash_message('success', 'Recibo emitido com sucesso!');
            header("Location: gerar_recibo_pdf.php?id=" . $recibo_id);
            exit;

        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao emitir recibo: ' . $e->getMessage());
        }
    }
}
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-file-signature text-success me-2"></i>Emitir Recibo Avulso</h1>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="recibos.php" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="POST" action="recibo_form.php">
                <div class="card card-outline card-success shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-receipt text-success me-2"></i>Informações do Comprovante de Pagamento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Cliente -->
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label fw-semibold">Pagador / Cliente <span class="text-danger">*</span></label>
                                <select name="cliente_id" id="cliente_id" class="form-select" required>
                                    <option value="">-- Selecione o Cliente --</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> <?= $c['cpf_cnpj'] ? '('.$c['cpf_cnpj'].')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Valor -->
                            <div class="col-md-3">
                                <label for="valor" class="form-label fw-semibold">Valor Recebido (R$) <span class="text-danger">*</span></label>
                                <input type="text" name="valor" id="valor" class="form-control mask-money fw-bold text-success fs-5" required placeholder="0,00">
                            </div>

                            <!-- Data -->
                            <div class="col-md-3">
                                <label for="data_recibo" class="form-label fw-semibold">Data do Pagamento</label>
                                <input type="date" name="data_recibo" id="data_recibo" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>

                            <!-- Forma de Pagamento -->
                            <div class="col-md-4">
                                <label for="forma_pagamento" class="form-label fw-semibold">Forma de Pagamento</label>
                                <select name="forma_pagamento" id="forma_pagamento" class="form-select">
                                    <option value="Pix">Pix</option>
                                    <option value="Dinheiro">Dinheiro</option>
                                    <option value="Boleto">Boleto Bancário</option>
                                    <option value="Cartao_Credito">Cartão de Crédito</option>
                                    <option value="Cartao_Debito">Cartão de Débito</option>
                                    <option value="Transferencia">Transferência Bancária</option>
                                </select>
                            </div>

                            <!-- Referente a -->
                            <div class="col-md-12">
                                <label for="referente_a" class="form-label fw-semibold">Referente a (Descrição Detalhada) <span class="text-danger">*</span></label>
                                <textarea name="referente_a" id="referente_a" class="form-control" rows="3" required placeholder="Ex: Pagamento referente à prestação de serviços de consultoria referente ao mês de Setembro..."></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <a href="recibos.php" class="btn btn-light border px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-file-pdf me-1"></i> Gerar Recibo PDF</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
