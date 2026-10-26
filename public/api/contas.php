<?php
require_once dirname(__DIR__,2).'/app/bootstrap.php';require_once dirname(__DIR__,2).'/app/controllers/ApiController.php';
runApi(function(){requireLogin();$repo=new JsonRepository('contas');$rows=$repo->all();if(method()==='GET')jsonResponse(['ok'=>true,'items'=>$rows]);requireAdmin();requireCsrf();$d=inputData();
 if(method()==='POST'){$name=cleanText($d['nome']??'',80);if(!$name)throw new InvalidArgumentException('Informe o nome da conta.');$rows[]=['id'=>newId('conta'),'nome'=>$name,'tipo'=>cleanText($d['tipo']??'Conta/Caixa',30),'ativa'=>true];}
 elseif(method()==='PUT'){$found=false;foreach($rows as &$r)if($r['id']===($d['id']??'')){$r['nome']=cleanText($d['nome']??'',80);$r['tipo']=cleanText($d['tipo']??$r['tipo'],30);$r['ativa']=!empty($d['ativa']);$found=true;break;}unset($r);if(!$found)jsonResponse(['ok'=>false,'message'=>'Conta não encontrada.'],404);}
 else jsonResponse(['ok'=>false,'message'=>'Método não permitido.'],405);$repo->saveAll($rows);jsonResponse(['ok'=>true,'message'=>'Conta salva.']);});

