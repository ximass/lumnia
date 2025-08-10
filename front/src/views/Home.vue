<template>
  <div>
    <v-container fluid>
      <v-row justify="center" align="center" style="height: 100vh">
        <v-col cols="12" md="8" lg="6">
          <v-card class="elevation-2">
            <v-card-title class="text-h5">Bem-vindo ao Lumnia</v-card-title>
            <v-card-text>
              <p>Esta é a página inicial do seu aplicativo.</p>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-btn color="primary" @click="$router.push('/chats')">Iniciar Chat</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script lang="ts">
  import { defineComponent } from 'vue'

  export default defineComponent({
    name: 'Home',
  })
</script>
