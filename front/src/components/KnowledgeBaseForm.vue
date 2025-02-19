<template>
    <v-dialog v-model="internalDialog" max-width="600px">
        <v-card>
            <v-card-title>
                Editar Base de Conhecimento
            </v-card-title>
            <v-card-text>
                <v-form ref="form" v-model="isValid">
                    <v-text-field label="Título" v-model="formData.title" :rules="titleRules" required />
                    <v-textarea label="Conteúdo" v-model="formData.content" :rules="contentRules" required />
                </v-form>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn text @click="handleClose">Cancelar</v-btn>
                <v-btn color="primary" @click="save" :disabled="!isValid">
                    Salvar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';

export default defineComponent({
    name: 'KnowledgeBaseForm',
    props: {
        dialog: {
            type: Boolean,
            required: true,
        },
        knowledgeBaseData: {
            type: Object,
            default: () => null,
        },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
        const internalDialog = ref(props.dialog);
        watch(
            () => props.dialog,
            (newVal) => {
                internalDialog.value = newVal;
            }
        );

        watch(internalDialog, (val) => {
            if (!val) {
                emit('close');
            }
        });

        const formData = ref({ title: '', content: '' });
        watch(
            () => props.knowledgeBaseData,
            (newVal) => {
                if (newVal) {
                    formData.value.title = newVal.title;
                    formData.value.content = newVal.content;
                } else {
                    formData.value = { title: '', content: '' };
                }
            },
            { immediate: true }
        );

        const form = ref<any>(null);
        const isValid = ref(false);
        const { showToast } = useToast();

        const titleRules = [
            (v: string) => !!v || 'Título é obrigatório',
        ];
        const contentRules = [
            (v: string) => !!v || 'Conteúdo é obrigatório',
        ];

        const save = async () => {
            if (!form.value.validate()) {
                return;
            }
            try {
                if (props.knowledgeBaseData && props.knowledgeBaseData.id) {
                    await axios.put(
                        `/api/knowledge-base/${props.knowledgeBaseData.id}`,
                        formData.value
                    );
                    showToast('Base de conhecimento atualizada com sucesso!');
                }
                internalDialog.value = false;
                emit('saved');
            } catch (error) {
                showToast('Erro ao atualizar a base de conhecimento');
            }
        };

        const handleClose = () => {
            internalDialog.value = false;
        };

        return {
            internalDialog,
            formData,
            form,
            isValid,
            titleRules,
            contentRules,
            save,
            handleClose,
        };
    },
});
</script>

<style scoped></style>