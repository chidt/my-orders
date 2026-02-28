<template>
    <Head :title="`Chi tiết quyền hạn: ${permission.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-4xl py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1
                                class="text-base leading-6 font-semibold text-gray-900"
                            >
                                Chi tiết quyền hạn: {{ permission.name }}
                            </h1>
                            <p class="mt-2 text-sm text-gray-700">
                                Xem thông tin chi tiết của quyền hạn này.
                            </p>
                        </div>
                        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                            <div class="flex items-center gap-3">
                                <Link
                                    v-if="can('edit_permissions')"
                                    :href="PermissionsEdit.url({ permission: permission.id })"
                                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                >
                                    Chỉnh sửa
                                </Link>
                                <button
                                    v-if="can('delete_permissions')"
                                    @click="deletePermission"
                                    class="block rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                                >
                                    Xóa quyền hạn
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <!-- Permission Information -->
                        <div
                            class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl"
                        >
                            <div class="px-4 py-6 sm:p-8">
                                <h2
                                    class="text-base leading-7 font-semibold text-gray-900"
                                >
                                    Thông tin quyền hạn
                                </h2>
                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    Thông tin cơ bản về quyền hạn này.
                                </p>

                                <dl
                                    class="mt-6 space-y-6 divide-y divide-gray-100 border-t border-gray-200 text-sm leading-6"
                                >
                                    <div class="pt-6 sm:flex">
                                        <dt
                                            class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6"
                                        >
                                            Tên quyền hạn
                                        </dt>
                                        <dd
                                            class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto"
                                        >
                                            <div class="text-gray-900">
                                                {{ permission.name }}
                                            </div>
                                        </dd>
                                    </div>
                                    <div class="pt-6 sm:flex">
                                        <dt
                                            class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6"
                                        >
                                            Mô tả
                                        </dt>
                                        <dd
                                            class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto"
                                        >
                                            <div class="text-gray-900">
                                                {{ getPermissionDescription(permission.name) }}
                                            </div>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/admin';
import {
    index as PermissionsIndex,
    edit as PermissionsEdit,
    destroy as PermissionsDestroy,
} from '@/routes/admin/permissions';

const { can } = usePermissions();

interface Permission {
    id: number;
    name: string;
}

interface Props {
    permission: Permission;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Quản trị viên', href: dashboard.url(), current: false },
    { title: 'Quyền hạn', href: PermissionsIndex.url(), current: false },
    { title: 'Chi tiết', href: '#', current: true },
];

const getPermissionDescription = (permissionName: string): string => {
    const descriptions: Record<string, string> = {
        'view_admin_dashboard': 'Xem bảng điều khiển quản trị viên',
        'view_site_dashboard': 'Xem bảng điều khiển trang web',
        'view_permissions': 'Xem danh sách quyền hạn',
        'create_permissions': 'Tạo quyền hạn mới',
        'edit_permissions': 'Chỉnh sửa quyền hạn',
        'delete_permissions': 'Xóa quyền hạn',
        'manage_permissions': 'Quản lý đầy đủ quyền hạn',
        'view_roles': 'Xem danh sách vai trò',
        'create_roles': 'Tạo vai trò mới',
        'edit_roles': 'Chỉnh sửa vai trò',
        'delete_roles': 'Xóa vai trò',
        'manage_roles': 'Quản lý đầy đủ vai trò',
        'view_users': 'Xem danh sách người dùng',
        'create_users': 'Tạo người dùng mới',
        'edit_users': 'Chỉnh sửa người dùng',
        'delete_users': 'Xóa người dùng',
        'manage_users': 'Quản lý đầy đủ người dùng',
        'view_own_site': 'Xem thông tin trang web của mình',
        'edit_own_site': 'Chỉnh sửa trang web của mình',
        'manage_own_site': 'Quản lý trang web của mình',
        'view_all_sites': 'Xem tất cả trang web',
        'edit_all_sites': 'Chỉnh sửa tất cả trang web',
        'manage_all_sites': 'Quản lý tất cả trang web',
    };

    return descriptions[permissionName] || 'Không có mô tả';
};

const deletePermission = () => {
    if (confirm('Bạn có chắc chắn muốn xóa quyền hạn này?')) {
        router.delete(PermissionsDestroy.url({ permission: props.permission.id }));
    }
};
</script>
