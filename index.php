<?php
session_start();
require __DIR__ . '/php/config.php';

$logado = isset($_SESSION['usuario_id']);

// Status usados tanto no ticker quanto no painel do cidadão
$statusInfo = [
    'pendente'   => ['label' => 'Pendente',    'pill' => 'pendente', 'tag' => 'tag-pend'],
    'em_analise' => ['label' => 'Em análise',  'pill' => 'analise',  'tag' => 'tag-analise'],
    'resolvido'  => ['label' => 'Resolvido',   'pill' => 'resolvido','tag' => 'tag-ok'],
];

// Últimas ocorrências de todos os usuários, pro ticker do topo (mural público)
$stmtTicker = $pdo->prepare('SELECT id, categoria, endereco, status FROM ocorrencias ORDER BY criado_em DESC LIMIT 6');
$stmtTicker->execute();
$ocorrenciasTicker = $stmtTicker->fetchAll();

// Todas as ocorrências registradas no banco, pro painel público (independe de login)
$stmtPainel = $pdo->prepare('SELECT id, categoria, bairro, endereco, descricao, status FROM ocorrencias ORDER BY criado_em DESC LIMIT 12');
$stmtPainel->execute();
$ocorrenciasPainel = $stmtPainel->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Olho na Cidade</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  .empty-state{ background:#fff; border:1px dashed var(--line); border-radius:6px; padding:40px 24px; text-align:center; color:#8A8F96; font-size:14px; }
</style>
</head>
<body>

<header>
  <div class="navwrap">
    <div class="logo"><span class="dot"></span> Olho na Cidade</div>
    <div class="navlinks">
      <nav>
        <ul>
          <li><a href="#como-funciona">Como funciona</a></li>
          <li><a href="#conta">Entrar / Cadastrar</a></li>
          <li><a href="#paineis">Painéis</a></li>
          <li><a href="#tecnologia">Tecnologia</a></li>
        </ul>
      </nav>
      <a class="nav-cta" href="php/login.php">Entrar</a>
    </div>
  </div>
</header>

<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <div class="eyebrow">Sistema municipal de participação cidadã</div>
      <h1>A cidade tem<br>problemas. Agora<br>ela tem <em>olhos</em>.</h1>
      <p class="lede">Uma plataforma para que cidadãos registrem buracos, iluminação apagada e descarte irregular — e para que a prefeitura organize, priorize e resolva com dados, não com boatos.</p>
      <div class="hero-ctas">
        <a href="php/Registrar.php" class="btn btn-primary">Registrar uma ocorrência</a>
        <a href="#paineis" class="btn btn-ghost">Ver painel do cidadão</a>
      </div>
    </div>
    <div class="radar-wrap" aria-hidden="true">
      <div class="radar-bg"></div>
      <div class="radar-ring r1"></div>
      <div class="radar-ring r2"></div>
      <div class="radar-ring r3"></div>
      <div class="sweep"></div>
      <div class="blip pend"></div>
      <div class="blip analise"></div>
      <div class="blip ok"></div>
      <div class="blip pend2"></div>
      <div class="radar-center"></div>
    </div>
  </div>
  <div class="ticker">
    <div class="ticker-track">
      <?php if (empty($ocorrenciasTicker)): ?>
        <span>Nenhuma ocorrência registrada ainda — seja o primeiro a registrar.</span>
      <?php else: ?>
        <?php $voltas = count($ocorrenciasTicker) >= 4 ? 2 : 1; ?>
        <?php for ($volta = 0; $volta < $voltas; $volta++): ?>
          <?php foreach ($ocorrenciasTicker as $o): ?>
            <?php $info = $statusInfo[$o['status']]; ?>
            <span><span class="tag <?= $info['tag'] ?>"><?= $info['label'] ?></span> #<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($o['categoria']) ?>, <?= htmlspecialchars($o['endereco']) ?></span>
          <?php endforeach; ?>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="como-funciona">
  <div class="wrap">
    <div class="section-head">
      <div class="kicker">Fluxo do sistema</div>
      <h2>Do relato à resolução</h2>
      <p>Quatro etapas conectam o que o cidadão vê na rua ao que a prefeitura consegue resolver — sem telefonemas perdidos nem papel.</p>
    </div>
  </div>
  <div class="wrap">
    <div class="steps">
      <div class="step">
        <div class="num">01</div>
        <h3>Cadastro seguro</h3>
        <p>O cidadão cria um perfil para acompanhar suas próprias denúncias com privacidade.</p>
      </div>
      <div class="step">
        <div class="num">02</div>
        <h3>Registro com evidência</h3>
        <p>Descrição, categoria, endereço e foto — informação suficiente para a prefeitura agir.</p>
      </div>
      <div class="step">
        <div class="num">03</div>
        <h3>Triagem municipal</h3>
        <p>A administração organiza por bairro e categoria, e muda o status conforme avança.</p>
      </div>
      <div class="step">
        <div class="num">04</div>
        <h3>Retorno ao cidadão</h3>
        <p>Cada mudança de status aparece no perfil de quem registrou — transparência ponta a ponta.</p>
      </div>
    </div>
  </div>
</section>

<section id="conta" class="conta">
  <div class="wrap">
    <div class="conta-grid">
      <div>
        <div class="kicker">Acesso do cidadão</div>
        <h2 style="font-size:clamp(28px,3.6vw,40px); font-weight:800; margin-bottom:6px;">Login e cadastro</h2>
        <p style="font-size:15.5px; line-height:1.7; color:#454B52; max-width:42ch; margin-top:16px;">O cadastro e o login são páginas PHP completas — validam os dados no servidor e gravam no MySQL com senha protegida por hash (nunca em texto puro).</p>
        <ul class="conta-points">
          <li><span class="bullet">1</span> Sem JavaScript: só HTML, CSS e PHP.</li>
          <li><span class="bullet">2</span> Se algum campo estiver errado, a própria página reaparece com o erro explicado.</li>
          <li><span class="bullet">3</span> Ao entrar ou se cadastrar com sucesso, o cidadão é redirecionado para <span class="mono">php/painel.php</span>, uma rota protegida por sessão.</li>
        </ul>
      </div>

      <div class="conta-teaser-card">
        <strong style="text-transform:uppercase; font-size:13px; letter-spacing:0.04em; color:#5B6169;">Área do cidadão</strong>
        <p style="margin-top:14px; font-size:14.5px; line-height:1.6; color:#454B52;">Acesse com seu e-mail e senha ou crie uma conta nova para começar a registrar ocorrências.</p>
        <div class="conta-teaser-actions">
          <a href="php/login.php" class="btn-solid" style="text-decoration:none; display:inline-block;">Entrar</a>
          <a href="php/cadastro.php" class="btn-solid" style="text-decoration:none; display:inline-block; background:var(--asphalt);">Criar conta</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="registro" class="registro">
  <div class="wrap">
    <div class="registro-grid">
      <div>
        <div class="kicker">Experiência do cidadão</div>
        <h2 style="font-size:clamp(28px,3.6vw,40px); font-weight:800; margin-bottom:18px;">Registrar leva menos<br>de um minuto</h2>
        <p style="font-size:15.5px; line-height:1.7; color:#454B52; max-width:42ch;">Campos obrigatórios evitam envios incompletos. Cada envio gera um protocolo que pode ser acompanhado depois no seu painel.</p>
        <p style="font-size:14px; color:#8A8F96; max-width:42ch; margin-top:10px;">É preciso estar logado para registrar — assim a denúncia fica vinculada ao seu perfil.</p>
      </div>
      <div class="conta-teaser-card">
        <strong style="text-transform:uppercase; font-size:13px; letter-spacing:0.04em; color:#5B6169;">Nova ocorrência</strong>
        <p style="margin-top:14px; font-size:14.5px; line-height:1.6; color:#454B52;">Categoria, bairro, endereço, descrição e uma foto opcional — leva menos de um minuto.</p>
        <div class="conta-teaser-actions">
          <a href="php/Registrar.php" class="btn-solid" style="text-decoration:none; display:inline-block;">Registrar ocorrência</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="paineis">
  <div class="wrap">
    <div class="section-head">
      <div class="kicker">Base de dados centralizada</div>
      <h2>Painéis por perfil de acesso</h2>
      <p>O cidadão acompanha o que registrou. A prefeitura enxerga a cidade inteira, em uma área restrita a administradores.</p>
    </div>

    <input type="radio" name="paineltab" id="tab-cidadao" class="panel-radio" checked>

    <div class="tabs">
      <label for="tab-cidadao">Painel do cidadão</label>
    </div>

    <div class="panel" id="panel-cidadao">
      <div class="card-list">
        <?php if (empty($ocorrenciasPainel)): ?>
          <div class="empty-state">
            Nenhuma ocorrência registrada ainda.
            <?php if ($logado): ?>
              <br><a href="php/Registrar.php" style="color:var(--municipal); font-weight:700;">Registrar a primeira</a>
            <?php else: ?>
              <br><a href="php/login.php" style="color:var(--municipal); font-weight:700;">Entrar</a> ou <a href="php/cadastro.php" style="color:var(--municipal); font-weight:700;">criar conta</a> pra registrar
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php foreach ($ocorrenciasPainel as $o): ?>
            <?php $info = $statusInfo[$o['status']]; ?>
            <div class="occ-card">
              <span class="proto-id mono">#<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></span>
              <span class="desc"><?= htmlspecialchars($o['descricao']) ?><span class="cat"><?= htmlspecialchars($o['categoria']) ?></span></span>
              <span class="pill <?= $info['pill'] ?>"><?= $info['label'] ?></span>
              <span class="bairro"><?= htmlspecialchars($o['endereco']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</section>

<section id="tecnologia" class="tech">
  <div class="wrap">
    <div class="section-head">
      <div class="kicker">Base técnica e conformidade</div>
      <h2>Construído para durar em cidades pequenas</h2>
      <p>Tecnologia simples de hospedar, dados protegidos por lei, e um escopo que deixa espaço claro para crescer.</p>
    </div>
    <div class="tech-grid">
      <div class="tech-card">
        <span class="mono-tag">Stack</span>
        <h3>PHP + MySQL</h3>
        <p>Desenvolvimento web tradicional, compatível com hospedagem acessível para prefeituras de pequeno porte.</p>
      </div>
      <div class="tech-card">
        <span class="mono-tag">Acesso</span>
        <h3>Perfis separados</h3>
        <p>Cidadãos e administradores têm permissões distintas — cada um vê e faz apenas o que lhe cabe.</p>
      </div>
      <div class="tech-card">
        <span class="mono-tag">Validação</span>
        <h3>Campos obrigatórios</h3>
        <p>Regras de preenchimento reduzem envios incompletos ou falsos antes de chegar à análise.</p>
      </div>
      <div class="tech-card">
        <span class="mono-tag">Privacidade</span>
        <h3>Conformidade com a LGPD</h3>
        <p>Dados pessoais de cidadãos tratados com as diretrizes de segurança e privacidade exigidas por lei.</p>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="wrap foot-row">
    <div class="foot-logo">Olho na Cidade</div>
    <div class="foot-note">SISTEMA DE PARTICIPAÇÃO CIDADÃ </div>
  </div>
</footer>

</body>
</html>