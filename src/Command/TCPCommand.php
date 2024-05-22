<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Server\TCPServer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TCPCommand extends Command
{
    protected static $defaultName = 'app:tcpserver';
    
    private $logger;

    public function __construct(LoggerInterface $logger) {                
        $this->logger = $logger;
        parent::__construct();               
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Start TCP server')
            ->setHelp("This command allows you to run a web socket to connect with winback devices. \r\nCreates and runs a TCP server on the specified port and address.");
            //->addArgument('port', InputArgument::REQUIRED, 'Server Port');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $helper = $this->getHelper('question');
        #portQuestion
        $portQuestion = new ChoiceQuestion(
            'Please select server port ',
            [$_ENV["PORT"], $_ENV["SERVER_SECURE_PORT"]],
            0
        );
        $portQuestion->setErrorMessage('Port %s is invalid.');
        $port = $helper->ask($input, $output, $portQuestion);
        $output->writeln('You have just selected: '.$port);
        
        $server = new TCPServer();
        $dispatcher = new EventDispatcher();
        $server->setDispatcher($dispatcher);
        $server->runServer($this->logger, $port);

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event): void {
            $output = $event->getOutput();
        
            $command = $event->getCommand();
        
            $output->writeln(sprintf('Oops, exception thrown while running command <info>%s</info>', $command->getName()));
        
            // gets the current exit code (the exception code)
            $exitCode = $event->getExitCode();
        
            // changes the exception to another one
            $event->setError(new \LogicException('Caught exception', $exitCode, $event->getError()));
        });
    }
}