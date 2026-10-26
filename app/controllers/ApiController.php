<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/FinancialService.php';

function runApi(callable $action): never
{
    try { $action(); }
    catch (InvalidArgumentException $e) { jsonResponse(['ok'=>false,'message'=>$e->getMessage()], 422); }
    catch (Throwable $e) {
        error_log((string)$e);
        jsonResponse(['ok'=>false,'message'=>'Não foi possível concluir. Verifique os dados locais e tente novamente.'], 500);
    }
}
function method(): string { return $_SERVER['REQUEST_METHOD'] ?? 'GET'; }
function filterRows(array $rows): array
{
    $cats = (new JsonRepository('categorias'))->all();
    $catMap = [];
    foreach ($cats as $c) $catMap[$c['id']] = $c['nome'];
    foreach ($rows as &$r) {
        $r['situacao_exibida'] = FinancialService::state($r);
        $r['categoria_nome'] = $catMap[$r['categoria_id'] ?? ''] ?? 'Sem categoria';
    }
    unset($r);
    return $rows;
}
function validateMovement(array $d, array $categories, array $accounts, array $existing = []): array
{
    $type = $d['tipo'] ?? '';
    if (!in_array($type, ['receita','despesa','transferencia'], true)) throw new InvalidArgumentException('Selecione o tipo da movimentação.');
    $date = (string)($d['data_vencimento'] ?? '');
    if (!validDate($date)) throw new InvalidArgumentException('Informe uma data válida.');
    $description = cleanText($d['descricao'] ?? '', 180);
    if ($description === '') throw new InvalidArgumentException('Informe a descrição.');
    $categoryId = cleanText($d['categoria_id'] ?? '', 80);
    if (!$categoryId || !array_filter($categories, fn($c)=>$c['id']===$categoryId && !empty($c['ativa']))) throw new InvalidArgumentException('Selecione uma categoria ativa.');
    $accountId = cleanText($d['conta_id'] ?? '', 80);
    if ($accountId && !array_filter($accounts, fn($a)=>$a['id']===$accountId && !empty($a['ativa']))) throw new InvalidArgumentException('Selecione uma conta ativa.');
    $destinationId = cleanText($d['conta_destino_id'] ?? '', 80);
    if ($type === 'transferencia') {
        if (!$accountId) throw new InvalidArgumentException('Selecione a conta de origem.');
        if (!$destinationId) throw new InvalidArgumentException('Selecione a conta de destino.');
        if ($destinationId && $destinationId === $accountId) throw new InvalidArgumentException('As contas de origem e destino devem ser diferentes.');
        if ($destinationId && !array_filter($accounts, fn($a)=>$a['id']===$destinationId && !empty($a['ativa']))) throw new InvalidArgumentException('Selecione uma conta de destino ativa.');
    } else {
        $destinationId = null;
    }
    if (!empty($existing) && (($existing['tipo'] ?? '') === 'transferencia') !== ($type === 'transferencia')) throw new InvalidArgumentException('O tipo de uma transferência salva não pode ser alterado. Registre uma nova movimentação para corrigir o lançamento.');
    if (($existing['tipo'] ?? '') === 'transferencia' && ($type !== 'transferencia' || ($existing['conta_id'] ?? '') !== $accountId || ($existing['conta_destino_id'] ?? '') !== $destinationId)) throw new InvalidArgumentException('A conta de origem e a conta de destino não podem ser alteradas em uma transferência salva. Registre uma nova transferência para retornar valores à conta principal.');
    $status = $d['situacao'] ?? 'pendente';
    if (!in_array($status, ['pendente','paga'], true)) throw new InvalidArgumentException('Situação inválida.');
    $fixedIncome = !empty($d['receita_fixa']);
    if ($fixedIncome && !in_array($type, ['receita','despesa'], true)) throw new InvalidArgumentException('Movimentação fixa só pode ser usada em receitas ou despesas.');
    if ($fixedIncome && ($d['modo'] ?? 'unica') !== 'unica') throw new InvalidArgumentException('Movimentação fixa não pode ser combinada com parcelamento ou repetição.');
    return ['tipo'=>$type,'data_vencimento'=>$date,'descricao'=>$description,'categoria_id'=>$categoryId,'conta_id'=>$accountId ?: null,'conta_destino_id'=>$destinationId,'observacao'=>cleanText($d['observacao'] ?? '',500),'situacao'=>$status,'receita_fixa'=>$fixedIncome,'valor_centavos'=>FinancialService::cents($d['valor'] ?? '')];
}

