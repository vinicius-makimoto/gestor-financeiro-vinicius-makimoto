<?php
require_once dirname(__DIR__).'/app/bootstrap.php';
if (!empty($_SESSION['user'])) { header('Location: dashboard.php'); exit; }
$users=(new JsonRepository('usuarios'))->all();$setup=!$users;
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?=htmlspecialchars($_SESSION['csrf'],ENT_QUOTES,'UTF-8')?>"><title><?=$setup?'Configuração inicial':'Entrar'?> | FinanGestão</title><link rel="stylesheet" href="assets/css/app.css"></head><body class="login-page">
<div class="login-card"><div class="brand-mark">FG</div><div class="eyebrow">CONTROLE FINANCEIRO EMPRESARIAL</div><h1><?=$setup?'Crie o administrador':'Acesse sua conta'?></h1><p class="muted"><?=$setup?'Este cadastro aparece uma única vez neste computador.':'Entre para consultar e organizar as movimentações.'?></p>
<form id="authForm" data-mode="<?=$setup?'setup':'login'?>">
<?php if($setup):?><label>Nome completo<input name="nome" autocomplete="name" required maxlength="100"></label><?php endif?>
<label>E-mail<input name="email" type="email" autocomplete="username" required></label><label>Senha<input name="senha" type="password" autocomplete="<?=$setup?'new-password':'current-password'?>" minlength="<?=$setup?'10':'1'?>" required></label>
<?php if($setup):?><small>A senha precisa ter pelo menos 10 caracteres. Não existe senha inicial definida.</small><?php endif?>
<button class="btn btn-primary btn-block" type="submit"><?=$setup?'Criar administrador':'Entrar'?></button><div id="authMessage" class="form-message" aria-live="polite"></div></form>
<div class="local-note"><span class="dot green"></span>Aplicação local · Seus dados ficam neste computador</div></div><script src="assets/js/app.js" defer></script></body></html>

