<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$stmt = $pdo->prepare('SELECT id, categoria, bairro, endereco, descricao, status, criado_em FROM ocorrencias WHERE usuario_id = ? ORDER BY criado_em DESC');
$stmt->execute([$_SESSION['usuario_id']]);
$ocorrencias = $stmt->fetchAll();

$excluida = isset($_GET['excluida']) && $_GET['excluida'] === '1';

$statusInfo = [
    'pendente'    => ['label' => 'Pendente',    'classe' => 'pendente'],
    'em_analise'  => ['label' => 'Em análise',  'classe' => 'analise'],
    'resolvido'   => ['label' => 'Resolvido',   'classe' => 'resolvido'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu painel — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .painel-wrap{ max-width:720px; margin:0 auto; padding:60px 20px; }
  .painel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .painel-top h1{ font-size:26px; font-weight:800; text-transform:none; letter-spacing:0; }
  .painel-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .painel-actions{ display:flex; gap:10px; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }
  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }
  .confirm-box{
    margin-bottom:20px; padding:14px 16px; border-radius:5px;
    background:rgba(62,156,111,0.1); border:1px solid rgba(62,156,111,0.35); color:#276B49;
    font-size:13.5px;
  }
  .btn-excluir{
    border:1px solid #B23D1F; background:#fff; color:#B23D1F; padding:10px 16px; border-radius:4px;
    font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; cursor:pointer;
    font-family:'Inter', sans-serif;
  }
  .card-acoes{ display:flex; gap:8px; justify-self:end; align-items:center; }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></h1>
      <div class="sub"><?= htmlspecialchars($_SESSION['usuario_email']) ?></div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="../index.php">&larr; Início</a>
      <a class="btn-line" href="perfil.php">Meu perfil</a>
      <a class="btn-solid" style="text-decoration:none;" href="Registrar.php">+ Nova ocorrência</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <?php if ($excluida): ?>
    <div class="confirm-box">✓ Ocorrência excluída com sucesso.</div>
  <?php endif; ?>

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
          <span class="card-acoes">
            <a class="btn-line" href="ocorrencia.php?id=<?= $o['id'] ?>">Ver detalhes</a>
            <?php if ($o['status'] === 'pendente'): ?>
              <form method="POST" action="excluir.php" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência? Essa ação não pode ser desfeita.');">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                <button class="btn-excluir" type="submit">Excluir</button>
              </form>
            <?php endif; ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>