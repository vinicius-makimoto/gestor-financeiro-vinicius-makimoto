<?php
require_once dirname(__DIR__,2).'/app/bootstrap.php';
require_once dirname(__DIR__,2).'/app/controllers/ApiController.php';
runApi(function () {
    $user=requireLogin(); $repo=new JsonRepository('transacoes'); $method=method();
    if ($method==='GET') {
        $stored=FinancialService::extendOpenSeries($repo);$month=$_GET['mes']??'';
        if(preg_match('/^\d{4}-\d{2}$/',$month))$rows=FinancialService::monthRows($stored,$month);
        elseif(($_GET['periodos_pagos']??'')==='1')$rows=array_merge(array_values(array_filter($stored,fn($r)=>empty($r['receita_fixa']))),FinancialService::paidFixedOccurrences($stored));
        else $rows=FinancialService::monthRows($stored,date('Y-m'));
        $seriesCounts=[];
        foreach($stored as $storedRow) if(!empty($storedRow['serie_id'])) $seriesCounts[$storedRow['serie_id']]=($seriesCounts[$storedRow['serie_id']]??0)+1;
        foreach($rows as &$row) if(!empty($row['serie_id'])) $row['quantidade_serie']=$seriesCounts[$row['serie_id']]??2;
        unset($row);
        $rows=filterRows($rows);
        $type=$_GET['tipo']??''; $status=$_GET['situacao']??''; $q=mb_strtolower(trim((string)($_GET['q']??'')));
        $rows=array_filter($rows,function($r)use($type,$status,$month,$q){
            $state=FinancialService::state($r);
            $search=mb_strtolower(implode(' ',[$r['descricao']??'', $r['observacao']??'', $r['categoria_nome']??'', $r['data_vencimento']??'', (string)($r['valor_centavos']??''), $r['tipo']??'', $state]));
            return (!$type||$r['tipo']===$type)&&(!$status||$state===$status)&&(!$month||str_starts_with($r['data_vencimento'],$month))&&(!$q||str_contains($search,$q));
        });
        usort($rows,fn($a,$b)=>strcmp($a['data_vencimento'],$b['data_vencimento']));
        jsonResponse(['ok'=>true,'items'=>array_values($rows)]);
    }
    requireCsrf(); $d=inputData(); $rows=$repo->all();
    if ($method==='POST') {
        $base=validateMovement($d,(new JsonRepository('categorias'))->all(),(new JsonRepository('contas'))->all());
        $mode=$d['modo']??'unica'; $quantity=(int)($d['quantidade']??1); $interval=(int)($d['intervalo']??1); $unit=$d['unidade']??'meses';if(!empty($d['infinita']))throw new InvalidArgumentException('A repetição deve ter quantidade definida; lançamentos sem término não são aceitos.');
        if (!in_array($mode,['unica','parcelada','recorrente'],true)) throw new InvalidArgumentException('Tipo de repetição inválido.');
        if ($mode!=='unica' && ($interval<1||$interval>365||!in_array($unit,['dias','semanas','meses','anos'],true))) throw new InvalidArgumentException('Informe a frequência da repetição.');
        if ($mode!=='unica' && ($quantity<2||$quantity>240)) throw new InvalidArgumentException('A quantidade deve ficar entre 2 e 240 ocorrências.');
        if($base['receita_fixa']){
            $base['id']=newId('mov');$base['criado_por']=$user['id'];$base['criado_em']=date(DATE_ATOM);$base['serie_id']=null;$base['serie_indice']=null;$base['recorrencia']=null;
            $base['dia_vencimento']=(int)(new DateTimeImmutable($base['data_vencimento']))->format('d');$base['ocorrencias']=[];
            $rows[]=$base;$repo->saveAll($rows);jsonResponse(['ok'=>true,'message'=>'Movimentação fixa cadastrada. Uma única regra será projetada em cada mês.']);
        }
        $count=$mode==='unica'?1:$quantity; $series=$count>1?newId('serie'):null; $date=$base['data_vencimento'];
        for($i=1;$i<=$count;$i++){
            $row=$base; $row['id']=newId('mov'); $row['criado_por']=$user['id']; $row['criado_em']=date(DATE_ATOM); $row['serie_id']=$series; $row['serie_indice']=$series?$i:null;
            $row['recorrencia']=$series?['modo'=>$mode,'intervalo'=>$interval,'unidade'=>$unit,'infinita'=>false]:null;
            if($i>1){$date=FinancialService::addDate($date,$interval,$unit);$row['data_vencimento']=$date;$row['situacao']='pendente';}
            $rows[]=$row;
        }
        $repo->saveAll($rows); jsonResponse(['ok'=>true,'message'=>'Movimentação salva.']);
    }
    if ($method==='PATCH') {
        $id=(string)($d['id']??'');$month=(string)($d['mes_referencia']??'');
        foreach($rows as &$r) if($r['id']===$id){
            if(!empty($r['receita_fixa'])){
                if(!preg_match('/^\d{4}-\d{2}$/',$month)||!FinancialService::fixedOccurrence($r,$month))throw new InvalidArgumentException('Informe o mês da receita fixa.');
                $r['ocorrencias']=$r['ocorrencias']??[];$r['ocorrencias'][$month]=!empty($d['paga'])?'paga':'pendente';
            }else $r['situacao']=!empty($d['paga'])?'paga':'pendente';
            $r['atualizado_em']=date(DATE_ATOM);$r['atualizado_por']=$user['id'];$found=true;break;
        } unset($r);
        if(empty($found)) jsonResponse(['ok'=>false,'message'=>'Lançamento não encontrado.'],404);
        $repo->saveAll($rows); jsonResponse(['ok'=>true,'message'=>'Situação atualizada.']);
    }
    if ($method==='PUT') {
        $id=(string)($d['id']??'');$scope=$d['escopo']??'unica';$index=null;
        foreach($rows as $i=>$r) if($r['id']===$id){$index=$i;break;}
        if($index===null) jsonResponse(['ok'=>false,'message'=>'Lançamento não encontrado.'],404);
        $existing=$rows[$index];$base=validateMovement($d,(new JsonRepository('categorias'))->all(),(new JsonRepository('contas'))->all(),$existing);
        if(!empty($existing['receita_fixa'])){
            if(!$base['receita_fixa'])throw new InvalidArgumentException('Para encerrar esta regra mensal, exclua a movimentação fixa.');
            $month=(string)($d['mes_referencia']??'');
            if(!preg_match('/^\d{4}-\d{2}$/',$month)||!FinancialService::fixedOccurrence($existing,$month))throw new InvalidArgumentException('Informe um mês válido para editar a receita fixa.');
            $occurrences=$existing['ocorrencias']??[];$occurrences[$month]=$base['situacao'];
            $base['id']=$existing['id'];$base['data_vencimento']=$existing['data_vencimento'];$base['dia_vencimento']=(int)(new DateTimeImmutable((string)($d['data_vencimento']??$existing['data_vencimento'])))->format('d');
            $base['situacao']=$existing['situacao']??'pendente';$base['ocorrencias']=$occurrences;$base['serie_id']=null;$base['serie_indice']=null;$base['recorrencia']=null;$base['criado_por']=$existing['criado_por']??$user['id'];$base['criado_em']=$existing['criado_em']??date(DATE_ATOM);$base['atualizado_em']=date(DATE_ATOM);$rows[$index]=$base;
        }elseif($base['receita_fixa']){
            $base['dia_vencimento']=(int)(new DateTimeImmutable($base['data_vencimento']))->format('d');$base['ocorrencias']=[substr($base['data_vencimento'],0,7)=>$base['situacao']];
            $base['serie_id']=null;$base['serie_indice']=null;$base['recorrencia']=null;$base['atualizado_em']=date(DATE_ATOM);$rows[$index]=array_merge($existing,$base);
        }elseif($existing['serie_id']??false){
            if(!in_array($scope,['unica','proximas','todas'],true))throw new InvalidArgumentException('Escolha o escopo da edição.');
            $selectedIndex=(int)$existing['serie_indice'];$lastDate=null;
            foreach($rows as $ri=>$candidate){
                if(($candidate['serie_id']??null)!==$existing['serie_id'])continue;
                $occurrence=(int)$candidate['serie_indice'];$isSelected=$candidate['id']===$id;
                $apply=$scope==='todas'||($scope==='proximas'&&$occurrence>=$selectedIndex)||($scope==='unica'&&$isSelected);
                $occurrenceDate=$candidate['data_vencimento'];
                if($isSelected)$occurrenceDate=$base['data_vencimento'];
                elseif($scope!=='unica'&&$occurrence>$selectedIndex&&$lastDate!==null){
                    $recurrence=$candidate['recorrencia']??$existing['recorrencia'];
                    $occurrenceDate=FinancialService::addDate($lastDate,(int)$recurrence['intervalo'],$recurrence['unidade']);
                }
                if($apply){$rows[$ri]=array_merge($candidate,$base);$rows[$ri]['data_vencimento']=$occurrenceDate;$rows[$ri]['atualizado_em']=date(DATE_ATOM);}
                $lastDate=$occurrenceDate;
            }
        }else{$rows[$index]=array_merge($existing,$base);$rows[$index]['atualizado_em']=date(DATE_ATOM);}
        $repo->saveAll($rows);jsonResponse(['ok'=>true,'message'=>'Movimentação atualizada.']);
    }
    if ($method==='DELETE') {
        $id=(string)($d['id']??'');$scope=(string)($d['escopo']??'unica');$month=(string)($d['mes_referencia']??'');$index=null;
        foreach($rows as $i=>$row)if($row['id']===$id){$index=$i;break;}
        if($index===null)jsonResponse(['ok'=>false,'message'=>'Lançamento não encontrado.'],404);
        $target=$rows[$index];
        if(!in_array($scope,['unica','proximas','todas'],true))throw new InvalidArgumentException('Escopo de exclusão inválido.');
        if(!empty($target['receita_fixa'])){
            if($scope==='todas')$rows=array_values(array_filter($rows,fn($r)=>$r['id']!==$id));
            elseif($scope==='proximas'){$start=preg_match('/^\d{4}-\d{2}$/',$month)?$month:substr((string)$target['data_vencimento'],0,7);$rows[$index]['encerrar_a_partir_de']=$start;}
            else {$current=preg_match('/^\d{4}-\d{2}$/',$month)?$month:substr((string)$target['data_vencimento'],0,7);$rows[$index]['ocorrencias']=$rows[$index]['ocorrencias']??[];$rows[$index]['ocorrencias'][$current]='excluida';}
        }elseif(!empty($target['serie_id'])){
            $series=$target['serie_id'];$selected=(int)$target['serie_indice'];
            $rows=array_values(array_filter($rows,function($row)use($id,$scope,$series,$selected){if(($row['serie_id']??null)!==$series)return true;if($scope==='todas')return false;if($scope==='proximas')return (int)($row['serie_indice']??0)<$selected;return $row['id']!==$id;}));
        }else $rows=array_values(array_filter($rows,fn($r)=>$r['id']!==$id));
        $repo->saveAll($rows);jsonResponse(['ok'=>true,'message'=>'Movimentação excluída.']);
    }
    jsonResponse(['ok'=>false,'message'=>'Método não permitido.'],405);
});

