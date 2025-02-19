import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import ChatView from '@/views/ChatView.vue';
import Login from '@/views/Login.vue';
import Register from '@/views/Register.vue';
import GroupView from '@/views/GroupView.vue';
import UserView from '@/views/UserView.vue';
import KnowledgeBaseView from '@/views/KnowledgeBaseView.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'Home',
    component: ChatView,
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
    meta: { requiresAuth: true },
  },
  {
    path: '/groups',
    name: 'GroupView',
    component: GroupView,
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'UserView',
    component: UserView,
    meta: { requiresAuth: true },
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
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authToken = localStorage.getItem('authToken');
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest);

  if (requiresAuth && !authToken) {
    next({ name: 'Login' });
  } else if (requiresGuest && authToken) {
    next({ name: 'Home' });
  } else {
    next();
  }
});

export default router;