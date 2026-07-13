# Arquitetura

## Estado atual

O projeto é um subplugin Moodle do tipo:

```text
assignfeedback
```

Ele se integra ao módulo `assign` sem substituir o fluxo de avaliação do Moodle.

## Componentes atuais

### `locallib.php`

Integra o plugin com:

- configurações da tarefa;
- formulário de correção;
- publicação do feedback;
- visualização pelo estudante;
- livro de notas;
- limpeza da instância.

### `classes/local/decision_policy.php`

Política determinística Human in Control. Decide apenas qual texto de feedback será publicado após ação humana.

Não calcula nem escreve nota numérica.

### `classes/local/feedback_repository.php`

Fronteira de persistência para:

- feedback oficial;
- sugestão privada de IA;
- estado da sugestão;
- decisão humana;
- modelo, prompt e rubrica usados.

### `classes/privacy/provider.php`

Implementa exportação e exclusão de dados pela Privacy API do Moodle.

### `db/install.xml` e `db/upgrade.php`

Definem o esquema canônico e a migração não destrutiva da configuração legada conhecida.

### `backup/moodle2`

Integra feedback e sugestões ao backup e à restauração de cursos.

## Fluxo Human in Control

```text
submissão do estudante
→ sugestão privada opcional
→ revisão do professor
→ aceitar, editar ou substituir
→ salvar feedback oficial
→ estudante visualiza
```

A nota numérica permanece fora desse fluxo.

## Arquitetura alvo

A evolução não deve transformar este subplugin em um monólito. A arquitetura prevista separa:

1. `assignfeedback_aitutoria`: interface de avaliação e revisão;
2. núcleo institucional de IA: provedores, políticas, auditoria, custos e filas;
3. registro de rubricas, competências e resultados de aprendizagem;
4. base de conhecimento autorizada por curso;
5. tutor conversacional;
6. relatórios de qualidade e governança.

## Princípios

- independência de fornecedor;
- processamento assíncrono e idempotente;
- scoring determinístico;
- evidência antes de julgamento;
- versionamento de rubrica, prompt e modelo;
- minimização de dados;
- controle de acesso Moodle;
- falha segura;
- operação manual sempre disponível;
- auditabilidade ponta a ponta.

## Limites atuais

- rubrica ainda é textual;
- não há fila de processamento;
- não há provedor de IA;
- não há análise por critério;
- não há painel de calibração;
- não há chat tutor;
- testes de banco, backup e navegador exigem uma instalação Moodle completa.
