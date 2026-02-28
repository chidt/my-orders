<template>
    <Head title="Quản lý quyền hạn" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1
                                class="text-base leading-6 font-semibold text-gray-900"
                            >
                                Quản lý quyền hạn
                            </h1>
                            <p class="mt-2 text-sm text-gray-700">
                                Danh sách tất cả quyền hạn trong hệ thống.
                            </p>
                        </div>
                        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                            <Link
                                v-if="can('create_permissions')"
                                :href="PermissionsCreate.url()"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            >
                                Tạo quyền hạn mới
                            </Link>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div
                        v-if="page.props.flash?.message"
                        class="mt-4 rounded-md bg-green-50 p-4"
                    >
                        <div class="flex">
                            <div class="shrink-0">
                                <svg
                                    class="h-5 w-5 text-green-400"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.53a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ page.props.flash.message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div
                        v-if="page.props.flash?.error"
                        class="mt-4 rounded-md bg-red-50 p-4"
                    >
                        <div class="flex">
                            <div class="shrink-0">
                                <svg
                                    class="h-5 w-5 text-red-400"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ page.props.flash.error }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Table -->
                    <div class="mt-8 flow-root">
                        <div
                            class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
                        >
                            <div
                                class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                            >
                                <div
                                    class="ring-opacity-5 overflow-hidden shadow ring-1 ring-black sm:rounded-lg"
                                >
                                    <table
                                        class="min-w-full divide-y divide-gray-300"
                                    >
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                                >
                                                    Tên quyền hạn
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                                >
                                                    Guard Name
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="relative px-6 py-3"
                                                >
                                                    <span class="sr-only"
                                                        >Hành động</span
                                                    >
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-gray-200 bg-white"
                                        >
                                            <tr
                                                v-for="permission in permissions.data"
                                                :key="permission.id"
                                            >
                                                <td
                                                    class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900"
                                                >
                                                    {{ permission.name }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                                >
                                                    {{ permission.guard_name }}
                                                </td>
                                                <td
                                                    class="relative py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-6"
                                                >
                                                    <div
                                                        class="flex items-center justify-end gap-2"
                                                    >
                                                        <Link
                                                            :href="
                                                                PermissionsShow.url(
                                                                    permission.id,
                                                                )
                                                            "
                                                            class="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Xem
                                                        </Link>
                                                        <Link
                                                            v-if="
                                                                can(
                                                                    'edit_permissions',
                                                                )
                                                            "
                                                            :href="
                                                                PermissionsEdit.url(
                                                                    permission.id,
                                                                )
                                                            "
                                                            class="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Sửa
                                                        </Link>
                                                        <button
                                                            v-if="
                                                                can(
                                                                    'delete_permissions',
                                                                )
                                                            "
                                                            @click="
                                                                openDeleteDialog(permission)
                                                            "
                                                            class="text-red-600 hover:text-red-900"
                                                        >
                                                            Xóa
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="permissions.links.length > 3" class="mt-6">
                        <nav
                            class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0"
                        >
                            <div class="-mt-px flex w-0 flex-1">
                            </div>
                            <div class="hidden md:-mt-px md:flex">
                                <template
                                    v-for="link in permissions.links"
                                    :key="link.label"
                                >
                                    <Link
                                        v-if="link.url && !link.active"
                                        :href="link.url"
                                        class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                    >
                                        <span v-html="link.label" />
                                    </Link>
                                    <span
                                        v-else-if="link.active"
                                        class="inline-flex items-center border-t-2 border-indigo-500 px-4 pt-4 text-sm font-medium text-indigo-600"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                            <div class="-mt-px flex w-0 flex-1 justify-end">
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa quyền hạn</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa quyền hạn
                        <span class="font-semibold">{{ permissionToDelete?.name }}</span>?
                        <br>
                        Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDelete">
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Xóa quyền hạn
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/admin';
import {
    create as PermissionsCreate,
    destroy as PermissionsDestroy,
    edit as PermissionsEdit,
    index as PermissionsIndex,
    show as PermissionsShow,
} from '@/routes/admin/permissions';

const page = usePage();
const { can } = usePermissions();

// Dialog state management
const showDeleteDialog = ref(false);
const permissionToDelete = ref<Permission | null>(null);

interface Permission {
    id: number;
    name: string;
    guard_name: string;
}

interface PaginationLink {
    url?: string;
    label: string;
    active: boolean;
}

interface PaginatedPermissions {
    data: Permission[];
    links: PaginationLink[];
    prev_page_url?: string;
    next_page_url?: string;
}

interface Props {
    permissions: PaginatedPermissions;
    can: {
        create: boolean;
        edit: boolean;
        delete: boolean;
    };
}

defineProps<Props>();

const breadcrumbs = [
    { title: 'Quản trị viên', href: dashboard.url(), current: false },
    { title: 'Quyền hạn', href: PermissionsIndex.url(), current: true },
];

const openDeleteDialog = (permission: Permission) => {
    permissionToDelete.value = permission;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!permissionToDelete.value) return;

    router.delete(PermissionsDestroy.url(permissionToDelete.value.id), {
        onFinish: () => {
            showDeleteDialog.value = false;
            permissionToDelete.value = null;
        }
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    permissionToDelete.value = null;
};
</script>

