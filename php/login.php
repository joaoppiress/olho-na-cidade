<?php
session_start();
require __DIR__ . '/config.php';

$erros = [];
$emailValor = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValor = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $senha      = isset($_POST['senha']) ? $_POST['senha'] : '';

    // Validação no servidor — a única que realmente importa.
    if (!filter_var($emailValor, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($senha === '') {
        $erros[] = 'Informe sua senha.';
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ?');
        $stmt->execute([$emailValor]);
        $usuario = $stmt->fetch();

        // Mensagem genérica de propósito: não revela se o problema foi o e-mail ou a senha.
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            $_SESSION['usuario_id']    = (int) $usuario['id'];
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];

            header('Location: painel.php');
            exit;
        }

        $erros[] = 'E-mail ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="authpage">
  <div class="authpage-inner">
    <a class="authpage-logo" href="../index.php"><span class="dot"></span> Olho na Cidade</a>

    <div class="auth-card">
      <?php if (isset($_GET['cadastrado'])): ?>
        <div class="hint" style="color:#276B49; margin-bottom:14px;">✓ Conta criada com sucesso. Faça login para continuar.</div>
      <?php endif; ?>

      <?php if (!empty($erros)): ?>
        <div class="error-list">
          <ul>
            <?php foreach ($erros as $erro): ?>
              <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="plain-form" method="POST" action="login.php" novalidate>
        <div class="hint">Informe e-mail e senha para acessar seu perfil de denúncias.</div>

        <div class="field">
          <label for="email">E-mail</label>
          <input id="email" name="email" type="email" placeholder="voce@exemplo.com"
                 value="<?= htmlspecialchars($emailValor) ?>" required>
        </div>

        <div class="field">
          <label for="senha">Senha</label>
          <input id="senha" name="senha" type="password" placeholder="••••••••" required>
        </div>

        <div class="submit-row">
          <button class="btn-solid" type="submit">Entrar</button>
        </div>
      </form>
    </div>

    <div class="authpage-switch-note">
      Ainda não tem conta? <a href="cadastro.php">Criar conta</a>
    </div>
  </div>
</div>

</body>
</html>