import { createRouter, createWebHistory } from 'vue-router';
import Home from '@/views/Home.vue';
import ChatView from '@/views/ChatView.vue';

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/chat', name: 'Chat', component: ChatView }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;