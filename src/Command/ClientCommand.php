<?php
namespace App\Command;

use App\Server\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ClientCommand extends Command
{
    protected static $defaultName = 'app:client';
    
    private $logger;

    public function __construct(LoggerInterface $logger) {                
        $this->logger = $logger; 
        parent::__construct();               
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Start Client')
            ->setHelp('This command allows you to run a client to connect with 5007 server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client();
        $client->main("hello");
    }
}