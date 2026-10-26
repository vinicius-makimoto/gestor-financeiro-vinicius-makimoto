<?php
declare(strict_types=1);

final class FinancialService
{
    public static function cents(mixed $value): int
    {
        $v = trim((string)$value);
        $v = preg_replace('/[^\d,.\-]/u', '', $v);
        if (str_contains($v, ',')) $v = str_replace(',', '.', str_replace('.', '', $v));
        if (!is_numeric($v) || (float)$v <= 0) throw new InvalidArgumentException('Informe um valor maior que zero.');
        return (int)round((float)$v * 100);
    }
    public static function isInternalTransfer(array $t): bool
    {
        return ($t['tipo'] ?? '') === 'transferencia' && !empty($t['conta_destino_id']);
    }
    public static function signed(array $t): int
    {
        // Transferências entre contas mudam a distribuição do dinheiro, não o saldo total.
        if (self::isInternalTransfer($t)) return 0;
        return ($t['tipo'] ?? '') === 'receita' ? (int)$t['valor_centavos'] : -(int)$t['valor_centavos'];
    }
    public static function accountEffect(array $t, string $accountId): int
    {
        $amount = (int)($t['valor_centavos'] ?? 0);
        if (self::isInternalTransfer($t)) {
            if (($t['conta_id'] ?? '') === $accountId) return -$amount;
            if (($t['conta_destino_id'] ?? '') === $accountId) return $amount;
            return 0;
        }
        return ($t['conta_id'] ?? '') === $accountId ? self::signed($t) : 0;
    }
    public static function state(array $t): string
    {
        if (($t['situacao'] ?? '') === 'paga') return 'paga';
        return ($t['data_vencimento'] ?? '') < date('Y-m-d') ? 'vencida' : 'pendente';
    }
    public static function addDate(string $date, int $interval, string $unit): string
    {
        $d = new DateTimeImmutable($date);
        if ($unit === 'meses' || $unit === 'anos') {
            $months = $interval * ($unit === 'anos' ? 12 : 1);
            $day = (int)$d->format('d');
            $first = $d->modify('first day of this month')->modify('+' . $months . ' months');
            $day = min($day, (int)$first->format('t'));
            return $first->setDate((int)$first->format('Y'), (int)$first->format('m'), $day)->format('Y-m-d');
        }
        $delta = $interval . ($unit === 'semanas' ? ' weeks' : ' days');
        return $d->modify('+' . $delta)->format('Y-m-d');
    }
    private static function monthDistance(string $from, string $to): int
    {
        [$fy,$fm] = array_map('intval', explode('-', substr($from, 0, 7)));
        [$ty,$tm] = array_map('intval', explode('-', substr($to, 0, 7)));
        return ($ty - $fy) * 12 + $tm - $fm;
    }
    private static function fixedStatus(array $base, string $month): string
    {
        if (isset($base['ocorrencias'][$month]) && in_array($base['ocorrencias'][$month], ['paga','pendente','excluida'], true)) return $base['ocorrencias'][$month];
        return $month === substr((string)$base['data_vencimento'], 0, 7) ? (($base['situacao'] ?? '') === 'paga' ? 'paga' : 'pendente') : 'pendente';
    }
    public static function fixedOccurrence(array $base, string $month): ?array
    {
        $start = substr((string)($base['data_vencimento'] ?? ''), 0, 7);
        if (empty($base['receita_fixa']) || !preg_match('/^\d{4}-\d{2}$/', $month) || !checkdate((int)substr($month,5,2), 1, (int)substr($month,0,4)) || $month < $start) return null;
        $distance = self::monthDistance($start, $month);
        $date = self::addDate((string)$base['data_vencimento'], $distance, 'meses');
        if (!empty($base['dia_vencimento'])) {
            $first = new DateTimeImmutable($month . '-01');
            $day = min((int)$base['dia_vencimento'], (int)$first->format('t'));
            $date = $first->setDate((int)$first->format('Y'), (int)$first->format('m'), $day)->format('Y-m-d');
        }
        if (self::fixedStatus($base, $month) === 'excluida' || (!empty($base['encerrar_a_partir_de']) && $month >= $base['encerrar_a_partir_de'])) return null;
        $row = $base;
        $row['data_vencimento'] = $date;
        $row['situacao'] = self::fixedStatus($base, $month);
        $row['mes_referencia'] = $month;
        $row['receita_fixa_virtual'] = true;
        $row['situacao_exibida'] = self::state($row);
        return $row;
    }
    public static function monthRows(array $rows, string $month): array
    {
        $result = array_values(array_filter($rows, fn($r) => empty($r['receita_fixa'])));
        foreach ($rows as $row) {
            if (empty($row['receita_fixa'])) continue;
            $occurrence = self::fixedOccurrence($row, $month);
            if ($occurrence) $result[] = $occurrence;
        }
        return $result;
    }
    public static function paidFixedOccurrences(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (empty($row['receita_fixa'])) continue;
            $months = $row['ocorrencias'] ?? [];
            $startMonth = substr((string)$row['data_vencimento'], 0, 7);
            if (!isset($months[$startMonth]) && ($row['situacao'] ?? '') === 'paga') $months[$startMonth] = 'paga';
            foreach ($months as $month => $status) {
                if ($status !== 'paga') continue;
                $occurrence = self::fixedOccurrence($row, (string)$month);
                if ($occurrence) $result[] = $occurrence;
            }
        }
        return $result;
    }
    public static function extendOpenSeries(JsonRepository $repo): array
    {
        // Não materializa recorrências indefinidas ao consultar a aplicação.
        // Repetições novas têm quantidade definida e receitas fixas são virtuais.
        return $repo->all();
    }
    public static function dashboard(array $rows, string $month, ?string $primaryAccountId = null): array
    {
        $start = $month . '-01'; $end = date('Y-m-t', strtotime($start));
        $realized = $openMonth = $income = $expense = $withdrawals = 0;
        foreach ($rows as $t) {
            if (!empty($t['receita_fixa'])) continue;
            $date = (string)$t['data_vencimento']; $signed = self::signed($t);
            if (($t['situacao'] ?? '') === 'paga') $realized += $primaryAccountId ? (empty($t['conta_id']) ? $signed : self::accountEffect($t, $primaryAccountId)) : $signed;
            if (($t['situacao'] ?? '') !== 'paga' && $date >= $start && $date <= $end) $openMonth += $signed;
            if ($date >= $start && $date <= $end) {
                if ($t['tipo'] === 'receita') $income += (int)$t['valor_centavos'];
                elseif ($t['tipo'] === 'despesa' || !self::isInternalTransfer($t)) $expense += (int)$t['valor_centavos'];
                elseif (self::isInternalTransfer($t)) $withdrawals += (int)$t['valor_centavos'];
            }
        }
        foreach (self::paidFixedOccurrences($rows) as $paid) $realized += $primaryAccountId ? (empty($paid['conta_id']) ? self::signed($paid) : self::accountEffect($paid, $primaryAccountId)) : self::signed($paid);
        foreach (self::monthRows($rows, $month) as $row) {
            if (empty($row['receita_fixa_virtual'])) continue;
            if ($row['situacao'] !== 'paga') $openMonth += self::signed($row);
            if (($row['tipo'] ?? '') === 'receita') $income += (int)$row['valor_centavos'];
            else $expense += (int)$row['valor_centavos'];
        }
        $paidIncome = $overdueIncome = 0;
        foreach (self::monthRows($rows, $month) as $row) {
            if (($row['tipo'] ?? '') !== 'receita' || !str_starts_with($row['data_vencimento'], $month)) continue;
            if (($row['situacao'] ?? '') === 'paga') $paidIncome += (int)$row['valor_centavos'];
            elseif (self::state($row) === 'vencida') $overdueIncome += (int)$row['valor_centavos'];
        }
        return ['saldo_atual'=>$realized,'saldo_previsto'=>$realized+$openMonth,'receitas_mes'=>$income,'despesas_mes'=>$expense,'retiradas_investimentos_mes'=>$withdrawals,'balanco_mes'=>$income-$expense,'receitas_pagas_mes'=>$paidIncome,'receitas_atrasadas_mes'=>$overdueIncome,'lancamentos_efetivados_mes'=>$paidIncome,'inadimplencia_mes'=>$overdueIncome];
    }
    public static function annualReport(array $rows, int $year): array
    {
        $realized = $commitments = $delinquent = $delinquentCount = 0;
        $today = date('Y-m-d');
        $incomeRows = array_values(array_filter($rows, fn($r) => ($r['tipo'] ?? '') === 'receita' && empty($r['receita_fixa'])));
        for ($month = 1; $month <= 12; $month++) {
            $key = sprintf('%04d-%02d', $year, $month);
            foreach ($rows as $row) {
                if (empty($row['receita_fixa'])) continue;
                $occurrence = self::fixedOccurrence($row, $key);
                if ($occurrence) $incomeRows[] = $occurrence;
            }
        }
        foreach ($incomeRows as $row) {
            $date = (string)$row['data_vencimento'];
            if (substr($date, 0, 4) !== (string)$year) continue;
            $amount = (int)$row['valor_centavos'];
            if (($row['situacao'] ?? '') === 'paga') $realized += $amount;
            else {
                $commitments += $amount;
                if ($date < $today) { $delinquent += $amount; $delinquentCount++; }
            }
        }
        return ['saldo_anual_previsto'=>$realized+$commitments,'realizado'=>$realized,'compromissos_pendentes'=>$commitments,'inadimplencia_valor'=>$delinquent,'inadimplencia_quantidade'=>$delinquentCount,'ano'=>$year];
    }
}
