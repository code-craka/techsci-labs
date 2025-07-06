<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\NightwatchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nightwatch:sync',
    description: 'Sync and manage Laravel Nightwatch integration',
)]
class NightwatchSyncCommand extends Command
{
    public function __construct(
        private readonly NightwatchService $nightwatchService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('setup-monitors', null, InputOption::VALUE_NONE, 'Setup default infrastructure monitors')
            ->addOption('test-connection', null, InputOption::VALUE_NONE, 'Test Nightwatch connection')
            ->addOption('show-stats', null, InputOption::VALUE_NONE, 'Show project statistics')
            ->setHelp('This command helps you manage the Laravel Nightwatch integration for TechSci Labs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🌙 TechSci Labs - Laravel Nightwatch Sync');

        // Test connection first
        if ($input->getOption('test-connection') || !$input->getOption('setup-monitors') && !$input->getOption('show-stats')) {
            $io->section('Testing Nightwatch Connection');
            
            if ($this->nightwatchService->testConnection()) {
                $io->success('✅ Successfully connected to Nightwatch!');
            } else {
                $io->error('❌ Failed to connect to Nightwatch. Check your configuration.');
                return Command::FAILURE;
            }
        }

        // Setup monitors
        if ($input->getOption('setup-monitors')) {
            $io->section('Setting up Infrastructure Monitors');
            
            $monitors = $this->nightwatchService->setupDefaultMonitors();
            
            if (!empty($monitors)) {
                $io->success(sprintf('✅ Successfully created %d monitors', count($monitors)));
                
                $monitorData = [];
                foreach ($monitors as $monitor) {
                    $monitorData[] = [
                        $monitor['name'],
                        $monitor['type'],
                        $monitor['id']
                    ];
                }
                
                $io->table(['Monitor Name', 'Type', 'Monitor ID'], $monitorData);
            } else {
                $io->warning('⚠️ No monitors were created. Check your configuration.');
            }
        }

        // Show statistics
        if ($input->getOption('show-stats')) {
            $io->section('Project Statistics');
            
            $stats = $this->nightwatchService->getProjectStats();
            
            if ($stats) {
                $io->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Emails', number_format($stats['total_emails'] ?? 0)],
                        ['Delivered', number_format($stats['delivered'] ?? 0)],
                        ['Bounced', number_format($stats['bounced'] ?? 0)],
                        ['Success Rate', ($stats['success_rate'] ?? 0) . '%'],
                        ['API Calls Today', number_format($stats['api_calls_today'] ?? 0)],
                        ['Active Monitors', $stats['active_monitors'] ?? 0],
                        ['Last Updated', $stats['last_updated'] ?? 'N/A'],
                    ]
                );
                
                if (isset($stats['recent_alerts']) && !empty($stats['recent_alerts'])) {
                    $io->section('Recent Alerts');
                    $alertData = [];
                    foreach ($stats['recent_alerts'] as $alert) {
                        $alertData[] = [
                            $alert['type'] ?? 'Unknown',
                            $alert['message'] ?? 'No message',
                            $alert['severity'] ?? 'info',
                            $alert['created_at'] ?? 'N/A'
                        ];
                    }
                    $io->table(['Type', 'Message', 'Severity', 'Created'], $alertData);
                }
            } else {
                $io->error('❌ Failed to retrieve project statistics.');
                return Command::FAILURE;
            }
        }

        // Default behavior - show basic info
        if (!$input->getOption('setup-monitors') && !$input->getOption('show-stats') && !$input->getOption('test-connection')) {
            $io->section('Nightwatch Integration Status');
            
            $stats = $this->nightwatchService->getProjectStats();
            if ($stats) {
                $io->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Emails', number_format($stats['total_emails'] ?? 0)],
                        ['Success Rate', ($stats['success_rate'] ?? 0) . '%'],
                        ['Active Monitors', $stats['active_monitors'] ?? 0],
                    ]
                );
            }
            
            $io->note('Use --help to see available options for detailed management.');
        }

        $io->success('🎉 Nightwatch sync completed successfully!');
        
        return Command::SUCCESS;
    }
}