# Gestor Financeiro

Projeto de uma aplicação web local para gerenciamento financeiro, desenvolvido em PHP, JavaScript e CSS. O sistema permite controlar receitas, despesas, contas, categorias, transferências e relatórios financeiros.

> A aplicação é voltada para uso local e educacional. Ela não realiza pagamentos, transferências bancárias ou qualquer operação financeira real.

## Organização do repositório

### Levantamento de requisitos

Contém o documento de levantamento e detalhamento dos requisitos do sistema, incluindo as etapas de definição das funcionalidades e regras do projeto.

### Google Stitch

Contém os materiais e prompts utilizados na etapa de criação e planejamento da interface do sistema com o Google Stitch.

### Código do projeto

Contém todo o código-fonte executável da aplicação:

- `app/`: serviços, controladores, repositórios e funções auxiliares;
- `config/`: configurações gerais da aplicação;
- `public/`: páginas, endpoints da API e recursos visuais acessíveis pelo navegador;
- `storage/`: diretórios locais usados para armazenar dados e backups;
- `iniciar-windows.bat`: atalho para iniciar o servidor no Windows.

### Entrega da atividade

Contém o documento final preenchido para entrega e avaliação da atividade.

## Como executar o projeto

### Requisitos

- PHP 8.1 ou superior;
- navegador atualizado;
- permissão de escrita na pasta `Código do projeto/storage`.

### Servidor integrado do PHP

1. Abra o terminal na pasta `Código do projeto`.
2. Execute:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

3. Acesse `http://127.0.0.1:8000`.
4. No primeiro acesso, cadastre o usuário administrador.

No Windows, também é possível executar o arquivo `iniciar-windows.bat` dentro da pasta de código.

### XAMPP

Copie a pasta `Código do projeto` para o diretório `htdocs` do XAMPP, inicie o Apache e acesse a subpasta `public` pelo navegador.

## Armazenamento e segurança

Os dados são gravados localmente em arquivos JSON dentro de `Código do projeto/storage/data`. Esses arquivos, assim como os backups gerados durante o uso, são ignorados pelo Git para evitar a publicação acidental de informações financeiras e credenciais de usuários.
