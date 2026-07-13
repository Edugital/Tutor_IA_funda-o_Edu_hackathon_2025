# Tutor IA para Moodle

Plugin aberto de feedback e tutoria assistida por inteligência artificial para Moodle, orientado por rubricas, competências, evidências e controle humano.

## Componente Moodle

```text
assignfeedback_aitutoria
```

O diretório raiz deste repositório corresponde a:

```text
<Moodle>/mod/assign/feedback/aitutoria
```

## Estado do produto

**Versão:** `0.2.0-recovery`  
**Maturidade:** alpha  
**Moodle mínimo:** 4.4  
**Política:** Human in Control

Esta baseline foi recuperada de uma implementação legada e reorganizada para se tornar instalável, auditável e evolutiva. Ela ainda não contém integração com provedor externo de IA.

## O que já funciona

- configuração por atividade;
- rubrica textual e versão da rubrica;
- feedback manual por avaliação;
- armazenamento separado de sugestão de IA ainda não publicada;
- aceitação explícita ou substituição da sugestão pelo professor;
- feedback visível ao estudante;
- sincronização textual com o livro de notas;
- Privacy API do Moodle;
- backup e restauração de cursos;
- migração não destrutiva da configuração legada documentada;
- português do Brasil e inglês;
- testes da política determinística de decisão humana;
- validação estática e empacotamento ZIP reproduzível.

## Garantias Human in Control

1. A IA não escreve nota numérica.
2. Sugestões ficam privadas até decisão humana explícita.
3. O professor pode aceitar, editar ou substituir integralmente a sugestão.
4. Falha ou ausência de IA não bloqueia a avaliação manual.
5. A automação é desativada por padrão.
6. A implementação não depende de um fornecedor específico.

## Instalação resumida

1. Gere ou baixe o pacote `assignfeedback_aitutoria-*.zip`.
2. No Moodle, acesse **Administração do site → Plugins → Instalar plugins**.
3. Instale o ZIP ou copie o conteúdo para:

```text
mod/assign/feedback/aitutoria
```

4. Execute a atualização do Moodle.
5. Mantenha o plugin desativado por padrão até concluir os testes em staging.

Consulte [docs/INSTALLATION.md](docs/INSTALLATION.md) para o procedimento completo.

## Desenvolvimento

```bash
python3 tools/validate.py
bash tools/package.sh
```

Em uma instalação completa do Moodle:

```bash
vendor/bin/phpunit --testsuite assignfeedback_aitutoria_testsuite
```

## Próximas frentes

- testes de banco e Behat em Moodle real;
- rubricas estruturadas e versionadas;
- motor assíncrono e idempotente;
- avaliação por critério, evidências e incerteza;
- painel de revisão e calibração;
- abstração de provedores;
- tutor fundamentado nos conteúdos autorizados do curso;
- governança institucional de competências, custos e privacidade;
- implantação simplificada para escolas públicas.

Consulte [docs/BACKLOG.md](docs/BACKLOG.md).

## Segurança e privacidade

Não envie dados reais de estudantes, chaves de API, dumps ou credenciais para o repositório. Vulnerabilidades devem seguir [SECURITY.md](SECURITY.md).

## Licença

GPL v3 ou posterior. Consulte [LICENSE](LICENSE).
