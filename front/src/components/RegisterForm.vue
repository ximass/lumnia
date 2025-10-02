<template>
  <v-card class="login-card pa-2" elevation="0">
    <v-card-title class="text-center pb-2">
      <span class="login-title">{{ title }}</span>
    </v-card-title>
    
    <v-card-text>
      <v-form @submit.prevent="handleRegister">
        <v-text-field 
          label="Nome" 
          v-model="name" 
          type="text" 
          required
          variant="outlined"
          color="cyan"
          class="mb-3"
        ></v-text-field>
        
        <v-text-field 
          label="Email" 
          v-model="email" 
          type="email" 
          required
          variant="outlined"
          color="cyan"
          class="mb-3"
        ></v-text-field>
        
        <v-text-field
          label="Senha"
          v-model="password"
          type="password"
          required
          variant="outlined"
          color="cyan"
          class="mb-3"
        ></v-text-field>
        
        <v-text-field
          label="Confirme a senha"
          v-model="passwordConfirmation"
          type="password"
          required
          variant="outlined"
          color="cyan"
          class="mb-4"
        ></v-text-field>
        
        <v-btn 
          :loading="loading" 
          type="submit" 
          color="cyan" 
          block 
          size="large"
          class="register-btn"
        >
          {{ buttonText }}
        </v-btn>
        
        <v-overlay :model-value="loading" class="align-center justify-center" persistent>
          <v-progress-circular indeterminate color="cyan" size="64" />
        </v-overlay>
      </v-form>
    </v-card-text>
    
    <v-card-actions class="justify-center pt-4">
      <router-link to="/login" class="login-link">
        {{ loginText }}
      </router-link>
    </v-card-actions>
  </v-card>
</template>

<script lang="ts">
  import { defineComponent, ref } from 'vue'

  export default defineComponent({
    name: 'RegisterForm',
    props: {
      title: {
        type: String,
        default: 'Registre-se'
      },
      buttonText: {
        type: String,
        default: 'CONCLUIR'
      },
      loginText: {
        type: String,
        default: 'Já possui uma conta? Faça login'
      },
      loading: {
        type: Boolean,
        default: false
      }
    },
    emits: ['register'],
    setup(props, { emit }) {
      const name = ref('')
      const email = ref('')
      const password = ref('')
      const passwordConfirmation = ref('')

      const handleRegister = () => {
        emit('register', {
          name: name.value,
          email: email.value,
          password: password.value,
          password_confirmation: passwordConfirmation.value
        })
      }

      return {
        name,
        email,
        password,
        passwordConfirmation,
        handleRegister
      }
    }
  })
</script>

<style scoped>
  .login-card {
    background: rgba(30, 41, 59, 0.05) !important;
    backdrop-filter: blur(1px);
    border: 1px solid rgba(0, 188, 212, 0.1);
    border-radius: 8px !important;
    box-shadow: 
      0 8px 32px rgba(0, 0, 0, 0.1),
      0 0 60px rgba(0, 188, 212, 0.2) !important;
  }

  .login-title {
    color: #ffffff !important;
    font-weight: 300;
    font-size: 1.5rem;
    letter-spacing: 0.05em;
  }

  :deep(.v-field) {
    background-color: rgba(15, 20, 25, 0.6) !important;
  }

  :deep(.v-field--variant-outlined .v-field__outline) {
    border-color: rgba(0, 188, 212, 0.3);
  }

  :deep(.v-field--focused .v-field__outline) {
    border-color: #00BCD4 !important;
    border-width: 2px;
  }

  :deep(.v-label) {
    color: #b0bec5 !important;
    opacity: 0.8;
  }

  :deep(.v-field--focused .v-label) {
    color: #00BCD4 !important;
  }

  :deep(.v-field__input) {
    color: #ffffff !important;
  }

  .register-btn {
    background: linear-gradient(45deg, #00BCD4, #00ACC1) !important;
    color: #ffffff !important;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    transition: all 0.3s ease;
  }

  .register-btn:hover {
    box-shadow: 
      0 6px 20px rgba(0, 188, 212, 0.4),
      0 0 30px rgba(0, 188, 212, 0.3) !important;
    transform: translateY(-2px);
  }

  .login-link {
    color: #00BCD4 !important;
    text-decoration: none;
    font-size: 0.9rem;
    opacity: 0.8;
    transition: all 0.3s ease;
  }

  .login-link:hover {
    opacity: 1;
    text-shadow: 0 0 10px rgba(0, 188, 212, 0.5);
  }
</style>