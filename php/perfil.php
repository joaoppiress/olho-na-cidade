<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$ehAdmin = (isset($_SESSION['tipo']) ? $_SESSION['tipo'] : 'cidadao') === 'admin';

$stmtUsuario = $pdo->prepare('SELECT nome, email, aceite_lgpd, criado_em FROM usuarios WHERE id = ?');
$stmtUsuario->execute([$_SESSION['usuario_id']]);
$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

if ($ehAdmin) {
    $ocorrencias = [];
    $total = $pendentes = $emAnalise = $resolvidas = 0;
} else {
    $stmt = $pdo->prepare('SELECT id, categoria, bairro, endereco, descricao, status, criado_em FROM ocorrencias WHERE usuario_id = ? ORDER BY criado_em DESC');
    $stmt->execute([$_SESSION['usuario_id']]);
    $ocorrencias = $stmt->fetchAll();

    $total      = count($ocorrencias);
    $pendentes  = 0;
    $emAnalise  = 0;
    $resolvidas = 0;
    foreach ($ocorrencias as $o) {
        if ($o['status'] === 'pendente')   $pendentes++;
        if ($o['status'] === 'em_analise') $emAnalise++;
        if ($o['status'] === 'resolvido')  $resolvidas++;
    }
}

$statusInfo = [
    'pendente'    => ['label' => 'Pendente',    'classe' => 'pendente'],
    'em_analise'  => ['label' => 'Em análise',  'classe' => 'analise'],
    'resolvido'   => ['label' => 'Resolvido',   'classe' => 'resolvido'],
];

$membroDesde = date('d/m/Y', strtotime($usuario['criado_em']));

