<?php $page='contas';$title='Contas e caixas';$subtitle='Organize referências de contas e caixas locais. Nenhuma conexão bancária é realizada.';require __DIR__.'/partials/layout.php';?>
<section class="notice"><strong>Somente controle informativo</strong><span>Os saldos são calculados pelos lançamentos pagos. Transferências internas reduzem a origem e aumentam o destino; nenhum banco é consultado ou movimentado.</span></section><section class="toolbar"><div></div><button class="btn btn-primary admin-only" id="addAccount">＋ Cadastrar conta</button></section><section id="accountsGrid" class="account-grid"></section>
<?php require __DIR__.'/partials/footer.php';?>

