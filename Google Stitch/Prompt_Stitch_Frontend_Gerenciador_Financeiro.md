# Prompt para Google Stitch — Frontend do Gerenciador Financeiro

## Contexto do projeto

Crie a interface frontend de um **sistema web local de controle e gerenciamento financeiro para uma empresa**. O sistema organiza e registra movimentações financeiras; ele **não realiza operações bancárias, pagamentos ou transferências reais**.

O planejamento aprovado para a aplicação completa prevê **PHP local, chamadas AJAX e arquivos JSON**, sem banco de dados, nuvem, APIs externas ou autenticação externa. **Neste trabalho, o Stitch deve produzir somente o protótipo frontend**. Não altere essa arquitetura aprovada: o HTML, CSS e JavaScript gerados serão integrados posteriormente à aplicação PHP.

## Limites do que deve ser gerado

- Gere código e arquivos utilizáveis de **HTML5, CSS3, Bootstrap e JavaScript** para a interface. Não entregue apenas imagens ou descrições visuais.
- Não gere PHP, banco de dados, endpoints, chamadas AJAX reais ou integrações externas. O Stitch está sendo usado nesta etapa somente para a interface.
- Use dados fictícios apenas para demonstrar a aparência e as interações. Não apresente a demonstração como persistência real.
- Não implemente autenticação funcional nem solicite ou armazene senhas reais. A tela de login é visual; a autenticação será ligada posteriormente ao backend PHP local.
- Não use `localStorage` como armazenamento oficial ou persistente do sistema financeiro.
- Evite CDN e dependências externas. Quando possível, mantenha os recursos necessários locais para futura integração.
- Não inclua funções que façam pagamentos, transferências bancárias ou movimentação real de dinheiro.

## Diretrizes visuais e responsividade

Crie uma interface profissional, clara e adequada ao uso financeiro empresarial. Use como referência de cores:

- Azul petróleo: `#166B87`
- Azul escuro: `#13283F`
- Fundo azul muito claro: `#EAF4F6`
- Fundo geral neutro, cartões claros, bordas discretas e bom contraste
- Verde para situação paga; vermelho para pendente; amarelo para vencida

A aplicação deve se adaptar a computador, tablet e celular. Em telas largas, use menu lateral vertical recolhível com ícone de seta. Em telas pequenas, adapte a navegação sem comprimir tabelas e formulários: permita rolagem horizontal onde necessário ou transforme linhas em cartões legíveis.

## Estrutura da aplicação final que a interface deve respeitar

Os arquivos gerados pelo Stitch serão reorganizados/integrados posteriormente nesta estrutura já aprovada para o projeto:

```text
gerenciador-financeiro/
├── public/
│   ├── index.php                 # login
│   ├── dashboard.php             # painel inicial
│   ├── transacoes.php            # filtros, tabela e ações
│   ├── contas.php                # cartões e gestão de contas
│   ├── relatorios.php            # indicadores e gráficos
│   ├── configuracoes.php         # categorias e dados do usuário
│   ├── api/                      # endpoints AJAX em PHP
│   │   ├── auth.php
│   │   ├── transacoes.php
│   │   ├── contas.php
│   │   ├── categorias.php
│   │   └── usuarios.php
│   └── assets/
│       ├── css/
│       ├── js/
│       └── vendor/               # bibliotecas locais
├── app/
│   ├── controllers/
│   ├── services/                 # regras, cálculos e validações
│   ├── repositories/             # leitura e gravação JSON
│   └── helpers/
├── config/
│   └── app.php
├── storage/                      # fora da pasta pública
│   ├── data/                     # arquivos JSON
│   └── backups/
└── README.md
```

Não é necessário criar esses arquivos PHP agora. Ao gerar o frontend, mantenha as telas e componentes organizados de modo que possam ser separados em páginas e recursos locais nessa estrutura. O armazenamento JSON ficará fora de `public/`; a interface não deve acessá-lo diretamente.

## Regras financeiras e termos da interface

- Diferencie receitas, despesas e transferências registradas.
- Uma transferência representa uma retirada/saída registrada. Não exige conta de destino e não é uma operação bancária real.
- Lançamentos pagos afetam o saldo realizado. Lançamentos pendentes aparecem nas previsões, sem compor o saldo realizado.
- Exiba, quando a tela correspondente existir, saldo atual, saldo realizado, previsão, receitas, despesas e balanço mensal.
- O saldo acumulado é calculado a partir dos lançamentos registrados; não presuma saldo inicial manual.
- Uma movimentação pode ser única, parcelada ou recorrente. Permita representar frequência em dias, semanas, meses ou anos e recorrência sem data final. Uma opção de receita fixa deve impedir a seleção simultânea de repetição, conforme a regra definida para o projeto.
- Ao editar uma ocorrência recorrente ou parcelada, a interface deve permitir escolher entre alterar somente aquela ocorrência ou as ocorrências seguintes/todas aplicáveis.
- Exclusões devem pedir confirmação. Registro de histórico e recuperação de exclusões ficam fora do MVP.

