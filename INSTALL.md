# INSTALL — Tutoria IA para escolas

**Plugin:** `assignfeedback_aitutoria`  
**Release alvo:** `0.4.0-alpha.0`  
**Requisito:** Moodle **4.4+** (testado em 4.5 LTS) · PHP 8.1+ (piloto EBAC: PHP 8.2)

## O que este plugin faz

Feedback de tarefa com **revisão humana obrigatória**. A IA, quando houver provedor configurado, só gera **sugestão privada**. Nenhuma nota numérica é alterada automaticamente.

## O que NÃO fazer

- Não ativar “por padrão” no site no primeiro dia.
- Não colocar chaves de API em repositório Git.
- Não publicar ZIP de `main`/HEAD sem tag de release.

## Instalação (ZIP)

1. Baixe o ZIP da **release tag** (não do branch solto).
2. Confirme a pasta raiz: `aitutoria/`.
3. Moodle → **Administração do site → Plugins → Instalar plugins** → enviar ZIP.
4. Conclua a atualização do banco.
5. Em **Plugins → Atividades → Tarefa → Plugins de feedback → Tutoria IA**:
   - **Ativar por padrão** = Não
   - **Permitir sugestões de IA** = Não (até haver política e provedor)
6. Em uma tarefa piloto, ative o feedback **Tutoria IA** só naquela atividade.
7. Diagnóstico:

```bash
php mod/assign/feedback/aitutoria/cli/diagnose.php --json
```

Esperado: `"status":"ok"` e `"critical":[]`.

## Instalação (manual / SSH)

```bash
cp -a aitutoria <MOODLE>/mod/assign/feedback/aitutoria
chown -R <webuser>:<webuser> <MOODLE>/mod/assign/feedback/aitutoria
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
php mod/assign/feedback/aitutoria/cli/diagnose.php --json
```

## Checklist de aceite (escola)

1. Professor abre a tarefa e vê o painel Tutoria IA.
2. Aluno envia resposta; professor registra feedback manual.
3. Aluno vê o feedback publicado (não vê sugestão privada não aceita).
4. Nota numérica só muda por ação humana.
5. Backup/restauração do curso preserva feedback (staging).

## Suporte

- Blueprint LMS (contexto EBAC): https://github.com/Edugital/ebac-lms-blueprint  
- Código canônico deste plugin: este repositório  

Maturidade **alpha**: use em piloto controlado, não como correção automática em massa.
