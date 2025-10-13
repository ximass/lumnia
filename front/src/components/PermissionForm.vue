<template>
  <v-dialog v-model="dialog" max-width="600px">
    <v-card>
      <v-card-title>{{ permissionData ? 'Editar' : 'Novo' }} permissão</v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-text-field v-model="local.name" :disabled="!!selectedMenuOption || !!permissionData?.id" label="Nome" required />

          <v-select
            :items="menuItems"
            item-title="title"
            item-value="route"
            v-model="selectedMenuOption"
            label="Opção do menu"
            clearable
          />
          
          <v-textarea v-model="local.description" label="Descrição" />
        </v-form>
      </v-card-text>
      <v-card-actions>
  <v-spacer />
  <v-btn variant="text" @click="$emit('close')">Fechar</v-btn>
        <v-btn color="primary" @click="save">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue'
import type { PermissionFormData } from '@/types/types'
import permissionService from '@/services/permission'
import { menuItems } from '@/constants/menu'

export default defineComponent({
  name: 'PermissionForm',
  props: {
    dialog: { type: Boolean, required: true },
    permissionData: { type: Object as () => PermissionFormData | null, required: false },
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const local = ref<PermissionFormData>({ name: '', label: '', description: '' })
    const selectedMenuOption = ref<string | null>(null)

    watch(
      () => props.permissionData,
      (v) => {
        if (v) {
          local.value = { ...v }
          // try to map to a menu option if possible
          const found = menuItems.find(m => m.permission === v.name || m.title === v.label || m.route.replace(/\//g, '_').replace(/^_/, '') === v.name)
          selectedMenuOption.value = found ? found.route : null
        } else {
          local.value = { name: '', label: '', description: '' }
          selectedMenuOption.value = null
        }
      },
      { immediate: true }
    )

    watch(selectedMenuOption, (val) => {
      if (!val) return
      const opt = menuItems.find(m => m.route === val)
      if (!opt) return
      local.value.label = opt.title
      local.value.name = opt.permission || opt.route.replace(/\//g, '_').replace(/^_/, '')
    })

    const save = async () => {
      if (props.permissionData && (props.permissionData as any).id) {
        await permissionService.update((props.permissionData as any).id, local.value)
      } else {
        await permissionService.create(local.value)
      }
      emit('saved')
      emit('close')
    }

    return { local, save, selectedMenuOption, menuItems }
  },
})
</script>

<style scoped></style>
