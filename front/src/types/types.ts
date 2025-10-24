export interface User {
  id: number
  name: string
  email: string
  password: string
  admin: boolean
  avatar?: string
  user_persona?: UserPersona
  enabled: boolean
}

export interface UserFormData {
  id?: number
  name: string
  email: string
  password?: string
  groups_ids: number[]
  admin: boolean
  avatar?: string
  enabled: boolean
}

export interface Profile {
  id?: number
  name: string
  avatar?: string
}

export interface Group {
  id: number
  name: string
}

export interface GroupFormData {
  id?: number
  name: string
  knowledge_base_ids: number[]
}

export interface GroupWithUsers extends Group {
  users: User[]
}

export interface GroupWithPermissions extends Group {
  permissions?: Permission[]
}

export interface UserWithGroups extends User {
  groups: GroupWithPermissions[]
}

export interface Chat {
  id: number
  name: string
  user_id: number
  kb_id: string
  persona_id?: number
  knowledge_base?: KnowledgeBase | null
}

export interface Message {
  id: number
  chat_id: number
  user_id: number
  text: string
  answer: string
}

export interface KnowledgeBase {
  id: string
  name: string
  description?: string
  owner_id: number
  created_at?: string
  updated_at?: string
  sources?: Source[]
  chunks?: Chunk[]
}

export interface KnowledgeBaseFormData {
  id?: string
  name: string
  description?: string
  owner_id: number
}

export interface GroupKnowledgeBase {
  id: number
  group_id: number
  knowledge_base_id: string
  created_at?: string
  updated_at?: string
}

export interface GroupWithKnowledgeBases extends Group {
  knowledge_bases: KnowledgeBase[]
}

export interface KnowledgeBaseWithGroups extends KnowledgeBase {
  groups: Group[]
}

export interface GroupWithKnowledgeBasesAndPivot extends Group {
  knowledge_bases: (KnowledgeBase & { pivot: GroupKnowledgeBase })[]
}

export interface KnowledgeBaseWithGroupsAndPivot extends KnowledgeBase {
  groups: (Group & { pivot: GroupKnowledgeBase })[]
}

// Source interfaces
export interface Source {
  id: string
  kb_id: string
  source_type: string
  source_identifier: string
  content_hash: string
  status: string
  metadata?: Record<string, any>
  created_at?: string
  updated_at?: string
}

export interface SourceFormData {
  kb_id: string
  source_type: string
  source_identifier: string
  content_hash: string
  status: string
  metadata?: Record<string, any>
}

// Chunk interfaces
export interface Chunk {
  id: string
  source_id: string
  kb_id: string
  chunk_index: number
  text: string
  metadata?: Record<string, any>
  created_at?: string
  updated_at?: string
}

export interface ChunkFormData {
  id: string
  source_id: string
  kb_id: string
  chunk_index: number
  text: string
  metadata?: Record<string, any>
}

// Chat related interfaces
export interface ChatWithLastMessage extends Chat {
  lastMessage: string
}

export interface MessageWithUser extends Message {
  user: User
  updated_at: string
  rating?: MessageRating
}

export interface InformationSource {
  id: number
  content: string
}

// API Response types
export interface ApiResponse<T = any> {
  status: 'success' | 'error' | 'partial_success'
  message: string
  data?: T
}

export interface SendMessageResponse {
  status: 'success' | 'error' | 'partial_success'
  message: string
  answer?: {
    text: string
    updated_at: string
  }
  errors?: Record<string, string[]>
}

// Persona interfaces
export interface Persona {
  id: number
  name: string
  description: string
  instructions: string
  response_format: string | null
  keywords: string[] | null
  creativity: number
  active: boolean
  created_at?: string
  updated_at?: string
}

export interface PersonaFormData {
  id?: number
  name: string
  description: string
  instructions: string
  response_format?: string
  keywords?: string[]
  creativity: number
  active?: boolean
}

export interface ActivePersona {
  id: number
  name: string
  description: string
}

export interface UserPersona {
  id: number
  user_id: number
  instructions: string
  response_format?: string
  creativity: number
  active: boolean
  created_at?: string
  updated_at?: string
}

export interface UserPersonaFormData {
  instructions: string
  response_format?: string
  creativity: number
  active?: boolean
}

export interface Permission {
  id: number
  name: string
  description?: string
}

export interface PermissionFormData {
  id?: number
  name: string
  description?: string
}

export interface MenuItem {
  title: string
  route: string
  admin?: boolean
  class?: string
  permission?: string
  icon?: string
}

export interface ChatContextInfo {
  context_enabled: boolean
  context_limit: number
  max_tokens: number
  total_messages: number
  context_messages: number
}

export interface ClearContextResponse {
  status: 'success' | 'error'
  message: string
  data?: {
    messages_deleted: number
  }
}

export interface MessageRating {
  id: number
  message_id: number
  user_id: number
  rating: 'like' | 'dislike'
  created_at?: string
  updated_at?: string
  message?: Message
  user?: User
}

export interface MessageRatingFormData {
  rating: 'like' | 'dislike'
}

export type ErrorLogLevel = 'error' | 'warning' | 'critical' | 'info';

export interface ErrorLog {
  id: number;
  level: ErrorLogLevel;
  message: string;
  exception: string | null;
  file: string | null;
  line: number | null;
  trace: string | null;
  url: string | null;
  method: string | null;
  request_data: any | null;
  ip: string | null;
  user_id: number | null;
  user?: User;
  created_at: string;
  updated_at: string;
}

export interface ErrorLogFilters {
  search: string;
  level: ErrorLogLevel | null;
  date_from: string;
  date_to: string;
  user_id: number | null;
}

export interface ErrorLogStatistics {
  total: number;
  by_level: {
    error?: number;
    warning?: number;
    critical?: number;
    info?: number;
  };
  last_24h: number;
  last_7days: number;
  last_30days: number;
}