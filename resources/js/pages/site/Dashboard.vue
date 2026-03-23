<template>
    <Head :title="`Bảng điều khiển ${page.props.site.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <nav class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex items-center">
                            <h1 class="text-xl font-semibold text-gray-900">
                                Bảng điều khiển {{ page.props.site.name }}
                            </h1>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Site Info Banner -->
                    <div class="mb-6 rounded-lg bg-white shadow">
                        <div class="px-4 py-5 sm:p-6">
                            <div
                                class="sm:flex sm:items-center sm:justify-between"
                            >
                                <div>
                                    <h3
                                        class="text-lg leading-6 font-medium text-gray-900"
                                    >
                                        {{ page.props.site.name }}
                                    </h3>
                                    <div
                                        class="mt-1 max-w-2xl text-sm text-gray-500"
                                    >
                                        <p>
                                            {{
                                                page.props.site.description ||
                                                'No description available'
                                            }}
                                        </p>
                                        <p class="mt-1">
                                            Site URL:
                                            <span
                                                class="font-mono text-blue-600"
                                                >/{{
                                                    page.props.site.slug
                                                }}</span
                                            >
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="mt-5 sm:mt-0 sm:ml-6 sm:flex sm:flex-shrink-0 sm:items-center"
                                >
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                    >
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div
                        class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div class="overflow-hidden rounded-lg bg-white shadow">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500"
                                        >
                                            <svg
                                                class="h-5 w-5 text-white"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="truncate text-sm font-medium text-gray-500"
                                            >
                                                Số lượng tài khoản
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                {{ stats.site_users }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg bg-white shadow">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-md bg-green-500"
                                        >
                                            <svg
                                                class="h-5 w-5 text-white"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="truncate text-sm font-medium text-gray-500"
                                            >
                                                Ngày tạo
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                {{
                                                    formatDate(stats.created_at)
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg bg-white shadow">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-md bg-purple-500"
                                        >
                                            <svg
                                                class="h-5 w-5 text-white"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="truncate text-sm font-medium text-gray-500"
                                            >
                                                Lần cập nhật gần nhất
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                {{
                                                    formatDate(
                                                        stats.last_updated,
                                                    )
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Site Management Actions -->
                    <div class="rounded-lg bg-white shadow">
                        <div class="px-4 py-5 sm:p-6">
                            <h3
                                class="mb-4 text-lg leading-6 font-medium text-gray-900"
                            >
                                Quản lý gian hàng
                            </h3>
                            <div
                                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <div
                                    class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:border-gray-400"
                                >
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100"
                                        >
                                            <svg
                                                class="h-6 w-6 text-blue-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="#" class="focus:outline-none">
                                            <span
                                                class="absolute inset-0"
                                                aria-hidden="true"
                                            ></span>
                                            <p
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                Cài đặt trang
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Quản lý cấu hình trang
                                            </p>
                                        </a>
                                    </div>
                                </div>

                                <div
                                    class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:border-gray-400"
                                >
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100"
                                        >
                                            <svg
                                                class="h-6 w-6 text-green-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="#" class="focus:outline-none">
                                            <span
                                                class="absolute inset-0"
                                                aria-hidden="true"
                                            ></span>
                                            <p
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                Quản lý người dùng
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Quản lý người dùng trên trang
                                            </p>
                                        </a>
                                    </div>
                                </div>

                                <div
                                    class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:border-gray-400"
                                >
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100"
                                        >
                                            <svg
                                                class="h-6 w-6 text-purple-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="#" class="focus:outline-none">
                                            <span
                                                class="absolute inset-0"
                                                aria-hidden="true"
                                            ></span>
                                            <p
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                Phân tích
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Xem phân tích trang
                                            </p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard as siteDashboard } from '@/routes/site';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    site: Object,
    stats: Object,
});

const page = usePage();

const breadcrumbs = [
    { title: 'Bảng điều khiển', href: siteDashboard.url(page.props.site.slug) },
];

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>
