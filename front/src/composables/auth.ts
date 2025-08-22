import { ref } from 'vue';
import axios from '../plugins/axios';
import type { User } from '../types/types';

const isAuthenticated = ref(false);
const user = ref<User | null>(null);
const isUserFetched = ref(false);

const fetchUser = async () => {
  if (!localStorage.getItem('authToken')) {
    user.value = null;
    isAuthenticated.value = false;
    isUserFetched.value = true;
    return;
  }

  try {
    const response = await axios.get('/api/user');

    user.value = response.data;
    isAuthenticated.value = true;
    isUserFetched.value = true;
  } catch (error) {
    user.value = null;
    isAuthenticated.value = false;
    isUserFetched.value = true;
    localStorage.removeItem('authToken');
  }
};

const login = async (email: string, password: string) => {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
  
  const response = await axios.post(
    '/api/login',
    { email, password },
    { withCredentials: true }
  );

  const token = response.data.token;

  localStorage.setItem('authToken', token);

  await fetchUser();
};

const logout = async () => {
  try {
    await axios.post('/api/logout', {}, { withCredentials: true });
  } catch (error) {
    console.error('Erro ao fazer logout:', error);
  } finally {
    localStorage.removeItem('authToken');
    user.value = null;
    isAuthenticated.value = false;
    isUserFetched.value = false;
    window.location.href = '/';
  }
};

export function useAuth() {
  return {
    isAuthenticated,
    user,
    isUserFetched,
    login,
    logout,
    fetchUser,
  };
}
