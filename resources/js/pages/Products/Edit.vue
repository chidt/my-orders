<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Check, Plus, Trash2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import QuickTagCreateDialog from '@/components/products/QuickTagCreateDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as ProductsIndex, update as ProductsUpdate } from '@/routes/products';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Option {
    id: number;
    name: string;
}

interface Unit extends Option {
    unit: string;
}

interface LocationOption {
    id: number;
    name: string;
    is_default: boolean;
}

type TagOption = Option;

type CategoryOption = Option;

interface AttributeOption {
    id: number;
    name: string;
    code: string;
    order: number;
}

type AttributeValueInput = {
    code: string;
    value: string;
    order: number;
    addition_value?: number | null;
    partner_addition_value?: number | null;
    purchase_addition_value?: number | null;
};

type AttributeBlockInput = {
    attribute_id: number;
    values: AttributeValueInput[];
};

type VariantImageInput = {
    key: string;
    media_id: number | null;
    slide_index: number | null;
    use_main_image: boolean;
};

interface ProductEdit {
    id: number;
    name: string;
    code: string;
    supplier_code: string | null;
    description: string | null;
    category_id: number;
    supplier_id: number;
    unit_id: number;
    product_type_id: number;
    default_location_id: number;
    qty_in_stock: number;
    weight: number | null;
    price: string;
    partner_price: string | null;
    purchase_price: string;
    order_closing_date: string | null;
    product_items_count: number;
    tags: { id: number }[];
}

interface Props {
    site?: Site;
    product: ProductEdit;
    mainImage?: { id: number; url: string } | null;
    slideImages?: { id: number; url: string }[];
    variantUploadImages?: { id: number; url: string; key?: string }[];
    variantImages?: VariantImageInput[];
    categories: CategoryOption[];
    suppliers: Option[];
    productTypes: Option[];
    units: Unit[];
    locations: LocationOption[];
    tags: TagOption[];
    attributes: AttributeOption[];
    productAttributes: AttributeBlockInput[];
}

const props = defineProps<Props>();

const orderClosingDateFormatted =
    props.product.order_closing_date == null
        ? null
        : props.product.order_closing_date.toString().slice(0, 10);

const form = useForm<{
    name: string;
    code: string;
    supplier_code: string;
    description: string;
    category_id: number | null;
    supplier_id: number | null;
    unit_id: number | null;
    product_type_id: number | null;
    default_location_id: number | null;
    qty_in_stock: number | null;
    weight: number | null;
    price: number | null;
    partner_price: number | null;
    purchase_price: number | null;
    order_closing_date: string | null;
    tags: number[];
    attributes: AttributeBlockInput[];
    main_image: File | null;
    slide_images: File[];
    remove_main_image: boolean;
    remove_slide_media_ids: number[];
    variant_images: VariantImageInput[];
    variant_image_files: File[];
    variant_image_file_keys: string[];
}>({
    name: props.product.name,
    code: props.product.code,
    supplier_code: props.product.supplier_code ?? '',
    description: props.product.description ?? '',
    category_id: props.product.category_id,
    supplier_id: props.product.supplier_id,
    unit_id: props.product.unit_id,
    product_type_id: props.product.product_type_id,
    default_location_id: props.product.default_location_id,
    qty_in_stock: props.product.qty_in_stock,
    weight: props.product.weight,
    price: parseFloat(props.product.price) || 0,
    partner_price: props.product.partner_price
        ? parseFloat(props.product.partner_price)
        : null,
    purchase_price: parseFloat(props.product.purchase_price) || 0,
    order_closing_date: orderClosingDateFormatted,
    tags: props.product.tags.map((t) => t.id),
    attributes: props.productAttributes ?? [],
    main_image: null,
    slide_images: [],
    remove_main_image: false,
    remove_slide_media_ids: [],
    variant_images: props.variantImages ?? [],
    variant_image_files: [],
    variant_image_file_keys: [],
});

const toggleTag = (tagId: number) => {
    if (form.tags.includes(tagId)) {
        form.tags = form.tags.filter((id) => id !== tagId);
        return;
    }
    form.tags.push(tagId);
};

const showQuickTagDialog = ref(false);

function onQuickTagCreated(tagId: number): void {
    if (!form.tags.includes(tagId)) {
        form.tags.push(tagId);
    }
}

const errorFor = (path: string): string | undefined => {
    return (form.errors as Record<string, string | undefined>)[path];
};

const tagsError = computed(() => {
    const errors = form.errors as Record<string, unknown> | undefined;
    if (!errors) return undefined;

    const getFirst = (v: unknown): string | undefined => {
        if (typeof v === 'string') return v;
        if (Array.isArray(v) && typeof v[0] === 'string') return v[0];
        return undefined;
    };

    const direct = getFirst((errors as any).tags);
    if (direct) return direct;

    const firstTagKey = Object.keys(errors).find((k) => k.startsWith('tags.'));
    if (!firstTagKey) return undefined;
    return getFirst(errors[firstTagKey]);
});