$iniciais = '';
foreach (array_slice(explode(' ', trim($usuario['nome'])), 0, 2) as $parte) {
    $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu perfil — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .perfil-wrap{ max-width:760px; margin:0 auto; padding:60px 20px; }
  .perfil-top{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .perfil-top h1{ font-size:26px; font-weight:800; text-transform:none; letter-spacing:0; }
  .perfil-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .perfil-actions{ display:flex; gap:10px; flex-wrap:wrap; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }

  .perfil-card{
    background:#fff; border:1px solid var(--line); border-radius:6px; padding:28px 26px; margin-bottom:24px;
    display:flex; gap:20px; align-items:center; flex-wrap:wrap;
  }
  .perfil-avatar{
    flex:none; width:64px; height:64px; border-radius:50%; background:var(--municipal); color:#fff;
    display:flex; align-items:center; justify-content:center; font-family:'Big Shoulders Display', sans-serif;
    font-size:24px; font-weight:800;
  }
  .perfil-id{ flex:1; min-width:200px; }
  .perfil-id .nome{ font-size:19px; font-weight:700; }
  .perfil-id .selo{
    display:inline-flex; align-items:center; gap:5px; margin-top:6px; font-size:11.5px; font-weight:700;
    text-transform:uppercase; letter-spacing:0.03em; color:#276B49; background:rgba(62,156,111,0.15);
    padding:3px 9px; border-radius:20px;
  }

  .info-grid{
    background:#fff; border:1px solid var(--line); border-radius:6px; overflow:hidden; margin-bottom:24px;
  }
  .info-row{
    display:flex; justify-content:space-between; align-items:center; gap:14px;
    padding:16px 22px; border-bottom:1px solid var(--line); flex-wrap:wrap;
  }
  .info-row:last-child{ border-bottom:none; }
  .info-row .label{ font-size:11.5px; text-transform:uppercase; letter-spacing:0.04em; color:#8A8F96; font-weight:700; }
  .info-row .value{ font-size:14.5px; font-weight:500; text-align:right; }
  .info-row .value.mono{ font-size:13.5px; letter-spacing:0.14em; color:#5B6169; }
  .info-hint{ font-size:12px; color:#8A8F96; margin-top:3px; text-align:right; }

  .section-label{
    font-size:12.5px; text-transform:uppercase; letter-spacing:0.04em; color:#8A8F96; font-weight:700;
    margin:0 0 12px 2px;
  }

  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }

  @media (max-width:520px){
    .info-row{ flex-direction:column; align-items:flex-start; }
    .info-row .value, .info-hint{ text-align:left; }
  }
</style>
</head>
<body style="background:var(--concrete);">

<div class="perfil-wrap">
  <div class="perfil-top">
    <div>
      <h1>Meu perfil</h1>
      <div class="sub"><?= $ehAdmin ? 'Seus dados de administrador.' : 'Seus dados e o histórico de ocorrências registradas.' ?></div>
    </div>
    <div class="perfil-actions">
      <?php if ($ehAdmin): ?>
        <a class="btn-line" href="paineladm.php">Painel administrativo</a>
      <?php else: ?>
        <a class="btn-line" href="painel.php">Meu painel</a>
      <?php endif; ?>
      <a class="btn-line" href="../index.php">&larr; Início</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <div class="perfil-card">
    <div class="perfil-avatar"><?= htmlspecialchars($iniciais ?: '?') ?></div>
    <div class="perfil-id">
      <div class="nome"><?= htmlspecialchars($usuario['nome']) ?></div>
      <?php if ($ehAdmin): ?>
        <span class="selo" style="color:#5B6169; background:rgba(91,97,105,0.12);">Administrador</span>
      <?php elseif ((int) $usuario['aceite_lgpd'] === 1): ?>
        <span class="selo">✓ LGPD aceita</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="section-label">Dados da conta</div>
  <div class="info-grid">
    <div class="info-row">
      <span class="label">Nome</span>
      <span class="value"><?= htmlspecialchars($usuario['nome']) ?></span>
    </div>
    <div class="info-row">
      <span class="label">E-mail</span>
      <span class="value"><?= htmlspecialchars($usuario['email']) ?></span>
    </div>
    <div class="info-row">
      <span class="label">Senha</span>
      <div>
        <span class="value mono">••••••••••</span>
        <div class="info-hint">Protegida por hash — não é exibida por segurança.</div>
      </div>
    </div>
    <div class="info-row">
      <span class="label">Membro desde</span>
      <span class="value"><?= $membroDesde ?></span>
    </div>
  </div>

  <?php if (!$ehAdmin): ?>
  <div class="section-label">Resumo das ocorrências</div>
  <div class="stats-grid">
    <div class="stat">
      <div class="num"><?= $total ?></div>
      <div class="label">Registradas</div>
    </div>
    <div class="stat">
      <div class="num"><?= $pendentes ?></div>
      <div class="label">Pendentes</div>
    </div>
    <div class="stat">
      <div class="num"><?= $emAnalise ?></div>
      <div class="label">Em análise</div>
    </div>
    <div class="stat accent">
      <div class="num"><?= $resolvidas ?></div>
      <div class="label">Resolvidas</div>
    </div>
  </div>

  <div class="section-label">Minhas ocorrências</div>
  <?php if (empty($ocorrencias)): ?>
    <div class="empty-state">
      Você ainda não registrou nenhuma ocorrência.
      <br><a href="Registrar.php" style="color:var(--municipal); font-weight:700;">Registrar a primeira</a>
    </div>
  <?php else: ?>
    <div class="card-list">
      <?php foreach ($ocorrencias as $o): ?>
        <?php $info = $statusInfo[$o['status']]; ?>
        <div class="occ-card">
          <span class="proto-id mono">#<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></span>
          <span class="desc"><?= htmlspecialchars($o['descricao']) ?><span class="cat"><?= htmlspecialchars($o['categoria']) ?></span></span>
          <span class="pill <?= $info['classe'] ?>"><?= $info['label'] ?></span>
          <span class="bairro"><?= htmlspecialchars($o['endereco']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>

</div>

</body>
</html>