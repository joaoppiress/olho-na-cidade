<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

// Total de ocorrências por categoria, com divisão por status
$sql = "
    SELECT categoria,
           COUNT(*) AS total,
           SUM(status = 'pendente')   AS pendentes,
           SUM(status = 'em_analise') AS em_analise,
           SUM(status = 'resolvido')  AS resolvidas
    FROM ocorrencias
    GROUP BY categoria
    ORDER BY total DESC, categoria ASC
";
$ranking = $pdo->query($sql)->fetchAll();

$totalGeral = 0;
$maiorTotal = 0;
foreach ($ranking as $r) {
    $totalGeral += (int) $r['total'];
    if ((int) $r['total'] > $maiorTotal) {
        $maiorTotal = (int) $r['total'];
    }
}

$maisReclamada = !empty($ranking) ? $ranking[0] : null;
$qtdCategorias = count($ranking);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categorias mais reclamadas — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .painel-wrap{ max-width:960px; margin:0 auto; padding:60px 20px; }
  .painel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .painel-top h1{ font-size:26px; font-weight:800; text-transform:none; letter-spacing:0; }
  .painel-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .painel-actions{ display:flex; gap:10px; flex-wrap:wrap; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }
  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }
  .barra{ background:var(--concrete-2); border-radius:3px; height:10px; min-width:120px; overflow:hidden; }
  .barra span{ display:block; height:100%; background:var(--signal-2); }
  .barra.topo span{ background:var(--municipal); }
  .rank{ font-family:'IBM Plex Mono', monospace; font-weight:600; color:#8A8F96; }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Categorias mais reclamadas</h1>
      <div class="sub">Ranking de ocorrências por categoria para apoiar a definição de prioridades</div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="paineladm.php">&larr; Painel</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat">
      <div class="num"><?= $totalGeral ?></div>
      <div class="label">Total de ocorrências</div>
    </div>
    <div class="stat">
      <div class="num"><?= $qtdCategorias ?></div>
      <div class="label">Categorias</div>
    </div>
    <div class="stat accent">
      <div class="num"><?= $maisReclamada ? (int) $maisReclamada['total'] : 0 ?></div>
      <div class="label"><?= $maisReclamada ? htmlspecialchars($maisReclamada['categoria']) : 'Sem dados' ?></div>
    </div>
  </div>

  <div class="admin-table-wrap">
    <?php if (empty($ranking)): ?>
      <div class="empty-state">Ainda não há ocorrências registradas.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Categoria</th>
            <th>Ocorrências</th>
            <th>%</th>
            <th>Pendentes</th>
            <th>Em análise</th>
            <th>Resolvidas</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php $pos = 0; foreach ($ranking as $r): $pos++; ?>
            <?php
              $qtd     = (int) $r['total'];
              $perc    = $totalGeral > 0 ? round($qtd * 100 / $totalGeral, 1) : 0;
              $largura = $maiorTotal > 0 ? round($qtd * 100 / $maiorTotal) : 0;
            ?>
            <tr>
              <td class="rank"><?= $pos ?>º</td>
              <td><?= htmlspecialchars($r['categoria']) ?></td>
              <td>
                <strong><?= $qtd ?></strong>
                <div class="barra <?= $pos === 1 ? 'topo' : '' ?>"><span style="width:<?= $largura ?>%"></span></div>
              </td>
              <td class="mono"><?= str_replace('.', ',', (string) $perc) ?>%</td>
              <td class="mono"><?= (int) $r['pendentes'] ?></td>
              <td class="mono"><?= (int) $r['em_analise'] ?></td>
              <td class="mono"><?= (int) $r['resolvidas'] ?></td>
              <td><a class="btn-line" href="paineladm.php?status=&amp;categoria=<?= urlencode($r['categoria']) ?>">Ver</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

</body>
</html>