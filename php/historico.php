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

// Filtros vindos da URL (GET)
$filtroStatus     = isset($_GET['status']) ? trim($_GET['status']) : '';
$filtroOcorrencia = isset($_GET['ocorrencia']) ? (int) $_GET['ocorrencia'] : 0;

if (!array_key_exists($filtroStatus, $statusInfo)) {
    $filtroStatus = '';
}

$condicoes  = [];
$parametros = [];

if ($filtroStatus !== '') {
    $condicoes[]          = 'h.status_novo = :status';
    $parametros['status'] = $filtroStatus;
}
if ($filtroOcorrencia > 0) {
    $condicoes[]              = 'h.ocorrencia_id = :ocorrencia';
    $parametros['ocorrencia'] = $filtroOcorrencia;
}

$sql = '
    SELECT h.id, h.ocorrencia_id, h.ocorrencia_categoria, h.status_anterior, h.status_novo,
           h.recado, h.admin_nome, h.criado_em,
           (o.id IS NOT NULL) AS ocorrencia_existe
    FROM ocorrencia_historico h
    LEFT JOIN ocorrencias o ON o.id = h.ocorrencia_id
';
if (!empty($condicoes)) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY h.criado_em DESC, h.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$historico = $stmt->fetchAll();

$totalRegistros = $pdo->query('SELECT COUNT(*) FROM ocorrencia_historico')->fetchColumn();
$totalResolvidas = $pdo->query("SELECT COUNT(*) FROM ocorrencia_historico WHERE status_novo = 'resolvido'")->fetchColumn();

$temFiltroAtivo = ($filtroStatus !== '' || $filtroOcorrencia > 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico de resoluções — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .painel-wrap{ max-width:1040px; margin:0 auto; padding:60px 20px; }
  .painel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .painel-top h1{ font-size:26px; font-weight:800; text-transform:none; letter-spacing:0; }
  .painel-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .painel-actions{ display:flex; gap:10px; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }
  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }
  .filter-row{ align-items:center; }
  .filter-row select, .filter-row input[type="number"]{
    border:1px solid var(--line); background:#fff; color:#454B52; padding:9px 12px;
    border-radius:20px; font-size:12.5px; font-weight:600; font-family:'Inter', sans-serif; width:150px;
  }
  .filter-row select:focus, .filter-row input:focus{ outline:2px solid var(--municipal-2); outline-offset:1px; }
  .filter-row .btn-filtrar{
    border:1px solid var(--municipal); background:var(--municipal); color:#fff; padding:9px 18px;
    border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em;
    cursor:pointer;
  }
  .filter-row .btn-limpar{ font-size:12.5px; font-weight:600; color:#8A8F96; text-decoration:none; padding:9px 6px; }
  .filter-row .btn-limpar:hover{ color:#454B52; }

  .painel-wrap .stats-grid{ grid-template-columns:repeat(2,1fr); }
  .transicao{ display:flex; align-items:center; gap:6px; font-size:12.5px; white-space:nowrap; }
  .transicao .seta{ color:#8A8F96; }
  .transicao .sem-anterior{ color:#8A8F96; font-style:italic; }
  .recado-cel{ max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:13px; color:#454B52; }
  .recado-cel.vazio{ color:#8A8F96; font-style:italic; }
  .ocorrencia-removida{ font-size:11.5px; color:#B23D1F; display:block; margin-top:2px; }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Histórico de resoluções</h1>
      <div class="sub">Registro de todas as alterações de status e recados feitos pela prefeitura, para controle e auditoria.</div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="paineladm.php">&larr; Painel</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat">
      <div class="num"><?= (int) $totalRegistros ?></div>
      <div class="label">Alterações registradas</div>
    </div>
    <div class="stat accent">
      <div class="num"><?= (int) $totalResolvidas ?></div>
      <div class="label">Resoluções concluídas</div>
    </div>
  </div>

  <div class="admin-table-wrap">
    <form class="filter-row" method="get" action="historico.php">
      <select name="status">
        <option value="">Todos os status</option>
        <?php foreach ($statusInfo as $valor => $info): ?>
          <option value="<?= htmlspecialchars($valor) ?>" <?= $filtroStatus === $valor ? 'selected' : '' ?>><?= htmlspecialchars($info['label']) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="number" name="ocorrencia" min="1" placeholder="Nº do protocolo" value="<?= $filtroOcorrencia > 0 ? $filtroOcorrencia : '' ?>">

      <button type="submit" class="btn-filtrar">Filtrar</button>
      <?php if ($temFiltroAtivo): ?>
        <a class="btn-limpar" href="historico.php">Limpar filtros</a>
      <?php endif; ?>
    </form>

    <?php if (empty($historico)): ?>
      <div class="empty-state">
        <?= $temFiltroAtivo ? 'Nenhum registro de histórico encontrado com esses filtros.' : 'Nenhuma alteração foi registrada ainda. O histórico é criado automaticamente quando o status ou o recado de uma ocorrência é atualizado.' ?>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Protocolo</th>
            <th>Categoria</th>
            <th>Alteração de status</th>
            <th>Recado registrado</th>
            <th>Responsável</th>
            <th>Data</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($historico as $h): ?>
            <?php
              $infoNovo      = $statusInfo[$h['status_novo']];
              $infoAnterior  = $h['status_anterior'] !== null ? $statusInfo[$h['status_anterior']] : null;
              $temRecado     = !empty($h['recado']);
            ?>
            <tr>
              <td class="mono">
                #<?= str_pad((string) $h['ocorrencia_id'], 4, '0', STR_PAD_LEFT) ?>
                <?php if (!$h['ocorrencia_existe']): ?>
                  <span class="ocorrencia-removida">Ocorrência excluída</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($h['ocorrencia_categoria']) ?></td>
              <td>
                <div class="transicao">
                  <?php if ($infoAnterior): ?>
                    <span class="pill <?= $infoAnterior['classe'] ?>"><?= $infoAnterior['label'] ?></span>
                    <span class="seta">&rarr;</span>
                  <?php else: ?>
                    <span class="sem-anterior">novo</span>
                    <span class="seta">&rarr;</span>
                  <?php endif; ?>
                  <span class="pill <?= $infoNovo['classe'] ?>"><?= $infoNovo['label'] ?></span>
                </div>
              </td>
              <td>
                <div class="recado-cel <?= $temRecado ? '' : 'vazio' ?>" title="<?= $temRecado ? htmlspecialchars($h['recado']) : '' ?>">
                  <?= $temRecado ? htmlspecialchars($h['recado']) : 'Sem recado nesta alteração' ?>
                </div>
              </td>
              <td><?= htmlspecialchars($h['admin_nome']) ?></td>
              <td class="mono"><?= htmlspecialchars($h['criado_em']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

</body>
</html>