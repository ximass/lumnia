<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use App\Models\KnowledgeBase;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //User::factory(10)->create();
        //Chat::factory(10)->create();
        //Message::factory(10)->create();
        //KnowledgeBase::factory(10)->create();

        User::factory()->create([
            'name' => 'Mateus Schmitz',
            'email' => 'mateus.schmitz@univates.br',
            'password' => bcrypt('123456'),
            'admin' => true,
        ]);
    }
}
