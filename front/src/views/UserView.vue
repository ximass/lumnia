<template>
    <v-container style="padding: 50px;">
        <v-row justify="space-between" align="center" class="mb-4" style="margin: 0;">
            <h2>Usuários</h2>
        </v-row>
        <v-data-table :items="users" :headers="headers" class="elevation-1">
            <template #item.actions="{ item }">
                <v-menu offset-y>
                    <template #activator="{ props }">
                        <v-btn icon v-bind="props" variant="text">
                            <v-icon>mdi-dots-vertical</v-icon>
                        </v-btn>
                    </template>
                    <v-list>
                        <v-list-item @click="editUser(item)">
                            <v-list-item-title>
                                <v-icon>mdi-pencil</v-icon>
                                Editar
                            </v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </template>
        </v-data-table>
        <UserForm :dialog="isFormOpen" :userData="selectedUser" @close="isFormOpen = false" @saved="handleUserSaved" />
    </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import UserForm from '@/components/UserForm.vue';
import { useToast } from '@/composables/useToast';

export default defineComponent({
    name: 'UserView',
    components: { UserForm },
    setup() {
        const users = ref<Array<{ id: number; name: string; email: string }>>([]);
        const isFormOpen = ref(false);
        const selectedUser = ref<any>(null);
        const { showToast } = useToast();

        const headers = [
            { title: 'Nome', value: 'name', sortable: true },
            { title: 'Email', value: 'email', sortable: true },
            { title: 'Ações', value: 'actions', sortable: false },
        ];

        const fetchUsers = async () => {
            try {
                const response = await axios.get('/api/users');
                users.value = response.data;
            } catch (error) {
                showToast('Erro ao buscar usuários');
            }
        };

        const editUser = (user: any) => {
            selectedUser.value = { ...user };
            isFormOpen.value = true;
        };

        const handleUserSaved = () => {
            isFormOpen.value = false;
            fetchUsers();
        };

        onMounted(() => {
            fetchUsers();
        });

        return {
            users,
            isFormOpen,
            selectedUser,
            headers,
            editUser,
            fetchUsers,
            handleUserSaved,
            showToast,
        };
    },
});
</script>

<style scoped>
.v-container {
    padding-top: 50px;
}
</style>