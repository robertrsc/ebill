<?php
/**
 * Processamento de Ações em Faturas (Marcar Paga, Cancelar, Excluir)
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/config/db.php';
checar_login();
$pdo = get_db_connection();

$acao = $_GET['acao'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: faturas.php");
    exit;
}

// 1. AÇÃO: MARCAR FATURA COMO PAGA
if ($acao === 'marcar_paga') {
    try {
        $pdo->beginTransaction();

        // Buscar fatura
        $stmt = $pdo->prepare("SELECT * FROM faturas WHERE id = ?");
        $stmt->execute([$id]);
        $fatura = $stmt->fetch();

        if (!$fatura) {
            throw new Exception("Fatura não encontrada.");
        }

        if ($fatura['status'] === 'Paga') {
            set_flash_message('info', 'Esta fatura já consta como paga.');
            header("Location: fatura_detalhes.php?id=" . $id);
            exit;
        }

        $data_hoje = date('Y-m-d');

        // Update status fatura
        $up = $pdo->prepare("UPDATE faturas SET status = 'Paga', data_pagamento = ? WHERE id = ?");
        $up->execute([$data_hoje, $id]);

        // Baixa no estoque dos produtos da fatura
        $st_itens = $pdo->prepare("SELECT item_id, tipo, quantidade FROM fatura_itens WHERE fatura_id = ?");
        $st_itens->execute([$id]);
        $itens = $st_itens->fetchAll();

        $up_estoque = $pdo->prepare("UPDATE produtos_servicos SET estoque = GREATEST(0, estoque - ?) WHERE id = ? AND tipo = 'produto'");
        foreach ($itens as $it) {
            if ($it['tipo'] === 'produto' && !empty($it['item_id'])) {
                $up_estoque->execute([$it['quantidade'], $it['item_id']]);
            }
        }

        // Criar lançamento financeiro (Receita)
        $st_fin = $pdo->prepare("
            INSERT INTO lancamentos_financeiros 
            (tipo, descricao, categoria, valor, data_vencimento, data_pagamento, status, fatura_id, cliente_id, observacoes) 
            VALUES ('receita', ?, 'Vendas', ?, ?, ?, 'Pago', ?, ?, ?)
        ");
        $st_fin->execute([
            'Recebimento Fatura ' . $fatura['numero_fatura'],
            $fatura['valor_total'],
            $fatura['data_vencimento'],
            $data_hoje,
            $id,
            $fatura['cliente_id'],
            'Recebimento registrado automaticamente no pagamento da fatura ' . $fatura['numero_fatura']
        ]);

        // Gerar Recibo se ainda não existir
        $st_check_rec = $pdo->prepare("SELECT id FROM recibos WHERE fatura_id = ?");
        $st_check_rec->execute([$id]);
        if (!$st_check_rec->fetch()) {
            $num_recibo = 'REC-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $hash_autenticacao = hash('sha256', $num_recibo . $id . microtime());
            $ref_msg = 'Quitação integral da Fatura ' . $fatura['numero_fatura'] . ' emitida em ' . format_data($fatura['data_emissao']);

            $st_recibo = $pdo->prepare("
                INSERT INTO recibos (numero_recibo, fatura_id, cliente_id, valor, data_recibo, referente_a, forma_pagamento, hash_autenticacao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $st_recibo->execute([
                $num_recibo, $id, $fatura['cliente_id'], $fatura['valor_total'], $data_hoje, $ref_msg, $fatura['forma_pagamento'], $hash_autenticacao
            ]);
        }

        $pdo->commit();
        set_flash_message('success', 'Fatura quitada com sucesso! Estoque atualizado e Recibo emitido.');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('danger', 'Erro ao dar baixa na fatura: ' . $e->getMessage());
    }

    header("Location: fatura_detalhes.php?id=" . $id);
    exit;
}

// 2. AÇÃO: CANCELAR FATURA
if ($acao === 'cancelar') {
    try {
        $stmt = $pdo->prepare("UPDATE faturas SET status = 'Cancelada' WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('warning', 'Fatura marcada como cancelada.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao cancelar fatura: ' . $e->getMessage());
    }
    header("Location: faturas.php");
    exit;
}

// 3. AÇÃO: EXCLUIR FATURA
if ($acao === 'excluir') {
    try {
        $stmt = $pdo->prepare("DELETE FROM faturas WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Fatura excluída do sistema.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Erro ao excluir fatura: ' . $e->getMessage());
    }
    header("Location: faturas.php");
    exit;
}

header("Location: faturas.php");
exit;
