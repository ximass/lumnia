import type { MenuItem } from '@/types/types'

export const menuItems: MenuItem[] = [
  {
    title: 'Tela inicial',
    route: '/home',
    admin: false,
    icon: 'mdi-home',
  },
  {
    title: 'Chats',
    route: '/chats',
    permission: 'use_chats',
    admin: false,
    icon: 'mdi-message-text-outline',
  },
  {
    title: 'Bases de conhecimento',
    route: '/knowledge-bases',
    permission: 'manage_knowledge_bases',
    admin: false,
    icon: 'mdi-book-open-variant-outline',
  },
  {
    title: 'Usuários',
    route: '/users',
    admin: true,
    permission: 'manage_users',
    icon: 'mdi-account',
  },
  {
    title: 'Grupos',
    route: '/groups',
    admin: true,
    permission: 'manage_groups',
    icon: 'mdi-account-group',
  },
  {
    title: 'Permissões',
    route: '/permissions',
    admin: true,
    permission: 'manage_permissions',
    icon: 'mdi-shield-key',
  },
  {
    title: 'Personas',
    route: '/personas',
    admin: true,
    permission: 'manage_personas',
    icon: 'mdi-account-tie',
  },
  {
    title: 'Logs de erros',
    route: '/error-logs',
    admin: true,
    permission: 'view_error_logs',
    icon: 'mdi-alert-circle-outline',
  },
]
