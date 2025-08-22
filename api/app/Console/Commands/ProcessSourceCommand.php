<?php

namespace App\Console\Commands;

use App\Jobs\ParseSourceJob;
use App\Models\Source;
use Illuminate\Console\Command;

class ProcessSourceCommand extends Command
{
    protected $signature = 'source:process {source_id : The UUID of the source to process}';

    protected $description = 'Process a source by extracting text, creating chunks and generating embeddings';

    public function handle()
    {
        $sourceId = $this->argument('source_id');
        
        $source = Source::find($sourceId);
        
        if (!$source) {
            $this->error("Source with ID {$sourceId} not found.");
            return 1;
        }

        $this->info("Processing source: {$source->source_identifier}");
        $this->info("Status: {$source->status}");

        if (in_array($source->status, ['processing', 'embedding', 'chunked'])) {
            $this->warn('Source is already being processed. Use --force to override.');
            if (!$this->confirm('Continue anyway?')) {
                return 0;
            }
        }

        try {
            ParseSourceJob::dispatch($sourceId);
            $this->info('Processing job dispatched successfully!');
            $this->info('Monitor the queue worker logs to track progress.');
            
        } catch (\Exception $e) {
            $this->error("Failed to dispatch processing job: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
