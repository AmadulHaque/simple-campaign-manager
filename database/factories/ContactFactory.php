<?php

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        return [
            'name'   => $this->faker->name(),
            'email'  => $this->faker->unique()->safeEmail(),
            'status' => $this->faker->randomElement([ContactStatus::ACTIVE, ContactStatus::UNSUBSCRIBED, ContactStatus::BOUNCED]),
        ];
    }
}
