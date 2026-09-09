<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$statusInfo = [
    'pendente'   => ['label' => 'Pendente',   'classe' => 'pendente'],
    'em_analise' => ['label' => 'Em análise', 'classe' => 'analise'],
    'resolvido'  => ['label' => 'Resolvido',  'classe' => 'resolvido'],
];

// Opções disponíveis para os filtros (vêm do próprio banco, sem valores fixos)
$categorias = $pdo->query('SELECT DISTINCT categoria FROM ocorrencias ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);
$bairros    = $pdo->query('SELECT DISTINCT bairro FROM ocorrencias ORDER BY bairro')->fetchAll(PDO::FETCH_COLUMN);

// Filtros vindos da URL (GET), validados contra listas conhecidas
$filtroStatus    = isset($_GET['status']) ? trim($_GET['status']) : '';
$filtroCategoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$filtroBairro    = isset($_GET['bairro']) ? trim($_GET['bairro']) : '';

if (!array_key_exists($filtroStatus, $statusInfo)) {
    $filtroStatus = '';
}
if (!in_array($filtroCategoria, $categorias, true)) {
    $filtroCategoria = '';
}
if (!in_array($filtroBairro, $bairros, true)) {
    $filtroBairro = '';
}

$condicoes  = [];
$parametros = [];

if ($filtroStatus !== '') {
    $condicoes[]           = 'o.status = :status';
    $parametros['status']  = $filtroStatus;
}
if ($filtroCategoria !== '') {
    $condicoes[]              = 'o.categoria = :categoria';
    $parametros['categoria']  = $filtroCategoria;
}
if ($filtroBairro !== '') {
    $condicoes[]           = 'o.bairro = :bairro';
    $parametros['bairro']  = $filtroBairro;
}

$sql = '
    SELECT o.id, o.categoria, o.bairro, o.endereco, o.descricao, o.status, o.criado_em, u.nome AS nome_cidadao
    FROM ocorrencias o
    JOIN usuarios u ON o.usuario_id = u.id
';
if (!empty($condicoes)) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY o.criado_em DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$ocorrencias = $stmt->fetchAll();

// Estatísticas gerais (sempre do total, independente do filtro aplicado)
$stmtTotais = $pdo->query('SELECT status, COUNT(*) AS qtd FROM ocorrencias GROUP BY status');
$totaisPorStatus = ['pendente' => 0, 'em_analise' => 0, 'resolvido' => 0];
foreach ($stmtTotais->fetchAll() as $linha) {
    $totaisPorStatus[$linha['status']] = (int) $linha['qtd'];
}
$total      = array_sum($totaisPorStatus);
$pendentes  = $totaisPorStatus['pendente'];
$emAnalise  = $totaisPorStatus['em_analise'];
$resolvidas = $totaisPorStatus['resolvido'];

$temFiltroAtivo = ($filtroStatus !== '' || $filtroCategoria !== '' || $filtroBairro !== '');
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
  .filter-row{ align-items:center; }
  .filter-row select{
    border:1px solid var(--line); background:#fff; color:#454B52; padding:9px 12px;
    border-radius:20px; font-size:12.5px; font-weight:600; font-family:'Inter', sans-serif;
  }
  .filter-row select:focus{ outline:2px solid var(--municipal-2); outline-offset:1px; }
  .filter-row .btn-filtrar{
    border:1px solid var(--municipal); background:var(--municipal); color:#fff; padding:9px 18px;
    border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em;
    cursor:pointer;
  }
  .filter-row .btn-limpar{
    font-size:12.5px; font-weight:600; color:#8A8F96; text-decoration:none; padding:9px 6px;
  }
  .filter-row .btn-limpar:hover{ color:#454B52; }
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

  <div class="admin-table-wrap">
    <form class="filter-row" method="get" action="paineladm.php">
      <select name="status">
        <option value="">Todos os status</option>
        <?php foreach ($statusInfo as $valor => $info): ?>
          <option value="<?= htmlspecialchars($valor) ?>" <?= $filtroStatus === $valor ? 'selected' : '' ?>><?= htmlspecialchars($info['label']) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="categoria">
        <option value="">Todas as categorias</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= htmlspecialchars($categoria) ?>" <?= $filtroCategoria === $categoria ? 'selected' : '' ?>><?= htmlspecialchars($categoria) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="bairro">
        <option value="">Todos os bairros</option>
        <?php foreach ($bairros as $bairro): ?>
          <option value="<?= htmlspecialchars($bairro) ?>" <?= $filtroBairro === $bairro ? 'selected' : '' ?>><?= htmlspecialchars($bairro) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn-filtrar">Filtrar</button>
      <?php if ($temFiltroAtivo): ?>
        <a class="btn-limpar" href="paineladm.php">Limpar filtros</a>
      <?php endif; ?>
    </form>

    <?php if (empty($ocorrencias)): ?>
      <div class="empty-state">
        <?= $temFiltroAtivo ? 'Nenhuma ocorrência encontrada com esses filtros.' : 'Nenhuma ocorrência registrada pelos cidadãos ainda.' ?>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Protocolo</th>
            <th>Cidadão</th>
            <th>Categoria</th>
            <th>Bairro</th>
            <th>Status</th>
            <th>Registrado em</th>
            <th></th>
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
              <td><a class="btn-line" href="ocorrencia.php?id=<?= $o['id'] ?>">Ver detalhes</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

</body>
</html>