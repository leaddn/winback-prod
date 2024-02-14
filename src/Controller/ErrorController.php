<?php

namespace App\Controller;

use App\Class\SearchError;
use App\Form\SearchErrorType;
use App\Repository\ErrorFamilyRepository;
use App\Repository\ErrorRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ErrorController extends AbstractController
{
    public $backgroundArray = [
        'rgba(255, 99, 132, 0.2)',
        'rgba(255, 159, 64, 0.2)',
        'rgba(255, 205, 86, 0.2)',
        'rgba(75, 192, 192, 0.2)',
        'rgba(54, 162, 235, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(201, 203, 207, 0.2)'
    ];
      
    public $borderArray = [
        'rgb(255, 99, 132)',
        'rgb(255, 159, 64)',
        'rgb(255, 205, 86)',
        'rgb(75, 192, 192)',
        'rgb(54, 162, 235)',
        'rgb(153, 102, 255)',
        'rgb(201, 203, 207)'
      ];

    private $errorArray = [
        218 => "Update error, one board software version not similar with GMU version",
        220 => "No communication with led driver component in ACCESS board.",
        221 => "5v voltage too high on ACCESS board.",
        222 => "5v voltage too low on ACCESS board.",
        234 => "Supply voltage too low on TECAR board.",
        246 => "Supply voltage too low on GMU board.",
        247 => "Emergency button cable not connected inside device.",
        248 => "Communication lost between GMU and TECAR board.",
        254 => "Emergency button pressed by someone.",

    ];

    private $mailer;
    private $errorRepository;

    public function __construct(MailerInterface $mailer, ErrorRepository $errorRepository) {                
        $this->mailer = $mailer; 
        $this->errorRepository = $errorRepository;             
    }
    /**
     * @Route("/{_locale<%app.supported_locales%>}/user/app_error", name="app_error")
     */
    public function index(ErrorRepository $errorRepository, ErrorFamilyRepository $errorFamilyRepository, Request $request, PaginatorInterface $paginator, ChartBuilderInterface $chartBuilder): Response
    {
        
        $data = new SearchError();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchErrorType::class, $data);
        $form->handleRequest($request);
        
        $errors = $errorRepository->findSearch($data, $paginator);
        if($form->isSubmitted() && $form->isValid()) {
            if ($errors->getItems() == null) {
                $this->addFlash(
                    'app-error-alert', 'Error(s) not found, please try again !'
                );
                return $this->redirectToRoute('app_error');
            }
        }
        
        //count by errors 
        $errorCount_array = $this->getDeviceCount($errorFamilyRepository);
        $errorChart = $this->getChart($chartBuilder, array_keys($errorCount_array), 'label', array_values($errorCount_array), 'Number of devices per error', Chart::TYPE_DOUGHNUT);

        $this->reportError($errorRepository);

        var_dump($this->getErrorCountByDeviceType($errorRepository));
        return $this->render('error/index.html.twig', [
            'errors' => $errors,
            'form' => $form->createView(),
            'errorChart' => $errorChart
        ]);
    }

    public function getDeviceCount(ErrorFamilyRepository $errorFamilyRepository) {
        $errorFamily = $errorFamilyRepository->findAll();
        for ($i=0; $i < sizeof($errorFamily); $i++) {
            $errorId = $errorFamily[$i]->getErrorId();
            $errorCount = count($errorFamily[$i]->getErrors());
            $deviceCountArray[$errorId] = $errorCount;
        }
        return $deviceCountArray;
    }

    public function getErrorCountByDeviceType(ErrorRepository $errorRepository) {
        foreach ($errorRepository->distinctDeviceType() as $key => $value) {
            $deviceTypes[$value["deviceType"]] = $value["deviceType"];
        }
        foreach ($deviceTypes as $key => $value) {
            $errors = $errorRepository->findBy(array('deviceType'=>$value), array('error'=>'ASC'));
            foreach ($errors as $errorKey => $errorValue) {
                $errorResult[$key][] = $errorValue->getError()->getErrorId();
                $errorDeviceType = $this->countOccurences($errorResult[$key]);
            }
            
            
            $deviceTypeResult[$key]=$errorDeviceType;
            
        }
        return $deviceTypeResult;
    }

    /**
     * get error by device type and date
     */
    public function getErrorCountByDate(ErrorRepository $errorRepository, $date) {
        foreach ($errorRepository->distinctDeviceType() as $key => $value) {
            $deviceTypes[$value["deviceType"]] = $value["deviceType"];
        }
        foreach ($deviceTypes as $key => $value) {
            //$errors = $errorRepository->findBy(array('deviceType'=>$value, 'date'=>$date), array('error'=>'ASC'));
            $errors = $errorRepository->findByDateDevice($value, $date);
            foreach ($errors as $errorKey => $errorValue) {
                $errorResult[$key][] = $errorValue->getError()->getErrorId();
                $errorDeviceType[$key] = $this->countOccurences($errorResult[$key]);
            }
            $deviceTypeResult[$key]=$errorDeviceType[$key];
            
        }
        return $deviceTypeResult;
    }
    
    /**
     * getChart
     * @param ChartBuilderInterface $chartBuilder
     * @param array $labels - array of legends
     * @param string $label
     * @param array $dataArray - array of values
     * @param string $text
     * @return Chart
     */
    function getChart(ChartBuilderInterface $chartBuilder, $labels, $label, $dataArray, $text, $chartType) {
        $backgroundArray = $this->backgroundArray;
        $borderArray = $this->borderArray;
        //Chart::TYPE_LINE
        $chart = $chartBuilder->createChart($chartType);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label,
                    'backgroundColor' => $backgroundArray,
                    'borderColor' => $borderArray,
                    'data' => $dataArray,
                ],
            ],
        ]);
    
        $chart->setOptions([
            
            'plugins'=> [
                'title'=> [
                    'display'=> true,
                    'text'=> $text
                ]
            ],
            'maintainAspectRatio' => false,
        ]);

        return $chart;
    }

    /**
     * @Route("/update_db/{deviceType}", name="error_update")
     */
    function updateDb(KernelInterface $kernel, $deviceType) {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:errorCommand',
            // (optional) define the value of command arguments
            'deviceType' => $deviceType
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();

        // return new Response(""), if you used NullOutput()
        return new Response($content);
    }

    /**
     * @Route("/mail-error", name="mail-error")
     */
    function reportError() {
        // check if error is 3
        // date of today
        $currentDate = date('Y-m-d');
        $yesterday = new \DateTime('yesterday'); // will use our default timezone, Paris
        $date = $yesterday->format('Y-m-d');
        $errorFamily = $this->errorRepository->findByDate($date);
        $result = [];
        $result3 = [];
        $result4 = [];
        $i = 0;
        foreach ($errorFamily as $key => $value) {
            $result[] = $value->getSn()->getSn();
            $result4[$value->getDeviceType()][$value->getSn()->getSn()]["errors"][] = [
                "date" => $value->getDate(), 
                "error_id" => $value->getError()->getErrorId(), 
                "version" => $value->getVersion(),
                "description" => $this->errorArray[$value->getError()->getErrorId()]];

        }

        $result2 = $this->countOccurences($result);
        /*
        foreach ($result2 as $key => $value) {
            $result4[$key]["occurences"] = $value;
        }
        */
        $deviceTypeResult = $this->getErrorCountByDate($this->errorRepository, $date);
        
        if (!empty($result4)) {
            $this->sendMail($result4, $deviceTypeResult);
        }
        

        return $this->render('error/mail.html.twig', [
            'result' => $result4,
            'deviceTypeResult' => $deviceTypeResult
        ]);
    }

    function sendMail($result, $deviceTypeResult) {
        $emailToAdmin = (new TemplatedEmail())
        ->from(new Address('noreply@winback-assist.com', 'Winback Team'))
        ->to('ldieudonat@winback.com', 'bwollensack@winback.com')
        ->subject('Winback Assist - Error report')
        ->htmlTemplate('error/mail.html.twig')
        ->context(['result' => $result, 'deviceTypeResult' => $deviceTypeResult]);
        $this->mailer->send($emailToAdmin);
    }

    function countOccurences($inputArray) {
        $occurences = array_count_values($inputArray);
        return $occurences;
    }
}
