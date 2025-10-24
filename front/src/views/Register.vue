<template>
  <div class="register-container">
    <!-- Background com pontos conectados -->
    <AnimatedBackground />
    
    <!-- Conteúdo principal -->
    <v-container class="register-content d-flex justify-center" fluid style="padding-top: 8vh;">
      <v-row justify="center" style="width: 100%">
        <v-col cols="12" sm="8" md="6" lg="4" xl="3">
          <!-- Logo e Texto -->
          <LumniaLogo :logo-src="logoLumnia" />
          
          <!-- Formulário de Registro -->
          <RegisterForm 
            :loading="loading"
            @register="handleRegister"
          />
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script lang="ts">
  import { defineComponent, ref } from 'vue'
  import axios from 'axios'
  import { useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import AnimatedBackground from '@/components/AnimatedBackground.vue'
  import LumniaLogo from '@/components/LumniaLogo.vue'
  import RegisterForm from '@/components/RegisterForm.vue'
  import logoLumnia from '@/assets/lumnia.png'

  export default defineComponent({
    name: 'Register',
    components: {
      AnimatedBackground,
      LumniaLogo,
      RegisterForm
    },
    setup() {
      const router = useRouter()
      const { showToast } = useToast()
      const loading = ref(false)

      const handleRegister = async (formData: {
        name: string
        email: string
        password: string
        password_confirmation: string
      }) => {
        try {
          loading.value = true
          await axios.get('/sanctum/csrf-cookie')
          await axios.post('/api/register', formData)
          
          showToast('Registro realizado com sucesso!', 'success')
          router.push('/login')
        } catch (err: any) {
          showToast('Erro ao se registrar: ' + (err.response?.data?.message || 'Erro desconhecido'), 'error')
        } finally {
          loading.value = false
        }
      }

      return { loading, handleRegister, logoLumnia }
    },
  })
</script>

<style scoped>
  .register-container {
    position: relative;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%);
  }

  .register-content {
    position: relative;
    z-index: 2;
    min-height: 100vh;
  }
</style>
