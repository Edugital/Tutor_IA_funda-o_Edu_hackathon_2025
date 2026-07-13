# Instalação e atualização

## Requisitos

- Moodle 4.4 ou superior;
- PHP compatível com a versão do Moodle;
- acesso administrativo ao Moodle;
- backup do banco e do diretório Moodle antes de atualizar uma instalação existente;
- cron do Moodle funcional.

## Instalação por ZIP

1. Gere o pacote:

```bash
bash tools/package.sh
```

2. Confirme que o ZIP contém a pasta raiz:

```text
aitutoria/
```

3. No Moodle, acesse:

```text
Administração do site → Plugins → Instalar plugins
```

4. Envie o ZIP e conclua a atualização do banco.
5. Acesse as configurações de plugins de feedback de tarefa.
6. Mantenha **Ativar por padrão** e **Permitir sugestões de IA** desativados durante o primeiro teste.

## Instalação manual

Copie o conteúdo do repositório para:

```text
<Moodle>/mod/assign/feedback/aitutoria
```

Depois execute:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

## Teste mínimo em staging

1. Crie uma tarefa de teste.
2. Ative o feedback com Tutoria IA apenas nessa tarefa.
3. Cadastre uma rubrica e uma versão.
4. Envie uma resposta usando um estudante fictício.
5. Registre feedback manual como professor.
6. Confirme que o estudante visualiza o feedback.
7. Confirme que nenhuma nota numérica foi alterada pelo plugin.
8. Faça backup do curso.
9. Restaure o curso em outra categoria ou instância de staging.
10. Confirme a preservação do feedback.

## Atualização de versão legada

A migração conhece apenas a tabela documentada:

```text
assignfeedback_aitut_cfg
```

O processo:

- cria a tabela canônica `assignfeedback_aitutoria` se necessário;
- renomeia a tabela candidata `assignfeedback_aitut_fb`, quando encontrada;
- migra configurações conhecidas para `assign_plugin_config`;
- força o modo `human_review`;
- registra que o autograde legado esteve ativo sem reativá-lo;
- não apaga a tabela legada documentada.

Antes de atualizar produção:

1. exporte e sanitize o esquema legado;
2. execute a atualização em cópia do banco;
3. compare quantidade de atividades e configurações;
4. valide feedback e notas;
5. teste rollback;
6. só então autorize staging e produção.

## Rollback

O rollback deve usar:

- snapshot ou backup do banco anterior à atualização;
- cópia da versão anterior do diretório do plugin;
- registro da versão e horário;
- janela de manutenção;
- critérios objetivos de interrupção.

Não use `DELETE` manual ou remoção de diretório como processo padrão de rollback.