const attributeBlockIndex = (attributeId: number): number => {
    return form.attributes.findIndex((a) => a.attribute_id === attributeId);
};

const selectedAttributeId = ref<string>('none');
const activeVariantTab = ref<'attributes' | 'preview'>('attributes');

const selectedAttributes = computed(() => {
    const selectedIds = new Set(form.attributes.map((a) => a.attribute_id));
    return props.attributes.filter((a) => selectedIds.has(a.id));
});

const combinationsCount = computed(() => {
    if (form.attributes.length === 0) return 1;
    return form.attributes.reduce((acc, block) => {
        const len = block.values.length || 0;
        return acc * (len === 0 ? 0 : len);
    }, 1);
});

const combinationsTooMany = computed(() => combinationsCount.value > 100);

/** Giống DB/BE: orderBy(order) rồi orderBy(name) — SKU preview theo đúng thứ tự này */
const attributeOrder = computed(() => {
    const meta = new Map(
        props.attributes.map((a) => [
            a.id,
            {
                order: Number(a.order) || 0,
                name: (a.name ?? '').toString(),
                id: a.id,
            },
        ]),
    );

    return [...form.attributes].sort((a, b) => {
        const ma = meta.get(a.attribute_id) ?? {
            order: Number.MAX_SAFE_INTEGER,
            name: '',
            id: a.attribute_id,
        };
        const mb = meta.get(b.attribute_id) ?? {
            order: Number.MAX_SAFE_INTEGER,
            name: '',
            id: b.attribute_id,
        };
        if (ma.order !== mb.order) {
            return ma.order - mb.order;
        }
        const byName = ma.name.localeCompare(mb.name, undefined, { sensitivity: 'base' });
        if (byName !== 0) {
            return byName;
        }
        return ma.id - mb.id;
    });
});

const previewVariants = computed(() => {
    const code = (form.code || '').trim() || 'AUTO';

    if (attributeOrder.value.length === 0) {
        return [{ key: '__default__', sku: code, label: 'Biến thể mặc định' }];
    }

    const blocks = attributeOrder.value.map((b) => ({
        values: [...b.values].sort((x, y) => {
            if (x.order !== y.order) {
                return x.order - y.order;
            }
            return (x.code || '')
                .toString()
                .localeCompare((y.code || '').toString(), undefined, { sensitivity: 'base' });
        }),
    }));

    const all = cartesian(blocks.map((b) => b.values));
    return all.slice(0, 100).map((combo) => {
        const key = combo.map((v) => (v.code || '').trim().toUpperCase()).join('-');
        const suffix = combo.map((v) => (v.code || '').trim().toUpperCase()).join('-');
        return {
            key,
            sku: `${code}-${suffix}`,
            label: combo.map((v) => v.value).join(' / '),
        };
    });
});

const variantUploadObjectUrls = ref<Record<string, string>>({});
const variantFileInputRefs = ref<Record<string, HTMLInputElement | null>>({});
const variantUploadOptionKeys = ref<Set<string>>(new Set());

const initializeVariantUploadOptionKeys = (): void => {
    const keys = new Set<string>();

    (props.variantUploadImages ?? []).forEach((img) => {
        if (img.key) {
            keys.add(img.key);
        }
    });

    variantUploadOptionKeys.value = keys;
};

initializeVariantUploadOptionKeys();

function setVariantFileInputRef(key: string, el: unknown): void {
    if (el instanceof HTMLInputElement) {
        variantFileInputRefs.value = { ...variantFileInputRefs.value, [key]: el };
        return;
    }
    if (el === null && key in variantFileInputRefs.value) {
        const next = { ...variantFileInputRefs.value };
        delete next[key];
        variantFileInputRefs.value = next;
    }
}

const getVariantImageConfig = (key: string): VariantImageInput => {
    let row = form.variant_images.find((v) => v.key === key);
    if (!row) {
        row = { key, media_id: null, slide_index: null, use_main_image: true };
        form.variant_images.push(row);
    } else if (typeof row.use_main_image !== 'boolean') {
        row.use_main_image = row.media_id === null && row.slide_index === null;
    }
    return row;
};

const hasVariantUploadFile = (key: string): boolean => {
    return form.variant_image_file_keys.includes(key);
};

const getExistingVariantUploadMediaIdForKey = (key: string): number | null => {
    const matched = (props.variantUploadImages ?? []).find((img) => img.key === key);
    return matched ? Number(matched.id) : null;
};

const isExistingVariantUploadMediaForKey = (key: string, mediaId: number): boolean => {
    return (props.variantUploadImages ?? []).some((img) => img.id === mediaId && img.key === key);
};

const hasVariantUploadSource = (key: string): boolean => {
    if (hasVariantUploadFile(key)) {
        return true;
    }

    const mediaId = form.variant_images.find((v) => v.key === key)?.media_id ?? null;
    if (mediaId !== null && isExistingVariantUploadMediaForKey(key, mediaId)) {
        return true;
    }

    return variantUploadOptionKeys.value.has(key);
};

