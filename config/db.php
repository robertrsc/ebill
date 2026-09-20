<?php
/**
 * Configuração de Conexão com o Banco de Dados MySQL, Sessões e Utilitários
 * eBill Mini ERP Web
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'ebill');
define('DB_USER', 'servidor');
define('DB_PASS', 'Nv32125');
define('DB_CHARSET', 'utf8mb4');

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("<div style='font-family: sans-serif; padding: 20px; color: red;'>
                <h2>Erro de Conexão com o Banco de Dados</h2>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>");
        }
    }
    return $pdo;
}

// Retorna dados cadastrais da empresa
function get_empresa_info() {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT * FROM configuracao_empresa WHERE id = 1 LIMIT 1");
    $empresa = $stmt->fetch();
    if (!$empresa) {
        return [
            'nome_fantasia' => 'eBill Soluções ERP',
            'razao_social'  => 'eBill Tecnologia & Sistemas Ltda',
            'cnpj'          => '12.345.678/0001-90',
            'email'         => 'contato@ebill.com.br',
            'telefone'      => '(11) 3333-4444',
            'celular'       => '(11) 98888-7777',
            'cep'           => '01001-000',
            'endereco'      => 'Av. Paulista',
            'numero'        => '1000',
            'bairro'        => 'Bela Vista',
            'cidade'        => 'São Paulo',
            'uf'            => 'SP',
            'chave_pix'      => '12.345.678/0001-90',
            'observacoes_padrao' => 'Obrigado pela preferência!',
            'smtp_host'     => 'localhost',
            'smtp_porta'    => 587,
            'smtp_usuario'  => '',
            'smtp_senha'    => '',
            'smtp_seguranca'=> 'tls',
            'email_remetente' => 'nao-responda@ebill.com.br',
            'nome_remetente'  => 'eBill ERP System'
        ];
    }
    return $empresa;
}

// Verifica se o usuário está autenticado
function checar_login() {
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        set_flash_message('warning', 'Por favor, efetue o login para acessar o sistema.');
        header("Location: login.php");
        exit;
    }
}

// Verifica se o usuário logado tem perfil root
function checar_perfil_root() {
    checar_login();
    if (($_SESSION['usuario_perfil'] ?? '') !== 'root') {
        set_flash_message('danger', 'Acesso negado. Apenas o usuário root master possui permissão para este módulo.');
        header("Location: index.php");
        exit;
    }
}

// Retorna o usuário logado
function usuario_logado() {
    if (!isset($_SESSION['usuario_id'])) return null;
    return [
        'id' => $_SESSION['usuario_id'],
        'usuario' => $_SESSION['usuario_login'] ?? '',
        'nome' => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email' => $_SESSION['usuario_email'] ?? '',
        'perfil' => $_SESSION['usuario_perfil'] ?? 'operador'
    ];
}

function is_root() {
    return ($_SESSION['usuario_perfil'] ?? '') === 'root';
}

// Formatação Monetária BR
function format_moeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

// Formatação de Data BR (YYYY-MM-DD -> DD/MM/YYYY)
function format_data($data) {
    if (empty($data) || $data === '0000-00-00') return '-';
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

// Sanitização contra XSS
function sanitizar($str) {
    if ($str === null) return '';
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

// Limpa caracteres especiais de CPF/CNPJ/CEP/Telefone
function limpar_mascara($str) {
    if ($str === null) return '';
    return preg_replace('/[^0-9]/', '', (string)$str);
}

// Mensagens Flash de Notificação
function set_flash_message($tipo, $mensagem) {
    $_SESSION['flash_message'] = [
        'tipo' => $tipo, // success, danger, warning, info
        'texto' => $mensagem
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Converte valor numérico para Extenso em Português do Brasil (Reais e Centavos)
 */
function valor_por_extenso($valor = 0) {
    $singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
    $plural   = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhões");

    $c = array("", "cento", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
    $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa");
    $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove");
    $u = array("", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove");

    $z = 0;
    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);
    for ($i = 0; $i < count($inteiro); $i++) {
        for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++) {
            $inteiro[$i] = "0" . $inteiro[$i];
        }
    }

    $fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);
    $rt = "";

    for ($i = 0; $i < count($inteiro); $i++) {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

        $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
        $t = count($inteiro) - 1 - $i;
        $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
        if ($valor == "000") $z++; elseif ($z > 0) $z--;
        if (($t == 1) && ($z > 0) && ($inteiro[0] > 0)) $r .= (($z > 1) ? " de " : " ") . $plural[$t];
        if ($r) $rt .= (($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
    }

    $rt = trim($rt);
    return $rt ? ucfirst($rt) : "Zero reais";
}

/**
 * Envio de E-mail via PHPMailer / SMTP compatível com cPanel
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviar_email_smtp($para_email, $para_nome, $assunto, $corpo_html) {
    $empresa = get_empresa_info();
    
    $phpmailer_path = __DIR__ . '/../lib/phpmailer/PHPMailer.php';
    if (!file_exists($phpmailer_path)) {
        // Fallback para mail() nativo caso PHPMailer não esteja presente
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . ($empresa['nome_remetente'] ?: 'eBill ERP') . " <" . ($empresa['email_remetente'] ?: $empresa['email']) . ">\r\n";
        return mail($para_email, $assunto, $corpo_html, $headers);
    }

    require_once __DIR__ . '/../lib/phpmailer/Exception.php';
    require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/phpmailer/SMTP.php';

    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP
        if (!empty($empresa['smtp_host']) && $empresa['smtp_host'] !== 'localhost') {
            $mail->isSMTP();
            $mail->Host       = $empresa['smtp_host'];
            $mail->SMTPAuth   = !empty($empresa['smtp_usuario']);
            $mail->Username   = $empresa['smtp_usuario'];
            $mail->Password   = $empresa['smtp_senha'];
            
            if ($empresa['smtp_seguranca'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($empresa['smtp_seguranca'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port       = (int)($empresa['smtp_porta'] ?: 587);
        } else {
            // Se for localhost (cPanel mailer padrão)
            $mail->isMail();
        }

        $mail->CharSet = 'UTF-8';
        $remetente_email = !empty($empresa['email_remetente']) ? $empresa['email_remetente'] : $empresa['email'];
        $remetente_nome  = !empty($empresa['nome_remetente']) ? $empresa['nome_remetente'] : $empresa['nome_fantasia'];

        $mail->setFrom($remetente_email, $remetente_nome);
        $mail->addAddress($para_email, $para_nome);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo_html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $corpo_html));

        return $mail->send();

    } catch (Exception $e) {
        // Fallback em hospedagem cPanel se SMTP falhar
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . ($empresa['nome_remetente'] ?: 'eBill ERP') . " <" . ($empresa['email_remetente'] ?: $empresa['email']) . ">\r\n";
        return mail($para_email, $assunto, $corpo_html, $headers);
    }
}
