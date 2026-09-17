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

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    render_mensagem('Ocorrência inválida', ['O identificador informado não é válido.'], 'erro', $voltarHref, 'Voltar');
}

// Admin registra ou atualiza o recado
if ($ehAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $recado = trim(isset($_POST['recado']) ? $_POST['recado'] : '');

    $stmtAtualiza = $pdo->prepare('UPDATE ocorrencias SET recado_adm = ?, recado_atualizado_em = NOW() WHERE id = ?');
    $stmtAtualiza->execute([$recado === '' ? null : $recado, $id]);

    header('Location: ocorrencia.php?id=' . $id . '&recado=1');
    exit;
}

$stmt = $pdo->prepare('
    SELECT o.*, u.nome AS nome_cidadao, u.email AS email_cidadao
    FROM ocorrencias o
    JOIN usuarios u ON o.usuario_id = u.id
    WHERE o.id = ?
');
$stmt->execute([$id]);
$o = $stmt->fetch();

if (!$o) {
    render_mensagem('Ocorrência não encontrada', ['Essa ocorrência não existe ou foi removida.'], 'erro', $voltarHref, 'Voltar');
}

if (!$ehAdmin && (int) $o['usuario_id'] !== (int) $_SESSION['usuario_id']) {
    render_mensagem('Acesso não permitido', ['Você só pode visualizar as suas próprias ocorrências.'], 'erro', $voltarHref, 'Voltar');
}

$statusInfo = [
    'pendente'   => ['label' => 'Pendente',   'classe' => 'pendente'],
    'em_analise' => ['label' => 'Em análise', 'classe' => 'analise'],
    'resolvido'  => ['label' => 'Resolvido',  'classe' => 'resolvido'],
];
$info = $statusInfo[$o['status']];
$recadoSalvo = isset($_GET['recado']) && $_GET['recado'] === '1';
$podeExcluir = $ehAdmin || $o['status'] === 'pendente';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ocorrência #<?= str_pad((string) $o['id'], 4, '0', STR_PAD_LEFT) ?> — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .painel-wrap{ max-width:720px; margin:0 auto; padding:60px 20px; }
  .painel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .painel-top h1{ font-size:24px; font-weight:800; text-transform:none; letter-spacing:0; }
  .painel-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .painel-actions{ display:flex; gap:10px; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }

  .detail-card{ background:#fff; border:1px solid var(--line); border-radius:6px; overflow:hidden; margin-bottom:22px; }
  .detail-head{ padding:20px 24px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; }
  .detail-head .proto{ font-family:'IBM Plex Mono', monospace; font-size:13px; color:#8A8F96; }
  .detail-grid{ display:grid; grid-template-columns:1fr 1fr; gap:0; }
  .detail-item{ padding:18px 24px; border-bottom:1px solid var(--line); }
  .detail-item.full{ grid-column:1 / -1; }
  .detail-item .k{ font-size:11.5px; text-transform:uppercase; letter-spacing:0.05em; color:#8A8F96; margin-bottom:6px; }
  .detail-item .v{ font-size:14.5px; color:var(--ink); line-height:1.5; }
  .detail-foto{ padding:20px 24px; }
  .detail-foto img{ max-width:100%; border-radius:6px; border:1px solid var(--line); display:block; }

  .recado-box{ background:#fff; border:1px solid var(--line); border-radius:6px; padding:22px 24px; margin-bottom:22px; }
  .recado-box h2{ font-size:15px; font-weight:700; margin:0 0 12px; }
  .recado-atual{ background:rgba(46,74,107,0.06); border:1px solid rgba(46,74,107,0.18); border-radius:5px; padding:14px 16px; font-size:14px; color:var(--ink); line-height:1.55; margin-bottom:14px; white-space:pre-wrap; }
  .recado-data{ font-size:11.5px; color:#8A8F96; margin-top:8px; }
  .recado-vazio{ font-size:13.5px; color:#8A8F96; font-style:italic; }
  .confirm-box{
    margin-bottom:20px; padding:14px 16px; border-radius:5px;
    background:rgba(62,156,111,0.1); border:1px solid rgba(62,156,111,0.35); color:#276B49;
    font-size:13.5px;
  }
  .excluir-box{ background:#fff; border:1px solid rgba(232,90,50,0.35); border-radius:6px; padding:22px 24px; }
  .excluir-box h2{ font-size:15px; font-weight:700; margin:0 0 8px; color:#B23D1F; }
  .excluir-box p{ font-size:13.5px; color:#8A8F96; line-height:1.55; margin:0 0 14px; }
  .btn-excluir{
    border:1px solid #B23D1F; background:#B23D1F; color:#fff; padding:10px 18px; border-radius:4px;
    font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; cursor:pointer;
    font-family:'Inter', sans-serif;
  }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Detalhes da ocorrência</h1>
      <div class="sub">Protocolo #<?= str_pad((string) $o['id'], 4, '0', STR_PAD_LEFT) ?></div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="<?= htmlspecialchars($voltarHref) ?>">&larr; Voltar</a>
      <a class="btn-line" href="perfil.php">Meu perfil</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <?php if ($recadoSalvo): ?>
    <div class="confirm-box">✓ Recado salvo com sucesso.</div>
  <?php endif; ?>

  <div class="detail-card">
    <div class="detail-head">
      <span class="proto">#<?= str_pad((string) $o['id'], 4, '0', STR_PAD_LEFT) ?></span>
      <span class="pill <?= $info['classe'] ?>"><?= $info['label'] ?></span>
    </div>
    <div class="detail-grid">
      <div class="detail-item">
        <div class="k">Categoria</div>
        <div class="v"><?= htmlspecialchars($o['categoria']) ?></div>
      </div>
      <div class="detail-item">
        <div class="k">Bairro</div>
        <div class="v"><?= htmlspecialchars($o['bairro']) ?></div>
      </div>
      <div class="detail-item full">
        <div class="k">Endereço</div>
        <div class="v"><?= htmlspecialchars($o['endereco']) ?></div>
      </div>
      <div class="detail-item full">
        <div class="k">Descrição</div>
        <div class="v"><?= nl2br(htmlspecialchars($o['descricao'])) ?></div>
      </div>
      <?php if ($ehAdmin): ?>
        <div class="detail-item">
          <div class="k">Cidadão</div>
          <div class="v"><?= htmlspecialchars($o['nome_cidadao']) ?></div>
        </div>
        <div class="detail-item">
          <div class="k">E-mail</div>
          <div class="v"><?= htmlspecialchars($o['email_cidadao']) ?></div>
        </div>
      <?php endif; ?>
      <div class="detail-item <?= $ehAdmin ? '' : 'full' ?>">
        <div class="k">Registrado em</div>
        <div class="v mono"><?= htmlspecialchars($o['criado_em']) ?></div>
      </div>
    </div>
    <?php if (!empty($o['foto_path'])): ?>
      <div class="detail-foto">
        <div class="k" style="font-size:11.5px; text-transform:uppercase; letter-spacing:0.05em; color:#8A8F96; margin-bottom:10px;">Evidência fotográfica</div>
        <img src="<?= htmlspecialchars($o['foto_path']) ?>" alt="Foto da ocorrência">
      </div>
    <?php endif; ?>
  </div>

  <div class="recado-box">
    <h2>Recado da prefeitura</h2>

    <?php if (!empty($o['recado_adm'])): ?>
      <div class="recado-atual"><?= nl2br(htmlspecialchars($o['recado_adm'])) ?></div>
      <?php if (!empty($o['recado_atualizado_em'])): ?>
        <div class="recado-data">Atualizado em <?= htmlspecialchars($o['recado_atualizado_em']) ?></div>
      <?php endif; ?>
    <?php elseif (!$ehAdmin): ?>
      <p class="recado-vazio">Nenhum recado deixado pela prefeitura até o momento.</p>
    <?php endif; ?>

    <?php if ($ehAdmin): ?>
      <form class="plain-form" method="POST" action="ocorrencia.php?id=<?= $o['id'] ?>" style="margin-top:<?= empty($o['recado_adm']) ? '0' : '18px' ?>;">
        <div class="field">
          <label for="recado">Deixar recado para o cidadão</label>
          <textarea id="recado" name="recado" placeholder="Ex.: Equipe de manutenção já foi acionada e deve atender em até 5 dias úteis."><?= htmlspecialchars(isset($o['recado_adm']) ? $o['recado_adm'] : '') ?></textarea>
        </div>
        <div class="submit-row">
          <button class="btn-solid" type="submit">Salvar recado</button>
          <span class="form-note">O cidadão verá esse recado ao abrir a ocorrência.</span>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <div class="excluir-box">
    <h2>Excluir ocorrência</h2>
    <?php if ($podeExcluir): ?>
      <p>
        <?= $ehAdmin
            ? 'A ocorrência será removida definitivamente do sistema, junto com a foto enviada pelo cidadão.'
            : 'Registrou por engano? Enquanto a prefeitura não começar a análise, você pode excluir esta ocorrência. A ação não pode ser desfeita.' ?>
      </p>
      <form method="POST" action="excluir.php" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência? Essa ação não pode ser desfeita.');">
        <input type="hidden" name="id" value="<?= $o['id'] ?>">
        <button class="btn-excluir" type="submit">Excluir ocorrência</button>
      </form>
    <?php else: ?>
      <p>Esta ocorrência já está em análise pela prefeitura e não pode mais ser excluída.</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
