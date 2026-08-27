<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$stmt = $pdo->query('
    SELECT o.id, o.categoria, o.bairro, o.endereco, o.descricao, o.status, o.criado_em, u.nome AS nome_cidadao
    FROM ocorrencias o
    JOIN usuarios u ON o.usuario_id = u.id
    ORDER BY o.criado_em DESC
');
$ocorrencias = $stmt->fetchAll();

$total      = count($ocorrencias);
$pendentes  = count(array_filter($ocorrencias, function ($o) { return $o['status'] === 'pendente'; }));
$emAnalise  = count(array_filter($ocorrencias, function ($o) { return $o['status'] === 'em_analise'; }));
$resolvidas = count(array_filter($ocorrencias, function ($o) { return $o['status'] === 'resolvido'; }));

$statusInfo = [
    'pendente'   => ['label' => 'Pendente',   'classe' => 'pendente'],
    'em_analise' => ['label' => 'Em análise', 'classe' => 'analise'],
    'resolvido'  => ['label' => 'Resolvido',  'classe' => 'resolvido'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel administrativo — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .painel-wrap{ max-width:960px; margin:0 auto; padding:60px 20px; }
  .painel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .painel-top h1{ font-size:26px; font-weight:800; text-transform:none; letter-spacing:0; }
  .painel-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .painel-actions{ display:flex; gap:10px; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }
  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Painel administrativo</h1>
      <div class="sub">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?> · todas as ocorrências dos cidadãos</div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="perfil.php">Meu perfil</a>
      <a class="btn-line" href="../index.php">&larr; Início</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat">
      <div class="num"><?= $total ?></div>
      <div class="label">Total</div>
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

  <?php if (empty($ocorrencias)): ?>
    <div class="empty-state">Nenhuma ocorrência registrada pelos cidadãos ainda.</div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <div class="filter-row">
        <span class="chip active">Todos os bairros</span>
        <span class="chip active">Todos os status</span>
        <span class="chip active">Todas as categorias</span>
      </div>
      <table>
        <thead>
          <tr>
            <th>Protocolo</th>
            <th>Cidadão</th>
            <th>Categoria</th>
            <th>Bairro</th>
            <th>Status</th>
            <th>Registrado em</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ocorrencias as $o): ?>
            <?php $info = $statusInfo[$o['status']]; ?>
            <tr>
              <td class="mono">#<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td><?= htmlspecialchars($o['nome_cidadao']) ?></td>
              <td><?= htmlspecialchars($o['categoria']) ?></td>
              <td class="bairro-tag"><?= htmlspecialchars($o['bairro']) ?></td>
              <td><span class="pill <?= $info['classe'] ?>"><?= $info['label'] ?></span></td>
              <td class="mono"><?= htmlspecialchars($o['criado_em']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</body>
</html>