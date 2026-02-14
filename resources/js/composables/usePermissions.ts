import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { User } from '@/types';

type Permissions = Record<string, boolean>;

export function usePermissions() {
    const page = usePage();
    const user = computed<User>(() => page.props.auth.user);
    const permissions = computed<Permissions>(() => user.value?.can ?? {});

    const can = (permission: string): boolean => permissions.value[permission];
    const canAny = (perms: string[]): boolean => perms.some(can);
    const canAll = (perms: string[]): boolean => perms.every(can);

    return {
        can,
        canAny,
        canAll,
    };
}
