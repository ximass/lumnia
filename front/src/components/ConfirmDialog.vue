<template>
  <v-dialog v-model="show" max-width="400px" persistent>
    <v-card>
      <v-card-title class="text-h6">
        {{ title }}
      </v-card-title>
      <v-card-text>
        {{ message }}
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="primary" variant="flat"  @click="$emit('cancel')">
          Cancelar
        </v-btn>
        <v-btn  variant="outlined" @click="$emit('confirm')">
          Confirmar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ConfirmDialog',
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    title: {
      type: String,
      default: 'Confirmar',
    },
    message: {
      type: String,
      required: true,
    },
  },
  emits: ['confirm', 'cancel'],
});
</script>
