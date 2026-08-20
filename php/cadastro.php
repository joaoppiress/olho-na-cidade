<?php
session_start();
require __DIR__ . '/config.php';

$erros = [];
$nomeValor  = '';
$emailValor = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeValor  = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
    $emailValor = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $senha      = isset($_POST['senha']) ? $_POST['senha'] : '';
    $confirma   = isset($_POST['confirma']) ? $_POST['confirma'] : '';
    $aceite     = isset($_POST['cadTermos']);

    // Validação no servidor — nunca confie apenas no HTML5 do navegador.
    if (mb_strlen($nomeValor) < 3) {
        $erros[] = 'Informe seu nome completo (mínimo 3 caracteres).';
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
    if (!$aceite) {
        $erros[] = 'É necessário aceitar o tratamento de dados conforme a LGPD.';
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$emailValor]);
        if ($stmt->fetch()) {
            $erros[] = 'Já existe uma conta cadastrada com este e-mail.';
        }
    }

    if (empty($erros)) {
        // Nunca armazene a senha em texto puro — sempre use password_hash().
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, aceite_lgpd) VALUES (?, ?, ?, 1)'
        );
        $stmt->execute([$nomeValor, $emailValor, $senhaHash]);

        // Cadastro concluído — manda para o login em vez de autenticar direto.
        header('Location: login.php?cadastrado=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar conta — Olho na Cidade</title>
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
      <?php if (!empty($erros)): ?>
        <div class="error-list">
          <ul>
            <?php foreach ($erros as $erro): ?>
              <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="plain-form" method="POST" action="cadastro.php" novalidate>
        <div class="hint">Preencha seus dados para criar um perfil seguro de denúncias.</div>

        <div class="field">
          <label for="nome">Nome completo</label>
          <input id="nome" name="nome" type="text" placeholder="Seu nome"
                 value="<?= htmlspecialchars($nomeValor) ?>" required minlength="3">
        </div>

        <div class="field">
          <label for="email">E-mail</label>
          <input id="email" name="email" type="email" placeholder="voce@exemplo.com"
                 value="<?= htmlspecialchars($emailValor) ?>" required>
        </div>

        <div class="row2">
          <div class="field">
            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" placeholder="Mínimo 6 caracteres" minlength="6" required>
          </div>
          <div class="field">
            <label for="confirma">Confirmar senha</label>
            <input id="confirma" name="confirma" type="password" placeholder="Repita a senha" minlength="6" required>
          </div>
        </div>

        <div class="auth-check">
          <input type="checkbox" id="cadTermos" name="cadTermos" required>
          <label for="cadTermos">Li e aceito o tratamento dos meus dados conforme a LGPD, para uso exclusivo do registro e acompanhamento de ocorrências.</label>
        </div>

        <div class="submit-row">
          <button class="btn-solid" type="submit">Criar conta</button>
        </div>
      </form>
    </div>

    <div class="authpage-switch-note">
      Já tem conta? <a href="login.php">Entrar</a>
    </div>
  </div>
</div>

</body>
</html>