import axios from 'axios';
import type { ApiResponse, Permission, PermissionFormData } from '@/types/types';

export default {
  async list(): Promise<ApiResponse<Permission[]>> {
    const r = await axios.get('/api/permissions');
    return r.data;
  },

  async create(payload: PermissionFormData): Promise<ApiResponse<Permission>> {
    const r = await axios.post('/api/permissions', payload);
    return r.data;
  },

  async update(id: number, payload: PermissionFormData): Promise<ApiResponse<Permission>> {
    const r = await axios.put(`/api/permissions/${id}`, payload);
    return r.data;
  },

  async remove(id: number): Promise<ApiResponse> {
    const r = await axios.delete(`/api/permissions/${id}`);
    return r.data;
  },

  async assignToGroup(permissionId: number, groupId: number): Promise<ApiResponse> {
    const r = await axios.post(`/api/permissions/${permissionId}/assign-to-group`, { group_id: groupId });
    return r.data;
  },

  async removeFromGroup(permissionId: number, groupId: number): Promise<ApiResponse> {
    const r = await axios.post(`/api/permissions/${permissionId}/remove-from-group`, { group_id: groupId });
    return r.data;
  },
};
