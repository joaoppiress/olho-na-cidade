<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$statusInfo = [
    'pendente'   => 'Pendente',
    'em_analise' => 'Em análise',
    'resolvido'  => 'Resolvido',
];

$nomesMeses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

// Mês e ano escolhidos (padrão: mês atual), validados no servidor.
$anoAtual = (int) date('Y');
$mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : $anoAtual;

if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}

$anoMinimo = (int) $pdo->query('SELECT COALESCE(MIN(YEAR(criado_em)), YEAR(CURDATE())) FROM ocorrencias')->fetchColumn();
if ($anoMinimo > $anoAtual) {
    $anoMinimo = $anoAtual;
}
if ($ano < $anoMinimo || $ano > $anoAtual) {
    $ano = $anoAtual;
}

$inicio = sprintf('%04d-%02d-01 00:00:00', $ano, $mes);
$fim    = date('Y-m-d H:i:s', strtotime($inicio . ' +1 month'));

$stmt = $pdo->prepare(
    'SELECT id, categoria, bairro, endereco, descricao, status, recado_adm, criado_em
     FROM ocorrencias
     WHERE criado_em >= ? AND criado_em < ?
     ORDER BY criado_em ASC, id ASC'
);
$stmt->execute([$inicio, $fim]);
$ocorrencias = $stmt->fetchAll();

