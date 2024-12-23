<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" sm="6" md="4">
        <v-card>
          <v-card-title class="justify-center">Login</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="login">
              <v-text-field
                label="Email"
                v-model="email"
                type="email"
                required
              ></v-text-field>
              <v-text-field
                label="Password"
                v-model="password"
                type="password"
                required
              ></v-text-field>
              <v-btn type="submit" color="primary" block>Login</v-btn>
            </v-form>
            <v-alert v-if="error" type="error" dense>{{ error }}</v-alert>
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

export default defineComponent({
  name: 'Login',
  setup() {
    const email = ref('');
    const password = ref('');
    const error = ref('');
    const router = useRouter();
    const { setAuth } = useAuth();

    const login = async () => {
      try {
        const csrf = await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post(
          '/api/login',
          { email: email.value, password: password.value },
          { withCredentials: true }
        );

        const token = response.data.token;
        const user = response.data.user;

        setAuth(token, user);

        router.push('/');
      } catch (err) {
        error.value = 'Login failed.';
      }
    };

    return { email, password, error, login };
  },
});
</script>