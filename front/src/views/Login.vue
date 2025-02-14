<template>
  <v-container class="login-bg" fluid>
    <v-row align="center" justify="center" style="height: 100vh;">
      <v-col cols="12" sm="6" md="4" class="offset-form">
        <v-card>
          <v-card-title class="justify-center">Faça login</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="login">
              <v-text-field
                label="Email"
                v-model="email"
                type="email"
                required
              ></v-text-field>
              <v-text-field
                label="Senha"
                v-model="password"
                type="password"
                required
              ></v-text-field>
              <v-btn type="submit" color="primary" block>Entrar</v-btn>
            </v-form>
          </v-card-text>
          <v-card-actions class="justify-center">
            <router-link to="/register">Não possui uma conta? Registre-se</router-link>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/auth';
import { useToast } from '@/composables/useToast';

export default defineComponent({
  name: 'Login',
  setup() {
    const email = ref('');
    const password = ref('');
    const router = useRouter();
    const { setAuth, user } = useAuth();
    const { showToast } = useToast();

    const login = async () => {
      try {
        const csrf = await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post(
          '/api/login',
          { email: email.value, password: password.value },
          { withCredentials: true }
        );

        const token = response.data.token;
        user.value = response.data.user;

        setAuth(token, user);

        router.push('/');
      } catch (err) {
        showToast('Credenciais inválidas');
      }
    };

    return { email, password, login };
  },
});
</script>

<style scoped>
.login-bg {
  background-image: url('@/assets/login-bg.jpg');
  background-size: cover;
  background-position: center;
}

.offset-form {
  margin-left: 30%;
}
</style>