<template>
    <Head :title="`Chỉnh sửa vai trò: ${role.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="pb-6">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">
                            Chỉnh sửa vai trò: {{ role.name }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">
                            Cập nhật thông tin và quyền hạn của vai trò.
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                        <form @submit.prevent="submit">
                            <div class="px-4 py-6 sm:p-8">
                                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                    <!-- Role Name -->
                                    <div class="sm:col-span-6">
                                        <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                                            Tên vai trò
                                        </label>
                                        <div class="mt-2">
                                            <input
                                                id="name"
                                                v-model="form.name"
                                                type="text"
                                                name="name"
                                                autocomplete="off"
                                                :class="[
                                                    'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6',
                                                    form.errors.name
                                                        ? 'ring-red-300 focus:ring-red-500'
                                                        : 'ring-gray-300'
                                                ]"
                                                placeholder="Ví dụ: Manager, Editor, Viewer..."
                                            />
                                        </div>
                                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- Permissions -->
                                    <div class="sm:col-span-6">
                                        <label class="block text-sm font-medium leading-6 text-gray-900">
                                            Quyền hạn
                                        </label>
                                        <p class="mt-1 text-sm text-gray-600">
                                            Chọn các quyền hạn cho vai trò này.
                                        </p>

                                        <div class="mt-4 space-y-4">
                                            <!-- Select All/None -->
                                            <div class="flex items-center gap-4">
                                                <button
                                                    type="button"
                                                    @click="selectAllPermissions"
                                                    class="text-sm text-indigo-600 hover:text-indigo-500"
                                                >
                                                    Chọn tất cả
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="clearAllPermissions"
                                                    class="text-sm text-gray-600 hover:text-gray-500"
                                                >
                                                    Bỏ chọn tất cả
                                                </button>
                                            </div>

                                            <!-- Permission Groups -->
                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <template v-for="(groupPermissions, groupName) in groupedPermissions" :key="groupName">
                                                    <div class="rounded-lg border border-gray-200 p-4">
                                                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                                                            {{ getGroupDisplayName(groupName) }}
                                                        </h4>
                                                        <div class="space-y-2">
                                                            <div v-for="permission in groupPermissions" :key="permission.id" class="flex items-center">
                                                                <input
                                                                    :id="`permission-${permission.id}`"
                                                                    v-model="form.permissions"
                                                                    :value="permission.id"
                                                                    type="checkbox"
                                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                                />
                                                                <label
                                                                    :for="`permission-${permission.id}`"
                                                                    class="ml-3 text-sm font-medium text-gray-700"
                                                                >
                                                                    {{ permission.name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <p v-if="form.errors.permissions" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.permissions }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                                <Link
                                    :href="RolesIndex.url()"
                                    class="text-sm font-semibold leading-6 text-gray-900"
                                >
                                    Hủy
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Đang cập nhật...</span>
                                    <span v-else>Cập nhật vai trò</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/admin';
import {
    index as RolesIndex,
    update as RolesUpdate,
} from '@/routes/admin/roles';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Props {
    role: Role;
    permissions: Permission[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions.map(p => p.id),
});

const breadcrumbs = [
    { title: 'Quản trị viên', href: dashboard.url(), current: false },
    { title: 'Vai trò', href: RolesIndex.url(), current: false },
    { title: 'Chỉnh sửa', href: '#', current: true },
];

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    props.permissions.forEach(permission => {
        const parts = permission.name.split(' ');
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        const _ = parts[0]; // view, create, edit, delete, manage, access
        const resource = parts.slice(1).join(' '); // users, roles, sites, etc.

        const groupKey = resource || 'system';

        if (!groups[groupKey]) {
            groups[groupKey] = [];
        }

        groups[groupKey].push(permission);
    });

    return groups;
});

const getGroupDisplayName = (groupName: string): string => {
    const displayNames: Record<string, string> = {
        'roles': 'Quản lý vai trò',
        'users': 'Quản lý người dùng',
        'sites': 'Quản lý trang web',
        'orders': 'Quản lý đơn hàng',
        'products': 'Quản lý sản phẩm',
        'customers': 'Quản lý khách hàng',
        'admin dashboard': 'Bảng điều khiển admin',
        'site dashboard': 'Bảng điều khiển site',
        'system settings': 'Cài đặt hệ thống',
        'activity logs': 'Nhật ký hoạt động',
        'system': 'Hệ thống',
    };

    return displayNames[groupName] || groupName;
};

const selectAllPermissions = () => {
    form.permissions = props.permissions.map(p => p.id);
};

const clearAllPermissions = () => {
    form.permissions = [];
};

const submit = () => {
    form.put(RolesUpdate.url(props.role.id));
};
</script>
