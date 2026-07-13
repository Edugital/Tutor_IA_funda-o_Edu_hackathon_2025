# Tutor IA para Moodle

Plugin aberto de feedback e tutoria assistida por inteligência artificial para Moodle, orientado por rubricas, competências, evidências e controle humano.

## Estado

Este repositório é a fonte canônica do plugin `assignfeedback_aitutoria`.

A versão inicial migrada é uma baseline de recuperação em estágio alpha. Ela prioriza:

- feedback publicado somente por decisão humana;
- nenhuma alteração automática de nota numérica;
- separação entre sugestão de IA e feedback oficial;
- instalação, atualização, privacidade, backup e restauração compatíveis com Moodle;
- documentação e testes reproduzíveis;
- independência de fornecedor de IA.

## Estrutura

O diretório raiz corresponde ao plugin Moodle `mod/assign/feedback/aitutoria`.

Para instalar manualmente, o conteúdo deve ser colocado em:

```text
<Moodle>/mod/assign/feedback/aitutoria
```

Consulte `docs/INSTALLATION.md` antes de usar em qualquer ambiente real.

## Governança

Mudanças devem seguir issue, branch, testes, pull request, staging e aprovação. Alterações diretas em produção não são aceitas.

## Licença

GPL v3 ou posterior.
