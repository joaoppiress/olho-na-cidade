<?php
/**
 * Limite de tentativas de login (proteção contra força bruta).
 *
 * Regras:
 *  - 5 falhas para o mesmo e-mail em 15 minutos bloqueiam novas tentativas
 *    com aquele e-mail.
 *  - 20 falhas vindas do mesmo IP em 15 minutos bloqueiam aquele IP.
 *  - O bloqueio dura até 15 minutos após a última falha registrada.
 *  - Um login bem-sucedido zera as falhas daquele e-mail.
 *
 * A contagem é feita pelo e-mail digitado (exista ou não), então o bloqueio
 * não revela se uma conta existe.
 */

const LOGIN_MAX_TENTATIVAS_EMAIL = 5;
const LOGIN_MAX_TENTATIVAS_IP    = 20;
const LOGIN_JANELA_MINUTOS       = 15;

/** Cria a tabela de controle caso ainda não exista. */
function login_garantir_tabela(PDO $pdo)
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS login_tentativas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_data (email, criado_em),
            INDEX idx_ip_data (ip, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

/** IP de quem fez a requisição (não confia em cabeçalhos forjáveis). */
function login_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '0.0.0.0';
}

/** Chave normalizada do e-mail para contagem. */
function login_chave_email($email)
{
    return mb_strtolower(trim($email));
}

/**
 * Quantos segundos faltam para liberar novas tentativas.
 * Retorna 0 se não há bloqueio.
 */
function login_segundos_bloqueio(PDO $pdo, $email)
{
    $janela = (int) LOGIN_JANELA_MINUTOS;
    $restante = 0;

    $consultas = [
        ['campo' => 'email', 'valor' => login_chave_email($email), 'max' => LOGIN_MAX_TENTATIVAS_EMAIL],
        ['campo' => 'ip',    'valor' => login_ip(),                'max' => LOGIN_MAX_TENTATIVAS_IP],
    ];

    foreach ($consultas as $c) {
        $sql = "SELECT COUNT(*) AS total,
                       TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(criado_em), INTERVAL {$janela} MINUTE)) AS restante
                FROM login_tentativas
                WHERE {$c['campo']} = ?
                  AND criado_em > DATE_SUB(NOW(), INTERVAL {$janela} MINUTE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$c['valor']]);
        $r = $stmt->fetch();

        if ($r && (int) $r['total'] >= $c['max'] && (int) $r['restante'] > $restante) {
            $restante = (int) $r['restante'];
        }
    }

    return $restante;
}

/** Quantas tentativas ainda restam para o e-mail antes do bloqueio. */
function login_tentativas_restantes(PDO $pdo, $email)
{
    $janela = (int) LOGIN_JANELA_MINUTOS;
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_tentativas
         WHERE email = ? AND criado_em > DATE_SUB(NOW(), INTERVAL {$janela} MINUTE)"
    );
    $stmt->execute([login_chave_email($email)]);
    return max(0, LOGIN_MAX_TENTATIVAS_EMAIL - (int) $stmt->fetchColumn());
}

/** Registra uma falha de login. */
function login_registrar_falha(PDO $pdo, $email)
{
    $stmt = $pdo->prepare('INSERT INTO login_tentativas (email, ip) VALUES (?, ?)');
    $stmt->execute([substr(login_chave_email($email), 0, 150), login_ip()]);

    // Limpeza: registros com mais de 1 dia não servem mais.
    $pdo->exec('DELETE FROM login_tentativas WHERE criado_em < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}

/** Zera as falhas do e-mail após um login correto. */
function login_limpar(PDO $pdo, $email)
{
    $stmt = $pdo->prepare('DELETE FROM login_tentativas WHERE email = ?');
    $stmt->execute([login_chave_email($email)]);
}

/** Texto amigável para o tempo restante de bloqueio. */
function login_texto_espera($segundos)
{
    $min = (int) ceil($segundos / 60);
    return $min <= 1 ? '1 minuto' : $min . ' minutos';
}