// ---------- Exportação em CSV ----------
if (isset($_GET['formato']) && $_GET['formato'] === 'csv') {
    // Evita "injeção de fórmula" ao abrir no Excel: células que começam com = + - @
    // ganham um apóstrofo na frente.
    $limpar = function ($valor) {
        $valor = (string) $valor;
        $valor = str_replace(["\r\n", "\r", "\n"], ' ', $valor);
        if ($valor !== '' && strpos("=+-@\t", $valor[0]) !== false) {
            $valor = "'" . $valor;
        }
        return $valor;
    };

    $nomeArquivo = sprintf('relatorio-ocorrencias-%04d-%02d.csv', $ano, $mes);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
    header('Pragma: no-cache');

    $saida = fopen('php://output', 'w');
    fwrite($saida, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer UTF-8 (acentos)

    // Separador ";" é o padrão do Excel em português.
    fputcsv($saida, ['ID', 'Data de registro', 'Categoria', 'Bairro', 'Endereço', 'Descrição', 'Status', 'Recado da prefeitura'], ';');

    foreach ($ocorrencias as $o) {
        fputcsv($saida, [
            $o['id'],
            date('d/m/Y H:i', strtotime($o['criado_em'])),
            $limpar($o['categoria']),
            $limpar($o['bairro']),
            $limpar($o['endereco']),
            $limpar($o['descricao']),
            $statusInfo[$o['status']],
            $limpar($o['recado_adm']),
        ], ';');
    }

    fclose($saida);
    exit;
}

// ---------- Resumo para a tela / impressão ----------
$total = count($ocorrencias);
$porStatus    = ['pendente' => 0, 'em_analise' => 0, 'resolvido' => 0];
$porCategoria = [];
$porBairro    = [];

foreach ($ocorrencias as $o) {
    if (isset($porStatus[$o['status']])) {
        $porStatus[$o['status']]++;
    }
    $porCategoria[$o['categoria']] = (isset($porCategoria[$o['categoria']]) ? $porCategoria[$o['categoria']] : 0) + 1;
    $porBairro[$o['bairro']]       = (isset($porBairro[$o['bairro']]) ? $porBairro[$o['bairro']] : 0) + 1;
}
arsort($porCategoria);
arsort($porBairro);

$taxaResolucao = $total > 0 ? round($porStatus['resolvido'] * 100 / $total, 1) : 0;
$tituloMes     = $nomesMeses[$mes] . ' de ' . $ano;
$queryCsv      = http_build_query(['mes' => $mes, 'ano' => $ano, 'formato' => 'csv']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatório mensal — <?= htmlspecialchars($tituloMes) ?> — Olho na Cidade</title>
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
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; cursor:pointer; font-family:inherit; }
  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }
  .filtro-mes{ display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; background:#fff; border:1px solid var(--line); border-radius:6px; padding:16px; margin-bottom:22px; }
  .filtro-mes label{ display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#8A8F96; margin-bottom:4px; }
  .filtro-mes select{ padding:9px 12px; border:1px solid var(--line); border-radius:4px; font-size:14px; background:#fff; }
  .secao-titulo{ font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#5B6169; margin:30px 0 10px; }
  .desc-cel{ max-width:280px; font-size:13px; color:#454B52; }
  .print-header{ display:none; }

  @media print{
    body{ background:#fff !important; }
    .painel-wrap{ padding:0; max-width:none; }
    .painel-actions, .filtro-mes{ display:none !important; }
    .print-header{ display:block; margin-bottom:14px; font-size:12px; color:#5B6169; }
    .admin-table-wrap{ box-shadow:none; }
    table{ font-size:11px; }
    tr{ page-break-inside:avoid; }
  }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Relatório mensal de ocorrências</h1>
      <div class="sub"><?= htmlspecialchars($tituloMes) ?> · gerado em <?= date('d/m/Y H:i') ?></div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="paineladm.php">&larr; Painel</a>
      <?php if ($total > 0): ?>
        <a class="btn-line" href="relatorio.php?<?= htmlspecialchars($queryCsv) ?>">Baixar CSV</a>
        <button class="btn-line" type="button" onclick="window.print()">Imprimir / Salvar PDF</button>
      <?php endif; ?>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <div class="print-header">Olho na Cidade — Relatório de ocorrências de <?= htmlspecialchars($tituloMes) ?></div>

  <form class="filtro-mes" method="GET" action="relatorio.php">
    <div>
      <label for="mes">Mês</label>
      <select id="mes" name="mes">
        <?php foreach ($nomesMeses as $n => $nome): ?>
          <option value="<?= $n ?>" <?= $n === $mes ? 'selected' : '' ?>><?= $nome ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="ano">Ano</label>
      <select id="ano" name="ano">
        <?php for ($a = $anoAtual; $a >= $anoMinimo; $a--): ?>
          <option value="<?= $a ?>" <?= $a === $ano ? 'selected' : '' ?>><?= $a ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <button class="btn-solid" type="submit">Gerar relatório</button>
  </form>

  <?php if ($total === 0): ?>
    <div class="empty-state">Nenhuma ocorrência registrada em <?= htmlspecialchars($tituloMes) ?>.</div>
  <?php else: ?>

    <div class="stats-grid">
      <div class="stat">
        <div class="num"><?= $total ?></div>
        <div class="label">Registradas no mês</div>
      </div>
      <div class="stat">
        <div class="num"><?= $porStatus['pendente'] ?></div>
        <div class="label">Pendentes</div>
      </div>
      <div class="stat">
        <div class="num"><?= $porStatus['em_analise'] ?></div>
        <div class="label">Em análise</div>
      </div>
      <div class="stat accent">
        <div class="num"><?= $porStatus['resolvido'] ?></div>
        <div class="label">Resolvidas (<?= str_replace('.', ',', (string) $taxaResolucao) ?>%)</div>
      </div>
    </div>

    <div class="secao-titulo">Por categoria</div>
    <div class="admin-table-wrap">
      <table>
        <thead><tr><th>Categoria</th><th>Ocorrências</th><th>%</th></tr></thead>
        <tbody>
          <?php foreach ($porCategoria as $cat => $qtd): ?>
            <tr>
              <td><?= htmlspecialchars($cat) ?></td>
              <td class="mono"><?= $qtd ?></td>
              <td class="mono"><?= str_replace('.', ',', (string) round($qtd * 100 / $total, 1)) ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="secao-titulo">Por bairro</div>
    <div class="admin-table-wrap">
      <table>
        <thead><tr><th>Bairro</th><th>Ocorrências</th><th>%</th></tr></thead>
        <tbody>
          <?php foreach ($porBairro as $bairro => $qtd): ?>
            <tr>
              <td><?= htmlspecialchars($bairro) ?></td>
              <td class="mono"><?= $qtd ?></td>
              <td class="mono"><?= str_replace('.', ',', (string) round($qtd * 100 / $total, 1)) ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="secao-titulo">Detalhamento</div>
    <div class="admin-table-wrap">
      <table>
        <thead>
          <tr><th>ID</th><th>Data</th><th>Categoria</th><th>Bairro</th><th>Descrição</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($ocorrencias as $o): ?>
            <tr>
              <td class="mono">#<?= (int) $o['id'] ?></td>
              <td class="mono"><?= date('d/m/Y', strtotime($o['criado_em'])) ?></td>
              <td><?= htmlspecialchars($o['categoria']) ?></td>
              <td><?= htmlspecialchars($o['bairro']) ?></td>
              <td class="desc-cel"><?= htmlspecialchars(mb_strimwidth($o['descricao'], 0, 120, '…')) ?></td>
              <td><?= htmlspecialchars($statusInfo[$o['status']]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php endif; ?>
</div>

</body>
</html>