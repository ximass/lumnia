export interface User {
  id: number;
  name: string;
  email: string;
  admin: boolean;
}

export interface Group {
  id: number;
  name: string;
  user_ids: number[];
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

  chats: Chat[];
}



