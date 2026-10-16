<?php
session_start();

// Precisa estar logado (vale para cidadão e administrador).
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config.php';

$ehAdmin  = (isset($_SESSION['tipo']) ? $_SESSION['tipo'] : 'cidadao') === 'admin';
$voltarPara = $ehAdmin ? 'paineladm.php' : 'painel.php';

$stmt = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual  = isset($_POST['senha_atual']) ? $_POST['senha_atual'] : '';
    $novaSenha   = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmacao = isset($_POST['confirma_senha']) ? $_POST['confirma_senha'] : '';

    // Validação no servidor.
    if ($senhaAtual === '') {
        $erros[] = 'Informe sua senha atual.';
    } elseif (!password_verify($senhaAtual, $usuario['senha_hash'])) {
        $erros[] = 'Senha atual incorreta.';
    }

    if (mb_strlen($novaSenha) < 6) {
        $erros[] = 'A nova senha precisa ter pelo menos 6 caracteres.';
    }
    if ($novaSenha !== $confirmacao) {
        $erros[] = 'A confirmação da nova senha não confere.';
    }
    if ($novaSenha !== '' && $novaSenha === $senhaAtual) {
        $erros[] = 'A nova senha deve ser diferente da senha atual.';
    }

    if (empty($erros)) {
        $stmtSalva = $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
        $stmtSalva->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $_SESSION['usuario_id']]);

        // Encerra a sessão: o usuário precisa entrar de novo com a nova senha.
        $_SESSION = [];
        session_destroy();

        header('Location: login.php?senha_alterada=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alterar senha — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .auth-card .error-list{ margin:22px 26px 0; }
</style>
</head>
<body>

<div class="authpage">
  <div class="authpage-inner">
    <a class="authpage-logo" href="../index.php"><span class="dot"></span> Olho na Cidade</a>

    <div class="auth-card">
      <?php if (!empty($erros)): ?>
        <div class="error-list">
          <ul>
            <?php foreach ($erros as $erro): ?>
              <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="plain-form" method="POST" action="alterar_senha.php" novalidate>
        <div class="hint">Para sua segurança, confirme a senha atual e escolha uma nova senha com no mínimo 6 caracteres.</div>

        <div class="field">
          <label for="senha_atual">Senha atual</label>
          <input id="senha_atual" name="senha_atual" type="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>

        <div class="row2">
          <div class="field">
            <label for="nova_senha">Nova senha</label>
            <input id="nova_senha" name="nova_senha" type="password" placeholder="Mínimo 6 caracteres" minlength="6" autocomplete="new-password" required>
          </div>
          <div class="field">
            <label for="confirma_senha">Confirmar nova senha</label>
            <input id="confirma_senha" name="confirma_senha" type="password" placeholder="Repita a nova senha" minlength="6" autocomplete="new-password" required>
          </div>
        </div>

        <div class="submit-row">
          <button class="btn-solid" type="submit">Alterar senha</button>
        </div>
      </form>
    </div>

    <div class="authpage-switch-note">
      <a href="<?= $voltarPara ?>">&larr; Voltar ao painel</a> &nbsp;·&nbsp; <a href="perfil.php">Meu perfil</a>
    </div>
  </div>
</div>

</body>
</html>