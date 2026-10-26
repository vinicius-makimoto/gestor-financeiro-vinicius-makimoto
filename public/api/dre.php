<?php
require_once dirname(__DIR__,2).'/app/bootstrap.php';
require_once dirname(__DIR__,2).'/app/controllers/ApiController.php';
runApi(function(){
    requireLogin();
    $start=(string)($_GET['inicio']??'');$end=(string)($_GET['fim']??'');
    if(!validDate($start)||!validDate($end)||$start>$end)throw new InvalidArgumentException('Informe um período válido para a DRE.');
    $stored=FinancialService::extendOpenSeries(new JsonRepository('transacoes'));
    $categories=(new JsonRepository('categorias'))->all();$names=[];foreach($categories as $category)$names[$category['id']]=$category['nome'];
    $rows=[];
    foreach($stored as $row)if(empty($row['receita_fixa'])&&($row['data_vencimento']??'')>=$start&&($row['data_vencimento']??'')<=$end)$rows[]=$row;
    $firstMonth=substr($start,0,7);$lastMonth=substr($end,0,7);
    foreach($stored as $row){if(empty($row['receita_fixa']))continue;for($month=$firstMonth;$month<=$lastMonth;$month=date('Y-m',strtotime($month.'-01 +1 month'))){$occurrence=FinancialService::fixedOccurrence($row,$month);if($occurrence&&$occurrence['data_vencimento']>=$start&&$occurrence['data_vencimento']<=$end)$rows[]=$occurrence;}}
    $gross=$deductions=$costs=$financial=$administrative=$fixed=0;
    foreach($rows as $row){$type=$row['tipo']??'';$amount=(int)($row['valor_centavos']??0);if($type==='receita'){$gross+=$amount;continue;}if($type!=='despesa')continue;$name=mb_strtolower((string)($names[$row['categoria_id']??'']??''));if(preg_match('/imposto|tribut|dedu|tax/u',$name)){$deductions+=$amount;continue;}if(preg_match('/custo|insumo|material|fornecedor/u',$name)){$costs+=$amount;continue;}if(preg_match('/juros|tarifa|financeir|banc/u',$name)){$financial+=$amount;continue;}if(!empty($row['receita_fixa']))$fixed+=$amount;else $administrative+=$amount;}
    $netRevenue=$gross-$deductions;$grossProfit=$netRevenue-$costs;$operational=$administrative+$fixed;$operationalResult=$grossProfit-$operational;$netIncome=$operationalResult-$financial;
    jsonResponse(['ok'=>true,'periodo'=>['inicio'=>$start,'fim'=>$end],'dre'=>['receita_bruta'=>$gross,'deducoes'=>$deductions,'receita_liquida'=>$netRevenue,'custos'=>$costs,'lucro_bruto'=>$grossProfit,'despesas_administrativas'=>$administrative,'despesas_fixas'=>$fixed,'despesas_operacionais'=>$operational,'resultado_operacional'=>$operationalResult,'despesas_financeiras'=>$financial,'lucro_liquido'=>$netIncome,'margem_liquida'=>$netRevenue?round($netIncome/$netRevenue*100,1):0]]);
});
