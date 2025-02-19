import { ref } from 'vue';

const isAuthenticated = ref(!!localStorage.getItem('authToken'));
const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));

const setAuth = (token: string, newUser: any) => {
  localStorage.setItem('authToken', token);
  localStorage.setItem('user', JSON.stringify(newUser));

  user.value = newUser;
  isAuthenticated.value = true;
};

const clearAuth = () => {
  localStorage.removeItem('authToken');
  localStorage.removeItem('user');

  user.value = {};
  
  isAuthenticated.value = false;
};

export function useAuth() {
  return {
    isAuthenticated,
    user,
    setAuth,
    clearAuth,
  };
}