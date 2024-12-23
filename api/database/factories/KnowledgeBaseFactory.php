<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KnowledgeBase>
 */
class KnowledgeBaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numero = random_int(1, 20) . '-' . random_int(1, 20);

        return [
            'title' => 'Base ' . $numero,
            'content' => 'Conteúdo ' . $numero,
        ];
    }
}
