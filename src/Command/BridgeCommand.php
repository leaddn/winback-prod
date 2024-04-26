<?php
namespace App\Command;

use App\Repository\DeviceFamilyRepository;
use App\Server\BridgeServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;
use App\Server\TCPServer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcher;

class BridgeCommand extends Command
{
    protected static $defaultName = 'app:bridgeserver';
    
    private $logger;
    private $deviceFamilyRepository;

    public function __construct(LoggerInterface $logger, DeviceFamilyRepository $deviceFamilyRepository) {                
        $this->logger = $logger;
        $this->deviceFamilyRepository = $deviceFamilyRepository;
        parent::__construct();               
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Start Bridge server')
            ->setHelp("This command allows you to run a web socket to connect with winback devices and send data to Azure TCP server. \r\nCreates and runs a TCP server on the specified port and address.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $helper = $this->getHelper('question');
        $portQuestion = new ChoiceQuestion(
            'Please select server port ',
            [$_ENV["PORT"], $_ENV["SERVER_SECURE_PORT"]],
            0
        );
        $portQuestion->setErrorMessage('Port %s is invalid.');
        $port = $helper->ask($input, $output, $portQuestion);
        $output->writeln('You have just selected: '.$port);
        
        $server = new BridgeServer();
        $dispatcher = new EventDispatcher();
        $server->setDispatcher($dispatcher);
        $server->runServer($this->logger, $this->deviceFamilyRepository, $port);

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event): void {
            $output = $event->getOutput();
        
            $command = $event->getCommand();
        
            $output->writeln(sprintf('Oops, exception thrown while running command <info>%s</info>', $command->getName()));
            $exitCode = $event->getExitCode();
            $event->setError(new \LogicException('Caught exception', $exitCode, $event->getError()));
        });
    }
}