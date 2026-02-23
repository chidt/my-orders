<?php

namespace App\Actions\Fortify;

use App\Concerns\AddressValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Concerns\SiteValidationRules;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use AddressValidationRules, PasswordValidationRules, ProfileValidationRules, SiteValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws \Throwable
     */
    public function create(array $input): User
    {
        \Log::info($input);
        Validator::make($input, [
            ...$this->profileRules(),
            ...$this->siteRules(),
            ...$this->addressRules($input),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $site = Site::create([
                'name' => $input['site_name'],
                'slug' => $input['site_slug'],
                'description' => $input['site_description'] ?? null,
            ]);

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone_number' => $input['phone_number'],
                'password' => $input['password'],
                'site_id' => $site->id,
            ]);

            // Create user address
            $user->addresses()->create([
                'address' => $input['address'],
                'ward_id' => $input['ward_id'],
            ]);

            // Assign default SiteAdmin role to newly registered user
            $user->assignRole('SiteAdmin');

            $site->update(['user_id' => $user->id]);

            return $user;
        });
    }
}
