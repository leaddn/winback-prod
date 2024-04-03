<?php

namespace App\Command;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
class UpdateSn extends Command
{
    //#[AsCommand(name: "app:updateSn")]
     protected static $defaultName = 'app:updateSn';
    protected function configure()
    {
        //...
    ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    { 
        $pathToPython = "./src/Process/UpdateSn.py";
        $pathToJson = "./src/Process/academy.json";

        $helper = $this->getHelper('question');
        #fileQuestion
        //$fileQuestion = new Question('Please enter filename: ', 'PRODUCT_SELLING_2024');
        $fileQuestion = new ChoiceQuestion('Please enter filename: ', 
        ['2024 PRODUCT SELLING 2024_EDITED', 'PRODUCT_SELLING_2024'],
        0);
        $filename = $helper->ask($input, $output, $fileQuestion);
        $output->writeln('You have just selected: '.$filename);
        
        #monthQuestion
        $monthQuestion = new ChoiceQuestion(
            'Please select month ',
            //['JAN'=>1, 'FEB'=>2, 'MAR'=>3, 'APR'=>4, 'MAY'=>5, 'JUN'=>6, 'JUL'=>7, 'AUG'=>8, 'SEP'=>9, 'OCT'=>10, 'NOV'=>11, 'DEC'=>12],
            //[1=>'JAN', 2=>'FEB', 3=>'MAR', 4=>'APR', 5=>'MAY', 6=>'JUN', 7=>'JUL', 8=>'AUG', 9=>'SEP', 10=>'OCT', 11=>'NOV', 12=>'DEC'],
            [1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9', 10=>'10', 11=>'11', 12=>'12'],
            0
        );
        $monthQuestion->setErrorMessage('Month %s is invalid.');
        $month = $helper->ask($input, $output, $monthQuestion);
        $output->writeln('You have just selected: '.$month);

        $process = new Process(['python', $pathToPython, $pathToJson, $filename, $month]);
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