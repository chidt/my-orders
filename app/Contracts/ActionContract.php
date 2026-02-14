<?php

namespace App\Contracts;

interface ActionContract
{
    /**
     * Execute the action.
     */
    public function handle(mixed ...$parameters): mixed;
}
