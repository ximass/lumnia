import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import Home from '@/views/Home.vue'
import ChatView from '@/views/ChatView.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import GroupView from '@/views/GroupView.vue'
import UserView from '@/views/UserView.vue'
import PersonaView from '@/views/PersonaView.vue'
import KnowledgeBaseView from '@/views/KnowledgeBaseView.vue'
import KnowledgeBaseForm from '@/views/KnowledgeBaseForm.vue'
import PermissionsView from '@/views/Permissions.vue'
import { useAuth } from '@/composables/auth'
import ErrorLogsView from '../views/ErrorLogsView.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { requiresAuth: true },
  },
  {
    path: '/chats',
    name: 'Chat',
    component: ChatView,
    meta: { requiresAuth: true },
  },
  {
    path: '/knowledge-bases',
    name: 'KnowledgeBaseView',
    component: KnowledgeBaseView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/knowledge-bases/create',
    name: 'KnowledgeBaseCreate',
    component: KnowledgeBaseForm,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/knowledge-bases/:id/edit',
    name: 'KnowledgeBaseEdit',
    component: KnowledgeBaseForm,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/groups',
    name: 'GroupView',
    component: GroupView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/users',
    name: 'UserView',
    component: UserView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/personas',
    name: 'PersonaView',
    component: PersonaView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/permissions',
    name: 'PermissionsView',
    component: PermissionsView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/error-logs',
    name: 'ErrorLogsView',
    component: ErrorLogsView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresGuest: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const { fetchUser, user, isAuthenticated, isUserFetched } = useAuth();
  
  if (!isUserFetched.value) {
    await fetchUser();
  }

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)

  if (requiresAuth && !isAuthenticated.value) {
    return next({ name: 'Login' })
  }

  if (requiresGuest && isAuthenticated.value) {
    return next({ name: 'Home' })
  }

  if (requiresAdmin && (!user.value || !user.value.admin)) {
    // Allow access if user belongs to a group that has 'manage_permissions'
    const hasGroupPermission = user.value && user.value.groups && user.value.groups.some(g => (g.permissions || []).some((p: any) => p.name === 'manage_permissions'))
    if (!user.value || (!user.value.admin && !hasGroupPermission)) {
      return next({ name: 'Home' })
    }
  }

  next()
})

export default router
