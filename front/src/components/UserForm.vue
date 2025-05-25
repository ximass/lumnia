<template>
  <v-dialog v-model="internalDialog" max-width="500px"> 
    <v-card>
      <v-card-title>
        <span class="text-h5">{{ user.id ? 'Editar usuário' : 'Novo usuário' }}</span>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" @submit.prevent="submitForm">
          <v-text-field label="Nome" v-model="user.name" :rules="[v => !!v || 'Nome é obrigatório']"
            required>
          </v-text-field> 
          <v-text-field label="Email" v-model="user.email" :rules="[
              v => !!v || 'Email é obrigatório',
              v => /.+@.+\..+/.test(v) || 'Email deve ser válido'
            ]" type="email" required>
          </v-text-field>
          <v-switch v-model="user.admin" label="Administrador"></v-switch>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="close">Cancelar</v-btn>
        <v-btn color="primary" @click="submitForm">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import type { User, UserFormData } from '@/types/types';

export default defineComponent({
  name: 'UserForm', props: {
    dialog: { type: Boolean, required: true },
    userData: {
      type: Object as () => User | null,
      default: null
    },
  },
  emits: ['close', 'saved'],  setup(props, { emit }) {
    const { showToast } = useToast();
    const internalDialog = ref(props.dialog);
    const form = ref();

    const user = ref<UserFormData>(
      { name: '', email: '', admin: false }
    );

    watch(() => props.dialog, (val) => {
      internalDialog.value = val;
    });

    watch(() => props.userData, (newData: User | null) => {
      if (newData) {
        user.value = { 
          id: newData.id,
          name: newData.name,
          email: newData.email,
          admin: newData.admin
        };
      } else {
        user.value = { name: '', email: '', admin: false };
      }
    }, { immediate: true });

    const close = () => {
      emit('close');
    }; 

    const submitForm = async () => {
      const validation = await form.value?.validate();

      if (!validation.valid) {
        return;
      }

      try {
        const payload: Omit<UserFormData, 'id'> = {
          name: user.value.name,
          email: user.value.email,
          admin: user.value.admin
        };

        if (user.value.id) {
          await axios.put<User>(`/api/user/${user.value.id}`, payload);
        } else {
          await axios.post<User>('/api/users', payload);
        }

        emit('saved');
        close();
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao salvar usuário';
        showToast(errorMsg);
      }
    };

    return { internalDialog, user, form, close, submitForm, showToast };
  },
});
</script>

<style scoped></style>