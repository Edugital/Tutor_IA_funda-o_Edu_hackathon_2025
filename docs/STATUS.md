# Estado consolidado

**Versão:** `0.4.0-alpha.0`  
**Maturidade:** alpha  
**Política:** Human in Control  
**Fonte canônica:** este repositório  
**Piloto EBAC LMS:** https://alandantas.net/moodle_ebac

## Implementado (0.3.x → 0.4.0-alpha.0)

- estrutura oficial `assignfeedback`;
- configuração por tarefa + site (`default=0`, `allowaisuggestions=0`);
- rubrica textual e versão;
- feedback por grade com decisão humana (aceitar / editar / rejeitar);
- sugestão de IA privada (quando houver provedor);
- visualização pelo estudante do feedback publicado;
- Privacy API;
- backup e restauração;
- idiomas `en` + `pt_br`;
- CLI `diagnose.php` / `governance_report.php`;
- contrato `provider_interface` + registry (sem provedor de produção habilitado);
- `INSTALL.md` orientado a escolas;
- docs alinhados ao release (STATUS/CHANGELOG).

## Não implementado (próximos 0.4.x)

- provedor externo de produção (OpenAI/Azure) plugável e configurável por UI;
- fila assíncrona em carga real com métricas;
- processamento de anexos;
- editor de rubricas estruturadas na UI;
- painel de calibração completo;
- tutor conversacional fundamentado no curso.

## Bloqueios para produção plena

1. Sem provedor de produção → sugestões IA não geram valor automático (fluxo humano OK).
2. Alpha: não habilitar globalmente.
3. Comparar sempre ZIP tagado vs cópia instalada (`diagnose` + SHA).

## Critério para liberar 0.4.0 estável

- provedor plugável com chaves só via UI admin;
- fluxo professor/aluno testado em staging;
- INSTALL escolas validado por terceiro;
- zero escrita automática de nota.
