<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MongoDB\Client;
use Illuminate\Support\Facades\Config;

class SetupMongoDBIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongodb:setup-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MongoDB koleksiyonları için indeksleri oluşturur';

    /**
     * MongoDB bağlantısı
     *
     * @var \MongoDB\Database
     */
    protected $database;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('MongoDB indeksleri oluşturuluyor...');

        // MongoDB bağlantısı
        $connectionString = Config::get('database.connections.mongodb.dsn');
        $databaseName = Config::get('database.connections.mongodb.database');
        
        try {
            $client = new Client($connectionString);
            $this->database = $client->selectDatabase($databaseName);

            // İndeksleri oluştur
            $this->setupAgoraChannelGiftIndexes();
            $this->setupAgoraChannelViewerIndexes();
            $this->setupAgoraChannelMessageIndexes();

            $this->info('MongoDB indeksleri başarıyla oluşturuldu!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('MongoDB indeksleri oluşturulurken bir hata oluştu: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * AgoraChannelGift koleksiyonu için indeksleri oluşturur
     */
    protected function setupAgoraChannelGiftIndexes()
    {
        $this->info('AgoraChannelGift indeksleri oluşturuluyor...');
        
        $collection = $this->database->agora_channel_gift;
        
        // Basit indeksler
        $collection->createIndex(['agora_channel_id' => 1]);
        $collection->createIndex(['user_id' => 1]);
        $collection->createIndex(['gift_id' => 1]);
        $collection->createIndex(['created_at' => -1]);
        $collection->createIndex(['is_featured' => 1]);
        
        // Bileşik indeksler
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'created_at' => -1
        ]);
        
        $collection->createIndex([
            'user_id' => 1, 
            'created_at' => -1
        ]);
        
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'gift_id' => 1
        ]);
        
        $collection->createIndex([
            'coin_value' => -1, 
            'created_at' => -1
        ]);
    }

    /**
     * AgoraChannelViewer koleksiyonu için indeksleri oluşturur
     */
    protected function setupAgoraChannelViewerIndexes()
    {
        $this->info('AgoraChannelViewer indeksleri oluşturuluyor...');
        
        $collection = $this->database->agora_channel_viewers;
        
        // Koleksiyon oluşturma
        if (!$this->collectionExists('agora_channel_viewers')) {
            $this->database->createCollection('agora_channel_viewers');
        }
        
        // Basit indeksler
        $collection->createIndex(['agora_channel_id' => 1]);
        $collection->createIndex(['user_id' => 1]);
        $collection->createIndex(['status' => 1]);
        $collection->createIndex(['joined_at' => -1]);
        $collection->createIndex(['left_at' => -1]);
        
        // Bileşik indeksler
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'status' => 1
        ]);
        
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'joined_at' => -1
        ]);
        
        $collection->createIndex([
            'user_id' => 1, 
            'watch_duration' => -1
        ]);
        
        $collection->createIndex([
            'user_id' => 1, 
            'coins_spent' => -1
        ]);
        
        $collection->createIndex([
            'roles' => 1, 
            'agora_channel_id' => 1
        ]);
    }

    /**
     * AgoraChannelMessage koleksiyonu için indeksleri oluşturur
     */
    protected function setupAgoraChannelMessageIndexes()
    {
        $this->info('AgoraChannelMessage indeksleri oluşturuluyor...');
        
        // Koleksiyon oluşturma
        if (!$this->collectionExists('agora_channel_messages')) {
            $this->database->createCollection('agora_channel_messages');
        }
        
        $collection = $this->database->agora_channel_messages;
        
        // Basit indeksler
        $collection->createIndex(['agora_channel_id' => 1]);
        $collection->createIndex(['user_id' => 1]);
        $collection->createIndex(['is_pinned' => 1]);
        $collection->createIndex(['is_blocked' => 1]);
        $collection->createIndex(['timestamp' => -1]);
        $collection->createIndex(['parent_message_id' => 1]);
        $collection->createIndex(['gift_id' => 1]);
        
        // Metin araması için indeks
        $collection->createIndex(['message' => 'text']);
        
        // Bileşik indeksler
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'timestamp' => -1
        ]);
        
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'is_pinned' => 1
        ]);
        
        $collection->createIndex([
            'user_id' => 1, 
            'timestamp' => -1
        ]);
        
        $collection->createIndex([
            'agora_channel_id' => 1, 
            'gift_id' => 1, 
            'timestamp' => -1
        ]);
    }

    /**
     * Koleksiyon varlığını kontrol eder
     *
     * @param string $name
     * @return bool
     */
    protected function collectionExists(string $name): bool
    {
        $collections = $this->database->listCollectionNames();
        foreach ($collections as $collection) {
            if ($collection === $name) {
                return true;
            }
        }
        
        return false;
    }
}
