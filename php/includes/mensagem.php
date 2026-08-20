<?php
/**
 * Renderiza uma página simples de retorno (erro ou sucesso),
 * reaproveitando as cores do site "Olho na Cidade".
 *
 * Uso:
 *   require 'includes/mensagem.php';
 *   render_mensagem('Não foi possível criar sua conta', $erros, 'erro', 'index.php#conta', 'Voltar ao cadastro');
 */

function render_mensagem(string $titulo, array $itens, string $tipo, string $voltarHref, string $voltarTexto): void
{
    $cor = $tipo === 'sucesso' ? '#276B49' : '#B23D1F';
    $fundo = $tipo === 'sucesso' ? 'rgba(62,156,111,0.1)' : 'rgba(232,90,50,0.08)';
    $borda = $tipo === 'sucesso' ? 'rgba(62,156,111,0.35)' : 'rgba(232,90,50,0.35)';
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> — Olho na Cidade</title>
    <style>
      body{ font-family: Arial, sans-serif; background:#FBF9F4; color:#14181D; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:24px; }
      .card{ max-width:440px; width:100%; background:#fff; border:1px solid rgba(20,24,29,0.12); border-radius:8px; padding:32px; }
      h1{ font-size:20px; margin:0 0 16px; }
      .msg{ background:<?= $fundo ?>; border:1px solid <?= $borda ?>; color:<?= $cor ?>; border-radius:6px; padding:14px 16px; font-size:14px; margin-bottom:20px; }
      .msg ul{ margin:0; padding-left:18px; }
      a.voltar{ display:inline-block; background:#2E4A6B; color:#fff; text-decoration:none; padding:11px 20px; border-radius:4px; font-size:13px; font-weight:bold; }
    </style>
    </head>
    <body>
      <div class="card">
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <div class="msg">
          <ul>
            <?php foreach ($itens as $item): ?>
              <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <a class="voltar" href="<?= htmlspecialchars($voltarHref) ?>"><?= htmlspecialchars($voltarTexto) ?></a>
      </div>
    </body>
    </html>
    <?php
    exit;
}
