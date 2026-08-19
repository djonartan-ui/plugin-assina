# REVELA Contratos v3

Plugin WordPress profissional para gestão de contratos fotográficos.

## Características

- **Gutenberg Blocks** — Edição visual de pacotes e propostas
- **REST API** — Endpoints públicos e administrativos
- **React Client Form** — Formulário do cliente embutível via shortcode
- **Planos de Pagamento Flexíveis** — Admin define, cliente escolhe
- **Reset de Dados** — Um clique para limpar dados do cliente
- **Segurança** — Rate limiting, validação CPF/CNPJ, nonces
- **LGPD Compliant** — Aceite eletrônico com timestamp e IP

## Requisitos

- WordPress 6.3+
- PHP 8.1+
- Node.js 18+ (para build)

## Instalação

```bash
# 1. Instalar plugin
wp plugin install revela-contratos-v3.zip --activate

# 2. Build assets (para blocos Gutenberg)
cd wp-content/plugins/revela-contratos
npm install
npm run build

# 3. Flush rewrite rules
wp rewrite flush
```

## Estrutura

```
revela-contratos/
├── revela-contratos.php          # Plugin principal
├── package.json                  # Build config (wp-scripts)
├── src/
│   ├── Models/                   # CPTs: Package, Proposal
│   ├── Services/                 # Contract, Email, Settings, Security, Cleanup
│   ├── REST/                     # API endpoints (v3)
│   ├── Blocks/                   # Gutenberg blocks registration
│   ├── Admin/                    # Assets, SettingsPage, Notices
│   ├── Validators/               # Settings validation
│   ├── DTO/                      # Money, ProposalData
│   ├── blocks/
│   │   ├── package/              # Package block (JS + CSS + block.json)
│   │   └── proposal/             # Proposal block (JS + CSS + block.json)
│   ├── client-form/              # React client form app
│   └── templates/                # Contract template (PHP)
├── build/                        # Compiled assets (generated)
├── languages/                    # Translations
└── config/                       # Configuration files
```

## Uso

### 1. Configurações
Menu **REVELA → Configurações** para dados do estúdio, prazos, cláusulas, segurança.

### 2. Criar Pacotes
Bloco **"Pacote REVELA"**:
- Categoria, preço, horas, descrição
- **Planos de pagamento**: nome, % entrada, nº parcelas (0 = à vista)
- Preview automático do valor da parcela
- Reordenação de planos (↑↓)

### 3. Criar Propostas
Bloco **"Proposta REVELA"**:
- Seleciona pacote, nome do cliente, e-mail, data
- Escolhe extras, plano de pagamento pré-selecionado
- Clica em **"Criar Proposta e Gerar Link"**
- Copia o link ou envia via WhatsApp

### 4. Cliente Preenche
Acesse o link ou use shortcode `[revela_proposta token="XYZ"]`:
- Vê resumo da proposta com plano de pagamento
- Preenche dados pessoais e endereço do evento
- **Opcional**: altera valor de entrada (recalcula parcelas em tempo real)
- Aceita valores e cláusulas → Envia

### 5. Reset de Dados
Na listagem de propostas ou via API:
- POST `/wp-json/revela/v3/propostas/{id}` com action reset
- Limpa dados do cliente e volta status para "pendente"

## Endpoints REST

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/revela/v3/proposta/{token}` | Dados da proposta para formulário |
| POST | `/revela/v3/proposta/{token}` | Envio dos dados do cliente |
| GET | `/revela/v3/proposta/{token}/contrato` | HTML do contrato (print/PDF) |
| GET | `/revela/v3/packages` | Lista pacotes (admin) |
| POST | `/revela/v3/propostas` | Cria proposta (admin) |
| GET | `/revela/v3/propostas/{id}` | Detalhes proposta (admin) |
| DELETE | `/revela/v3/propostas/{id}` | Exclui proposta (admin) |
| POST | `/revela/v3/propostas/{id}` | Reset dados cliente (admin) |
| GET/POST | `/revela/v3/settings` | Configurações (admin) |
| GET | `/revela/v3/health` | Health check |

## Segurança

- Rate limiting: 5 requisições/minuto por IP no envio de propostas
- Validação CPF/CNPJ com algoritmo oficial
- Nonces em todas as operações
- Sanitização rigorosa de inputs
- Diretório de uploads protegido com .htaccess

## Build

```bash
npm run build    # Produção
npm run start    # Desenvolvimento (watch)
npm run format   # Formatação de código
npm run lint:js  # Lint JavaScript
```

## LGPD

- Aceite eletrônico registrado com timestamp e IP
- Dados usados apenas para o contrato
- Cláusula LGPD incluída por padrão
- Opção de reset/remoção de dados do cliente