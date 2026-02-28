<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import siteRoute from '@/routes/site'

interface Site {
  id: number
  name: string
  slug: string
}

interface Warehouse {
  id: number
  code: string
  name: string
  address: string
}

interface Props {
  site: Site
  warehouse: Warehouse
}

const props = defineProps<Props>()
const page = usePage()

const breadcrumbs = [
  { title: props.site.name, href: siteRoute.dashboard.url(props.site.slug), current: false },
  { title: 'Quản lý kho', href: siteRoute.warehouses.index.url(props.site.slug), current: false },
  { title: 'Chỉnh sửa kho', href: siteRoute.warehouses.edit.url([props.site.slug, props.warehouse.id]), current: true },
]

const form = useForm({
  code: props.warehouse.code,
  name: props.warehouse.name,
  address: props.warehouse.address
})

const submit = () => {
  form.put(siteRoute.warehouses.update.url([props.site.slug, props.warehouse.id]))
}

const generateCode = () => {
  // Simple code generation based on warehouse name
  if (form.name) {
    const words = form.name.split(' ')
    let code: string;

    if (words.length === 1) {
      code = words[0].substring(0, 3).toUpperCase()
    } else {
      code = words.map(word => word.charAt(0)).join('').toUpperCase()
    }

    // Add number suffix if code is too short
    if (code.length < 2) {
      code += '001'
    }

    form.code = code
  }
}
</script>

<template>
  <Head :title="`Chỉnh sửa kho ${warehouse.name} - ${site.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-gray-50">
      <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <!-- Header -->
          <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
              <div class="flex items-center space-x-4">
                  <Link
                    :href="siteRoute.warehouses.index.url(site.slug)"
                    class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                    title="Quay lại"
                  >
                  <ArrowLeft class="w-5 h-5" />
                </Link>

                <div>
                  <h1 class="text-base font-semibold leading-6 text-gray-900">Chỉnh sửa kho</h1>
                  <p class="mt-1 text-sm text-gray-700">
                    Cập nhật thông tin kho {{ warehouse.name }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Success Message -->
          <div
            v-if="page.props.flash?.message"
            class="mt-4 rounded-md bg-green-50 p-4"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.53a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
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
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-red-800">
                  {{ page.props.flash.error }}
                </p>
              </div>
            </div>
          </div>

          <!-- Form -->
          <div class="mt-8">
            <div class="bg-white shadow sm:rounded-lg">
              <form @submit.prevent="submit" class="p-6 space-y-6">
                <!-- Warehouse Code -->
                <div>
                  <Label for="code">Mã kho</Label>
                  <div class="mt-1 flex rounded-md shadow-sm">
                    <Input
                      id="code"
                      v-model="form.code"
                      type="text"
                      class="flex-1"
                      placeholder="VD: W001, KHO01, MAIN"
                      required
                    />
                    <Button
                      type="button"
                      variant="outline"
                      @click="generateCode"
                      :disabled="!form.name"
                      class="ml-3"
                    >
                      Tự động tạo
                    </Button>
                  </div>
                  <p class="mt-1 text-sm text-gray-500">
                    Mã kho duy nhất trong phạm vi trang web này. Chỉ sử dụng chữ cái in hoa, số và dấu gạch ngang.
                  </p>
                  <InputError class="mt-2" :message="form.errors.code" />
                </div>

                <!-- Warehouse Name -->
                <div>
                  <Label for="name">Tên kho</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="VD: Kho trung tâm Hà Nội, Warehouse A"
                    required
                  />
                  <p class="mt-1 text-sm text-gray-500">
                    Tên mô tả cho kho hàng của bạn
                  </p>
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Warehouse Address -->
                <div>
                  <Label for="address">Địa chỉ kho</Label>
                  <Textarea
                    id="address"
                    v-model="form.address"
                    rows="3"
                    class="mt-1"
                    placeholder="VD: 123 Đường ABC, Phường XYZ, Quận 1, TP.HCM"
                    required
                  />
                  <p class="mt-1 text-sm text-gray-500">
                    Địa chỉ đầy đủ của kho hàng
                  </p>
                  <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                  <Button
                    variant="outline"
                    as="Link"
                    :href="siteRoute.warehouses.index.url(site.slug)"
                  >
                    Hủy
                  </Button>

                  <Button
                    type="submit"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing">Đang cập nhật...</span>
                    <span v-else>Cập nhật kho</span>
                  </Button>
                </div>
              </form>
            </div>

            <!-- Warning Section -->
            <div class="mt-8 bg-amber-50 border border-amber-200 rounded-lg p-6">
              <h3 class="text-sm font-medium text-amber-900 mb-2">⚠️ Lưu ý</h3>
              <ul class="text-sm text-amber-800 space-y-1">
                <li>• Thay đổi mã kho có thể ảnh hưởng đến các báo cáo và tài liệu có sẵn</li>
                <li>• Nếu kho đã có vị trí lưu trữ, việc thay đổi thông tin sẽ được áp dụng cho tất cả vị trí</li>
                <li>• Địa chỉ kho được sử dụng cho việc vận chuyển và báo cáo logistics</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
