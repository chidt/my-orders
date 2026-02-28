<template>
    <Head title="Tạo quyền hạn mới" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="pb-6">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">Tạo quyền hạn mới</h1>
                        <p class="mt-2 text-sm text-gray-700">
                            Tạo quyền hạn mới cho hệ thống.
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                        <form @submit.prevent="submit">
                            <div class="px-4 py-6 sm:p-8">
                                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                    <!-- Permission Name -->
                                    <div class="sm:col-span-6">
                                        <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                                            Tên quyền hạn
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
                                                placeholder="Ví dụ: view_users, create_orders, manage_products..."
                                            />
                                        </div>
                                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.name }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            Sử dụng format: action_resource (ví dụ: view_users, edit_orders)
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                                <Link
                                    :href="PermissionsIndex.url()"
                                    class="text-sm font-semibold leading-6 text-gray-900"
                                >
                                    Hủy
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Đang tạo...</span>
                                    <span v-else>Tạo quyền hạn</span>
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
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/admin';
import {
    index as PermissionsIndex,
    store as PermissionsStore,
} from '@/routes/admin/permissions';

const form = useForm({
    name: '',
});

const breadcrumbs = [
    { title: 'Quản trị viên', href: dashboard.url(), current: false },
    { title: 'Quyền hạn', href: PermissionsIndex.url(), current: false },
    { title: 'Tạo mới', href: '#', current: true },
];

const submit = () => {
    form.post(PermissionsStore.url());
};
</script>
