<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Plus, Trash2, CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CustomerFormProps } from '@/types/customer';
import { cn } from '@/lib/utils';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

const props = defineProps<CustomerFormProps>();

const breadcrumbs = [
  { title: props.site.name, href: `/${props.site.slug}/dashboard`, current: false },
  { title: 'Quản lý khách hàng', href: `/${props.site.slug}/customers`, current: false },
  { title: 'Chỉnh sửa khách hàng', href: `/${props.site.slug}/customers/${props.customer?.id}/edit`, current: true },
];

const initialAddresses =
  props.customer?.addresses && props.customer.addresses.length > 0
    ? props.customer.addresses.map((item) => ({
      id: item.id,
      address: item.address ?? '',
      province_id: String(item.province_id ?? ''),
      ward_id: String(item.ward_id ?? ''),
      is_default: Number(item.is_default) === 1 ? 1 : 0,
    }))
    : [
      {
        id: undefined as number | undefined,
        address: props.customer?.address ?? '',
        province_id: String(props.customer?.province_id ?? ''),
        ward_id: String(props.customer?.ward_id ?? ''),
        is_default: 1,
      },
    ];

const wardOptions = ref<Record<number, Array<{ id: number; name: string }>>>({});
const selectedDefaultIndex = ref(Math.max(0, initialAddresses.findIndex((item) => Number(item.is_default) === 1)));
const activeProvinceIndex = ref<number | null>(null);
const activeWardIndex = ref<number | null>(null);

const form = useForm({
  name: props.customer?.name ?? '',
  phone: props.customer?.phone ?? '',
  email: props.customer?.email ?? '',
  type: String(props.customer?.type ?? ''),
  description: props.customer?.description ?? '',
  address: props.customer?.address ?? '',
  province_id: String(props.customer?.province_id ?? ''),
  ward_id: String(props.customer?.ward_id ?? ''),
  addresses: initialAddresses,
});

const isIndividual = computed(() => Number(form.type || 0) === 1);

const onlyDigitsPhone = () => {
  form.phone = form.phone.replace(/\D/g, '').slice(0, 11);
};

const loadWards = async (provinceId: string, index?: number) => {
  if (!provinceId) return;

  try {
    const response = await axios.get(`/api/provinces/${provinceId}/wards`);

    if (typeof index === 'number') {
      wardOptions.value[index] = response.data;
    }
  } catch {
    if (typeof index === 'number') {
      wardOptions.value[index] = [];
    }
  }
};

watch(
  () => form.type,
  (value) => {
    if (Number(value) === 1) {
      form.addresses = [
        {
          id: form.addresses[0]?.id,
          address: form.addresses[0]?.address ?? '',
          province_id: form.addresses[0]?.province_id ?? '',
          ward_id: form.addresses[0]?.ward_id ?? '',
          is_default: 1,
        },
      ];
      selectedDefaultIndex.value = 0;
    }
  },
);

const addAddress = () => {
  form.addresses.push({
    id: undefined,
    address: '',
    province_id: '',
    ward_id: '',
    is_default: form.addresses.length === 0 ? 1 : 0,
  });

  if (form.addresses.length === 1) {
    selectedDefaultIndex.value = 0;
  }
};

const removeAddress = (index: number) => {
  if (form.addresses.length <= 1) return;

  form.addresses.splice(index, 1);

  if (selectedDefaultIndex.value === index) {
    selectedDefaultIndex.value = 0;
  } else if (selectedDefaultIndex.value > index) {
    selectedDefaultIndex.value -= 1;
  }

  setDefaultAddress(selectedDefaultIndex.value);
};

const setDefaultAddress = (index: number) => {
  selectedDefaultIndex.value = index;

  form.addresses.forEach((addr, idx) => {
    addr.is_default = idx === selectedDefaultIndex.value ? 1 : 0;
  });
};

const onProvinceChange = (index: number) => {
  form.addresses[index].ward_id = '';
  loadWards(form.addresses[index].province_id, index);
};

onMounted(async () => {
  await Promise.all(
    form.addresses.map((address, index) =>
      address.province_id ? loadWards(address.province_id, index) : Promise.resolve(),
    ),
  );

  setDefaultAddress(selectedDefaultIndex.value);
});

