<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/includes/mensagem.php';

$ehAdmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
$voltarHref = $ehAdmin ? 'paineladm.php' : 'painel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_mensagem('Exclusão inválida', ['A exclusão precisa ser confirmada pelo botão da página.'], 'erro', $voltarHref, 'Voltar');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    render_mensagem('Ocorrência inválida', ['O identificador informado não é válido.'], 'erro', $voltarHref, 'Voltar');
}

$stmt = $pdo->prepare('
    SELECT o.*, u.nome AS nome_cidadao
    FROM ocorrencias o
    JOIN usuarios u ON o.usuario_id = u.id
    WHERE o.id = ?
');
$stmt->execute([$id]);
$o = $stmt->fetch();

if (!$o) {
    render_mensagem('Ocorrência não encontrada', ['Essa ocorrência não existe ou já foi removida.'], 'erro', $voltarHref, 'Voltar');
}

if (!$ehAdmin) {
    if ((int) $o['usuario_id'] !== (int) $_SESSION['usuario_id']) {
        render_mensagem('Acesso não permitido', ['Você só pode excluir as suas próprias ocorrências.'], 'erro', $voltarHref, 'Voltar');
    }
    if ($o['status'] !== 'pendente') {
        render_mensagem(
            'Não é possível excluir',
            ['Essa ocorrência já está sendo analisada pela prefeitura e não pode mais ser excluída.'],
            'erro',
            'ocorrencia.php?id=' . $id,
            'Voltar à ocorrência'
        );
    }
}

// Guarda uma cópia da ocorrência antes de excluir, para controle e auditoria.
// Só registra quando é o administrador quem exclui — exclusão feita pelo
// próprio cidadão (de uma ocorrência ainda pendente) não entra no histórico.
if ($ehAdmin) {
    $stmtLog = $pdo->prepare('
        INSERT INTO ocorrencia_excluida
            (ocorrencia_id_original, usuario_id, usuario_nome, categoria, bairro, endereco, descricao,
             foto_path, status_no_momento, criado_em_original, excluido_por_id, excluido_por_nome, excluido_por_tipo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmtLog->execute([
        $o['id'],
        $o['usuario_id'],
        $o['nome_cidadao'],
        $o['categoria'],
        $o['bairro'],
        $o['endereco'],
        $o['descricao'],
        $o['foto_path'],
        $o['status'],
        $o['criado_em'],
        $_SESSION['usuario_id'],
        $_SESSION['usuario_nome'],
        'admin',
    ]);
}

$stmtExclui = $pdo->prepare('DELETE FROM ocorrencias WHERE id = ?');
$stmtExclui->execute([$id]);

if (!empty($o['foto_path'])) {
    $arquivo = __DIR__ . '/' . $o['foto_path'];
    if (is_file($arquivo)) {
        unlink($arquivo);
    }
}

header('Location: ' . $voltarHref . '?excluida=1');
exit;