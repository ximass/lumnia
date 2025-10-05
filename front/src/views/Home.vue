<template>
  <div>
    <v-container fluid>
      <v-row justify="center" align="center" style="height: 100vh">
        <v-col cols="12" md="8" lg="6">
          <v-card class="home-card pa-6" elevation="0">
            <v-card-title class="text-center pb-4">
              <span class="home-title">Bem-vindo ao Lumnia</span>
            </v-card-title>
            <v-card-text class="text-center">
              <p class="home-subtitle mb-4">Illuminate Your Knowledge.</p>
              <p class="home-description">
                Explore o poder da inteligência artificial integrada com suas bases de conhecimento.
              </p>
            </v-card-text>
            <v-card-actions class="justify-center">
              <v-btn 
                color="cyan" 
                size="large"
                class="home-btn"
                @click="navigateTo('/chats')"
              >
                INICIAR CHAT
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script lang="ts">
  import { defineComponent } from 'vue'
  import { useRouter } from 'vue-router'

  export default defineComponent({
    name: 'Home',
    setup() {
      const router = useRouter()

      const navigateTo = (route: string) => {
        router.push(route)
      }

      return {
        navigateTo
      }
    },
  })
</script>

<style scoped>
  .home-card {
    background: rgba(30, 41, 59, 0.15) !important;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 12px !important;
  }

  .home-title {
    color: #ffffff !important;
    font-weight: 300;
    font-size: 2rem;
    letter-spacing: 0.05em;
  }

  .home-subtitle {
    font-size: 1.2rem;
    color: #ffffff;
    letter-spacing: 0.1em;
    font-weight: 300;
  }

  .home-description {
    color: #b0bec5 !important;
    font-size: 1rem;
    line-height: 1.6;
    opacity: 0.9;
  }

  .home-btn {
    background: linear-gradient(45deg, #ffffff, #ffffff) !important;
    color: #000000 !important;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 12px 32px;
    box-shadow: 
      0 4px 15px rgba(255, 255, 255, 0.3),
      0 0 20px rgba(255, 255, 255, 0.2) !important;
    transition: all 0.3s ease;
  }

  .home-btn:hover {
    box-shadow: 
      0 6px 20px rgba(255, 255, 255, 0.4),
      0 0 30px rgba(255, 255, 255, 0.3) !important;
    transform: translateY(-2px);
  }

  /* Responsividade */
  @media (max-width: 600px) {
    .home-title {
      font-size: 1.5rem;
    }
  }
</style>
