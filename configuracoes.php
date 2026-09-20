<?php
/**
 * Configurações da Empresa e SMTP
 * eBill Mini ERP Web
 */
$page_title = "Configurações da Empresa & SMTP";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = get_db_connection();
$empresa = get_empresa_info();

// Envio de E-mail de Teste SMTP
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['acao_smtp']) && $_POST['acao_smtp'] === 'testar') {
    $email_destino = trim($_POST['email_teste'] ?? $_SESSION['usuario_email'] ?? $empresa['email']);
    
    if (empty($email_destino)) {
        set_flash_message('danger', 'Informe um e-mail de destino válido para o teste.');
    } else {
        $assunto = "Teste de Conexão SMTP - eBill ERP";
        $corpo = "
        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #0284c7; border-radius: 8px;'>
            <h3 style='color: #0284c7; margin-top: 0;'>Conexão SMTP Configurada com Sucesso!</h3>
            <p>Este é um e-mail de teste disparado pelo <strong>eBill Mini ERP</strong> para validar as configurações de SMTP no seu servidor/hospedagem cPanel.</p>
            <p><strong>Data do Teste:</strong> " . date('d/m/Y H:i:s') . "</p>
            <p><strong>Servidor SMTP:</strong> " . htmlspecialchars($empresa['smtp_host']) . ":" . $empresa['smtp_porta'] . "</p>
            <hr style='border: 0; border-top: 1px solid #e2e8f0;'>
            <small style='color: #64748b;'>eBill Mini ERP System &bull; Hospedagem cPanel Pronta</small>
        </div>";

        $res = enviar_email_smtp($email_destino, "Administrador ERP", $assunto, $corpo);
        if ($res) {
            set_flash_message('success', 'E-mail de teste enviado com sucesso para ' . $email_destino . '!');
        } else {
            set_flash_message('danger', 'Falha ao enviar e-mail de teste. Verifique os dados do servidor SMTP.');
        }
    }
    header("Location: configuracoes.php");
    exit;
}

