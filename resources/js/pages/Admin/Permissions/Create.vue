<template>

</script>
const form = useForm({ name: '' });
import { useForm, router } from '@inertiajs/vue3';
<script setup>

</template>
  </div>
    </Form>
      <button type="submit" class="btn btn-primary">Create</button>
      </div>
        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
        <input v-model="form.name" name="name" class="input input-bordered w-full" required />
        <label class="block mb-1 font-semibold">Name</label>
      <div class="mb-4">
    <Form :action="route('admin.permissions.store')" method="post">
    <h1 class="text-2xl font-bold mb-4">Create Permission</h1>
  <div>
