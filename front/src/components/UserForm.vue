<template>
    <v-dialog v-model="internalDialog" max-width="500px">
        <v-card>
            <v-card-title>
                <span class="text-h5">Editar Usuário</span>
            </v-card-title>
            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-text-field label="Nome" v-model="user.name" :rules="[v => !!v || 'Nome é obrigatório']"
                        required></v-text-field>
                    <v-text-field label="Email" v-model="user.email" :rules="[v => !!v || 'Email é obrigatório']"
                        required></v-text-field>
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

export default defineComponent({
    name: 'UserForm',
    props: {
        dialog: { type: Boolean, required: true },
        userData: { type: Object, default: null },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
        const { showToast } = useToast();
        const user = ref({ id: 0, name: '', email: '', admin: false });
        const internalDialog = ref(props.dialog);
        const form = ref();

        watch(() => props.dialog, (val) => {
            internalDialog.value = val;
        });

        watch(() => props.userData, (newData) => {
            if (newData) {
                user.value = { ...newData };
            }
        }, { immediate: true });

        const close = () => {
            emit('close');
        };

        const submitForm = async () => {
            if (!user.value.name || !user.value.email) {
                showToast('Preencha todos os campos');
                return;
            }

            try {
                await axios.put(`/api/user/${user.value.id}`, user.value);
                emit('saved');
            } catch (error) {
                showToast('Erro ao atualizar usuário');
            }
        };

        return { internalDialog, user, form, close, submitForm, showToast };
    },
});
</script>

<style scoped></style>