<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class StorePermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): Permission
    {
        $request = $parameters[0];

        return Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);
    }

    public function __invoke(Request $request): Permission
    {
        return $this->handle($request);
    }
}
