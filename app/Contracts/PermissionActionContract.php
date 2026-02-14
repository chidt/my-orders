<?php

namespace App\Contracts;

interface PermissionActionContract
{
    public function execute(mixed ...$parameters): mixed;
}
