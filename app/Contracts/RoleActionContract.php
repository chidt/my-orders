<?php

namespace App\Contracts;

interface RoleActionContract
{
    public function execute(mixed ...$parameters): mixed;
}
