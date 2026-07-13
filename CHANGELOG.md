# Changelog

Todas as alterações relevantes deste projeto serão documentadas aqui.

O formato segue os princípios de Keep a Changelog e versionamento semântico, adaptados ao versionamento obrigatório de plugins Moodle.

## [Unreleased]

### Planejado

- testes de banco e Behat em Moodle real;
- rubricas estruturadas e versionadas;
- processamento assíncrono e idempotente;
- abstração de provedores;
- avaliação por critério e evidência;
- painel de revisão humana e calibração;
- tutor fundamentado no curso.

## [0.2.0-recovery] - 2026-07-13

### Adicionado

- fonte canônica do plugin `assignfeedback_aitutoria`;
- feedback manual por grade;
- rubrica e versão por atividade;
- armazenamento separado de sugestão de IA;
- decisão humana `manual`, `accepted_ai` ou `overridden_ai`;
- feedback visível ao estudante;
- Privacy API;
- backup e restauração;
- migração não destrutiva da configuração legada conhecida;
- idiomas inglês e português do Brasil;
- teste unitário da política Human in Control;
- validação estática e empacotamento ZIP.

### Segurança

- plugin desativado por padrão;
- sugestões de IA desativadas por padrão;
- nenhuma alteração automática de nota numérica;
- nenhuma credencial ou endpoint de produção incorporado ao código.

### Limitações

- sem provedor externo de IA;
- sem processamento de anexos;
- sem rubrica estruturada;
- sem painel administrativo avançado;
- instalação e atualização ainda precisam de validação em Moodle real.
