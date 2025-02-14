<template>
  <v-container class="login-bg" fluid>
    <v-row align="center" justify="center" style="height: 100vh;">
      <v-col cols="12" sm="6" md="4" class="offset-form">
        <v-card>
          <v-card-title class="justify-center">Registre-se</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="register">
              <v-text-field
                label="Nome"
                v-model="name"
                required
              ></v-text-field>
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
              <v-text-field
                label="Confirme a senha"
                v-model="password_confirmation"
                type="password"
                required
              ></v-text-field>
              <v-btn type="submit" color="primary" block>Concluir</v-btn>
            </v-form>
          </v-card-text>
          <v-card-actions class="justify-center">
            <router-link to="/login">Já possui uma conta? Faça login</router-link>
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
import { useToast } from '@/composables/useToast';

export default defineComponent({
  name: 'Register',
  setup() {
    const name = ref('');
    const email = ref('');
    const password = ref('');
    const password_confirmation = ref('');
    const router = useRouter();
    const { showToast } = useToast();

    const register = async () => {
      try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.post('/api/register', {
          name: name.value,
          email: email.value,
          password: password.value,
          password_confirmation: password_confirmation.value,
        });

        router.push('/login');
      } catch (err: any) {
        showToast('Erro ao se registrar: ' + err.response.data.message);
      }
    };

    return { name, email, password, password_confirmation, register };
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