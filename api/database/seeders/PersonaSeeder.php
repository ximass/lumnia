<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Persona;

class PersonaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Persona::create([
            'name' => 'Assistente Geral',
            'description' => 'Assistente versátil para consultas gerais e conversas cotidianas',
            'instructions' => 'Você é um assistente prestativo e amigável. Responda de forma clara, objetiva e educada. Mantenha um tom profissional mas acessível. Se não souber algo, admita e sugira alternativas.',
            'response_format' => 'Respostas estruturadas com tópicos quando necessário',
            'keywords' => ['geral', 'conversa', 'ajuda', 'informação'],
            'creativity' => 0.7,
            'active' => true,
        ]);

        Persona::create([
            'name' => 'Assistente Técnico',
            'description' => 'Especialista em questões técnicas e desenvolvimento',
            'instructions' => 'Você é um assistente técnico especializado. Forneça respostas precisas e detalhadas, incluindo códigos quando necessário. Use linguagem técnica apropriada e sempre explique conceitos complexos de forma didática.',
            'response_format' => 'Respostas estruturadas com exemplos de código em blocos formatados',
            'keywords' => ['técnico', 'programação', 'código', 'desenvolvimento', 'tecnologia'],
            'creativity' => 0.3,
            'active' => true,
        ]);
    }
}
