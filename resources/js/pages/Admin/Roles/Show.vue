<template>
    <Head :title="`Chi tiết vai trò: ${role.name}`" />

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
                                Chi tiết vai trò: {{ role.name }}
                            </h1>
                            <p class="mt-2 text-sm text-gray-700">
                                Xem thông tin chi tiết, quyền hạn và danh sách
                                người dùng của vai trò.
                            </p>
                        </div>
                        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                            <div class="flex items-center gap-3">
                                <Link
                                    v-if="can('edit_roles')"
                                    :href="RolesEdit.url(role.id)"
                                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                >
                                    Chỉnh sửa
                                </Link>
                                <button
                                    v-if="can('delete_roles') && role.users.length === 0"
                                    @click="deleteRole"
                                    class="block rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                                >
                                    Xóa vai trò
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Role Information -->
                        <div
                            class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl"
                        >
                            <div class="px-4 py-6 sm:p-8">
                                <h2
                                    class="text-base leading-7 font-semibold text-gray-900"
                                >
                                    Thông tin vai trò
                                </h2>
                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    Thông tin cơ bản về vai trò này.
                                </p>

                                <dl
                                    class="mt-6 space-y-6 divide-y divide-gray-100 border-t border-gray-200 text-sm leading-6"
                                >
                                    <div class="pt-6 sm:flex">
                                        <dt
                                            class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6"
                                        >
                                            Tên vai trò
                                        </dt>
                                        <dd
                                            class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto"
                                        >
                                            <div class="text-gray-900">
                                                {{ role.name }}
                                            </div>
                                        </dd>
                                    </div>
                                    <div class="pt-6 sm:flex">
                                        <dt
                                            class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6"
                                        >
                                            Số người dùng
                                        </dt>
                                        <dd
                                            class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto"
                                        >
                                            <div class="text-gray-900">
                                                {{ role.users.length }} người
                                                dùng
                                            </div>
                                        </dd>
                                    </div>
                                    <div class="pt-6 sm:flex">
                                        <dt
                                            class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6"
                                        >
                                            Số quyền hạn
                                        </dt>
                                        <dd
                                            class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto"
                                        >
                                            <div class="text-gray-900">
                                                {{
                                                    role.permissions.length
                                                }}
                                                quyền
                                            </div>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Permissions List -->
                        <div
                            class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl"
                        >
                            <div class="px-4 py-6 sm:p-8">
                                <h2
                                    class="text-base leading-7 font-semibold text-gray-900"
                                >
                                    Quyền hạn
                                </h2>
                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    Danh sách tất cả quyền hạn được gán cho vai
                                    trò này.
                                </p>

                                <div
                                    v-if="role.permissions.length > 0"
                                    class="mt-6"
                                >
                                    <div class="space-y-4">
                                        <template
                                            v-for="(
                                                groupPermissions, groupName
                                            ) in groupedPermissions"
                                            :key="groupName"
                                        >
                                            <div>
                                                <h4
                                                    class="mb-2 text-sm font-medium text-gray-900"
                                                >
                                                    {{
                                                        getGroupDisplayName(
                                                            groupName,
                                                        )
                                                    }}
                                                </h4>
                                                <div
                                                    class="flex flex-wrap gap-2"
                                                >
                                                    <span
                                                        v-for="permission in groupPermissions"
                                                        :key="permission.id"
                                                        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-700/10 ring-inset"
                                                    >
                                                        {{ permission.name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div v-else class="mt-6">
                                    <p class="text-sm text-gray-500 italic">
                                        Vai trò này chưa được gán quyền hạn nào.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div
                        v-if="role.users.length > 0"
                        class="mt-8 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl"
                    >
                        <div class="px-4 py-6 sm:p-8">
                            <h2
                                class="text-base leading-7 font-semibold text-gray-900"
                            >
                                Người dùng có vai trò này
                            </h2>
                            <p class="mt-1 text-sm leading-6 text-gray-600">
                                Danh sách người dùng được gán vai trò
                                {{ role.name }}.
                            </p>

                            <div class="mt-6 flow-root">
                                <div
                                    class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
                                >
                                    <div
                                        class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                                    >
                                        <table
                                            class="min-w-full divide-y divide-gray-300"
                                        >
                                            <thead>
                                                <tr>
                                                    <th
                                                        scope="col"
                                                        class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0"
                                                    >
                                                        Tên người dùng
                                                    </th>
                                                    <th
                                                        scope="col"
                                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                                                    >
                                                        Email
                                                    </th>
                                                    <th
                                                        scope="col"
                                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                                                    >
                                                        Số điện thoại
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="divide-y divide-gray-200"
                                            >
                                                <tr
                                                    v-for="user in role.users"
                                                    :key="user.id"
                                                >
                                                    <td
                                                        class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0"
                                                    >
                                                        {{ user.name }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm whitespace-nowrap text-gray-500"
                                                    >
                                                        {{ user.email }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm whitespace-nowrap text-gray-500"
                                                    >
                                                        {{ user.phone_number }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty Users State -->
                    <div
                        v-else
                        class="mt-8 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl"
                    >
                        <div class="px-4 py-6 sm:p-8">
                            <div class="text-center">
                                <svg
                                    class="mx-auto h-12 w-12 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                    />
                                </svg>
                                <h3
                                    class="mt-2 text-sm font-semibold text-gray-900"
                                >
                                    Chưa có người dùng nào
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Vai trò này chưa được gán cho người dùng nào
                                    trong hệ thống.
                                </p>
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
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/admin';
import {
    index as RolesIndex,
    edit as RolesEdit,
    destroy as RolesDestroy,
} from '@/routes/admin/roles';

// eslint-disable-next-line vue/no-dupe-keys
const { can } = usePermissions();

interface Permission {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    phone_number: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
    users: User[];
}

interface Props {
    role: Role;
    can: {
        edit: boolean;
        delete: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Quản trị viên', href: dashboard.url(), current: false },
    { title: 'Vai trò', href: RolesIndex.url(), current: false },
    { title: 'Chi tiết', href: '#', current: true },
];

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    props.role.permissions.forEach((permission) => {
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
        roles: 'Quản lý vai trò',
        users: 'Quản lý người dùng',
        sites: 'Quản lý trang web',
        orders: 'Quản lý đơn hàng',
        products: 'Quản lý sản phẩm',
        customers: 'Quản lý khách hàng',
        'admin dashboard': 'Bảng điều khiển admin',
        'site dashboard': 'Bảng điều khiển site',
        'system settings': 'Cài đặt hệ thống',
        'activity logs': 'Nhật ký hoạt động',
        system: 'Hệ thống',
    };

    return displayNames[groupName] || groupName;
};

const deleteRole = () => {
    if (confirm(`Bạn có chắc chắn muốn xóa vai trò "${props.role.name}"?`)) {
        router.delete(RolesDestroy.url(props.role.id));
    }
};
</script>
