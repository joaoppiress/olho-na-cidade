<?php
session_start();
require __DIR__ . '/config.php';

$logado = isset($_SESSION['usuario_id']);
$erros = [];
$sucesso = false;
$protocolo = null;

// valores para reexibir o formulário em caso de erro
$categoriaValor = '';
$bairroValor    = '';
$enderecoValor  = '';
$descricaoValor = '';

if ($logado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoriaValor = trim(isset($_POST['categoria']) ? $_POST['categoria'] : '');
    $bairroValor    = trim(isset($_POST['bairro']) ? $_POST['bairro'] : '');
    $enderecoValor  = trim(isset($_POST['endereco']) ? $_POST['endereco'] : '');
    $descricaoValor = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

    if ($categoriaValor === '') {
        $erros[] = 'Selecione uma categoria.';
    }
    if ($bairroValor === '') {
        $erros[] = 'Informe o bairro.';
    }
    if ($enderecoValor === '') {
        $erros[] = 'Informe o endereço.';
    }
    if (mb_strlen($descricaoValor) < 10) {
        $erros[] = 'Descreva o problema com pelo menos 10 caracteres.';
    }

    // Upload de foto — opcional
    $fotoPath = null;
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Não foi possível enviar a foto. Tente novamente.';
        } else {
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            $tipoReal = mime_content_type($_FILES['foto']['tmp_name']);

            if (!in_array($tipoReal, $tiposPermitidos, true)) {
                $erros[] = 'A foto precisa ser JPG, PNG ou WEBP.';
            } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                $erros[] = 'A foto precisa ter até 5MB.';
            } else {
                $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $nomeArquivo = uniqid('ocorrencia_', true) . '.' . $extensoes[$tipoReal];
                $pastaUploads = __DIR__ . '/uploads';

                if (!is_dir($pastaUploads)) {
                    mkdir($pastaUploads, 0755, true);
                }

                $destino = $pastaUploads . '/' . $nomeArquivo;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $fotoPath = 'uploads/' . $nomeArquivo;
                } else {
                    $erros[] = 'Não foi possível salvar a foto no servidor.';
                }
            }
        }
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare(
            'INSERT INTO ocorrencias (usuario_id, categoria, bairro, endereco, descricao, foto_path, status)
             VALUES (?, ?, ?, ?, ?, ?, "pendente")'
        );
        $stmt->execute([
            $_SESSION['usuario_id'],
            $categoriaValor,
            $bairroValor,
            $enderecoValor,
            $descricaoValor,
            $fotoPath,
        ]);

        $sucesso = true;
        $protocolo = str_pad((string) $pdo->lastInsertId(), 4, '0', STR_PAD_LEFT);

        // limpa os campos após sucesso
        $categoriaValor = $bairroValor = $enderecoValor = $descricaoValor = '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar ocorrência — Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="authpage">
  <div class="authpage-inner" style="max-width:560px;">
    <a class="authpage-logo" href="../index.php"><span class="dot"></span> Olho na Cidade</a>

    <?php if (!$logado): ?>

      <!-- Bloqueio: só cidadão logado pode registrar uma ocorrência (US01/US02) -->
      <div class="auth-card">
        <div class="plain-form" style="text-align:center;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2E4A6B" stroke-width="1.6" style="margin:4px auto 16px;">
            <rect x="4" y="10" width="16" height="10" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/>
          </svg>
          <p style="font-size:15px; color:#454B52; line-height:1.6; margin-bottom:22px;">
            Você só pode registrar uma ocorrência estando logado. Isso garante que a denúncia
            fique vinculada ao seu perfil, para você acompanhar o status depois.
          </p>
          <div class="submit-row" style="justify-content:center;">
            <a href="login.php" class="btn-solid" style="text-decoration:none; display:inline-block;">Entrar</a>
            <a href="cadastro.php" class="btn-solid" style="text-decoration:none; display:inline-block; background:var(--asphalt);">Criar conta</a>
          </div>
        </div>
      </div>

    <?php elseif ($sucesso): ?>

      <!-- Confirmação de envio -->
      <div class="auth-card">
        <div class="plain-form" style="text-align:center;">
          <p style="font-size:15px; color:#276B49; line-height:1.6; margin-bottom:6px;">✓ Ocorrência registrada</p>
          <p style="font-size:14px; color:#454B52; margin-bottom:22px;">
            Protocolo <strong class="mono">#<?= htmlspecialchars($protocolo) ?></strong> — acompanhe o status no seu painel.
          </p>
          <div class="submit-row" style="justify-content:center;">
            <a href="painel.php" class="btn-solid" style="text-decoration:none; display:inline-block;">Ver meu painel</a>
            <a href="Registrar.php" class="btn-solid" style="text-decoration:none; display:inline-block; background:var(--asphalt);">Registrar outra</a>
          </div>
        </div>
      </div>

    <?php else: ?>

      <!-- Formulário de registro -->
      <div class="form-card" style="box-shadow:none; padding:8px;">
        <?php if (!empty($erros)): ?>
          <div class="error-list">
            <ul>
              <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form class="plain-form" method="POST" action="Registrar.php" enctype="multipart/form-data" novalidate>
          <div class="hint">Logado como <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong> — <a href="painel.php" style="color:var(--municipal);">voltar</a></div>

          <div class="field">
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria" required>
              <option value="">Selecione...</option>
              <?php foreach (['Buraco na via', 'Iluminação pública', 'Descarte irregular de lixo', 'Vazamento de água', 'Outro'] as $opt): ?>
                <option <?= $categoriaValor === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row2">
            <div class="field">
              <label for="bairro">Bairro</label>
              <input id="bairro" name="bairro" type="text" placeholder="Ex.: Centro" value="<?= htmlspecialchars($bairroValor) ?>" required>
            </div>
            <div class="field">
              <label for="endereco">Endereço</label>
              <input id="endereco" name="endereco" type="text" placeholder="Rua, número" value="<?= htmlspecialchars($enderecoValor) ?>" required>
            </div>
          </div>

          <div class="field">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" placeholder="Descreva o problema com o máximo de detalhe possível..." required minlength="10"><?= htmlspecialchars($descricaoValor) ?></textarea>
          </div>

          <div class="field">
            <label for="foto">Evidência fotográfica (opcional)</label>
            <label class="dropzone" for="foto">Clique para selecionar uma foto do problema</label>
            <input class="file-input" id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
          </div>

          <div class="submit-row">
            <button class="btn-solid" type="submit">Enviar ocorrência</button>
            <span class="form-note">Campos obrigatórios: categoria, bairro, endereço, descrição</span>
          </div>
        </form>
      </div>

    <?php endif; ?>
  </div>
</div>

</body>
</html>