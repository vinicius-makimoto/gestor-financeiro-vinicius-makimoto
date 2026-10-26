<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/controllers/ApiController.php';
runApi(function () {
    $users = new JsonRepository('usuarios'); $rows = $users->all();
    $action = $_GET['action'] ?? '';
    if ($action === 'setup' && method() === 'POST') {
        if ($rows) jsonResponse(['ok'=>false,'message'=>'A configuração inicial já foi concluída.'], 409);
        requireCsrf(); $d = inputData();
        $name = cleanText($d['nome'] ?? '',100); $email = filter_var(trim((string)($d['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string)($d['senha'] ?? '');
        if (!$name || !$email || strlen($password) < 10) throw new InvalidArgumentException('Informe nome, e-mail válido e senha com pelo menos 10 caracteres.');
        $rows[] = ['id'=>newId('usr'),'nome'=>$name,'email'=>strtolower($email),'senha_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>'admin','ativo'=>true,'criado_em'=>date(DATE_ATOM)];
        $users->saveAll($rows);
        $categories = new JsonRepository('categorias');
        if (!$categories->all()) $categories->saveAll([
            ['id'=>newId('cat'),'nome'=>'Vendas e serviços','tipo'=>'receita','ativa'=>true],
            ['id'=>newId('cat'),'nome'=>'Outras receitas','tipo'=>'receita','ativa'=>true],
            ['id'=>newId('cat'),'nome'=>'Fornecedores','tipo'=>'despesa','ativa'=>true],
            ['id'=>newId('cat'),'nome'=>'Despesas operacionais','tipo'=>'despesa','ativa'=>true],
            ['id'=>newId('cat'),'nome'=>'Retiradas e investimentos','tipo'=>'transferencia','ativa'=>true],
        ]);
        $accounts = new JsonRepository('contas');
        if (!$accounts->all()) $accounts->saveAll([
            ['id'=>newId('conta'),'nome'=>'Caixa principal','tipo'=>'Caixa','ativa'=>true],
        ]);
        session_regenerate_id(true); $_SESSION['user']=['id'=>$rows[0]['id'],'nome'=>$name,'email'=>strtolower($email),'role'=>'admin']; jsonResponse(['ok'=>true,'user'=>$_SESSION['user']]);
    }
    if ($action === 'login' && method() === 'POST') {
        requireCsrf(); $d=inputData(); $email=strtolower(trim((string)($d['email']??''))); $password=(string)($d['senha']??'');
        foreach ($rows as $u) if (($u['email']??'')===$email && !empty($u['ativo']) && password_verify($password,$u['senha_hash'])) {
            session_regenerate_id(true); $_SESSION['user']=['id'=>$u['id'],'nome'=>$u['nome'],'email'=>$u['email'],'role'=>$u['role']]; $_SESSION['csrf']=bin2hex(random_bytes(32)); jsonResponse(['ok'=>true,'user'=>$_SESSION['user']]);
        }
        jsonResponse(['ok'=>false,'message'=>'E-mail ou senha inválidos.'], 401);
    }
    if ($action === 'logout' && method() === 'POST') { requireLogin(); requireCsrf(); $_SESSION=[]; session_destroy(); jsonResponse(['ok'=>true]); }
    if ($action === 'me' && method() === 'GET') {
        if (empty($_SESSION['user'])) jsonResponse(['ok'=>true,'setup'=>!$rows,'user'=>null]);
        jsonResponse(['ok'=>true,'setup'=>false,'user'=>$_SESSION['user'],'csrf'=>$_SESSION['csrf']]);
    }
    jsonResponse(['ok'=>false,'message'=>'Ação inválida.'], 405);
});