## Navegação e telas previstas

A aplicação final terá uma tela de login e, após a entrada, um painel com menu lateral recolhível. O menu deverá contemplar:

- Home / Dashboard
- Transações
- Contas
- Relatórios
- Configurações
- Botão de adicionar (`+`) com opções para nova despesa, nova receita e transferência registrada

No topo das telas internas, mostre o título da página, o usuário conectado com menu de conta/sair e controles úteis à tela, como tipo/filtros e busca.

### Dashboard

Mostre cartões de resumo para saldo atual/realizado, previsão, receitas, despesas e balanço do mês. Inclua visualizações demonstrativas simples e uma lista de transações recentes. Use somente dados fictícios na demonstração.

### Transações

Inclua filtros por todas, despesas, receitas e transferências, período e categoria, além de pesquisa textual. A tabela/lista deve apresentar seleção por caixa, situação, data prevista, descrição, categoria e valor. Inclua menu de ações por linha com opções visuais para marcar como paga, editar e excluir. A confirmação de exclusão deve ser visível.

### Formulário de receita/despesa/transferência

Apresente um modal ou painel de formulário com valor, data, descrição, categoria, observação e situação. Demonstre opções para parcelas e recorrência, com campos condicionais para quantidade e unidade de frequência. A interface deve validar campos obrigatórios e valores inválidos visualmente; não finja que a gravação persiste.

### Contas

Crie uma tela demonstrativa de gestão/visualização de contas e cartões, com dados fictícios e indicação de saldo. Não implemente processamento de cartão ou comunicação bancária.

### Relatórios

Inclua ao menos os relatórios prioritários do projeto: saldo do mês, saldo atual, balanço mensal e despesas. Use gráficos e tabelas legíveis, com filtros de período quando fizer sentido.

### Configurações

Demonstre manutenção de categorias e dados do usuário. Inclua elementos visuais para cadastrar/editar categorias e atualizar dados de perfil. Não implemente gestão real de credenciais no frontend.

### Login

Crie uma página visual de acesso com campos de e-mail e senha e botão de entrar. Não valide credenciais reais nem salve a senha. Identifique a tela como protótipo se isso ajudar a evitar confusão durante a demonstração.

## Acessibilidade e comportamento

- Use rótulos associados aos campos, estados de foco visíveis e contraste suficiente.
- Botões, menus, modais, filtros e navegação devem ter estados claros e funcionar na demonstração sempre que possível.
- Mensagens de sucesso, erro e confirmação devem ser compreensíveis, sem afirmar que dados foram gravados permanentemente.
- Use moeda brasileira (R$), datas no formato brasileiro e conteúdo em português.
- Evite texto cortado, elementos sobrepostos e tabelas ilegíveis em telas pequenas.

## Formato da entrega do Stitch

Entregue os arquivos de código do frontend, com nomes claros, e informe:

1. Quais telas/componentes foram criados.
2. Quais arquivos contêm HTML, CSS e JavaScript.
3. Como abrir e testar o protótipo visual localmente.
4. Quais bibliotecas ou recursos foram usados e se precisam ser colocados em `public/assets/vendor`.
5. Quais dados e interações são apenas demonstração e precisarão ser ligados ao PHP/AJAX depois.

## Ciclo incremental — solicitação inicial

Implemente **somente o Ciclo 1: estrutura visual compartilhada e Dashboard**. Crie o shell da aplicação autenticada com menu lateral recolhível, cabeçalho, responsividade, dados fictícios e os cartões/lista/gráfico do Dashboard. Inclua uma tela visual de login apenas se o Stitch conseguir mantê-la separada do shell autenticado. Não avance para telas completas de transações, contas, relatórios ou configurações neste ciclo.

### Critérios de aceite do Ciclo 1

- O menu lateral abre/fecha em computador e tem navegação adequada no celular.
- O Dashboard apresenta os resumos financeiros descritos, usando dados fictícios identificáveis como demonstração.
- O layout funciona em computador, tablet e celular, sem sobreposição ou conteúdo ilegível.
- A navegação e os controles demonstrativos têm estados visuais e interações básicas.
- Os arquivos HTML/CSS/JS são entregues para revisão; não há PHP, backend, banco, chamadas externas ou persistência real.
- A resposta lista limitações e passos de execução local do protótipo.

Após entregar o Ciclo 1, pare e aguarde a revisão da equipe. Não implemente o próximo ciclo sem solicitação.

## Registro de cada ciclo da Etapa 5

Depois de cada ciclo, a equipe registrará:

- Prompt utilizado
- Resultado obtido
- Problemas encontrados
- Ajustes realizados
- Verificações manuais executadas pela equipe

O Stitch não é responsável pela validação final. A equipe deverá abrir os arquivos e testar o comportamento antes de aceitar o ciclo.
