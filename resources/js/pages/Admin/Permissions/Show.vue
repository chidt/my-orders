<template>
    <div>
        <h1 class="mb-4 text-2xl font-bold">Permission Details</h1>
        <div class="mb-4"><strong>Name:</strong> {{ permission.name }}</div>
        <div class="flex space-x-2">
            <Link
                v-if="can.edit"
                :href="route('admin.permissions.edit', permission.id)"
                class="btn btn-sm"
                >Edit</Link
            >
            <form
                v-if="can.delete"
                :action="route('admin.permissions.destroy', permission.id)"
                method="post"
                class="inline"
            >
                <input type="hidden" name="_method" value="delete" />
                <button type="submit" class="btn btn-sm btn-danger">
                    Delete
                </button>
            </form>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
const { permission, can } = defineProps(['permission', 'can']);
</script>
