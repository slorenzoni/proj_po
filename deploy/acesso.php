<?php
// Lê um campo ("Host", "Usuário", "Senha"...) de uma seção do .Producao_acessos e imprime o valor.
// Uso: php deploy/acesso.php <arquivo de acessos> <seção> <campo>
// A seção é procurada no início da linha, para não casar com menções como "(ver [SSH])".
// Chamado por deploy/lib.sh e deploy/askpass.sh; nada é gravado.

[, $arquivo, $secao, $campo] = $argv + [null, null, null, null];
if ($campo === null) {
    fwrite(STDERR, "Uso: php deploy/acesso.php <arquivo> <seção> <campo>\n");
    exit(2);
}

$texto = str_replace("\r\n", "\n", file_get_contents($arquivo));
if (! preg_match('/^\[' . preg_quote($secao, '/') . '/mu', $texto, $m, PREG_OFFSET_CAPTURE)) {
    fwrite(STDERR, "Seção não encontrada no arquivo de acessos: [$secao]\n");
    exit(1);
}
$inicio = $m[0][1];
$fim = strpos($texto, "\n[", $inicio + 1);
$trecho = $fim === false ? substr($texto, $inicio) : substr($texto, $inicio, $fim - $inicio);

if (! preg_match('/^' . preg_quote($campo, '/') . ':\s*(\S+)/mu', $trecho, $m)) {
    fwrite(STDERR, "Campo \"$campo\" não encontrado na seção [$secao]\n");
    exit(1);
}
echo $m[1], "\n";
