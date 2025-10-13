<template>
  <v-dialog v-model="dialog" max-width="600px">
    <v-card>
      <v-card-title>{{ permissionData ? 'Editar' : 'Novo' }} permissão</v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-text-field v-model="local.name" label="Nome" required />
          <v-text-field v-model="local.label" label="Rótulo" />
          <v-textarea v-model="local.description" label="Descrição" />
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="$emit('close')">Fechar</v-btn>
        <v-btn color="primary" @click="save">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue'
import type { PermissionFormData } from '@/types/types'
import permissionService from '@/services/permission'

export default defineComponent({
  name: 'PermissionForm',
  props: {
    dialog: { type: Boolean, required: true },
    permissionData: { type: Object as () => PermissionFormData | null, required: false },
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const local = ref<PermissionFormData>({ name: '', label: '', description: '' })

    watch(
      () => props.permissionData,
      (v) => {
        if (v) local.value = { ...v }
        else local.value = { name: '', label: '', description: '' }
      },
      { immediate: true }
    )

    const save = async () => {
      if (props.permissionData && (props.permissionData as any).id) {
        await permissionService.update((props.permissionData as any).id, local.value)
      } else {
        await permissionService.create(local.value)
      }
      emit('saved')
      emit('close')
    }

    return { local, save }
  },
})
</script>

<style scoped></style>
