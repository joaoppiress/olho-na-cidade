<?php
session_start();

// Somente administradores logados podem criar outros administradores.
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$erros      = [];
$sucesso    = isset($_GET['ok']) && $_GET['ok'] === '1';
$nomeValor  = '';
$emailValor = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeValor  = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
    $emailValor = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $senha      = isset($_POST['senha']) ? $_POST['senha'] : '';
    $confirma   = isset($_POST['confirma']) ? $_POST['confirma'] : '';

    if (mb_strlen($nomeValor) < 3) {
        $erros[] = 'Informe o nome completo (mínimo 3 caracteres).';
    }
    if (!filter_var($emailValor, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (mb_strlen($senha) < 6) {
        $erros[] = 'A senha precisa ter ao menos 6 caracteres.';
    }
    if ($senha !== $confirma) {
        $erros[] = 'As senhas não coincidem.';
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$emailValor]);
        if ($stmt->fetch()) {
            $erros[] = 'Já existe uma conta cadastrada com este e-mail.';
        }
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, aceite_lgpd, tipo) VALUES (?, ?, ?, 0, 'admin')"
        );
        $stmt->execute([$nomeValor, $emailValor, password_hash($senha, PASSWORD_DEFAULT)]);

        header('Location: novo_admin.php?ok=1');
        exit;
    }
}

$admins = $pdo->query("SELECT nome, email, criado_em FROM usuarios WHERE tipo = 'admin' ORDER BY criado_em ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Novo administrador — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .painel-wrap{ max-width:760px; margin:0 auto; padding:60px 20px; }
  .painel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:14px; }
  .painel-top h1{ font-size:26px; font-weight:800; text-transform:none; letter-spacing:0; }
  .painel-top .sub{ font-size:13.5px; color:#8A8F96; margin-top:4px; }
  .painel-actions{ display:flex; gap:10px; flex-wrap:wrap; }
  .btn-line{ border:1px solid var(--line); background:#fff; color:#454B52; padding:10px 16px; border-radius:4px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; text-decoration:none; }
  .confirm-box{ margin-bottom:16px; padding:14px 16px; border-radius:5px; background:rgba(62,156,111,0.1); border:1px solid rgba(62,156,111,0.35); color:#276B49; font-size:13.5px; }
  .card-form{ background:#fff; border:1px solid var(--line); border-radius:6px; margin-bottom:26px; }
  .card-form .error-list{ margin:22px 26px 0; }
</style>
</head>
<body style="background:var(--concrete);">

<div class="painel-wrap">
  <div class="painel-top">
    <div>
      <h1>Novo administrador</h1>
      <div class="sub">Contas de administrador só podem ser criadas por aqui ou diretamente no banco</div>
    </div>
    <div class="painel-actions">
      <a class="btn-line" href="paineladm.php">&larr; Painel</a>
      <a class="btn-line" href="logout.php">Sair</a>
    </div>
  </div>

  <?php if ($sucesso): ?>
    <div class="confirm-box">✓ Administrador criado com sucesso.</div>
  <?php endif; ?>

  <div class="card-form">
    <?php if (!empty($erros)): ?>
      <div class="error-list">
        <ul>
          <?php foreach ($erros as $erro): ?>
            <li><?= htmlspecialchars($erro) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="plain-form" method="POST" action="novo_admin.php" novalidate>
      <div class="field">
        <label for="nome">Nome completo</label>
        <input id="nome" name="nome" type="text" value="<?= htmlspecialchars($nomeValor) ?>" required minlength="3">
      </div>

      <div class="field">
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($emailValor) ?>" required>
      </div>

      <div class="row2">
        <div class="field">
          <label for="senha">Senha</label>
          <input id="senha" name="senha" type="password" placeholder="Mínimo 6 caracteres" minlength="6" autocomplete="new-password" required>
        </div>
        <div class="field">
          <label for="confirma">Confirmar senha</label>
          <input id="confirma" name="confirma" type="password" placeholder="Repita a senha" minlength="6" autocomplete="new-password" required>
        </div>
      </div>

      <div class="submit-row">
        <button class="btn-solid" type="submit">Criar administrador</button>
      </div>
    </form>
  </div>

  <div class="admin-table-wrap">
    <table>
      <thead>
        <tr><th>Administradores atuais</th><th>E-mail</th><th>Criado em</th></tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['nome']) ?></td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td class="mono"><?= htmlspecialchars(date('d/m/Y', strtotime($a['criado_em']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>