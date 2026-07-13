# Backlog tecnológico e pedagógico

## P0 — validar a baseline

- [ ] Instalar em Moodle 4.4 limpo.
- [ ] Executar atualização sobre banco legado sanitizado.
- [ ] Testar backup e restauração de curso.
- [ ] Adicionar testes de banco para persistência e Privacy API.
- [ ] Adicionar cenários Behat para professor e estudante.
- [ ] Testar rollback.
- [ ] Comparar com a cópia efetivamente instalada em produção.

## P1 — motor de avaliação assistida

- [ ] Criar contrato de provedor independente de fornecedor.
- [ ] Implementar fila assíncrona e idempotente.
- [ ] Criar snapshot imutável de submissão, rubrica e política.
- [ ] Estruturar resultados por critério.
- [ ] Registrar evidências textuais e localização.
- [ ] Registrar incerteza e necessidade de revisão.
- [ ] Manter cálculo de nota determinístico e separado da IA.
- [ ] Implementar estados: sombra, assistido e liberação controlada.
- [ ] Implementar aceitar, editar, rejeitar e escalar.
- [ ] Criar trilha de auditoria.

## P1 — rubricas e competências

- [ ] Editor estruturado de rubricas.
- [ ] Critérios, níveis, indicadores e evidências esperadas.
- [ ] Pesos e regras determinísticas.
- [ ] Versionamento e vigência.
- [ ] Bloqueio de edição após uso em avaliação.
- [ ] Importação e exportação JSON/CSV.
- [ ] Registro institucional de competências e resultados de aprendizagem.
- [ ] Mapeamento atividade → critério → competência.

## P2 — governança institucional

- [ ] Políticas por site, categoria, curso e atividade.
- [ ] Provedores e modelos por finalidade.
- [ ] Quotas e limites de custo.
- [ ] Retenção e exclusão configuráveis.
- [ ] Painel de saúde e erros.
- [ ] Métricas de divergência IA-professor.
- [ ] Calibração com conjunto de referência.
- [ ] Monitoramento de vieses e baixa confiança.
- [ ] Fluxo de contestação do estudante.

## P2 — tutor fundamentado no curso

- [ ] Indexar apenas conteúdos autorizados.
- [ ] Respostas com fontes e incerteza.
- [ ] Perguntas diagnósticas.
- [ ] Dicas progressivas.
- [ ] Revisão de rascunho sem produzir a entrega final.
- [ ] Plano de estudo por competência.
- [ ] Proteção contra vazamento de gabaritos.
- [ ] Escalonamento ao professor.
- [ ] Controle de retenção das conversas.

## P3 — distribuição para escolas públicas

- [ ] Assistente de instalação.
- [ ] Diagnóstico de compatibilidade.
- [ ] Curso demonstrativo.
- [ ] Pacotes de rubricas.
- [ ] Formação de professores e administradores.
- [ ] Modelo com API externa.
- [ ] Gateway compartilhado.
- [ ] Opção de modelo local.
- [ ] Avaliação de acessibilidade.
- [ ] Auditoria independente de segurança.
- [ ] Preparação para o diretório oficial de plugins Moodle.

## Não negociável

- nenhuma nota numérica alterada automaticamente;
- nenhuma publicação sem decisão humana;
- nenhum segredo ou dado real no Git;
- nenhum provedor obrigatório;
- nenhuma falha externa bloqueando avaliação manual;
- toda sugestão vinculada a modelo, prompt, rubrica e evidências;
- toda decisão relevante auditável e contestável.
