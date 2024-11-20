# base-module
laravel base module

## Objetivo

O objetivo deste módulo é fornecer uma convenção de desenvolvimento para a criação de módulos do Laravel que podem ser
usados em qualquer projeto Laravel.

Ele oferece uma estrutura base para o desenvolvimento de módulos do Laravel, incluindo os seguintes componentes:

### BaseModel - Classe base para modelos

A classe BaseModel tem o objetivo de entregar funcionalidades base para modelos

### BaseMigration

A classe BaseMigration entrega a possibilidade de criar tabelas via Entidades

### BaseSeeder

A classe BaseSeeder fornece metodos para melhorar o feedback dos seeders mostrando uma barra de progresso principalmente
para uma abundância de registros

### BaseRepository

A classe BaseRepository tem o objetivo de desencorajar o uso direto do modelo por outras classes. Criando uma convenção
de desenvolvimento onde o usuário não possa mais espalhar consultas ao banco de qualquer lugar.
O principal objetivo é forçar a organização de consultas em um único local.

### BaseFactory

A classe BaseFactory permite que você trabalhe com factories, sem precisar criar classes Factory para seus modelos.
Basta usar a classe para conseguir semear suas tabelas sem precisar definir dados falsos manualmente.
Ao invés de criar uma classe Factory para seu modelo e definir cada um dos muitos campos com dados fakes, basta usar o
codigo a seguir no seu modelo:

```php
use App\Models\User;

protected static function newFactory(): BaseFactory
{
    return new class extends BaseFactory {
        protected $model = User::class;
    };
}
```

E no seu seeder basta chamar a factory normalmente:

```php
use App\Models\User;

User::factory()->create();
```

Sua tabela de usuários será populada corretamente com dados falsos.

Você é livre para customizar normalmente os campos como sempre fez:

```php
use App\Models\User;

User::factory()->create([
    'name' => 'John Doe',
]);
```

Todos os outros campos serão gerados automaticamente.

### BaseDomain

A classe BaseDomain tem o objetivo de guiar o fluxo da informação podendo usar ou não o repositório.
A objetivo é guiar o fluxo da informação baseada no Modelo como sendo um Domínio onde a informação deve passar por um
organizador da informação.

Ex: Precisa buscar algo no banco de dados, tratar, chamar processos e retornar os dados.
Neste cenário, é comum que ao receber a requisição no Controller, usamos o Modelo para fazer a consulta ao banco, e ali
mesmo no controller, tratamos os dados, chamamos algum serviço e passamos os dados tratados e depois retornamos uma
resposta da requisição.

Embora este fluxo seja comum, este cenário desconsidera a necessidade de criar e manter um códgio manutenível, que seja
fácil de manter e evoluir.
Uma vez que os controladores acumulam consultas e chamadas a serviços externos, acabamos perdendo o controle de onde
estão as regras de negócios da aplicação.
Mas usando o Domínio, definimos que o mesmo é o responsável por definir e manter a ordem da informação.

Em um cenário mais organizado, a requisição chegaria no controlador, e o mesmo decide qual dominio chamar e este
solicita ao seu modelo diretamente ou ao seu repositorio o que precisa
e se tiver que usar o repositório, ele traz a informação, a mesma é tratada fora do reposiório e se precisar chamar
algum cerviço de terceiro, ele chama e recebe o retorno, trata de devolve.

O controlador fica então responsável apenas em receber e devolver a resposta.

Todo este cenário é para manter um fluxo organizado sendo

- O Controller seria usado para receber e devolver a requisição, tratando a resposta como desejar.
- O Domínio seria usado para definir e manter a ordem da informação. Para garantir o fluxo organizado da regra de
  negócio.
- O Modelo seria usado para garantir espelhar os dados de sua tabela evitando consultas customizadas mas apenas
  relacionamentos.
- O Repositorio seria usado para evitar o uso direto do modelo, garantindo que todas as consultas customizadas ficariam
  dentro de classes de repositório. Garantindo a organização das consultas.
- A Factory seria usada para gerar dados falsos para suas tabelas, sem precisar criar classes Factory para seus modelos.
- O Seeder seria usado para melhorar o feedback dos seeders mostrando uma barra de progresso em grande volumes de dados.
- O HTTPService seria usado para chamar serviços externos com uma convenção definida.
- O BaseController seria usado para usar a convenção de dominios onde o controller assumiria um papel mais simples
  delegando responsabilidades de negocio para o Domínio. O BaseController usa a trait BaseResponse para tratar respostas
  complexas se necessário.
- o Livewire/BaseComponent entrega um componente dinâmico baseado no Livewire.

### Middlewares

#### SpotLightMiddleware

O SpotLightMiddleware fornece uma maneira de executar comandos e até navegar em rotas do sistema usando uma interface e
comandos de teclado.

### BaseForm

Usa o BaseComponente para entregar um formulário dinâmico baseado no Livewire.

### Rules

- MinWords serve para validar o número mínimo de palavras de uma string.

### Services

#### DateFn

- Valida datas

#### Errors

- Tratamento de erros avançados com códigos e mensagens de erros específicos

#### Notification

- Padroniza a ação de notificações

#### Response

- O BaseResponse é geralmente utilizado por domínios para tratar respostas complexas, principalmente quando se trabalha
  com APIs.

### Tipos de usuários

A maioria das aplicações adminstrativas possuem um conjunto de usuários que podem ser classificados em grupos distintos.

Este módulo entrega tipos de usuários para podermos classificar usuários por tipo de função.

Telas

- Perfil
- Listagem
- Formulário

### Record

Record é uma entidade que representa a forma mais genéria de um item no banco. Sabendo que um item pode ter:
id, data de criação, data de atualização, data de remoção etc, essa entidade possui essas características de forma
genérica que todo modelo pode se beneficiar, sendo assim não seria necessário repetir estes campos em todos os modelos.

Se seu negócio segue esta lógica, você pode se beneficiar desta forma de atuação.

### Config

O objetivo desta entidade é agrupar as configurações do sistema. Por exemplo, o idioma do sistema, o tema do sistema,
etc.

Telas

- Listagem
- Formulário

### Notifications

O objetivo desta entidade é ter notificações fora do padrão laravel, mas será descontinuada.
Telas

- Listagem
- Visualização
