<?php
namespace App\Command;

use App\Controller\ErrorController;
use App\Repository\ErrorRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\MailerInterface;

class ErrorMailCommand extends Command
{
    protected static $defaultName = 'app:errorMail';
    private $mailer;
    private $errorRepository;

    public function __construct(MailerInterface $mailer, ErrorRepository $errorRepository) {
        $this->mailer = $mailer;
        $this->errorRepository = $errorRepository;
        parent::__construct();               
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Start Error mail command')
            ->setHelp('This command allows you to send mail when error alert.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = new ErrorController($this->mailer, $this->errorRepository);
        $dispatcher = new EventDispatcher();
        //$server->setDispatcher($dispatcher);
        $server->sendMail();

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event): void {
            $output = $event->getOutput();
        
            $command = $event->getCommand();
        
            $output->writeln(sprintf('Oops, exception thrown while running command <info>%s</info>', $command->getName()));
        
            // gets the current exit code (the exception code)
            $exitCode = $event->getExitCode();
        
            // changes the exception to another one
            $event->setError(new \LogicException('Caught exception', $exitCode, $event->getError()));
        });
        return 0;
    }
}