<?php
function notif_garantir_coluna(PDO $pdo)
{
    if (!empty($_SESSION['_notif_coluna_ok'])) {
        return;
    }

    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ocorrencias' AND COLUMN_NAME = 'atualizacao_vista'"
    );
    if ((int) $stmt->fetchColumn() === 0) {
        // DEFAULT 1: ocorrências já existentes começam como "vistas".
        $pdo->exec('ALTER TABLE ocorrencias ADD COLUMN atualizacao_vista TINYINT(1) NOT NULL DEFAULT 1');
    }

    $_SESSION['_notif_coluna_ok'] = true;
}

/** Ocorrências do cidadão com atualização ainda não vista, da mais recente para a mais antiga. */
function notif_buscar(PDO $pdo, $usuarioId)
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.categoria, o.status, o.recado_adm,
                h.status_anterior, h.status_novo, h.recado AS recado_hist, h.criado_em AS quando
         FROM ocorrencias o
         LEFT JOIN ocorrencia_historico h
                ON h.id = (SELECT MAX(h2.id) FROM ocorrencia_historico h2 WHERE h2.ocorrencia_id = o.id)
         WHERE o.usuario_id = ? AND o.atualizacao_vista = 0
         ORDER BY COALESCE(h.criado_em, o.recado_atualizado_em) DESC, o.id DESC'
    );
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

/** Última alteração registrada no histórico de uma ocorrência (ou null). */
function notif_ultima_alteracao(PDO $pdo, $ocorrenciaId)
{
    $stmt = $pdo->prepare(
        'SELECT status_anterior, status_novo, recado, criado_em
         FROM ocorrencia_historico WHERE ocorrencia_id = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$ocorrenciaId]);
    $linha = $stmt->fetch();
    return $linha ? $linha : null;
}

/** Marca a atualização como vista (só se a ocorrência for do usuário informado). */
function notif_marcar_vista(PDO $pdo, $ocorrenciaId, $usuarioId)
{
    $stmt = $pdo->prepare('UPDATE ocorrencias SET atualizacao_vista = 1 WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$ocorrenciaId, $usuarioId]);
}

/** Texto curto "Pendente → Resolvido" (ou só o status atual quando só o recado mudou). */
function notif_texto_status(array $n, array $statusInfo)
{
    $novo = isset($n['status_novo']) && $n['status_novo'] !== null ? $n['status_novo'] : (isset($n['status']) ? $n['status'] : null);
    $ant  = isset($n['status_anterior']) ? $n['status_anterior'] : null;

    if ($novo === null || !isset($statusInfo[$novo])) {
        return 'Atualizada pela prefeitura';
    }
    if ($ant !== null && $ant !== $novo && isset($statusInfo[$ant])) {
        return $statusInfo[$ant]['label'] . ' → ' . $statusInfo[$novo]['label'];
    }
    return 'Novo recado · ' . $statusInfo[$novo]['label'];
}
?>