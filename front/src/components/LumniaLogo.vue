<template>
  <div class="text-center mb-3">
    <v-img :src="logoSrc" max-width="400" class="mx-auto" />
    <p class="lumnia-subtitle" style="margin-top: -20px;">{{ subtitle }}</p>
  </div>
</template>

<script lang="ts">
  import { defineComponent } from 'vue'

  export default defineComponent({
    name: 'LumniaLogo',
    props: {
      logoSrc: {
        type: String,
        required: true
      },
      subtitle: {
        type: String,
        default: 'Illuminate Your Knowledge.'
      }
    }
  })
</script>

<style scoped>
  .lumnia-subtitle {
    font-size: 1rem;
    color: #b0bec5;
    letter-spacing: 0.1em;
    margin: 0;
    opacity: 0.8;
  }
</style>
