<?php

namespace App\Command;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
class ErrorCommand extends Command
{
     protected static $defaultName = 'app:errorCommand';
    protected function configure()
    {
        $this->addArgument('deviceType', InputArgument::REQUIRED)
    ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    { 
        $pathToPython = "./src/Process/ErrorStats.py";
        $deviceType = $input->getArgument('deviceType');
        $process = new Process(['python', $pathToPython, $deviceType]);
        $process->start();
        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                echo "\nDebug :".$data;
            } else { // $process::ERR === $type
                echo "\nErreur : ".$data;
            }
        }
        echo $process->getOutput();
        return 0;
    }
}