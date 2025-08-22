import type { ApiResponse } from '@/types/types'

export interface SearchRequest {
  kb_id: string
  query: string
}

export interface CandidateChunk {
  id: string
  text: string
  source_id: string
  score: number
}

export interface SearchResponse {
  answer: null
  candidate_chunks: CandidateChunk[]
  prompt: string
}

export const searchService = {
  async search(request: SearchRequest): Promise<ApiResponse<SearchResponse>> {
    const response = await fetch('/api/search', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      },
      body: JSON.stringify(request),
    })

    if (!response.ok) {
      throw new Error(`Search failed: ${response.statusText}`)
    }

    return response.json()
  }
}