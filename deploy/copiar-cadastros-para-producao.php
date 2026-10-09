<?php

/**
 * Copia os cadastros do banco LOCAL para o banco de PRODUÇÃO, mantendo os IDs.
 * - Só registros ativos (deleted_at nulo).
 * - Nada ligado a usuários (palpites, comentários, dicas, assinaturas, papéis de usuário).
 * - Produção precisa estar só com o seeder; aborta se alguma tabela de destino já tiver dados.
 *
 * Local: lido do .env do projeto. Produção: variáveis PROD_DB_* do processo.
 * Usado uma vez, no primeiro deploy (09/10/2026). Uso:
 *   source deploy/lib.sh   (define a função "acesso")
 *   export PROD_DB_HOST="$(acesso 'Banco de dados MySQL' Host)" ... (idem Banco, Usuário, Senha)
 *   php deploy/copiar-cadastros-para-producao.php
 *
 * Observação: o ALTER TABLE confirma a transação automaticamente no MySQL, então em caso de
 * erro no meio parte dos dados pode ter sido gravada; confira as contagens depois.
 */
$tabelas = [
    'organizacoes', 'categorias', 'categorias_peso', 'estilos_luta', 'treinadores', 'juizes',
    'atletas', 'atleta_fotos', 'atleta_estilos', 'eventos', 'lutas', 'luta_juizes',
];

$env = [];
foreach (file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES) as $linha) {
    if (preg_match('/^([A-Z_]+)=(.*)$/', $linha, $m)) {
        $env[$m[1]] = trim($m[2], "\"'");
    }
}

$opcoes = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$local = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD'], $opcoes);
$prod = new PDO('mysql:host='.getenv('PROD_DB_HOST').';port=3306;dbname='.getenv('PROD_DB_DATABASE').';charset=utf8mb4', getenv('PROD_DB_USERNAME'), getenv('PROD_DB_PASSWORD'), $opcoes);

foreach ($tabelas as $tabela) {
    if ((int) $prod->query("select count(*) from `{$tabela}`")->fetchColumn() > 0) {
        exit("Produção já tem dados em {$tabela}. Nada foi copiado.\n");
    }
}

$prod->exec('SET FOREIGN_KEY_CHECKS=0');
$prod->beginTransaction();

try {
    foreach ($tabelas as $tabela) {
        $linhas = $local->query("select * from `{$tabela}` where deleted_at is null order by id")->fetchAll();
        if ($linhas === []) {
            echo str_pad($tabela, 16)." 0\n";

            continue;
        }

        $colunas = array_keys($linhas[0]);
        $sql = "insert into `{$tabela}` (`".implode('`,`', $colunas).'`) values ('.implode(',', array_fill(0, count($colunas), '?')).')';
        $insert = $prod->prepare($sql);
        foreach ($linhas as $linha) {
            $insert->execute(array_values($linha));
        }

        // O próximo id em produção continua depois dos copiados.
        $proximo = (int) $local->query("select coalesce(max(id),0)+1 from `{$tabela}`")->fetchColumn();
        $prod->exec("alter table `{$tabela}` auto_increment = {$proximo}");

        echo str_pad($tabela, 16).' '.count($linhas)."\n";
    }

    if ($prod->inTransaction()) {
        $prod->commit();
    }
} catch (Throwable $erro) {
    if ($prod->inTransaction()) {
        $prod->rollBack();
    }
    exit('Erro, nada foi gravado: '.$erro->getMessage()."\n");
} finally {
    $prod->exec('SET FOREIGN_KEY_CHECKS=1');
}

echo "ok\n";
