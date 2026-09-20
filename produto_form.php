<?php
/**
 * Cadastro e Edição de Produtos / Serviços
 * eBill Mini ERP Web
 */
$page_title = "Cadastro de Produto/Serviço";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = [
    'id' => 0,
    'codigo_sku' => 'PRD-' . rand(100, 999),
    'nome' => '',
    'tipo' => 'produto',
    'preco' => 0.00,
    'estoque' => 0,
    'estoque_minimo' => 5,
    'descricao' => '',
    'status' => 'Ativo'
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM produtos_servicos WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch();
    if ($dados) {
        $item = $dados;
        $page_title = "Editar Item #" . $id;
    } else {
        set_flash_message('danger', 'Item não encontrado.');
        header("Location: produtos_servicos.php");
        exit;
    }
}

// POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $codigo_sku     = trim($_POST['codigo_sku'] ?? '');
    $nome           = trim($_POST['nome'] ?? '');
    $tipo           = $_POST['tipo'] ?? 'produto';
    
    // Tratamento formato moeda (1.500,00 -> 1500.00)
    $preco_raw      = str_replace('.', '', $_POST['preco'] ?? '0');
    $preco_raw      = str_replace(',', '.', $preco_raw);
    $preco          = (float)$preco_raw;

    $estoque        = (int)($_POST['estoque'] ?? 0);
    $estoque_minimo = (int)($_POST['estoque_minimo'] ?? 5);
    $descricao      = trim($_POST['descricao'] ?? '');
    $status         = $_POST['status'] ?? 'Ativo';

    if (empty($nome) || empty($codigo_sku)) {
        set_flash_message('danger', 'Os campos Código SKU e Nome são obrigatórios.');
    } else {
        try {
            if ($id > 0) {
                // UPDATE
                $sql = "UPDATE produtos_servicos SET 
                        codigo_sku = ?, nome = ?, tipo = ?, preco = ?, estoque = ?, 
                        estoque_minimo = ?, descricao = ?, status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo_sku, $nome, $tipo, $preco, $estoque, $estoque_minimo, $descricao, $status, $id]);
                set_flash_message('success', 'Item atualizado com sucesso!');
            } else {
                // INSERT
                $sql = "INSERT INTO produtos_servicos (codigo_sku, nome, tipo, preco, estoque, estoque_minimo, descricao, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo_sku, $nome, $tipo, $preco, $estoque, $estoque_minimo, $descricao, $status]);
                set_flash_message('success', 'Item cadastrado com sucesso!');
            }
            header("Location: produtos_servicos.php");
            exit;
        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao salvar item: ' . $e->getMessage());
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
                        <i class="fas fa-box text-primary me-2"></i><?= $id > 0 ? 'Editar Item' : 'Novo Item' ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="produtos_servicos.php" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="POST" action="produto_form.php<?= $id > 0 ? '?id='.$id : '' ?>">
                
                <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-info-circle text-primary me-2"></i>Informações do Produto / Serviço</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Tipo -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo do Item</label>
                                <select name="tipo" id="select_tipo" class="form-select">
                                    <option value="produto" <?= $item['tipo'] === 'produto' ? 'selected' : '' ?>>Produto Físico</option>
                                    <option value="servico" <?= $item['tipo'] === 'servico' ? 'selected' : '' ?>>Serviço Prestado</option>
                                </select>
                            </div>

                            <!-- Código SKU -->
                            <div class="col-md-3">
                                <label for="codigo_sku" class="form-label fw-semibold">Código SKU <span class="text-danger">*</span></label>
                                <input type="text" name="codigo_sku" id="codigo_sku" class="form-control text-uppercase" required value="<?= sanitizar($item['codigo_sku'] ?? '') ?>">
                            </div>

                            <!-- Nome -->
                            <div class="col-md-6">
                                <label for="nome" class="form-label fw-semibold">Nome do Item / Serviço <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="nome" class="form-control" required value="<?= sanitizar($item['nome'] ?? '') ?>" placeholder="Ex: Monitor 24 polegadas ou Hora de Consultoria">
                            </div>

                            <!-- Preço -->
                            <div class="col-md-3">
                                <label for="preco" class="form-label fw-semibold">Preço Unitário (R$) <span class="text-danger">*</span></label>
                                <input type="text" name="preco" id="preco" class="form-control mask-money" required value="<?= number_format($item['preco'] ?? 0, 2, ',', '.') ?>">
                            </div>

                            <!-- Campo Estoque (Só para produtos) -->
                            <div class="col-md-3 campo-estoque" style="<?= ($item['tipo'] ?? 'produto') === 'servico' ? 'display:none;' : '' ?>">
                                <label for="estoque" class="form-label fw-semibold">Estoque Atual</label>
                                <input type="number" name="estoque" id="estoque" class="form-control" min="0" value="<?= (int)($item['estoque'] ?? 0) ?>">
                            </div>

                            <div class="col-md-3 campo-estoque" style="<?= ($item['tipo'] ?? 'produto') === 'servico' ? 'display:none;' : '' ?>">
                                <label for="estoque_minimo" class="form-label fw-semibold">Estoque Mínimo (Alerta)</label>
                                <input type="number" name="estoque_minimo" id="estoque_minimo" class="form-control" min="0" value="<?= (int)($item['estoque_minimo'] ?? 5) ?>">
                            </div>

                            <!-- Status -->
                            <div class="col-md-3">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Ativo" <?= ($item['status'] ?? 'Ativo') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="Inativo" <?= ($item['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>

                            <!-- Descrição -->
                            <div class="col-md-12">
                                <label for="descricao" class="form-label fw-semibold">Descrição Detalhada</label>
                                <textarea name="descricao" id="descricao" class="form-control" rows="3" placeholder="Insira detalhes técnicos ou observações sobre o item..."><?= sanitizar($item['descricao'] ?? '') ?></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <a href="produtos_servicos.php" class="btn btn-light border px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Salvar Item</button>
                    </div>
                </div>

            </form>
        </div>
    </section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const selectTipo = document.getElementById('select_tipo');
    const camposEstoque = document.querySelectorAll('.campo-estoque');
    
    selectTipo.addEventListener('change', function() {
        if (this.value === 'servico') {
            camposEstoque.forEach(el => el.style.display = 'none');
        } else {
            camposEstoque.forEach(el => el.style.display = 'block');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
