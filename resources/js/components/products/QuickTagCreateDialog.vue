<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    siteSlug?: string;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [tagId: number];
}>();

const name = ref('');
const slug = ref('');
const errors = ref<Record<string, string>>({});
const generalError = ref('');
const submitting = ref(false);

function reset(): void {
    name.value = '';
    slug.value = '';
    errors.value = {};
    generalError.value = '';
}

function getXsrfToken(): string | null {
    const raw = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')
        .slice(1)
        .join('=');
    return raw ? decodeURIComponent(raw) : null;
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            reset();
        }
    },
);

function close(): void {
    emit('update:open', false);
}

async function submit(): Promise<void> {
    const site = props.siteSlug;
    if (!site) {
        return;
    }

    submitting.value = true;
    errors.value = {};
    generalError.value = '';

    const xsrf = getXsrfToken();
    const body: Record<string, string> = { name: name.value.trim() };
    const slugTrimmed = slug.value.trim();
    if (slugTrimmed) {
        body.slug = slugTrimmed;
    }

    try {
        const res = await fetch(`/${site}/tags/quick-store`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });

        const data = (await res.json().catch(() => ({}))) as {
            message?: string;
            errors?: Record<string, string[]>;
            tag?: { id: number; name: string };
        };

        if (res.status === 422 && data.errors) {
            const mapped: Record<string, string> = {};
            for (const [key, messages] of Object.entries(data.errors)) {
                mapped[key] = Array.isArray(messages)
                    ? messages[0]
                    : String(messages);
            }
            errors.value = mapped;
            return;
        }

        if (!res.ok) {
            generalError.value = data.message ?? 'Không thể tạo thẻ.';
            return;
        }

        const newId = data.tag?.id;
        if (newId == null) {
            generalError.value = 'Phản hồi không hợp lệ.';
            return;
        }

        await router.reload({ only: ['tags'] });
        emit('created', newId);
        close();
        reset();
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Tạo thẻ mới</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="quick-tag-name">Tên thẻ *</Label>
                    <Input
                        id="quick-tag-name"
                        v-model="name"
                        type="text"
                        class="block w-full"
                        placeholder="Ví dụ: Bán chạy, Mới, Sale..."
                        autocomplete="off"
                    />
                    <InputError class="mt-1" :message="errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="quick-tag-slug">Đường dẫn (slug)</Label>
                    <Input
                        id="quick-tag-slug"
                        v-model="slug"
                        type="text"
                        class="block w-full"
                        placeholder="ban-chay (tự động nếu để trống)"
                        autocomplete="off"
                    />
                    <InputError class="mt-1" :message="errors.slug" />
                </div>

                <p v-if="generalError" class="text-sm text-red-600">
                    {{ generalError }}
                </p>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="submitting"
                        @click="close"
                    >
                        Hủy
                    </Button>
                    <Button type="submit" :disabled="submitting || !siteSlug">
                        {{ submitting ? 'Đang lưu…' : 'Tạo thẻ' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
