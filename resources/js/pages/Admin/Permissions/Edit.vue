<template>
    <div>
        <h1 class="mb-4 text-2xl font-bold">Edit Permission</h1>
        <Form
            :action="route('admin.permissions.update', permission.id)"
            method="post"
        >
            <input type="hidden" name="_method" value="put" />
            <div class="mb-4">
                <label class="mb-1 block font-semibold">Name</label>
                <input
                    v-model="form.name"
                    name="name"
                    class="input input-bordered w-full"
                    required
                />
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </Form>
    </div>
</template>

<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
const { permission } = defineProps(['permission']);
const form = useForm({ name: permission.name });
</script>
