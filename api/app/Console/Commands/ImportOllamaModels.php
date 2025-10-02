<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;

class ImportOllamaModels extends Command
{
    /**
     * Nome/assinatura do comando Artisan.
     *
     * @var string
     */
    protected $signature = 'import:ollama-models';

    /**
     * Descrição do comando.
     *
     * @var string
     */
    protected $description = 'Importa as models existentes do endpoint do Ollama para a entidade knowledge_base';

    /**
     * Executa o comando.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando a importação de models do Ollama...');

        $ollamaConfig = config('providers.llm.ollama');
        if (!$ollamaConfig || !($ollamaConfig['enabled'] ?? false)) {
            $this->error('Ollama provider não está habilitado.');
            return 1;
        }

        $baseUrl = rtrim($ollamaConfig['base_url'], '/');
        $response = Http::get($baseUrl . '/api/tags');

        if ($response->failed()) {
            $this->error('Falha ao acessar o endpoint.');
            return 1;
        }

        $data = $response->json();

        if (!isset($data['models'])) {
            $this->error('Formato de resposta inválido.');
            return 1;
        }

        foreach ($data['models'] as $modelData) {
            KnowledgeBase::updateOrCreate(
                ['title' => $modelData['name']],
                [
                    'modified_at' => $modelData['modified_at'],
                    'size' => $modelData['size'],
                    'digest' => $modelData['digest'],
                    'details' => json_encode($modelData['details']),
                ]
            );
        }

        $this->info('Importação concluída com sucesso.');
        return 0;
    }
}