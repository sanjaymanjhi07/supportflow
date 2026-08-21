<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(['open', 'pending', 'resolved', 'closed']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
        ];
    }

    public function forTenant(User $requester): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $requester->tenant_id,
            'requester_id' => $requester->id,
        ]);
    }
}
