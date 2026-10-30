<?php

namespace Database\Factories;

use App\Enums\Prioridade;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Um ticket válido. O status não é informado:
     * a máquina de estados define Aberto ao criar, como no sistema real.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(4),
            'descricao' => fake()->paragraph(),
            'prioridade' => fake()->randomElement(Prioridade::cases()),
            'responsavel_id' => User::factory(),
        ];
    }
}