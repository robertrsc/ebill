<?php
/**
 * Gerador de Recibos em PDF de Alta Qualidade
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/config/db.php';
checar_login();
require_once __DIR__ . '/lib/fpdf.php';

$pdo = get_db_connection();
$empresa = get_empresa_info();

$recibo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$fatura_id = isset($_GET['fatura_id']) ? (int)$_GET['fatura_id'] : 0;

$recibo = null;

if ($recibo_id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nome as cliente_nome, c.cpf_cnpj, c.endereco, c.numero, c.bairro, c.cidade, c.uf, c.cep 
        FROM recibos r 
        JOIN clientes c ON r.cliente_id = c.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$recibo_id]);
    $recibo = $stmt->fetch();
} elseif ($fatura_id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nome as cliente_nome, c.cpf_cnpj, c.endereco, c.numero, c.bairro, c.cidade, c.uf, c.cep 
        FROM recibos r 
        JOIN clientes c ON r.cliente_id = c.id 
        WHERE r.fatura_id = ?
    ");
    $stmt->execute([$fatura_id]);
    $recibo = $stmt->fetch();
}

if (!$recibo) {
    die("<div style='font-family: sans-serif; padding: 20px; color: red;'>
        <h2>Recibo não encontrado</h2>
        <p>Não foi possível localizar o recibo especificado.</p>
        <a href='recibos.php'>Voltar para Recibos</a>
    </div>");
}

// Converter UTF-8 para ISO-8859-1 / Windows-1252 para FPDF
function txt($str) {
    return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$str);
}

// Classe de PDF Customizada
class ReciboPDF extends FPDF {
    function Header() {
        // Moldura externa elegante
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(2, 132, 199); // Azul eBill
        $this->Rect(10, 10, 190, 135);
    }
}

$pdf = new ReciboPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false);

// 1. Cabeçalho da Empresa
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(2, 132, 199);
$pdf->Cell(120, 8, txt($empresa['nome_fantasia']), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(60, 8, txt('RECIBO N° ' . $recibo['numero_recibo']), 0, 1, 'R');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(120, 5, txt('CNPJ: ' . $empresa['cnpj'] . ' | Tel: ' . $empresa['telefone']), 0, 0, 'L');
$pdf->Cell(60, 5, txt('Data: ' . format_data($recibo['data_recibo'])), 0, 1, 'R');

$pdf->Cell(180, 5, txt($empresa['endereco'] . ', ' . $empresa['numero'] . ' - ' . $empresa['cidade'] . '/' . $empresa['uf']), 0, 1, 'L');

$pdf->SetDrawColor(220, 220, 220);
$pdf->Line(15, 33, 195, 33);
$pdf->Ln(6);

// 2. Caixa Destaque de Valor
$pdf->SetFillColor(241, 245, 249);
$pdf->SetDrawColor(2, 132, 199);
$pdf->SetLineWidth(0.8);
$pdf->Rect(15, 38, 180, 14, 'DF');

$pdf->SetY(41);
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(90, 8, txt('  VALOR RECEBIDO:'), 0, 0, 'L');
$pdf->SetTextColor(16, 185, 129); // Verde dinheiro
$pdf->Cell(85, 8, txt(format_moeda($recibo['valor']) . '  '), 0, 1, 'R');

$pdf->Ln(8);

// 3. Texto do Recibo
$valor_extenso = valor_por_extenso($recibo['valor']);

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(30, 41, 59);

$texto = "Recebemos de " . $recibo['cliente_nome'] . " (CPF/CNPJ: " . ($recibo['cpf_cnpj'] ?: 'Não informado') . "), a quantia de " . format_moeda($recibo['valor']) . " (" . $valor_extenso . "), referente a " . $recibo['referente_a'] . ", com quitação efetuada através de " . $recibo['forma_pagamento'] . ".";

$pdf->MultiCell(180, 7, txt($texto), 0, 'J');

$pdf->Ln(6);

// 4. Local e Data por extenso
$meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
$ts_data = strtotime($recibo['data_recibo']);
$data_extenso = $empresa['cidade'] . ', ' . date('d', $ts_data) . ' de ' . $meses[(int)date('m', $ts_data)] . ' de ' . date('Y', $ts_data) . '.';

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(180, 6, txt($data_extenso), 0, 1, 'R');

$pdf->Ln(12);

// 5. Linha de Assinatura
$pdf->SetLineWidth(0.3);
$pdf->SetDrawColor(100, 100, 100);
$pdf->Line(110, 110, 190, 110);

$pdf->SetY(111);
$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 5, txt($empresa['razao_social']), 0, 1, 'C');

$pdf->SetX(110);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(80, 4, txt('Emitente / Assinatura Autorizada'), 0, 1, 'C');

// 6. Rodapé de Validação
$pdf->SetY(128);
$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(140, 140, 140);
$pdf->Cell(180, 4, txt('Código de Autenticação Digital: ' . ($recibo['hash_autenticacao'] ?: md5($recibo['id']))), 0, 1, 'C');
$pdf->Cell(180, 3, txt('Emitido via eBill ERP em ' . date('d/m/Y H:i:s')), 0, 1, 'C');

// Gerar PDF no navegador
$pdf->Output('I', 'Recibo_' . $recibo['numero_recibo'] . '.pdf');
exit;
