# Contribuindo

## Fluxo obrigatório

```text
issue ou especificação
→ branch
→ implementação
→ testes
→ pull request
→ revisão
→ staging
→ aprovação
→ release
```

Não altere instalações Moodle diretamente como forma de desenvolvimento.

## Requisitos para mudanças

- explicar o problema e o valor pedagógico;
- indicar impactos em privacidade, segurança e controle humano;
- adicionar ou atualizar testes;
- atualizar documentação e changelog quando necessário;
- manter compatibilidade com avaliação manual;
- não acoplar o produto a um único provedor de IA;
- não incluir dados reais, segredos ou endpoints privados.

## Human in Control

Toda funcionalidade de IA deve respeitar:

1. saída consultiva por padrão;
2. decisão humana explícita antes de publicação;
3. nenhuma alteração automática de nota numérica;
4. trilha de auditoria da sugestão e da decisão;
5. opção de operar sem IA;
6. possibilidade de contestação e revisão.

## Padrões de código

O código PHP deve seguir os padrões Moodle.

```bash
python3 tools/validate.py
```

Quando o Moodle Plugin CI estiver configurado:

```bash
moodle-plugin-ci phplint
moodle-plugin-ci codechecker
moodle-plugin-ci phpunit
```

## Pull requests

Um PR deve conter:

- resumo da mudança;
- risco e mitigação;
- testes executados;
- testes ainda pendentes em Moodle real;
- evidências de que não há escrita automática de notas;
- plano de migração e rollback quando houver mudança de banco.
