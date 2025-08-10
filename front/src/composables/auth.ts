import { ref } from 'vue'
import axios from 'axios'
import type { User } from '@/types/types'

const isAuthenticated = ref(false)
const user = ref<User | null>(null)

const fetchUser = async () => {
  try {
    const response = await axios.get('/api/user')

    user.value = response.data
    isAuthenticated.value = true
  } catch (error) {
    user.value = null
    isAuthenticated.value = false
  }
}

const login = async (email: string, password: string) => {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
  const response = await axios.post('/api/login', { email, password }, { withCredentials: true })

  const token = response.data.token

  localStorage.setItem('authToken', token)

  await fetchUser()
}

const logout = async () => {
  await axios.post('/api/logout', {}, { withCredentials: true })
  localStorage.removeItem('authToken')

  user.value = null
  isAuthenticated.value = false

  window.location.href = '/'
}

export function useAuth() {
  return {
    isAuthenticated,
    user,
    login,
    logout,
    fetchUser,
  }
}
