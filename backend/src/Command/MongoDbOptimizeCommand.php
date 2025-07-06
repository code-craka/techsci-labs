<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mongodb:optimize',
    description: 'Optimize MongoDB collections with proper indexing',
)]
class MongoDbOptimizeCommand extends Command
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('drop-indexes', 'd', InputOption::VALUE_NONE, 'Drop existing indexes before creating new ones')
            ->addOption('analyze', 'a', InputOption::VALUE_NONE, 'Analyze query performance')
            ->setHelp('Create optimized indexes for all MongoDB collections')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dropIndexes = $input->getOption('drop-indexes');
        $analyze = $input->getOption('analyze');
        
        $io->title('MongoDB Optimization');
        
        try {
            $database = $this->documentManager->getDocumentDatabase();
            
            if ($analyze) {
                $this->analyzePerformance($io);
            }
            
            // Define optimized indexes for each collection
            $indexDefinitions = $this->getIndexDefinitions();
            
            foreach ($indexDefinitions as $collectionName => $indexes) {
                $io->section("Optimizing collection: $collectionName");
                
                $collection = $database->selectCollection($collectionName);
                
                if ($dropIndexes) {
                    $io->info('Dropping existing indexes...');
                    $this->dropNonDefaultIndexes($collection);
                }
                
                foreach ($indexes as $indexName => $indexDef) {
                    $io->info("Creating index: $indexName");
                    $this->createIndex($collection, $indexDef['keys'], $indexDef['options'], $io);
                }
            }
            
            // Run collection statistics
            $this->showCollectionStats($io);
            
            $io->success('MongoDB optimization completed successfully');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error(sprintf('Optimization failed: %s', $e->getMessage()));
            $this->logger->error('MongoDB optimization error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Define optimized indexes for all collections
     */
    private function getIndexDefinitions(): array
    {
        return [
            // Domain collection indexes
            'Domain' => [
                'domain_name_unique' => [
                    'keys' => ['name' => 1],
                    'options' => ['unique' => true, 'background' => true]
                ],
                'domain_status' => [
                    'keys' => ['status' => 1],
                    'options' => ['background' => true]
                ],
                'domain_owner' => [
                    'keys' => ['owner' => 1],
                    'options' => ['background' => true]
                ],
                'domain_created' => [
                    'keys' => ['createdAt' => -1],
                    'options' => ['background' => true]
                ]
            ],
            
            // EmailAccount collection indexes
            'EmailAccount' => [
                'email_unique' => [
                    'keys' => ['email' => 1],
                    'options' => ['unique' => true, 'background' => true]
                ],
                'email_domain' => [
                    'keys' => ['domain' => 1],
                    'options' => ['background' => true]
                ],
                'email_status' => [
                    'keys' => ['status' => 1],
                    'options' => ['background' => true]
                ],
                'email_created' => [
                    'keys' => ['createdAt' => -1],
                    'options' => ['background' => true]
                ],
                'email_quota' => [
                    'keys' => ['quotaUsed' => -1],
                    'options' => ['background' => true]
                ]
            ],
            
            // Mailbox collection indexes
            'Mailbox' => [
                'mailbox_account' => [
                    'keys' => ['account' => 1],
                    'options' => ['background' => true]
                ],
                'mailbox_name' => [
                    'keys' => ['name' => 1, 'account' => 1],
                    'options' => ['background' => true]
                ],
                'mailbox_type' => [
                    'keys' => ['type' => 1],
                    'options' => ['background' => true]
                ],
                'mailbox_created' => [
                    'keys' => ['createdAt' => -1],
                    'options' => ['background' => true]
                ]
            ],
            
            // Message collection indexes
            'Message' => [
                'message_id_unique' => [
                    'keys' => ['messageId' => 1],
                    'options' => ['unique' => true, 'background' => true]
                ],
                'message_mailbox' => [
                    'keys' => ['mailbox' => 1],
                    'options' => ['background' => true]
                ],
                'message_date' => [
                    'keys' => ['date' => -1],
                    'options' => ['background' => true]
                ],
                'message_from' => [
                    'keys' => ['from.email' => 1],
                    'options' => ['background' => true]
                ],
                'message_to' => [
                    'keys' => ['to.email' => 1],
                    'options' => ['background' => true]
                ],
                'message_subject' => [
                    'keys' => ['subject' => 'text'],
                    'options' => ['background' => true]
                ],
                'message_flags' => [
                    'keys' => ['flags' => 1],
                    'options' => ['background' => true]
                ],
                'message_thread' => [
                    'keys' => ['threadId' => 1],
                    'options' => ['background' => true, 'sparse' => true]
                ],
                'message_size' => [
                    'keys' => ['size' => -1],
                    'options' => ['background' => true]
                ],
                'message_compound_search' => [
                    'keys' => ['mailbox' => 1, 'date' => -1, 'flags' => 1],
                    'options' => ['background' => true]
                ]
            ],
            
            // Attachment collection indexes
            'Attachment' => [
                'attachment_message' => [
                    'keys' => ['message' => 1],
                    'options' => ['background' => true]
                ],
                'attachment_filename' => [
                    'keys' => ['filename' => 1],
                    'options' => ['background' => true]
                ],
                'attachment_type' => [
                    'keys' => ['mimeType' => 1],
                    'options' => ['background' => true]
                ],
                'attachment_size' => [
                    'keys' => ['size' => -1],
                    'options' => ['background' => true]
                ],
                'attachment_hash' => [
                    'keys' => ['hash' => 1],
                    'options' => ['background' => true, 'sparse' => true]
                ],
                'attachment_security' => [
                    'keys' => ['securityScan.status' => 1],
                    'options' => ['background' => true, 'sparse' => true]
                ]
            ],
            
            // Token collection indexes
            'Token' => [
                'token_unique' => [
                    'keys' => ['token' => 1],
                    'options' => ['unique' => true, 'background' => true]
                ],
                'token_user' => [
                    'keys' => ['user' => 1],
                    'options' => ['background' => true]
                ],
                'token_type' => [
                    'keys' => ['type' => 1],
                    'options' => ['background' => true]
                ],
                'token_expiry' => [
                    'keys' => ['expiresAt' => 1],
                    'options' => ['background' => true, 'expireAfterSeconds' => 0]
                ],
                'token_created' => [
                    'keys' => ['createdAt' => -1],
                    'options' => ['background' => true]
                ]
            ],
            
            // AuditLog collection indexes
            'AuditLog' => [
                'audit_timestamp' => [
                    'keys' => ['timestamp' => -1],
                    'options' => ['background' => true, 'expireAfterSeconds' => 2592000] // 30 days
                ],
                'audit_user' => [
                    'keys' => ['user_id' => 1],
                    'options' => ['background' => true, 'sparse' => true]
                ],
                'audit_operation' => [
                    'keys' => ['operation' => 1],
                    'options' => ['background' => true]
                ],
                'audit_ip' => [
                    'keys' => ['ip_address' => 1],
                    'options' => ['background' => true]
                ],
                'audit_risk' => [
                    'keys' => ['risk_level' => 1],
                    'options' => ['background' => true]
                ],
                'audit_compound' => [
                    'keys' => ['operation' => 1, 'timestamp' => -1],
                    'options' => ['background' => true]
                ]
            ]
        ];
    }

    /**
     * Create index on collection
     */
    private function createIndex($collection, array $keys, array $options, SymfonyStyle $io): void
    {
        try {
            $result = $collection->createIndex($keys, $options);
            $io->info(sprintf('Index created: %s', $result));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                $io->comment('Index already exists, skipping');
            } else {
                throw $e;
            }
        }
    }

    /**
     * Drop non-default indexes
     */
    private function dropNonDefaultIndexes($collection): void
    {
        $indexes = $collection->listIndexes();
        
        foreach ($indexes as $index) {
            $indexName = $index['name'];
            if ($indexName !== '_id_') {
                $collection->dropIndex($indexName);
            }
        }
    }

    /**
     * Show collection statistics
     */
    private function showCollectionStats(SymfonyStyle $io): void
    {
        $io->section('Collection Statistics');
        
        $database = $this->documentManager->getDocumentDatabase();
        $collections = ['Domain', 'EmailAccount', 'Mailbox', 'Message', 'Attachment', 'Token'];
        
        $headers = ['Collection', 'Documents', 'Data Size', 'Index Size', 'Total Size'];
        $rows = [];
        
        foreach ($collections as $collectionName) {
            try {
                $collection = $database->selectCollection($collectionName);
                $stats = $collection->aggregate([
                    ['$collStats' => ['storageStats' => []]]
                ])->toArray();
                
                if (!empty($stats)) {
                    $stat = $stats[0];
                    $storageStats = $stat['storageStats'] ?? [];
                    
                    $rows[] = [
                        $collectionName,
                        number_format($storageStats['count'] ?? 0),
                        $this->formatBytes($storageStats['size'] ?? 0),
                        $this->formatBytes($storageStats['totalIndexSize'] ?? 0),
                        $this->formatBytes(($storageStats['size'] ?? 0) + ($storageStats['totalIndexSize'] ?? 0))
                    ];
                }
            } catch (\Exception $e) {
                $rows[] = [$collectionName, 'Error', $e->getMessage(), '', ''];
            }
        }
        
        $io->table($headers, $rows);
    }

    /**
     * Analyze query performance
     */
    private function analyzePerformance(SymfonyStyle $io): void
    {
        $io->section('Query Performance Analysis');
        
        $database = $this->documentManager->getDocumentDatabase();
        
        // Enable profiling
        $database->command(['profile' => 2, 'slowms' => 100]);
        
        $io->info('Profiling enabled. Slow queries (>100ms) will be logged.');
        
        // Sample some common queries to analyze
        $this->runSampleQueries($database, $io);
    }

    /**
     * Run sample queries for performance testing
     */
    private function runSampleQueries($database, SymfonyStyle $io): void
    {
        $queries = [
            'Recent messages' => [
                'collection' => 'Message',
                'query' => ['date' => ['$gte' => new \MongoDB\BSON\UTCDateTime(strtotime('-1 day') * 1000)]]
            ],
            'Messages by mailbox' => [
                'collection' => 'Message', 
                'query' => ['mailbox' => 'inbox']
            ],
            'Unread messages' => [
                'collection' => 'Message',
                'query' => ['flags' => ['$nin' => ['seen']]]
            ],
            'Large attachments' => [
                'collection' => 'Attachment',
                'query' => ['size' => ['$gt' => 1048576]] // > 1MB
            ]
        ];
        
        foreach ($queries as $description => $queryInfo) {
            $startTime = microtime(true);
            
            $collection = $database->selectCollection($queryInfo['collection']);
            $count = $collection->countDocuments($queryInfo['query']);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            $io->info(sprintf('%s: %d documents, %.2f ms', $description, $count, $duration));
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = 0;
        
        while ($bytes >= 1024 && $factor < count($units) - 1) {
            $bytes /= 1024;
            $factor++;
        }
        
        return sprintf('%.2f %s', $bytes, $units[$factor]);
    }
}