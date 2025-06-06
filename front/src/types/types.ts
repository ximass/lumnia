export interface User {
  id: number;
  name: string;
  email: string;
  admin: boolean;
}

export interface UserFormData {
  id?: number;
  name: string;
  email: string;
  admin: boolean;
}

export interface Group {
  id: number;
  name: string;
}

export interface GroupFormData {
  id?: number;
  name: string;
  user_ids: number[];
  knowledge_base_ids: number[];
}

export interface GroupWithUsers extends Group {
  users: User[];
}

export interface UserWithGroups extends User {
  groups: Group[];
}

export interface Chat {
  id: number;
  name: string;
  user_id: number;
  knowledge_base_id: number;
}

export interface Message {
  id: number;
  chat_id: number;
  user_id: number;
  text: string;
  answer: string;
}

export interface KnowledgeBase {
  id: number;
  title: string;
  content: string;
  size: number;
  digest: string;
  details: string;
  modified_at: string;
}

export interface KnowledgeBaseFormData {
  id?: number;
  title: string;
  content: string;
}

export interface GroupKnowledgeBase {
  id: number;
  group_id: number;
  knowledge_base_id: number;
  created_at?: string;
  updated_at?: string;
}

export interface GroupWithKnowledgeBases extends Group {
  knowledge_bases: KnowledgeBase[];
}

export interface KnowledgeBaseWithGroups extends KnowledgeBase {
  groups: Group[];
}

export interface GroupWithKnowledgeBasesAndPivot extends Group {
  knowledge_bases: (KnowledgeBase & { pivot: GroupKnowledgeBase })[];
}

export interface KnowledgeBaseWithGroupsAndPivot extends KnowledgeBase {
  groups: (Group & { pivot: GroupKnowledgeBase })[];
}

// Chat related interfaces
export interface ChatWithLastMessage extends Chat {
  lastMessage: string;
}

export interface MessageWithUser extends Message {
  user: User;
  updated_at: string;
}

export interface InformationSource {
  id: number;
  content: string;
}
