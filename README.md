# Gerenciador Financeiro

Aplicação local para organizar receitas, despesas e saídas classificadas como transferências/retiradas. **Não realiza operações bancárias, pagamentos ou transferências reais.**

## Requisitos e execução

- PHP 8.1 ou superior, com suporte a sessões e escrita de arquivos.
- No terminal, entre na pasta gerenciador-financeiro e execute php -v para conferir a instalação.
- Inicie o servidor com php -S 127.0.0.1:8000 -t public.
- Abra http://127.0.0.1:8000 no navegador. Na primeira execução, a página de configuração permite cadastrar o primeiro administrador; não existe senha padrão.
- Depois, entre com o e-mail e a senha que você cadastrou.

Em Windows também é possível executar iniciar-windows.bat após instalar o PHP e adicioná-lo ao PATH.

### Usando XAMPP e Apache

Copie a pasta completa para C:\\xampp\\htdocs\\gerenciador-financeiro e inicie somente o módulo Apache no painel do XAMPP. Acesse http://localhost/gerenciador-financeiro/public/. Os arquivos .htaccess em app/, config/ e storage/ bloqueiam acesso HTTP direto a essas pastas; mantenha-os. A configuração do Apache deve permitir diretivas .htaccess (AllowOverride).

## Dados e segurança

Os dados ficam nos JSON locais em storage/data/, fora de public/. São criados automaticamente na primeira execução. Os valores são guardados em centavos inteiros. Cópias dos arquivos JSON são feitas em storage/backups/ antes de cada gravação.

O sistema usa sessão PHP, senhas com password_hash, token CSRF nas alterações, validação no servidor, bloqueio de arquivo e gravação atômica. Mantenha cópias adicionais de storage/ em local seguro. Como o sistema é local e baseado em JSON, não é adequado para uso simultâneo por muitos usuários/dispositivos nem para publicar na internet.

## Regras implementadas nesta versão

- Receitas aumentam o saldo; despesas diminuem o saldo. Transferências internas exigem conta de origem e destino: diminuem o saldo da origem e aumentam o da conta de destino, sem alterar o total consolidado nem os indicadores de receitas e despesas. Não iniciam operações bancárias reais.
- Transferências antigas sem conta de destino são preservadas como retiradas legadas: continuam reduzindo o saldo da conta de origem e aparecem nos relatórios como saídas. Ao editar uma delas, é possível mantê-la como retirada ou escolher uma conta de destino para convertê-la em transferência interna.
- Somente lançamentos pagos compõem o realizado. Pendentes e vencidos entram na previsão. Uma pendência anterior à data atual é exibida como vencida.
- Saldo acumulado começa em zero e considera lançamentos pagos até hoje. Não há campo de saldo inicial.
- Receita fixa grava uma única regra no JSON. A ocorrência mensal é calculada em memória ao abrir o mês e não é gravada como um novo lançamento. A situação paga/pendente de cada mês é guardada de forma esparsa somente quando alterada. Editar uma ocorrência altera os dados da regra; a situação editada vale para o mês selecionado. Excluir remove a regra fixa e suas situações mensais.
- O dashboard apresenta saldo realizado e totais mensais, sem o saldo anual previsto. Relatórios exibem a previsão do ano como receitas pagas mais receitas pendentes do ano corrente, sem descontar despesas, retiradas ou transferências. Os cartões de recebidas, pendentes e inadimplência consideram somente receitas do ano, por data prevista. Pendentes incluem as vencidas. O fluxo mensal mostra receitas, despesas/retiradas, balanço, receitas pagas e receitas atrasadas; pagas e atrasadas são partes das receitas, não valores adicionais.
- Parcelamentos e recorrências criados nesta versão exibem somente quantidade e periodicidade: cada ocorrência é gerada após uma unidade da periodicidade escolhida (por exemplo, a cada mês) e a série termina ao atingir a quantidade definida. Consultas não materializam novas transações. Séries sem término de versões anteriores deixam de ser ampliadas; os lançamentos que já estavam gravados são preservados.
- Em edição de série, escolha somente esta ocorrência, esta e as próximas, ou a série inteira.
- Administrador gerencia usuários e categorias. Usuário padrão pode operar movimentações e consultar relatórios. A matriz final de permissões ainda precisa de validação da equipe.
- “Receita fixa” só pode ser aplicada a receitas e é exclusiva de parcelamento/recorrência. Ela se repete mensalmente desde a data de início, com projeção em memória e situação individual por mês. Parcelamentos e recorrências usam quantidade definida e periodicidade; a frequência entre ocorrências é de uma unidade escolhida (por exemplo, a cada mês), sem opção de intervalo adicional ou repetição sem data para terminar.

## Estrutura

    gerenciador-financeiro/
    ├── public/                 páginas e recursos acessíveis pelo navegador
    │   ├── api/                endpoints PHP para AJAX
    │   └── assets/             CSS e JavaScript locais
    ├── app/                    serviços, controladores, repositórios e helpers
    ├── config/                 configuração local
    └── storage/                JSON e backups, fora da pasta pública

## Bibliotecas visuais

O HTML de referência anexado ao projeto depende de Tailwind, fontes Google e ícones carregados por CDN, o que contraria a arquitetura local sem dependências externas. A primeira versão mantém a organização visual e o comportamento responsivo com CSS/JavaScript locais e sem chamadas externas. AJAX é feito com fetch. Bootstrap, jQuery, SweetAlert2 e Chart.js podem ser adicionados posteriormente em public/assets/vendor/ quando seus arquivos locais forem fornecidos ou autorizados para inclusão; a interface não depende de CDN.

## Testes

Execute php -l nos arquivos PHP para validar sintaxe e teste o fluxo no navegador. Valide a receita fixa criando uma regra, consultando dois meses sem aumentar a quantidade de regras no JSON, marcando um mês como pago e conferindo os saldos e inadimplência no relatório. A máquina de geração não possui PHP instalado, portanto a execução PHP precisa ser validada no computador local antes de considerar a versão pronta.

