<template>
  <div class="login-container">
    <!-- Background com pontos conectados -->
    <AnimatedBackground />
    
    <!-- Conteúdo principal -->
    <v-container class="login-content d-flex justify-center" fluid style="padding-top: 8vh;">
      <v-row justify="center" style="width: 100%">
        <v-col cols="12" sm="8" md="6" lg="4" xl="3">
          <!-- Logo e Texto -->
          <LumniaLogo :logo-src="LogoLumnia" />
          
          <!-- Card de Login -->
          <LoginForm 
            :loading="loading" 
            @login="loginHandler"
          />
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script lang="ts">
  import { defineComponent, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuth } from '@/composables/auth'
  import { useToast } from '@/composables/useToast'
  import AnimatedBackground from '@/components/AnimatedBackground.vue'
  import LumniaLogo from '@/components/LumniaLogo.vue'
  import LoginForm from '@/components/LoginForm.vue'
  import LogoLumnia from '@/assets/lumnia.png'

  interface LoginCredentials {
    email: string
    password: string
  }

  export default defineComponent({
    name: 'Login',
    components: {
      AnimatedBackground,
      LumniaLogo,
      LoginForm
    },
    setup() {
      const router = useRouter()
      const loading = ref(false)

      const { login } = useAuth()
      const { showToast } = useToast()
      const { user } = useAuth()

      const loginHandler = async (credentials: LoginCredentials) => {
        try {
          loading.value = true
          await login(credentials.email, credentials.password)
          if (user.value && user.value.id) {
            localStorage.setItem('user', JSON.stringify(user.value))
          }
          router.push('/')
        } catch (err) {
          loading.value = false
          showToast('Credenciais inválidas')
        }
      }

      loading.value = false

      return { 
        loading, 
        loginHandler,
        LogoLumnia
      }
    },
  })
</script>

<style scoped>
  .login-container {
    position: relative;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%);
  }

  .login-content {
    position: relative;
    z-index: 2;
    min-height: 100vh;
  }
</style>
