<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Edit Permission</h1>
    <Form :action="route('admin.permissions.update', permission.id)" method="post">
      <input type="hidden" name="_method" value="put" />
      <div class="mb-4">
        <label class="block mb-1 font-semibold">Name</label>
        <input v-model="form.name" name="name" class="input input-bordered w-full" required />
        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
      </div>
      <button type="submit" class="btn btn-primary">Update</button>
    </Form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
const { permission } = defineProps(['permission']);
const form = useForm({ name: permission.name });
</script>

