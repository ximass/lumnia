# SearchController Documentation

## Endpoint
`POST /api/search`

## Description
The SearchController provides hybrid search functionality that combines semantic search using embeddings with lexical search using PostgreSQL's full-text search capabilities. It returns candidate chunks without calling the final LLM, maintaining separation of concerns.

## Authentication
This endpoint requires authentication using Laravel Sanctum.

## Request Parameters

### Required
- `kb_id` (UUID): Knowledge base identifier
- `query` (string): Search query (3-1000 characters)

### Example Request
```json
{
    "kb_id": "123e4567-e89b-12d3-a456-426614174000",
    "query": "Como configurar autenticação no Laravel?"
}
```

## Response Format

### Success Response (200)
```json
{
    "status": "success",
    "data": {
        "answer": null,
        "candidate_chunks": [
            {
                "id": "chunk_123",
                "text": "Para configurar autenticação no Laravel...",
                "source_id": "source_456",
                "score": 0.85
            }
        ],
        "prompt": "Contexto relevante:\n\n[1] Para configurar autenticação no Laravel...\n\nPergunta: Como configurar autenticação no Laravel?\n\nResponda à pergunta baseando-se no contexto fornecido acima..."
    }
}
```

### Error Response (500)
```json
{
    "status": "error",
    "message": "Search failed: Failed to generate query embedding"
}
```

## Configuration

The search behavior can be configured via environment variables:

### Search Scoring
- `SEARCH_SEMANTIC_WEIGHT` (default: 0.7): Weight for semantic search (0.0-1.0)
- `SEARCH_LEXICAL_WEIGHT` (default: 0.3): Weight for lexical search (0.0-1.0)
- `SEARCH_MAX_CHUNKS` (default: 100): Maximum chunks to return
- `SEARCH_RERANK_TOP_K` (default: 10): Number of top chunks to rerank
- `SEARCH_ENABLE_RERANKING` (default: false): Enable cross-encoder reranking

### Embedding Service
- `EMBEDDING_PROVIDER`: 'local' or 'remote'
- `EMBEDDING_LOCAL_URL`: Local embedding service URL
- `EMBEDDING_MODEL`: Embedding model name

## Algorithm

1. **Query Embedding**: Generate embedding for the search query using EmbeddingClient
2. **Hybrid Search**: Execute SQL query that combines:
   - **Semantic Search**: Cosine similarity using pgvector
   - **Lexical Search**: BM25 scoring using ts_rank_cd
3. **Score Combination**: Weighted combination of semantic and lexical scores
4. **Optional Reranking**: Cross-encoder reranking of top-k results (if enabled)
5. **Response Building**: Format candidate chunks and build prompt

## SQL Query Details

The hybrid search uses a PostgreSQL CTE (Common Table Expression) query that:

1. **Semantic Search CTE**: 
   - Uses cosine similarity with pgvector: `(1 - (embedding <=> query_embedding))`
   - Orders by embedding distance
   - Limits to max_chunks

2. **Lexical Search CTE**:
   - Uses ts_rank_cd with normalization flag 32 (document length normalization)
   - Matches against tsvector using plainto_tsquery
   - Orders by BM25 score
   - Limits to max_chunks

3. **Combined Results CTE**:
   - FULL OUTER JOIN of semantic and lexical results
   - Weighted score combination: `(alpha * semantic_score + beta * lexical_score)`

4. **Final Selection**:
   - Filters results with combined_score > 0
   - Orders by combined score descending
   - Limits to max_chunks

## Dependencies

- `EmbeddingClient`: For generating query embeddings
- PostgreSQL with pgvector extension
- Full-text search columns (tsvector) on chunks table

## Error Handling

- Validates input parameters
- Handles embedding generation failures
- Logs errors with context information
- Returns appropriate HTTP status codes

## Future Enhancements

- Cross-encoder reranking implementation
- Query expansion
- Semantic caching
- Multi-modal search support
