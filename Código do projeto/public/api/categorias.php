<?php
require_once dirname(__DIR__,2).'/app/bootstrap.php';require_once dirname(__DIR__,2).'/app/controllers/ApiController.php';
runApi(function(){requireLogin();$repo=new JsonRepository('categorias');$rows=$repo->all();if(method()==='GET')jsonResponse(['ok'=>true,'items'=>$rows]);requireAdmin();requireCsrf();$d=inputData();
 if(method()==='POST'){$name=cleanText($d['nome']??'',70);$type=$d['tipo']??'todas';if(!$name||!in_array($type,['todas','receita','despesa','transferencia'],true))throw new InvalidArgumentException('Informe nome e tipo válidos.');foreach($rows as $r)if(mb_strtolower($r['nome'])===mb_strtolower($name))throw new InvalidArgumentException('Já existe uma categoria com esse nome.');$rows[]=['id'=>newId('cat'),'nome'=>$name,'tipo'=>$type,'ativa'=>true];}
 elseif(method()==='PUT'){
  $id=(string)($d['id']??'');$existing=null;
  foreach($rows as $row)if($row['id']===$id){$existing=$row;break;}
  if(!$existing)jsonResponse(['ok'=>false,'message'=>'Categoria não encontrada.'],404);
  $name=cleanText($d['nome']??'',70);$type=$d['tipo']??$existing['tipo'];
  if(!$name||!in_array($type,['todas','receita','despesa','transferencia'],true))throw new InvalidArgumentException('Informe nome e tipo válidos.');
  foreach($rows as $row)if($row['id']!==$id&&mb_strtolower($row['nome'])===mb_strtolower($name))throw new InvalidArgumentException('Já existe uma categoria com esse nome.');
  foreach($rows as &$row)if($row['id']===$id){$row['nome']=$name;$row['tipo']=$type;$row['ativa']=!empty($d['ativa']);break;}
  unset($row);
 }
 else jsonResponse(['ok'=>false,'message'=>'Método não permitido.'],405);
 $repo->saveAll($rows);jsonResponse(['ok'=>true,'message'=>'Categoria salva.']);});