const setVariantImageSource = (key: string, value: string) => {
    const row = getVariantImageConfig(key);

    if (value === 'upload') {
        row.use_main_image = false;
        row.slide_index = null;
        if (!hasVariantUploadFile(key)) {
            row.media_id = getExistingVariantUploadMediaIdForKey(key);
        }
        return;
    }

    if (value === 'main') {
        row.use_main_image = true;
        row.media_id = null;
        row.slide_index = null;
        return;
    }

    row.use_main_image = false;

    if (value.startsWith('existing:')) {
        row.media_id = Number(value.replace('existing:', ''));
        row.slide_index = null;
        return;
    }

    if (value.startsWith('new:')) {
        row.media_id = null;
        row.slide_index = Number(value.replace('new:', ''));
    }
};

const getVariantImageSource = (key: string): string => {
    const row = getVariantImageConfig(key);
    if (hasVariantUploadFile(key)) {
        return 'upload';
    }
    if (row.use_main_image) {
        return 'main';
    }
    if (row.media_id !== null) {
        if (isExistingVariantUploadMediaForKey(key, row.media_id)) {
            return 'upload';
        }
        return `existing:${row.media_id}`;
    }
    if (row.slide_index !== null) {
        return `new:${row.slide_index}`;
    }
    return 'main';
};

const clearVariantImage = (key: string) => {
    const row = getVariantImageConfig(key);
    row.use_main_image = true;
    row.media_id = null;
    row.slide_index = null;

    const fileIndex = form.variant_image_file_keys.findIndex((k) => k === key);
    if (fileIndex !== -1) {
        form.variant_image_file_keys.splice(fileIndex, 1);
        form.variant_image_files.splice(fileIndex, 1);
    }

    const prevUpload = variantUploadObjectUrls.value[key];
    if (prevUpload) {
        URL.revokeObjectURL(prevUpload);
    }
    if (key in variantUploadObjectUrls.value) {
        const next = { ...variantUploadObjectUrls.value };
        delete next[key];
        variantUploadObjectUrls.value = next;
    }

    const inputEl = variantFileInputRefs.value[key];
    if (inputEl) {
        inputEl.value = '';
    }
};

/**
 * Preview thống nhất (upload / slide mới / slide đã lưu / ảnh chính).
 * Chỉ đọc state — không gọi getVariantImageConfig (tránh side-effect trong computed).
 */
const getVariantImagePreviewUrl = (key: string): string | null => {
    const uploadUrl = variantUploadObjectUrls.value[key];
    if (uploadUrl) {
        return uploadUrl;
    }

    const row = form.variant_images.find((v) => v.key === key);
    if (!row || row.use_main_image) {
        return mainImagePreviewUrl.value ?? props.mainImage?.url ?? null;
    }

    if (row.media_id !== null) {
        const existingSlide = props.slideImages?.find((img) => img.id === row.media_id);
        if (existingSlide) {
            return existingSlide.url ?? null;
        }

        const existingVariant = props.variantUploadImages?.find(
            (img) => img.id === row.media_id,
        );
        return existingVariant?.url ?? null;
    }

    if (row.slide_index !== null) {
        const u = slideImagePreviewUrls.value[row.slide_index];
        if (u) {
            return u;
        }
    }

    return null;
};

const previewVariantRows = computed(() =>
    previewVariants.value.map((variant) => ({
        variant,
        previewUrl: getVariantImagePreviewUrl(variant.key),
    })),
);

const existingSlideImagesCount = computed(
    () =>
        props.slideImages?.filter((x) => !form.remove_slide_media_ids.includes(x.id)).length ??
        0,
);

const maxNewSlideImages = computed(() => Math.max(0, 10 - existingSlideImagesCount.value));

const setVariantUploadFile = (key: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (!file) {
        return;
    }

    const row = getVariantImageConfig(key);
    row.use_main_image = false;
    row.media_id = null;
    row.slide_index = null;
    variantUploadOptionKeys.value.add(key);

    const existedIndex = form.variant_image_file_keys.findIndex((k) => k === key);
    if (existedIndex !== -1) {
        form.variant_image_files[existedIndex] = file;
    } else {
        form.variant_image_file_keys.push(key);
        form.variant_image_files.push(file);
    }

    const prevUrl = variantUploadObjectUrls.value[key];
    if (prevUrl) {
        URL.revokeObjectURL(prevUrl);
    }
    variantUploadObjectUrls.value = {
        ...variantUploadObjectUrls.value,
        [key]: URL.createObjectURL(file),
    };

    input.value = '';
};

const getVariantUploadFileName = (key: string): string | null => {
    const index = form.variant_image_file_keys.findIndex((k) => k === key);
    if (index === -1) {
        return null;
    }
    const file = form.variant_image_files[index];
    return file?.name ?? null;
};

const addAttribute = () => {
    if (selectedAttributeId.value === 'none') return;
    const id = Number(selectedAttributeId.value);
    if (Number.isNaN(id)) return;
    if (form.attributes.some((a) => a.attribute_id === id)) return;

    form.attributes.push({
        attribute_id: id,
        values: [
            {
                code: '',
                value: '',
                order: 1,
                addition_value: 0,
                partner_addition_value: 0,
                purchase_addition_value: 0,
            },
        ],
    });

    selectedAttributeId.value = 'none';
};

