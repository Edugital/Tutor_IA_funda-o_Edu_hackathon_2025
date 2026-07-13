# Checklist de release

## Código

- [ ] `version.php` atualizado.
- [ ] `CHANGELOG.md` atualizado.
- [ ] `db/install.xml` consistente com `db/upgrade.php`.
- [ ] nenhuma escrita automática em `assign_grades`.
- [ ] nenhuma credencial, host privado ou dado pessoal.
- [ ] PHP lint aprovado.
- [ ] XML bem-formado.
- [ ] padrões Moodle verificados.

## Testes

- [ ] testes unitários aprovados.
- [ ] instalação limpa aprovada.
- [ ] atualização de versão anterior aprovada.
- [ ] migração legada aprovada em banco sanitizado.
- [ ] feedback manual exibido ao estudante.
- [ ] sugestão permanece privada antes da decisão humana.
- [ ] aceitação explícita registrada.
- [ ] edição e substituição registradas.
- [ ] nenhuma alteração automática de nota.
- [ ] backup e restauração aprovados.
- [ ] exportação e exclusão de privacidade aprovadas.
- [ ] falha de integração externa não bloqueia avaliação manual.

## Pacote

- [ ] ZIP contém somente a pasta raiz `aitutoria/`.
- [ ] arquivos de desenvolvimento desnecessários não entram no ZIP.
- [ ] instalação pelo painel Moodle aprovada.
- [ ] checksum do pacote registrado.

## Staging

- [ ] backup realizado.
- [ ] janela e responsável definidos.
- [ ] smoke test documentado.
- [ ] rollback executável.
- [ ] professor responsável aprovou o fluxo.
- [ ] privacidade e transparência revisadas.

## Produção

- [ ] release assinada e publicada.
- [ ] plugin desativado por padrão.
- [ ] piloto limitado a cursos autorizados.
- [ ] monitoramento ativo.
- [ ] critérios de interrupção definidos.
- [ ] canal de suporte e incidente disponível.