const submit = () => {
  setDefaultAddress(selectedDefaultIndex.value);

  const filteredAddresses = form.addresses.filter((item) => item.address && item.ward_id);
  const hasDefault = filteredAddresses.some((item) => Number(item.is_default) === 1);

  const addressesPayload = filteredAddresses.map((item, index) => ({
    id: item.id,
    address: item.address,
    ward_id: Number(item.ward_id),
    is_default: Number(item.is_default) === 1 || (!hasDefault && index === 0) ? 1 : 0,
  }));

  form
    .transform((data) => ({
      ...data,
      type: Number(data.type),
      ward_id: addressesPayload[0]?.ward_id ?? Number(data.ward_id || 0),
      address: addressesPayload[0]?.address ?? data.address,
      addresses: Number(data.type) === 1 ? addressesPayload.slice(0, 1) : addressesPayload,
    }))
    .put(`/${props.site.slug}/customers/${props.customer?.id}`);
};
</script>

<template>

  <Head :title="`Chỉnh sửa khách hàng - ${site.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-6xl px-4 py-4 sm:px-6 lg:px-8">
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form class="space-y-6 p-4 sm:p-6" @submit.prevent="submit">
          <div class="flex items-start gap-3 sm:items-center sm:space-x-4">
            <Link :href="`/${site.slug}/customers`"
              class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
              <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
              <h1 class="text-base font-semibold text-gray-900 sm:text-lg">Chỉnh sửa khách hàng</h1>
              <p class="text-sm text-gray-700">Cập nhật thông tin khách hàng cho {{ site.name }}</p>
            </div>
          </div>

          <div class="rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            <p class="font-semibold">💡 Gợi ý</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
              <li>Email khách hàng là bắt buộc và không được trùng trong cùng một site.</li>
              <li>Khách hàng cá nhân chỉ có 1 địa chỉ; doanh nghiệp có thể có nhiều địa chỉ.</li>
              <li>Không thể xóa khách hàng khi đã có đơn hàng liên kết.</li>
            </ul>
          </div>

          <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
              <Label for="name">Tên khách hàng</Label>
              <Input id="name" v-model="form.name" type="text" class="mt-1" placeholder="VD: Nguyễn Văn A" required />
              <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
              <Label for="phone">Số điện thoại</Label>
              <Input id="phone" v-model="form.phone" type="text" class="mt-1" placeholder="VD: 0901234567"
                inputmode="numeric" maxlength="11" required @input="onlyDigitsPhone" />
              <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div>
              <Label for="email">Email</Label>
              <Input id="email" v-model="form.email" type="email" class="mt-1" placeholder="VD: nguyenvana@gmail.com"
                required />
              <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="md:col-span-2">
              <Label for="type">Loại khách hàng</Label>
              <select id="type" v-model="form.type"
                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" required>
                <option value="" disabled>Chọn loại khách hàng</option>
                <option v-for="(label, key) in customerTypes" :key="key" :value="String(key)">{{ label }}</option>
              </select>
              <InputError class="mt-2" :message="form.errors.type" />
            </div>

            <div class="md:col-span-2 rounded-lg border border-gray-200 p-4">
              <div class="mb-4 flex items-center justify-between">
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">Địa chỉ khách hàng</h3>
                  <p class="text-xs text-gray-500">
                    {{ isIndividual ? 'Khách hàng cá nhân chỉ có một địa chỉ chính.' : 'Doanh nghiệp có thể thêm nhiều địa chỉ.' }}
                  </p>
                </div>
                <Button v-if="!isIndividual" type="button" variant="outline" class="cursor-pointer" @click="addAddress">
                  <Plus class="mr-2 h-4 w-4" />
                  Thêm địa chỉ
                </Button>
              </div>

              <div class="space-y-4">
                <div v-for="(addressItem, index) in form.addresses" :key="addressItem.id ?? index"
                  class="rounded-md border border-gray-200 p-4">
                  <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-800">Địa chỉ #{{ index + 1 }}</p>
                    <div class="flex items-center gap-2">
                      <label class="inline-flex items-center gap-2 text-xs text-gray-600">
                        <input type="radio" name="default_address" :checked="selectedDefaultIndex === index"
                          @change="setDefaultAddress(index)">
                          Mặc định
                      </label>
                      <Button v-if="!isIndividual && form.addresses.length > 1" type="button" variant="ghost" size="sm"
                        class="cursor-pointer text-red-600 hover:text-red-700" @click="removeAddress(index)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  <div class="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label :for="`province_${index}`">Tỉnh/Thành phố</Label>
                      <Popover :open="activeProvinceIndex === index"
                        @update:open="(val) => activeProvinceIndex = val ? index : null">
                        <PopoverTrigger as-child>
                          <Button variant="outline" role="combobox" class="w-full justify-between font-normal">
                            {{
                              addressItem.province_id
                                ? provinces.find((p) => String(p.id) === addressItem.province_id)?.name
                                : 'Chọn tỉnh/thành phố'
                            }}
                            <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
                          <Command>
                            <CommandInput placeholder="Tìm kiếm tỉnh..." />
                            <CommandList>
                              <CommandEmpty>Không tìm thấy kết quả.</CommandEmpty>
                              <CommandGroup>
                                <CommandItem v-for="province in provinces" :key="province.id" :value="province.name"
                                  @select="() => {
                                    addressItem.province_id = addressItem.province_id === String(province.id) ? '' : String(province.id)
                                    onProvinceChange(index)
                                    activeProvinceIndex = null;
                                  }">
                                  <CheckIcon :class="cn(
                                    'mr-2 h-4 w-4',
                                    addressItem.province_id === String(province.id) ? 'opacity-100' : 'opacity-0'
                                  )" />
                                  {{ province.name }}
                                </CommandItem>
                              </CommandGroup>
                            </CommandList>
                          </Command>
                        </PopoverContent>
                      </Popover>
                    </div>

                    <div>
                      <Label :for="`ward_${index}`">Phường/Xã</Label>
                      <Popover :open="activeWardIndex === index"
                        @update:open="(val) => activeWardIndex = val ? index : null">
                        <PopoverTrigger as-child>
                          <Button variant="outline" role="combobox" class="w-full justify-between font-normal">
                            {{
                              addressItem.ward_id
                                ? wardOptions[index]?.find((w) => String(w.id) === addressItem.ward_id)?.name
                                : 'Chọn phường/xã'
                            }}
                            <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
                          <Command>
                            <CommandInput placeholder="Tìm kiếm phường/xã..." />
                            <CommandList>
                              <CommandEmpty>Không tìm thấy kết quả.</CommandEmpty>
                              <CommandGroup>
                                <CommandItem v-for="ward in wardOptions[index] ?? []" :key="ward.id" :value="ward.name"
                                  @select="() => {
                                    addressItem.ward_id = addressItem.ward_id === String(ward.id) ? '' : String(ward.id)
                                    activeWardIndex = null;
                                  }">
                                  <CheckIcon :class="cn(
                                    'mr-2 h-4 w-4',
                                    addressItem.ward_id === String(ward.id) ? 'opacity-100' : 'opacity-0'
                                  )" />
                                  {{ ward.name }}
                                </CommandItem>
                              </CommandGroup>
                            </CommandList>
                          </Command>
                        </PopoverContent>
                      </Popover>
                    </div>

                    <div class="md:col-span-2">
                      <Label :for="`address_${index}`">Địa chỉ</Label>
                      <Input :id="`address_${index}`" v-model="addressItem.address" type="text" class="mt-1"
                        placeholder="VD: 123 Nguyễn Trãi" required />
                    </div>
                  </div>
                </div>
              </div>

              <InputError class="mt-2" :message="form.errors.addresses" />
              <InputError class="mt-2" :message="form.errors.address" />
              <InputError class="mt-2" :message="form.errors.ward_id" />
            </div>

            <div class="md:col-span-2">
              <Label for="description">Mô tả</Label>
              <Textarea id="description" v-model="form.description" rows="3" class="mt-1"
                placeholder="Ghi chú thêm về khách hàng (nếu có)" />
              <InputError class="mt-2" :message="form.errors.description" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
            <Link :href="`/${site.slug}/customers`"
              class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
              Hủy
            </Link>
            <Button type="submit" :disabled="form.processing" class="cursor-pointer disabled:cursor-not-allowed">
              <span v-if="form.processing">Đang lưu...</span>
              <span v-else>Cập nhật khách hàng</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