const removeAttribute = (attributeId: number) => {
    form.attributes = form.attributes.filter((a) => a.attribute_id !== attributeId);
};

const quickValuesByAttribute = ref<Record<number, string>>({});

const normalizeValueCode = (value: string): string => {
    const noAccent = value
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase();
    return noAccent.replace(/[^A-Z0-9]+/g, '');
};

const addValue = (attributeId: number) => {
    const block = form.attributes.find((a) => a.attribute_id === attributeId);
    if (!block) return;
    const nextOrder = (block.values.at(-1)?.order ?? 0) + 1;
    block.values.push({
        code: '',
        value: '',
        order: nextOrder,
        addition_value: 0,
        partner_addition_value: 0,
        purchase_addition_value: 0,
    });
};

const addValuesFromQuickInput = (attributeId: number) => {
    const block = form.attributes.find((a) => a.attribute_id === attributeId);
    if (!block) return;

    const raw = quickValuesByAttribute.value[attributeId] ?? '';
    const items = raw
        .split(/[,\n;|]+/)
        .map((x) => x.trim())
        .filter((x) => x.length > 0);
    if (items.length === 0) return;

    if (block.values.length === 1 && !block.values[0].value.trim() && !block.values[0].code.trim()) {
        block.values = [];
    }

    let nextOrder = Math.max(0, ...block.values.map((v) => Number(v.order) || 0)) + 1;
    const existedCodes = new Set(
        block.values.map((v) => (v.code || '').toString().trim().toUpperCase()).filter(Boolean),
    );

    items.forEach((item) => {
        let code = normalizeValueCode(item);
        if (!code) {
            code = `V${nextOrder}`;
        }
        if (existedCodes.has(code)) {
            return;
        }
        existedCodes.add(code);

        block.values.push({
            code,
            value: item,
            order: nextOrder,
            addition_value: 0,
            partner_addition_value: 0,
            purchase_addition_value: 0,
        });
        nextOrder += 1;
    });

    quickValuesByAttribute.value = {
        ...quickValuesByAttribute.value,
        [attributeId]: '',
    };
};

const removeValue = (attributeId: number, index: number) => {
    const block = form.attributes.find((a) => a.attribute_id === attributeId);
    if (!block) return;
    block.values.splice(index, 1);
};

const mainImagePreviewUrl = ref<string | null>(null);
const slideImagePreviewUrls = ref<string[]>([]);
const mainImageInputRef = ref<HTMLInputElement | null>(null);
const slideImagesInputRef = ref<HTMLInputElement | null>(null);

function revokeAllVariantUploadObjectUrls(): void {
    Object.values(variantUploadObjectUrls.value).forEach((u) => URL.revokeObjectURL(u));
    variantUploadObjectUrls.value = {};
}

const revokePreviews = () => {
    if (mainImagePreviewUrl.value) {
        URL.revokeObjectURL(mainImagePreviewUrl.value);
    }
    mainImagePreviewUrl.value = null;

    slideImagePreviewUrls.value.forEach((url) => URL.revokeObjectURL(url));
    slideImagePreviewUrls.value = [];

    revokeAllVariantUploadObjectUrls();
};

watch(
    () => form.main_image,
    (file) => {
        if (mainImagePreviewUrl.value) {
            URL.revokeObjectURL(mainImagePreviewUrl.value);
        }
        mainImagePreviewUrl.value = file ? URL.createObjectURL(file) : null;
    },
);

watch(
    () => form.slide_images,
    (files) => {
        slideImagePreviewUrls.value.forEach((url) => URL.revokeObjectURL(url));
        slideImagePreviewUrls.value = (files ?? []).map((f) => URL.createObjectURL(f));
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => {
    revokePreviews();
});

const removeMainImage = () => {
    if (mainImagePreviewUrl.value) {
        URL.revokeObjectURL(mainImagePreviewUrl.value);
    }
    mainImagePreviewUrl.value = null;
    form.main_image = null;

    if (mainImageInputRef.value) {
        mainImageInputRef.value.value = '';
    }

    form.remove_main_image = true;
};

const resetVariantToMainImage = (row: VariantImageInput) => {
    row.use_main_image = true;
    row.media_id = null;
    row.slide_index = null;
};

const slideFileKey = (f: File) => `${f.name}_${f.size}_${f.lastModified}`;

/** Sau khi merge/dedupe danh sách slide mới upload, map lại slide_index theo đúng file (không giữ index cũ). */
const remapVariantSlideIndicesAfterNewSlidesChange = (previous: File[], next: File[]) => {
    form.variant_images.forEach((row) => {
        if (row.use_main_image) return;
        if (row.media_id !== null) return;
        if (row.slide_index === null) return;

        const i = row.slide_index;
        if (i < 0 || i >= previous.length) {
            resetVariantToMainImage(row);
            return;
        }

        const key = slideFileKey(previous[i]);
        const newIdx = next.findIndex((f) => slideFileKey(f) === key);
        if (newIdx === -1) {
            resetVariantToMainImage(row);
        } else {
            row.slide_index = newIdx;
        }
    });
};

const removeSlideImage = (index: number) => {
    form.variant_images.forEach((row) => {
        if (row.slide_index === null) return;

        if (row.slide_index === index) {
            resetVariantToMainImage(row);
            return;
        }

        if (row.slide_index > index) {
            row.slide_index -= 1;
        }
    });

    form.slide_images = form.slide_images.filter((_, i) => i !== index);

    if (slideImagesInputRef.value) {
        slideImagesInputRef.value.value = '';
    }
};

const removeExistingMainImage = () => {
    form.remove_main_image = true;
};

const removeExistingSlideImage = (mediaId: number) => {
    if (!form.remove_slide_media_ids.includes(mediaId)) {
        form.remove_slide_media_ids.push(mediaId);
    }

    form.variant_images.forEach((row) => {
        if (row.media_id === mediaId) {
            resetVariantToMainImage(row);
        }
    });
};

const onMainImageChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.main_image = file;
    input.value = '';
};

const onSlideImagesChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const files = input.files ? Array.from(input.files) : [];

    const previous = [...(form.slide_images ?? [])];
    const existing = form.slide_images ?? [];
    const unique: File[] = [];
    const seen = new Set<string>();
    const allowedNewCount = maxNewSlideImages.value;

    for (const f of [...existing, ...files]) {
        const key = slideFileKey(f);
        if (seen.has(key)) continue;
        seen.add(key);
        unique.push(f);
        if (unique.length >= allowedNewCount) break;
    }

    form.slide_images = unique;
    remapVariantSlideIndicesAfterNewSlidesChange(previous, unique);
    input.value = '';
};

const submit = () => {
    if (!props.site?.slug) return;
    form.put(
        ProductsUpdate.url({
            site: props.site.slug,
            product: props.product.id,
        }),
        {
            preserveScroll: true,
            forceFormData: true,
        },
    );
};

function cartesian<T>(sets: T[][]): T[][] {
    let result: T[][] = [[]];
    for (const set of sets) {
        const next: T[][] = [];
        for (const prefix of result) {
            for (const item of set) {
                next.push([...prefix, item]);
            }
        }
        result = next;
    }
    return result;
}
</script>

<template>
    <AppLayout>
        <Head title="Chỉnh sửa sản phẩm" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center gap-4">
                <Button
                    :as="Link"
                    :href="
                        props.site?.slug
                            ? ProductsIndex.url({ site: props.site.slug })
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
                        Chỉnh sửa sản phẩm
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Mã: {{ product.code }} ·
                        {{ product.product_items_count }} biến thể
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Thông tin cơ bản
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Cập nhật thông tin và giá. Khi thay đổi thuộc tính,
                            hệ thống sẽ tạo lại biến thể (SKU).
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Tên sản phẩm *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Ví dụ: Áo thun nam"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="code">Mã sản phẩm (code)</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="Mã duy nhất"
                            />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="space-y-2">
                            <Label for="supplier_code">Mã nhà cung cấp</Label>
                            <Input
                                id="supplier_code"
                                v-model="form.supplier_code"
                                placeholder="Ví dụ: SUP-001"
                            />
                            <InputError :message="form.errors.supplier_code" />
                        </div>

                        <div class="space-y-2">
                            <Label for="category_id">Danh mục *</Label>
                            <Select v-model="form.category_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Chọn danh mục" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in props.categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.category_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="supplier_id">Nhà cung cấp *</Label>
                            <Select v-model="form.supplier_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Chọn nhà cung cấp"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="supplier in props.suppliers"
                                        :key="supplier.id"
                                        :value="supplier.id"
                                    >
                                        {{ supplier.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.supplier_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product_type_id">Loại sản phẩm *</Label>
                            <Select v-model="form.product_type_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Chọn loại sản phẩm"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="pt in props.productTypes"
                                        :key="pt.id"
                                        :value="pt.id"
                                    >
                                        {{ pt.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.product_type_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="unit_id">Đơn vị tính *</Label>
                            <Select v-model="form.unit_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Chọn đơn vị" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="unit in props.units"
                                        :key="unit.id"
                                        :value="unit.id"
                                    >
                                        {{ unit.name }} ({{ unit.unit }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.unit_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="default_location_id">
                                Vị trí mặc định *
                            </Label>
                            <Select v-model="form.default_location_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Chọn vị trí"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="loc in props.locations"
                                        :key="loc.id"
                                        :value="loc.id"
                                    >
                                        {{ loc.name }}
                                        <span v-if="loc.is_default">
                                            (mặc định)
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="form.errors.default_location_id"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="price">Giá bán *</Label>
                            <Input
                                id="price"
                                v-model.number="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                required
                            />
                            <InputError :message="form.errors.price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="partner_price">Giá đối tác</Label>
                            <Input
                                id="partner_price"
                                v-model.number="form.partner_price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0"
                            />
                            <InputError :message="form.errors.partner_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="purchase_price">Giá nhập *</Label>
                            <Input
                                id="purchase_price"
                                v-model.number="form.purchase_price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                required
                            />
                            <InputError :message="form.errors.purchase_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="qty_in_stock">Tồn kho</Label>
                            <Input
                                id="qty_in_stock"
                                v-model.number="form.qty_in_stock"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="0"
                            />
                            <InputError :message="form.errors.qty_in_stock" />
                        </div>

                        <div class="space-y-2">
                            <Label for="order_closing_date">
                                Ngày đóng đặt hàng
                            </Label>
                            <Input
                                id="order_closing_date"
                                v-model="form.order_closing_date"
                                type="date"
                            />
                            <InputError
                                :message="form.errors.order_closing_date"
                            />
                        </div>

                        <div class="space-y-2 lg:col-span-2">
                            <Label for="description">Mô tả</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Mô tả sản phẩm..."
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Hình ảnh sản phẩm
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Cập nhật 1 ảnh chính và tối đa 10 ảnh cho
                            slide.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <Label for="main_image">Ảnh chính</Label>
                            <input
                                id="main_image"
                                ref="mainImageInputRef"
                                type="file"
                                accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                                @change="onMainImageChange"
                            />
                            <InputError :message="form.errors.main_image" />

                            <div v-if="mainImagePreviewUrl" class="mt-2">
                                <div class="relative inline-block">
                                    <img
                                        :src="mainImagePreviewUrl"
                                        alt="Ảnh chính"
                                        class="h-24 w-24 rounded-md border object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="absolute right-1 top-1 h-7 w-7 rounded-full bg-white/90 text-red-600 hover:bg-white"
                                        @click="removeMainImage"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div
                                v-else-if="props.mainImage && !form.remove_main_image"
                                class="mt-2"
                            >
                                <div class="relative inline-block">
                                    <img
                                        :src="props.mainImage.url"
                                        alt="Ảnh chính"
                                        class="h-24 w-24 rounded-md border object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="absolute right-1 top-1 h-7 w-7 rounded-full bg-white/90 text-red-600 hover:bg-white"
                                        @click="removeExistingMainImage"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="slide_images"
                                >Ảnh slide (tối đa 10)</Label
                            >
                            <input
                                id="slide_images"
                                ref="slideImagesInputRef"
                                type="file"
                                accept="image/*"
                                multiple
                                :disabled="maxNewSlideImages === 0"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                                @change="onSlideImagesChange"
                            />
                            <p class="text-xs text-gray-500">
                                Bạn có thể chọn thêm tối đa {{ maxNewSlideImages }} ảnh
                                (tổng tối đa 10 ảnh slide).
                            </p>
                            <InputError :message="form.errors.slide_images" />

                            <div
                                class="mt-2 flex flex-wrap gap-2"
                            >
                                <div
                                    v-for="img in props.slideImages?.filter((x) => !form.remove_slide_media_ids.includes(x.id))"
                                    :key="`existing_${img.id}`"
                                    class="relative"
                                >
                                    <img
                                        :src="img.url"
                                        alt="Ảnh slide"
                                        class="h-20 w-20 rounded-md border object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="absolute right-1 top-1 h-7 w-7 rounded-full bg-white/90 text-red-600 hover:bg-white"
                                        @click="removeExistingSlideImage(img.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div
                                    v-for="(url, idx) in slideImagePreviewUrls"
                                    :key="`new_${idx}`"
                                    class="relative"
                                >
                                    <img
                                        :src="url"
                                        alt="Ảnh slide"
                                        class="h-20 w-20 rounded-md border object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="absolute right-1 top-1 h-7 w-7 rounded-full bg-white/90 text-red-600 hover:bg-white"
                                        @click="removeSlideImage(idx)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Thẻ
                        </h2>
                        <Button
                            v-if="props.site?.slug"
                            type="button"
                            size="sm"
                            variant="outline"
                            class="flex items-center gap-2"
                            @click="showQuickTagDialog = true"
                        >
                            <Plus class="h-4 w-4" />
                            Tạo tag mới
                        </Button>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        Gắn thẻ để lọc và tìm kiếm nhanh
                    </p>

                    <div
                        v-if="props.tags.length === 0"
                        class="text-sm text-gray-600"
                    >
                        Chưa có tag nào.
                    </div>
                    <div
                        v-else
                        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"
                    >
                        <button
                            v-for="tag in props.tags"
                            :key="tag.id"
                            type="button"
                            @click="toggleTag(tag.id)"
                            class="flex items-center justify-between gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-left text-sm hover:bg-gray-50"
                        >
                            <span class="truncate">{{ tag.name }}</span>
                            <Badge
                                variant="secondary"
                                :class="
                                    form.tags.includes(tag.id)
                                        ? 'border border-green-200 bg-green-50 text-green-700'
                                        : 'text-gray-600'
                                "
                            >
                                <Check
                                    v-if="form.tags.includes(tag.id)"
                                    class="h-4 w-4 text-green-600"
                                />
                                <span v-else>—</span>
                            </Badge>
                        </button>
                    </div>
                    <InputError :message="tagsError" />
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Thuộc tính & Biến thể
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Thay đổi thuộc tính sẽ tạo lại biến thể + SKU. Tối đa
                            100 tổ hợp.
                        </p>
                    </div>

                    <div class="mb-6 flex gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="activeVariantTab === 'attributes' ? 'default' : 'outline'"
                            @click="activeVariantTab = 'attributes'"
                        >
                            Thuộc tính & Biến thể
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="activeVariantTab === 'preview' ? 'default' : 'outline'"
                            @click="activeVariantTab = 'preview'"
                        >
                            Xem trước biến thể
                        </Button>
                    </div>

                    <div v-if="activeVariantTab === 'attributes'">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1 space-y-2">
                            <Label>Thêm thuộc tính</Label>
                            <Select v-model="selectedAttributeId">
                                <SelectTrigger>
                                    <SelectValue placeholder="Chọn thuộc tính..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">—</SelectItem>
                                    <SelectItem
                                        v-for="attr in props.attributes"
                                        :key="attr.id"
                                        :value="attr.id.toString()"
                                    >
                                        {{ attr.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            class="flex items-center gap-2"
                            @click="addAttribute"
                        >
                            <Plus class="h-4 w-4" />
                            Thêm
                        </Button>
                    </div>

                    <div v-if="form.attributes.length === 0" class="mt-6">
                        <div class="text-sm text-gray-600">
                            Nếu không chọn attributes, hệ thống sẽ tạo 1 SKU =
                            product_code.
                        </div>
                    </div>

                    <div v-else class="mt-6 space-y-6">
                        <div
                            v-for="attr in selectedAttributes"
                            :key="attr.id"
                            class="rounded-lg border border-gray-200 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        {{ attr.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        order: {{ attr.order }} • code: {{ attr.code }}
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-600 hover:text-red-700"
                                    @click="removeAttribute(attr.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div class="rounded-md border border-dashed border-gray-300 p-3">
                                    <Label class="mb-2 block text-xs text-gray-600">
                                        Thêm nhanh values (phân tách bằng dấu phẩy)
                                    </Label>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <Input
                                            :model-value="quickValuesByAttribute[attr.id] ?? ''"
                                            placeholder="Ví dụ: Xanh, Đỏ, Vàng"
                                            @update:model-value="
                                                quickValuesByAttribute = {
                                                    ...quickValuesByAttribute,
                                                    [attr.id]: ($event as string) ?? '',
                                                }
                                            "
                                            @keydown.enter.prevent="addValuesFromQuickInput(attr.id)"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="whitespace-nowrap"
                                            @click="addValuesFromQuickInput(attr.id)"
                                        >
                                            Tạo nhiều value
                                        </Button>
                                    </div>
                                </div>
                                <div class="hidden grid-cols-12 gap-2 text-xs text-gray-500 md:grid">
                                    <div class="col-span-2">Mã *</div>
                                    <div class="col-span-3">Giá trị *</div>
                                    <div class="col-span-1">Thứ tự *</div>
                                    <div class="col-span-2">Phụ phí giá nhập</div>
                                    <div class="col-span-2">Phụ phí giá đối tác</div>
                                    <div class="col-span-2">Phụ phí giá bán lẻ</div>
                                </div>

                                <div
                                    v-for="(value, idx) in form.attributes.find((a) => a.attribute_id === attr.id)?.values"
                                    :key="idx"
                                    class="grid grid-cols-1 gap-2 md:grid-cols-12"
                                >
                                    <div class="md:col-span-2">
                                        <Input v-model="value.code" placeholder="VD: S" />
                                        <InputError
                                            :message="errorFor(`attributes.${attributeBlockIndex(attr.id)}.values.${idx}.code`)"
                                        />
                                    </div>
                                    <div class="md:col-span-3">
                                        <Input v-model="value.value" placeholder="VD: Small" />
                                        <InputError
                                            :message="errorFor(`attributes.${attributeBlockIndex(attr.id)}.values.${idx}.value`)"
                                        />
                                    </div>
                                    <div class="md:col-span-1">
                                        <Input
                                            v-model.number="value.order"
                                            type="number"
                                            min="0"
                                            step="1"
                                        />
                                        <InputError
                                            :message="errorFor(`attributes.${attributeBlockIndex(attr.id)}.values.${idx}.order`)"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <Input
                                            v-model.number="value.purchase_addition_value"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <Input
                                            v-model.number="value.partner_addition_value"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <Input
                                            v-model.number="value.addition_value"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="md:col-span-12">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700"
                                            @click="removeValue(attr.id, idx)"
                                            :disabled="
                                                (form.attributes.find((a) => a.attribute_id === attr.id)?.values.length ?? 0) <= 1
                                            "
                                        >
                                            Xóa value
                                        </Button>
                                    </div>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-2"
                                    @click="addValue(attr.id)"
                                >
                                    <Plus class="h-4 w-4" />
                                    Thêm value
                                </Button>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-if="combinationsTooMany"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                        >
                            <div class="flex items-start gap-3">
                                <AlertTriangle class="h-5 w-5 shrink-0" />
                                <div>
                                    <div class="font-medium">
                                        Quá nhiều tổ hợp
                                    </div>
                                    <div class="mt-1">
                                        Hiện tại:
                                        <b>{{ combinationsCount }}</b>
                                        biến thể (tối đa 100). Vui lòng giảm số
                                        lượng giá trị.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <div>
                                <div class="font-medium text-gray-900">
                                    Xem trước biến thể
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ combinationsCount }} tổ hợp
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div
                                    v-for="row in previewVariantRows"
                                    :key="row.variant.key"
                                    class="rounded-md border border-gray-200 p-4"
                                >
                                    <div
                                        class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-start"
                                    >
                                        <div class="space-y-1 lg:col-span-3">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ row.variant.sku }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ row.variant.label }}
                                            </div>
                                        </div>

                                        <div class="space-y-3 lg:col-span-5">
                                            <div>
                                                <Label class="mb-1 block text-xs">Nguồn ảnh</Label>
                                                <select
                                                    class="h-9 w-full rounded-md border border-gray-300 bg-white px-3 text-sm"
                                                    :value="getVariantImageSource(row.variant.key)"
                                                    @change="
                                                        setVariantImageSource(
                                                            row.variant.key,
                                                            ($event.target as HTMLSelectElement).value,
                                                        )
                                                    "
                                                >
                                                    <option
                                                        v-if="hasVariantUploadSource(row.variant.key)"
                                                        value="upload"
                                                    >
                                                        Ảnh upload
                                                    </option>
                                                    <option value="main">
                                                        Sử dụng ảnh chính
                                                    </option>
                                                    <option
                                                        v-for="(img, idx) in props.slideImages?.filter((x) => !form.remove_slide_media_ids.includes(x.id))"
                                                        :key="`existing_${img.id}`"
                                                        :value="`existing:${img.id}`"
                                                    >
                                                        Ảnh slide {{ idx + 1 }}
                                                    </option>
                                                    <option
                                                        v-for="(url, idx) in slideImagePreviewUrls"
                                                        :key="`new_${idx}`"
                                                        :value="`new:${idx}`"
                                                    >
                                                        Ảnh slide
                                                        {{
                                                            (props.slideImages?.filter((x) => !form.remove_slide_media_ids.includes(x.id)).length ?? 0) +
                                                            idx +
                                                            1
                                                        }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <Label class="mb-1 block text-xs">
                                                    Hoặc upload riêng cho biến thể
                                                </Label>
                                                <input
                                                    :ref="(el) => setVariantFileInputRef(row.variant.key, el)"
                                                    type="file"
                                                    accept="image/*"
                                                    class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-2 file:py-1.5 file:text-xs file:font-medium"
                                                    @change="setVariantUploadFile(row.variant.key, $event)"
                                                />
                                                <div
                                                    v-if="getVariantUploadFileName(row.variant.key)"
                                                    class="mt-1 flex flex-wrap items-center gap-2"
                                                >
                                                    <p class="text-xs text-gray-600">
                                                        File:
                                                        {{ getVariantUploadFileName(row.variant.key) }}
                                                    </p>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="h-7 gap-1 px-2 text-red-600 hover:text-red-700"
                                                        @click="clearVariantImage(row.variant.key)"
                                                    >
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                        Xóa ảnh upload
                                                    </Button>
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                @click="clearVariantImage(row.variant.key)"
                                            >
                                                Xóa chọn ảnh
                                            </Button>
                                        </div>

                                        <div class="lg:col-span-4">
                                            <Label class="mb-2 block text-xs text-gray-600">
                                                Xem trước
                                            </Label>
                                            <div
                                                class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-md border border-gray-200 bg-gray-50"
                                            >
                                                <img
                                                    v-if="row.previewUrl"
                                                    :src="row.previewUrl"
                                                    alt=""
                                                    class="h-full w-full object-cover"
                                                />
                                                <span
                                                    v-else
                                                    class="px-2 text-center text-xs text-gray-400"
                                                >
                                                    Chưa có ảnh xem trước
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.attributes" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-2">
                    <Button
                        :as="Link"
                        :href="
                            props.site?.slug
                                ? ProductsIndex.url({ site: props.site.slug })
                                : '#'
                        "
                        variant="outline"
                        type="button"
                    >
                        Hủy
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || combinationsTooMany"
                        class="min-w-36"
                    >
                        <span v-if="form.processing">Đang lưu...</span>
                        <span v-else>Lưu thay đổi</span>
                    </Button>
                </div>
            </form>
        </div>

        <QuickTagCreateDialog
            v-if="showQuickTagDialog"
            v-model:open="showQuickTagDialog"
            :site-slug="props.site?.slug"
            @created="onQuickTagCreated"
        />
    </AppLayout>
</template>
