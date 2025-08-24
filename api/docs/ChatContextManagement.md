# Chat Context Management

Este documento descreve como o sistema de controle de sessões (memória do contexto) funciona no chat da aplicação Lumnia.

## Visão Geral

O sistema de contexto permite que o chat mantenha a memória das mensagens anteriores da conversa, proporcionando respostas mais coerentes e contextualmente relevantes.

## Funcionalidades Implementadas

### 1. Controle de Contexto Automático
- O sistema automaticamente inclui as últimas mensagens da conversa como contexto
- Funciona tanto com LLM Studio quanto com Ollama
- Respeita limites configuráveis de mensagens e tokens

### 2. Configurações Disponíveis

#### Variáveis de Ambiente
```bash
# Habilitar/desabilitar contexto
CHAT_CONTEXT_ENABLED=true

# Número máximo de mensagens anteriores a incluir
CHAT_CONTEXT_LIMIT=10

# Limite máximo de tokens para o contexto
CHAT_CONTEXT_MAX_TOKENS=4000

# Configurações específicas do LLM Studio
LLM_STUDIO_MODEL=gemma-3-1b-it-qat
LLM_STUDIO_MAX_TOKENS=1000

# Configurações específicas do Ollama
LLM_DEFAULT_MODEL=llama2
OLLAMA_CONTEXT_FORMAT=conversational
```

#### Arquivo de Configuração (`config/chat.php`)
```php
'context' => [
    'enabled' => env('CHAT_CONTEXT_ENABLED', true),
    'limit' => env('CHAT_CONTEXT_LIMIT', 10),
    'max_tokens' => env('CHAT_CONTEXT_MAX_TOKENS', 4000),
],
```

### 3. API Endpoints

#### Obter Informações do Contexto
```http
GET /api/chats/{chat}/context
```

**Resposta:**
```json
{
    "status": "success",
    "data": {
        "context_enabled": true,
        "context_limit": 10,
        "max_tokens": 4000,
        "total_messages": 25,
        "context_messages": 10
    }
}
```

#### Limpar Contexto do Chat
```http
DELETE /api/chats/{chat}/context
```

**Resposta:**
```json
{
    "status": "success",
    "message": "Contexto do chat limpo com sucesso!",
    "data": {
        "messages_deleted": 25
    }
}
```

## Como Funciona

### 1. Recuperação do Contexto
- O sistema busca as últimas N mensagens do chat (configurável)
- Ordena chronologicamente as mensagens
- Aplica otimização baseada no limite de tokens

### 2. Construção do Prompt

#### Para LLM Studio (OpenAI-compatible)
O sistema constrói um array de mensagens seguindo o formato:
```json
[
    {"role": "system", "content": "instruções da persona"},
    {"role": "user", "content": "mensagem antiga do usuário"},
    {"role": "assistant", "content": "resposta antiga da IA"},
    {"role": "user", "content": "nova mensagem do usuário"}
]
```

#### Para Ollama
O sistema constrói um prompt linear incluindo o histórico:
```
Instruções do sistema: [instruções da persona]

Histórico da conversa:

Usuário: mensagem antiga do usuário
Assistente: resposta antiga da IA

Usuário: nova mensagem do usuário
Assistente: 
```

### 3. Otimização de Tokens
- Calcula aproximadamente os tokens de cada mensagem (1 token ≈ 4 caracteres)
- Remove mensagens mais antigas se o limite de tokens for excedido
- Mantém sempre as mensagens mais recentes

## Benefícios

1. **Conversas Mais Naturais**: A IA mantém o contexto da conversa
2. **Respostas Coerentes**: Referências a mensagens anteriores funcionam corretamente
3. **Configurável**: Administradores podem ajustar os limites conforme necessário
4. **Eficiente**: Otimização automática evita exceder limites de tokens
5. **Compatível**: Funciona com múltiplos provedores de LLM

## Considerações de Performance

- Mensagens com contexto consomem mais tokens
- Respostas podem ser ligeiramente mais lentas devido ao contexto adicional
- O limite de contexto deve ser balanceado entre qualidade e performance

## Monitoramento

O sistema gera logs detalhados sobre:
- Número de mensagens recuperadas para contexto
- Otimizações aplicadas
- Tokens estimados utilizados
- Erros relacionados ao contexto

Verifique os logs em `storage/logs/laravel.log` para informações detalhadas.
