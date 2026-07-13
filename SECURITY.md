# Política de segurança

## Versões suportadas

Enquanto o projeto estiver em maturidade alpha, apenas a versão mais recente da branch `main` receberá correções de segurança.

## Relato responsável

Não publique vulnerabilidades com dados reais de estudantes, credenciais, tokens, dumps ou instruções de exploração em issues públicas.

Ao relatar um problema, informe sem expor dados pessoais:

- versão do Moodle e do PHP;
- versão do plugin;
- cenário afetado;
- impacto esperado;
- passos mínimos para reprodução com dados fictícios;
- correção ou mitigação sugerida, quando disponível.

## Princípios obrigatórios

- nenhuma chave de API no repositório;
- nenhum dado real de estudante em testes;
- nenhuma nota numérica alterada por IA;
- nenhuma sugestão publicada sem decisão humana;
- permissões Moodle verificadas antes de ações sensíveis;
- integrações externas devem falhar sem bloquear avaliação manual;
- logs devem minimizar e redigir dados pessoais e segredos;
- migrações devem ser idempotentes e não destrutivas.

## Incidentes

Quando houver exposição de credencial ou dado pessoal:

1. revogue ou contenha imediatamente;
2. preserve evidências sem reproduzir o segredo;
3. avalie escopo e uso indevido;
4. corrija a causa;
5. limpe histórico e artefatos quando necessário;
6. documente a remediação;
7. execute validação independente antes de encerrar o incidente.
