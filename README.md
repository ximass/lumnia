# Lumnia 🌌

Lumnia é uma plataforma inteligente de gestão de conhecimento e RAG projetada para transformar documentos estáticos em bases de dados acionáveis e interativas.

Com foco em flexibilidade, o Lumnia permite a integração de bases de conhecimento com modelos de LLMs, tanto locais quanto via API's.

## 🚀 Funcionalidades

- **Gestão de knowledge bases (KB)**: Organize múltiplas bases de conhecimento de forma independente.
- **Ingestão multi-formato**: Extração profunda de texto de PDFs, DOCX, XLSX, CSV, JSON e mais.
- **RAG híbrido avançado**: Combinação de busca vetorial (semântica) com busca textual (Lexical).
- **Interação conversacional**: Chat interativo alimentado pelo contexto da sua própria base de dados.
- **Flexibilidade de modelos**: Utilize modelos de ponta como Google Gemini ou modelos locais via LM Studio/Ollama.
- **Painel administrativo**: Monitoramento de jobs de processamento e status das fontes de dados.

## 🏗️ Arquitetura e tecnologias

O projeto é estruturado em uma arquitetura moderna e escalável:

- **Backend**: [Laravel 11](https://laravel.com/) (PHP 8.2+) como API robusta e orquestrador de jobs.
- **Frontend**: [Vue.js 3](https://vuejs.org/) (Vite, TypeScript, TailwindCSS) para uma interface reativa e premium.
- **Banco de dados**: [PostgreSQL](https://www.postgresql.org/) com a extensão **pgvector** para armazenamento e busca de embeddings.
- **Cache & queue**: [Redis](https://redis.io/) para gestão de processamento em segundo plano (Queues).

---

## 🛠️ Pipeline de processamento de dados

A extração e tratamento de informações é o coração do Lumnia. O pipeline segue as seguintes etapas:

### 1. Extração de informação
Utilizamos drivers específicos para garantir a integridade do conteúdo:
- **PDF**: `Smalot PDFParser` para extração estruturada.
- **Office (Word/Excel)**: `PHPWord` e `Maatwebsite/Excel`.
- **Structured Data**: `League\Csv` e parsing customizado para JSON/JSONL.

### 2. Tokenização e chunking
O sistema implementa o `Chunker` inteligente:
- **Estratégia**: Chunking baseado em tokens com **janela de sobreposição (overlap)** para manter a continuidade do contexto entre blocos.
- **Tokenização**: Estimativa precisa de tokens para otimizar o limite de contexto dos modelos.
- **Suporte a JSONL**: Chunking granular por linha para datasets estruturados.

### 3. Vetorização (Embeddings)
Integramos com múltiplos provedores através do `EmbeddingClient`:
- **Local**: Suporte nativo para **LM Studio** e **Ollama** via API local.
- **SaaS**: Integração otimizada com **Google Gemini** (Vertex AI/AI Studio).

---

## 🔍 Motor de RAG (pgvector & hybrid search)

Diferente de implementações RAG simples, o Lumnia utiliza um motor de busca híbrido de alta performance implementado diretamente no SQL:

- **Busca semântica**: Utiliza a extensão `pgvector` com comparação de cosseno (`<=>`) para encontrar relevância conceitual.
- **Busca lexical**: Implementada via `tsvector` e `tsquery` nativos do PostgreSQL para garantir que termos técnicos específicos sejam encontrados.
- **Scoring & Reranking**: 
    - Os resultados são combinados via pesos configuráveis (Semantic vs Lexical).
    - Suporte a **Reranking Service** para reordenar os resultados top-K antes de enviá-los ao LLM, diminuindo ruídos.
- **Injeção de Contexto**: Prompt engineering dinâmico que isola o conhecimento da KB, impedindo alucinações.

---

## 🤖 Integrações com LLM

O Lumnia permite transitar entre infraestrutura local e nuvem sem troca de código:

- **API's (Google Gemini, OpenAI, Claude)**: Ideal para alta velocidade e modelos de maior raciocínio.
- **Local (Privacy-First)**:
    - **LM Studio**: Interface simples para rodar qualquer modelo GGUF localmente.
    - **Ollama**: Orquestração simplificada de modelos em containers.

---

## 🛠️ Instalação Rápida

### Requisitos
- Docker & Docker Compose
- PHP 8.2+ e Composer (Local)
- Node.js & NPM

### Setup API
1. `cd api`
2. `composer install`
3. `php artisan migrate`

### Setup Frontend
1. `cd front`
2. `npm install`
3. `npm run dev`

---

## ⚖️ Licença
Este projeto é distribuído sob a licença MIT. Consulte o arquivo `LICENSE` para mais detalhes.
