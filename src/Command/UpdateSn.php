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
        $this
        ->setDescription('Update and insert new SN in database')
        ->setHelp("This command allows you to insert new SN in database.\r\nTo add new file, share the file with user 'academy@academy-369611.iam.gserviceaccount.com' and append file to fileQuestion.");
    ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    { 
        $pathToPython = "./src/Process/UpdateSn.py";
        $pathToJson = "./src/Process/academy.json";

        $helper = $this->getHelper('question');
        #fileQuestion
        $fileQuestion = new ChoiceQuestion('Please enter filename: ', 
        ['2024 PRODUCT SELLING 2024_EDITED'],
        0);
        $filename = $helper->ask($input, $output, $fileQuestion);
        $output->writeln('You have just selected: '.$filename);
        
        #monthQuestion
        $monthQuestion = new ChoiceQuestion(
            'Please select month ',
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