// Salvar Configurações Gerais e SMTP
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !isset($_POST['acao_smtp'])) {
    $nome_fantasia      = trim($_POST['nome_fantasia'] ?? '');
    $razao_social       = trim($_POST['razao_social'] ?? '');
    $cnpj               = trim($_POST['cnpj'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $telefone           = trim($_POST['telefone'] ?? '');
    $celular            = trim($_POST['celular'] ?? '');
    $cep                = trim($_POST['cep'] ?? '');
    $endereco           = trim($_POST['endereco'] ?? '');
    $numero             = trim($_POST['numero'] ?? '');
    $complemento        = trim($_POST['complemento'] ?? '');
    $bairro             = trim($_POST['bairro'] ?? '');
    $cidade             = trim($_POST['cidade'] ?? '');
    $uf                 = strtoupper(trim($_POST['uf'] ?? ''));
    $chave_pix          = trim($_POST['chave_pix'] ?? '');
    $observacoes_padrao = trim($_POST['observacoes_padrao'] ?? '');

    // Configurações SMTP
    $smtp_host          = trim($_POST['smtp_host'] ?? 'localhost');
    $smtp_porta         = (int)($_POST['smtp_porta'] ?? 587);
    $smtp_usuario       = trim($_POST['smtp_usuario'] ?? '');
    $smtp_senha         = $_POST['smtp_senha'] ?? '';
    $smtp_seguranca     = $_POST['smtp_seguranca'] ?? 'tls';
    $email_remetente    = trim($_POST['email_remetente'] ?? '');
    $nome_remetente     = trim($_POST['nome_remetente'] ?? '');

    if (empty($nome_fantasia) || empty($razao_social) || empty($cnpj)) {
        set_flash_message('danger', 'Os campos Nome Fantasia, Razão Social e CNPJ são obrigatórios.');
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE configuracao_empresa SET 
                nome_fantasia = ?, razao_social = ?, cnpj = ?, email = ?, telefone = ?, celular = ?, 
                cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, 
                uf = ?, chave_pix = ?, observacoes_padrao = ?, 
                smtp_host = ?, smtp_porta = ?, smtp_usuario = ?, smtp_senha = ?, smtp_seguranca = ?, 
                email_remetente = ?, nome_remetente = ? WHERE id = 1
            ");
            $stmt->execute([
                $nome_fantasia, $razao_social, $cnpj, $email, $telefone, $celular,
                $cep, $endereco, $numero, $complemento, $bairro, $cidade,
                $uf, $chave_pix, $observacoes_padrao,
                $smtp_host, $smtp_porta, $smtp_usuario, $smtp_senha, $smtp_seguranca,
                $email_remetente, $nome_remetente
            ]);
            set_flash_message('success', 'Configurações registradas com sucesso!');
            header("Location: configuracoes.php");
            exit;
        } catch (PDOException $e) {
            set_flash_message('danger', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }
}
?>

<div class="content-wrapper bg-light">
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold text-dark fs-3"><i class="fas fa-cogs text-primary me-2"></i>Configurações da Empresa & SMTP</h1>
                    <p class="text-muted small mb-0">Gerencie os dados cadastrais da empresa e os parâmetros de envio de e-mail por SMTP.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <form method="POST" action="configuracoes.php">
                
                <!-- Perfil Institucional da Empresa -->
                <div class="card card-outline card-primary shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-building text-primary me-2"></i>Perfil Institucional</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="nome_fantasia" class="form-label fw-semibold">Nome Fantasia <span class="text-danger">*</span></label>
                                <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control" required value="<?= sanitizar($empresa['nome_fantasia'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="razao_social" class="form-label fw-semibold">Razão Social <span class="text-danger">*</span></label>
                                <input type="text" name="razao_social" id="razao_social" class="form-control" required value="<?= sanitizar($empresa['razao_social'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="cnpj" class="form-label fw-semibold">CNPJ <span class="text-danger">*</span></label>
                                <input type="text" name="cnpj" id="cnpj" class="form-control mask-cpf-cnpj" required value="<?= sanitizar($empresa['cnpj'] ?? '') ?>">
                            </div>

                            <div class="col-md-4">
                                <label for="email" class="form-label fw-semibold">E-mail de Contato da Empresa</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= sanitizar($empresa['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="telefone" class="form-label fw-semibold">Telefone Fixo</label>
                                <input type="text" name="telefone" id="telefone" class="form-control mask-phone" value="<?= sanitizar($empresa['telefone'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="celular" class="form-label fw-semibold">Celular / WhatsApp</label>
                                <input type="text" name="celular" id="celular" class="form-control mask-phone" value="<?= sanitizar($empresa['celular'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço da Empresa -->
                <div class="card card-outline card-info shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-map-marker-alt text-info me-2"></i>Endereço</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="cep" class="form-label fw-semibold">CEP</label>
                                <input type="text" name="cep" id="cep" class="form-control mask-cep" value="<?= sanitizar($empresa['cep'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="endereco" class="form-label fw-semibold">Endereço</label>
                                <input type="text" name="endereco" id="endereco" class="form-control" value="<?= sanitizar($empresa['endereco'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="numero" class="form-label fw-semibold">Número</label>
                                <input type="text" name="numero" id="numero" class="form-control" value="<?= sanitizar($empresa['numero'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="complemento" class="form-label fw-semibold">Complemento</label>
                                <input type="text" name="complemento" id="complemento" class="form-control" value="<?= sanitizar($empresa['complemento'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="bairro" class="form-label fw-semibold">Bairro</label>
                                <input type="text" name="bairro" id="bairro" class="form-control" value="<?= sanitizar($empresa['bairro'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="cidade" class="form-label fw-semibold">Cidade</label>
                                <input type="text" name="cidade" id="cidade" class="form-control" value="<?= sanitizar($empresa['cidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="uf" class="form-label fw-semibold">UF</label>
                                <input type="text" name="uf" id="uf" class="form-control text-uppercase" maxlength="2" value="<?= sanitizar($empresa['uf'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chave PIX e Instruções -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-qrcode text-success me-2"></i>Chave PIX & Instruções</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="chave_pix" class="form-label fw-semibold">Chave PIX da Empresa</label>
                                <input type="text" name="chave_pix" id="chave_pix" class="form-control font-monospace" value="<?= sanitizar($empresa['chave_pix'] ?? '') ?>" placeholder="CNPJ, E-mail, Celular ou Chave Aleatória">
                            </div>
                            <div class="col-md-12">
                                <label for="observacoes_padrao" class="form-label fw-semibold">Mensagem Padrão no Rodapé dos Documentos</label>
                                <textarea name="observacoes_padrao" id="observacoes_padrao" class="form-control" rows="2"><?= sanitizar($empresa['observacoes_padrao'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configurações de E-mail & Servidor SMTP cPanel -->
                <div class="card card-outline card-warning shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold m-0"><i class="fas fa-paper-plane text-warning me-2"></i>Servidor SMTP & E-mail Transacional (cPanel)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <div class="col-md-4">
                                <label for="smtp_host" class="form-label fw-semibold">Servidor SMTP (Host)</label>
                                <input type="text" name="smtp_host" id="smtp_host" class="form-control font-monospace" value="<?= sanitizar($empresa['smtp_host'] ?? 'localhost') ?>" placeholder="ex: mail.seu-dominio.com.br">
                                <small class="text-muted">Use <code>localhost</code> para mailer nativo do cPanel</small>
                            </div>

                            <div class="col-md-2">
                                <label for="smtp_porta" class="form-label fw-semibold">Porta SMTP</label>
                                <input type="number" name="smtp_porta" id="smtp_porta" class="form-control" value="<?= (int)($empresa['smtp_porta'] ?? 587) ?>" placeholder="587">
                            </div>

                            <div class="col-md-3">
                                <label for="smtp_seguranca" class="form-label fw-semibold">Criptografia / Segurança</label>
                                <select name="smtp_seguranca" id="smtp_seguranca" class="form-select">
                                    <option value="tls" <?= ($empresa['smtp_seguranca'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (Porta 587)</option>
                                    <option value="ssl" <?= ($empresa['smtp_seguranca'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Porta 465)</option>
                                    <option value="none" <?= ($empresa['smtp_seguranca'] ?? '') === 'none' ? 'selected' : '' ?>>Nenhuma (Sem SSL)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="smtp_usuario" class="form-label fw-semibold">Usuário SMTP</label>
                                <input type="text" name="smtp_usuario" id="smtp_usuario" class="form-control" value="<?= sanitizar($empresa['smtp_usuario'] ?? '') ?>" placeholder="ex: financeiro@seu-dominio.com">
                            </div>

                            <div class="col-md-4">
                                <label for="smtp_senha" class="form-label fw-semibold">Senha SMTP</label>
                                <input type="password" name="smtp_senha" id="smtp_senha" class="form-control" value="<?= sanitizar($empresa['smtp_senha'] ?? '') ?>" placeholder="••••••••">
                            </div>

                            <div class="col-md-4">
                                <label for="email_remetente" class="form-label fw-semibold">E-mail do Remetente (From)</label>
                                <input type="email" name="email_remetente" id="email_remetente" class="form-control" value="<?= sanitizar($empresa['email_remetente'] ?? '') ?>" placeholder="ex: nao-responda@seu-dominio.com">
                            </div>

                            <div class="col-md-4">
                                <label for="nome_remetente" class="form-label fw-semibold">Nome do Remetente</label>
                                <input type="text" name="nome_remetente" id="nome_remetente" class="form-control" value="<?= sanitizar($empresa['nome_remetente'] ?? '') ?>" placeholder="ex: eBill ERP">
                            </div>

                        </div>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <button type="submit" class="btn btn-primary px-4 me-2"><i class="fas fa-save me-1"></i> Salvar Todas as Configurações</button>
                    </div>
                </div>

            </form>

            <!-- Testar Disparo de E-mail SMTP -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold m-0"><i class="fas fa-vial text-info me-2"></i>Testar Conexão de E-mail SMTP</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="configuracoes.php" class="row g-2 align-items-center">
                        <input type="hidden" name="acao_smtp" value="testar">
                        <div class="col-md-8">
                            <input type="email" name="email_teste" class="form-control" required value="<?= htmlspecialchars($_SESSION['usuario_email'] ?? $empresa['email']) ?>" placeholder="Informe um e-mail para receber a mensagem de teste">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-info w-100 fw-bold"><i class="fas fa-paper-plane me-1"></i> Disparar E-mail de Teste</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
