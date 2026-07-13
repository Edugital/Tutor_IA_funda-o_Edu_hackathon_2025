# Estado consolidado

**Versão:** `0.2.1-recovery`  
**Maturidade:** alpha  
**Política:** Human in Control  
**Fonte canônica:** este repositório

## Implementado

- estrutura oficial `assignfeedback`;
- configuração por tarefa;
- rubrica textual e versão;
- feedback por grade;
- sugestão de IA privada;
- decisão humana explícita;
- visualização pelo estudante;
- integração textual com o livro de notas;
- Privacy API;
- backup e restauração;
- migração não destrutiva do legado conhecido;
- idiomas inglês e português do Brasil;
- teste da política de decisão;
- validador estático;
- pacote ZIP reproduzível;
- instalação automatizada em Moodle 4.4/MariaDB;
- instalação automatizada em Moodle 4.5/PostgreSQL;
- PHP lint, PHPUnit, Behat, PHPDoc e savepoints em CI.

## Não implementado

- provedor externo de IA;
- fila assíncrona;
- processamento de anexos;
- rubrica estruturada;
- avaliação por critério e evidência;
- painel de calibração;
- matriz institucional de competências;
- tutor conversacional;
- relatórios de custo, qualidade e risco.

## Bloqueios para produção

1. atualização sobre cópia sanitizada do banco legado real ainda não ensaiada;
2. backup e restauração precisam de cenário automatizado com verificação de dados;
3. Privacy API precisa de teste de banco específico;
4. código instalado na VM antiga ainda não foi comparado integralmente;
5. teste exploratório de professor e estudante ainda depende de staging;
6. versão alpha não deve ser habilitada globalmente.

## Critério para a próxima versão

A próxima versão só poderá aumentar a automação se:

- o fluxo manual estiver integralmente testado;
- a sugestão permanecer privada;
- o professor tiver revisão, edição, rejeição e escalonamento;
- toda saída estiver vinculada a proveniência;
- não houver escrita automática de nota;
- falha do provedor não interromper o Moodle.
