<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import CategoriesRoutes from '@/routes/categories';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit, FolderTree, Package } from 'lucide-vue-next';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    order: number;
    is_active: boolean;
    parent_id: number | null;
    parent?: Category;
    products_count: number;
    depth: number;
    created_at: string;
    updated_at: string;
    children?: Category[];
}

interface Props {
    site?: Site;
    category: Category;
}

const props = defineProps<Props>();
const { can } = usePermissions();

// Build breadcrumb from category hierarchy
const buildBreadcrumb = (category: Category): string[] => {
    const breadcrumb: string[] = [];
    let current = category;

    while (current) {
        breadcrumb.unshift(current.name);
        current = current.parent!;
    }

    return breadcrumb;
};

const breadcrumb = buildBreadcrumb(props.category);
</script>

<template>
    <AppLayout>
        <Head :title="`Danh mục: ${category.name}`" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <Button
                        :as="Link"
                        :href="
                            props.site?.slug
                                ? CategoriesRoutes.index.url({
                                      site: props.site.slug,
                                  })
                                : '#'
                        "
                        variant="outline"
                        size="icon"
                        class="shrink-0"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ category.name }}
                        </h1>
                        <div class="mt-1 flex items-center gap-2">
                            <div
                                class="flex items-center text-sm text-gray-600"
                            >
                                <FolderTree class="mr-1 h-4 w-4" />
                                <span
                                    v-for="(item, index) in breadcrumb"
                                    :key="index"
                                    class="flex items-center"
                                >
                                    {{ item }}
                                    <span
                                        v-if="index < breadcrumb.length - 1"
                                        class="mx-2"
                                        >></span
                                    >
                                </span>
                            </div>
                            <Badge
                                :variant="
                                    category.is_active ? 'default' : 'secondary'
                                "
                            >
                                {{
                                    category.is_active
                                        ? 'Hoạt động'
                                        : 'Không hoạt động'
                                }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <Button
                    v-if="can('update_categories')"
                    :as="Link"
                    :href="
                        props.site?.slug
                            ? CategoriesRoutes.edit.url({
                                  site: props.site.slug,
                                  category: category.id,
                              })
                            : '#'
                    "
                    class="shrink-0"
                >
                    <Edit class="mr-2 h-4 w-4" />
                    Chỉnh sửa
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Category Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Thông tin danh mục</CardTitle>
                            <CardDescription
                                >Chi tiết về danh mục này</CardDescription
                            >
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-700"
                                    >Tên danh mục</Label
                                >
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ category.name }}
                                </p>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-gray-700"
                                    >Slug</Label
                                >
                                <p
                                    class="mt-1 rounded bg-gray-50 px-2 py-1 font-mono text-sm text-gray-900"
                                >
                                    {{ category.slug }}
                                </p>
                            </div>

                            <div v-if="category.description">
                                <Label class="text-sm font-medium text-gray-700"
                                    >Mô tả</Label
                                >
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ category.description }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label
                                        class="text-sm font-medium text-gray-700"
                                        >Thứ tự</Label
                                    >
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ category.order }}
                                    </p>
                                </div>
                                <div>
                                    <Label
                                        class="text-sm font-medium text-gray-700"
                                        >Trạng thái</Label
                                    >
                                    <p class="mt-1">
                                        <Badge
                                            :variant="
                                                category.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                        >
                                            {{
                                                category.is_active
                                                    ? 'Hoạt động'
                                                    : 'Không hoạt động'
                                            }}
                                        </Badge>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Child Categories -->
                    <Card
                        v-if="category.children && category.children.length > 0"
                    >
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FolderTree class="h-5 w-5" />
                                Danh mục con ({{ category.children.length }})
                            </CardTitle>
                            <CardDescription
                                >Các danh mục con thuộc danh mục
                                này</CardDescription
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="child in category.children"
                                    :key="child.id"
                                    class="flex items-center justify-between rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50"
                                >
                                    <div class="flex items-center gap-3">
                                        <FolderTree
                                            class="h-4 w-4 text-gray-400"
                                        />
                                        <div>
                                            <Link
                                                :href="
                                                    props.site?.slug
                                                        ? CategoriesRoutes.show.url(
                                                              {
                                                                  site: props
                                                                      .site
                                                                      .slug,
                                                                  category:
                                                                      child.id,
                                                              },
                                                          )
                                                        : '#'
                                                "
                                                class="font-medium text-gray-900 hover:text-blue-600"
                                            >
                                                {{ child.name }}
                                            </Link>
                                            <p
                                                v-if="child.description"
                                                class="mt-1 text-sm text-gray-600"
                                            >
                                                {{ child.description }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            <Package class="mr-1 h-3 w-3" />
                                            {{ child.products_count }} sản phẩm
                                        </Badge>
                                        <Badge
                                            :variant="
                                                child.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                            class="text-xs"
                                        >
                                            {{
                                                child.is_active
                                                    ? 'Hoạt động'
                                                    : 'Tạm dừng'
                                            }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Statistics -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Thống kê</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                class="flex items-center gap-3 rounded-lg bg-blue-50 p-3"
                            >
                                <Package class="h-8 w-8 text-blue-600" />
                                <div>
                                    <p class="text-2xl font-bold text-blue-900">
                                        {{ category.products_count }}
                                    </p>
                                    <p class="text-sm text-blue-700">
                                        Sản phẩm
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="category.children"
                                class="flex items-center gap-3 rounded-lg bg-green-50 p-3"
                            >
                                <FolderTree class="h-8 w-8 text-green-600" />
                                <div>
                                    <p
                                        class="text-2xl font-bold text-green-900"
                                    >
                                        {{ category.children.length }}
                                    </p>
                                    <p class="text-sm text-green-700">
                                        Danh mục con
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >Thông tin khác</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-700"
                                    >Danh mục cha</Label
                                >
                                <p v-if="category.parent" class="mt-1">
                                    <Link
                                        :href="
                                            props.site?.slug
                                                ? CategoriesRoutes.show.url({
                                                      site: props.site.slug,
                                                      category:
                                                          category.parent.id,
                                                  })
                                                : '#'
                                        "
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        {{ category.parent.name }}
                                    </Link>
                                </p>
                                <p v-else class="mt-1 text-sm text-gray-500">
                                    Danh mục gốc
                                </p>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-gray-700"
                                    >Ngày tạo</Label
                                >
                                <p class="mt-1 text-sm text-gray-900">
                                    {{
                                        new Date(
                                            category.created_at,
                                        ).toLocaleDateString('vi-VN')
                                    }}
                                </p>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-gray-700"
                                    >Cập nhật lần cuối</Label
                                >
                                <p class="mt-1 text-sm text-gray-900">
                                    {{
                                        new Date(
                                            category.updated_at,
                                        ).toLocaleDateString('vi-VN')
                                    }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
