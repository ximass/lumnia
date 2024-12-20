<template>
    <v-container>
      <v-row justify="center">
        <v-col cols="12" sm="6" md="4">
          <v-card>
            <v-card-title class="justify-center">Register</v-card-title>
            <v-card-text>
              <v-form @submit.prevent="register">
                <v-text-field
                  label="Name"
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
                  label="Password"
                  v-model="password"
                  type="password"
                  required
                ></v-text-field>
                <v-text-field
                  label="Confirm Password"
                  v-model="password_confirmation"
                  type="password"
                  required
                ></v-text-field>
                <v-btn type="submit" color="primary" block>Register</v-btn>
              </v-form>
              <v-alert v-if="error" type="error" dense>{{ error }}</v-alert>
            </v-card-text>
            <v-card-actions class="justify-center">
              <router-link to="/login">Already have an account? Login</router-link>
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
  
  export default defineComponent({
    name: 'Register',
    setup() {
      const name = ref('');
      const email = ref('');
      const password = ref('');
      const password_confirmation = ref('');
      const error = ref('');
      const router = useRouter();
  
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
        } catch (err) {
          error.value = 'Registration failed.';
        }
      };
  
      return { name, email, password, password_confirmation, error, register };
    },
  });
  </script>