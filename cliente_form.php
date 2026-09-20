<?php
/**
 * Formulário de Cadastro e Edição de Cliente
 * eBill Mini ERP Web
 */
$page_title = "Cadastro de Cliente";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cliente = [
    'id' => 0,
    'tipo_pessoa' => 'Física',
    'nome' => '',
    'cpf_cnpj' => '',
    'email' => '',
    'telefone' => '',
    'celular' => '',
    'cep' => '',
    'endereco' => '',
    'numero' => '',
    'complemento' => '',
    'bairro' => '',
    'cidade' => '',
    'uf' => '',
    'status' => 'Ativo',
    'observacoes' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch();
    if ($dados) {
        $cliente = $dados;
        $page_title = "Editar Cliente #" . $id;
    } else {
        set_flash_message('danger', 'Cliente não encontrado.');
        header("Location: clientes.php");
        exit;
    }
}

// Processamento POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tipo_pessoa = $_POST['tipo_pessoa'] ?? 'Física';
    $nome        = trim($_POST['nome'] ?? '');
    $cpf_cnpj    = trim($_POST['cpf_cnpj'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $telefone    = trim($_POST['telefone'] ?? '');
    $celular     = trim($_POST['celular'] ?? '');
    $cep         = trim($_POST['cep'] ?? '');
    $endereco    = trim($_POST['endereco'] ?? '');
    $numero      = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro      = trim($_POST['bairro'] ?? '');
    $cidade      = trim($_POST['cidade'] ?? '');
    $uf          = strtoupper(trim($_POST['uf'] ?? ''));
    $status      = $_POST['status'] ?? 'Ativo';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (empty($nome)) {
        set_flash_message('danger', 'O campo Nome / Razão Social é obrigatório.');
    } else {
        try {
            if ($id > 0) {
                // UPDATE
                $sql = "UPDATE clientes SET 
                        tipo_pessoa = ?, nome = ?, cpf_cnpj = ?, email = ?, telefone = ?, celular = ?, 
                        cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, 
                        uf = ?, status = ?, observacoes = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $tipo_pessoa, $nome, $cpf_cnpj, $email, $telefone, $celular,
                    $cep, $endereco, $numero, $complemento, $bairro, $cidade,
                    $uf, $status, $observacoes, $id
                ]);
                set_flash_message('success', 'Cliente atualizado com sucesso!');
            } else {
                // INSERT
                $sql = "INSERT INTO clientes (tipo_pessoa, nome, cpf_cnpj, email, telefone, celular, cep, endereco, numero, complemento, bairro, cidade, uf, status, observacoes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $tipo_pessoa, $nome, $cpf_cnpj, $email, $telefone, $celular,
                    $cep, $endereco, $numero, $complemento, $bairro, $cidade,
                    $uf, $status, $observacoes
                ]);
                set_flash_message('success', 'Cliente cadastrado com sucesso!');
            }
            header("Location: clientes.php");
            exit;
        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao salvar cliente: ' . $e->getMessage());
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
                        <i class="fas fa-user-edit text-primary me-2"></i><?= $id > 0 ? 'Editar Cliente' : 'Novo Cliente' ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="clientes.php" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="POST" action="cliente_form.php<?= $id > 0 ? '?id='.$id : '' ?>">
                
                <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-id-card text-primary me-2"></i>Dados Principais</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Tipo Pessoa -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo de Pessoa</label>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_pessoa" id="tp_fisica" value="Física" <?= $cliente['tipo_pessoa'] === 'Física' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="tp_fisica">Física (CPF)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_pessoa" id="tp_juridica" value="Jurídica" <?= $cliente['tipo_pessoa'] === 'Jurídica' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="tp_juridica">Jurídica (CNPJ)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Nome -->
                            <div class="col-md-6">
                                <label for="nome" class="form-label fw-semibold">Nome Completo / Razão Social <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="nome" class="form-control" required value="<?= sanitizar($cliente['nome'] ?? '') ?>" placeholder="Ex: João da Silva ou Empresa Ltda">
                            </div>

                            <!-- CPF / CNPJ -->
                            <div class="col-md-3">
                                <label for="cpf_cnpj" class="form-label fw-semibold">CPF / CNPJ</label>
                                <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control mask-cpf-cnpj" value="<?= sanitizar($cliente['cpf_cnpj'] ?? '') ?>" placeholder="000.000.000-00">
                            </div>

                            <!-- Email -->
                            <div class="col-md-4">
                                <label for="email" class="form-label fw-semibold">E-mail</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= sanitizar($cliente['email'] ?? '') ?>" placeholder="cliente@email.com">
                            </div>

                            <!-- Telefone -->
                            <div class="col-md-3">
                                <label for="telefone" class="form-label fw-semibold">Telefone Fixo</label>
                                <input type="text" name="telefone" id="telefone" class="form-control mask-phone" value="<?= sanitizar($cliente['telefone'] ?? '') ?>" placeholder="(00) 0000-0000">
                            </div>

                            <!-- Celular -->
                            <div class="col-md-3">
                                <label for="celular" class="form-label fw-semibold">Celular / WhatsApp</label>
                                <input type="text" name="celular" id="celular" class="form-control mask-phone" value="<?= sanitizar($cliente['celular'] ?? '') ?>" placeholder="(00) 90000-0000">
                            </div>

                            <!-- Status -->
                            <div class="col-md-2">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Ativo" <?= ($cliente['status'] ?? 'Ativo') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="Inativo" <?= ($cliente['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="card card-outline card-info shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-map-marker-alt text-info me-2"></i>Endereço</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="cep" class="form-label fw-semibold">CEP <small class="text-muted">(Busca automática)</small></label>
                                <div class="input-group">
                                    <input type="text" name="cep" id="cep" class="form-control mask-cep" value="<?= sanitizar($cliente['cep'] ?? '') ?>" placeholder="00000-000">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="endereco" class="form-label fw-semibold">Endereço (Rua/Avenida)</label>
                                <input type="text" name="endereco" id="endereco" class="form-control" value="<?= sanitizar($cliente['endereco'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="numero" class="form-label fw-semibold">Número</label>
                                <input type="text" name="numero" id="numero" class="form-control" value="<?= sanitizar($cliente['numero'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="complemento" class="form-label fw-semibold">Complemento</label>
                                <input type="text" name="complemento" id="complemento" class="form-control" value="<?= sanitizar($cliente['complemento'] ?? '') ?>" placeholder="Ex: Sala 101, Bloco B">
                            </div>
                            <div class="col-md-3">
                                <label for="bairro" class="form-label fw-semibold">Bairro</label>
                                <input type="text" name="bairro" id="bairro" class="form-control" value="<?= sanitizar($cliente['bairro'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="cidade" class="form-label fw-semibold">Cidade</label>
                                <input type="text" name="cidade" id="cidade" class="form-control" value="<?= sanitizar($cliente['cidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="uf" class="form-label fw-semibold">UF</label>
                                <input type="text" name="uf" id="uf" class="form-control text-uppercase" maxlength="2" value="<?= sanitizar($cliente['uf'] ?? '') ?>" placeholder="SP">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-sticky-note text-secondary me-2"></i>Observações Internas</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="observacoes" class="form-control" rows="3" placeholder="Anotações internas sobre o cliente..."><?= sanitizar($cliente['observacoes'] ?? '') ?></textarea>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <a href="clientes.php" class="btn btn-light border px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Salvar Cliente</button>
                    </div>
                </div>

            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
