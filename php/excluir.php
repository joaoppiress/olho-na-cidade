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

$stmt = $pdo->prepare('SELECT id, usuario_id, status, foto_path FROM ocorrencias WHERE id = ?');
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
