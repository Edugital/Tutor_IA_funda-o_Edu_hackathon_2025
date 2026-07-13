# Changelog

Todas as alterações relevantes deste projeto serão documentadas aqui.

O formato segue os princípios de Keep a Changelog e versionamento semântico, adaptados ao versionamento obrigatório de plugins Moodle.

## [Unreleased]

### Planejado

- provedor OpenAI/Azure plugável (chaves só na UI);
- rubricas estruturadas e versionadas na UI;
- painel de calibração e métricas de divergência;
- tutor fundamentado no curso.

## [0.4.0-alpha.0] - 2026-07-13

### Adicionado

- `INSTALL.md` para escolas (ZIP + SSH + checklist de aceite);
- docs STATUS alinhados ao release atual;
- habilitação demonstrável por atividade no piloto EBAC (site default permanece off).

### Alterado

- bump de versão Moodle plugin para `2026071310` / release `0.4.0-alpha.0`;
- registro explícito do contrato de provedores (ainda sem provedor de produção).

## [0.3.0-alpha.2] - 2026-07-13

### Validado no piloto EBAC LMS (Moodle 4.5)

- instalação SHA-pinned;
- `diagnose.php --json` sem critical;
- `default=0` e `allowaisuggestions=0`.

## [0.2.1-recovery] - 2026-07-13

### Corrigido

- conformidade com o Moodle Code Checker;
- prefixo canônico da tabela `assignfeedback_aitutoria`;
- validação oficial de metadados do plugin;
- migração automática da tabela curta usada pela primeira baseline candidata;
- rastreabilidade dos relatórios de CI.

### Validado

- instalação limpa no Moodle 4.4 com MariaDB;
- instalação limpa no Moodle 4.5 com PostgreSQL;
- PHP lint;
- PHPUnit;
- Behat;
- savepoints de atualização;
- pacote ZIP instalável com raiz `aitutoria/`.

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
- atualização sobre o banco legado real ainda depende de teste externo.
