<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FeedConsumerService;

class ConsumeJsonFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:consume {file? : The path to the JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengkonsumsi data JSON feed submission dan menyimpannya ke database';

    /**
     * Execute the console command.
     */
    public function handle(FeedConsumerService $consumer) : int
    {
        $filePath =  $this->argument('file') ?? base_path('database/submission.json');

        if (!file_exists($filePath)) {
            $this->error('File tidak ditemukan: ' . $filePath);
            return Command::FAILURE;;
        }

        $startTime = microtime(true);

        $startMemory = memory_get_usage(true);

        try{
            $stats = $consumer->consumeFromFile($filePath);

            $duration = round(microtime(true) - $startTime, 4);
            $memoryUsed = round((memory_get_usage(true) - $startMemory)/1024/1024, 2);

            $this->newLine();
            $this->info("===================================================");
            $this->info("=== HASIL KONSUMSI FILE JSON FEED SUBMISSION ===");
            $this->info("===================================================");
            $this->line("Sections dibuat/diupdate : <comment>{$stats['sections']}</comment>");
            $this->line("Fields dibuat/diupdate   : <comment>{$stats['fields']}</comment>");
            $this->line("Options dibuat/diupdate  : <comment>{$stats['options']}</comment>");
            $this->line("Answers disimpan         : <comment>{$stats['answers']}</comment>");
            $this->line("Waktu Eksekusi           : <comment>{$duration} detik</comment>");
            $this->line("Memori yang Digunakan    : <comment>{$memoryUsed} MB</comment>");
            $this->info("==========================================");

            return Command::SUCCESS;

        }catch(\Exception $e){
            $this->error('Gagal mengkonsumsi feed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
