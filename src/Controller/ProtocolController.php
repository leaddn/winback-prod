<?php

namespace App\Controller;

use App\Repository\ProtocolRepository;
use SearchProtoType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ProtocolController extends AbstractController
{
    public $backgroundArray = [
        'rgba(255, 99, 132, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(255, 205, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(201, 203, 207, 0.7)'
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

      public $borderMode = [
        '#fe8c22',
        '#391fff',
        '#00fff7',
        '#6EB0FF',
        '#3DD931',
        '#B7E007',
        '#C9CBCF'
    ];
    public $backgroundMode = [
        '#fe8c22cc',
        '#391fff',
        '#00fff7a2',
        '#6EB0FFa2',
        '#3DD931a2',
        '#B7E007a2',
        '#C9CBCFa2'
    ];

    public $modes_name = [
        0=>[
            'name'=>'CET',
            'param2'=>[
                0=>'SOFT',
                1=>'DYNAMIC',
                2=>'DEEP'
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        1=>[
            'name'=>'RET',
            'param2'=>[
                0=>'SOFT',
                1=>'DYNAMIC',
                2=>'DEEP'
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        2=>[
            'name'=>'HI-TENS',
            'param2'=>[
                0=>'CHRONIC',
                1=>'DYNAMIC', //TODO vérifier avec Bastien
                2=>'MANUAL'
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        3=>[
            'name'=>'HI-EMS',
            'param2'=>[
                0=>'RADIAL',
                1=>'DYNAMIC',
                2=>'FOCAL',
                3=>'DRAIN'
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        4=>[
            'name'=>'MIX',
            'param2'=>[
                0=>'SOFT',
                5=>'5 Hz pulsed deep'
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        5=>[
            'name'=>'MIX+HITENS',
            'param2'=>[
                0=>'SOFT',
                1=>'DYNAMIC',
                2=>'DEEP'
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        
        6=>[
            'name'=>'NEUTRAL',
            'param2'=>[
                //0=>'ko',
                //1=>'ok'
                0=>'',
                1=>''
            ],
            'param3'=>[
                0=>'LOW',
                1=>'MEDIUM',
                2=>'BOOST',
            ]
        ],
        
    ];

    public $acc_name = [
        0=>"NO_CONNECTED",
        1=>"TECARX RET",
        2=>"TECARX RET",
        3=>"TECARX RET",
        4=>"TECARX CET",
        5=>"TECARX CET",
        6=>"TECARX CET", // TODO regrouper les tecar
        7=>"TECARX_CET_60_CVX",
        8=>"TECARX_MIX_BODY",
        9=>"TECARX_MIX_FACE",
        10=>"TECARX_RET_40_CVX",
        11=>"TECARX_T6", # Hi-ret
        12=>"TECAR3_RET_40", //tecar mobile
        13=>"TECAR3_RET_60",
        14=>"TECAR3_RET_70",
        15=>"TECAR3_CET_40",
        16=>"TECAR3_CET_60",
        17=>"TECAR3_CET_70",
        18=>"TECAR3_CET_60_CVX",
        19=>"TECAR3_RET_40_CVX",
        20=>"TECAR3_HYB_40",
        21=>"TECAR3_HYB_60",
        22=>"TECAR3_T6", # Hi-ret
        23=>"TECAR6",
        24=>"MIX_FACE",
        25=>"MIX_BODY",
        26=>"ADHESIVE_RET",
        28=>"RET_BRACELET",
        29=>"STIM_CABLE",
        30=>"CET_FIXPAD",
        31=>"RET_FIXPAD",
        32=>"TECAR3_CET_40_CVX",
        33=>"TECARX_CET_40_CVX",
        34=>"EXTENDER_CET",
        35=>"EXTENDER_RET"
    ];

    #[Route('/{_locale<%app.supported_locales%>}/protocol', name: 'app_protocol')]
    public function index(ProtocolRepository $protocolRepository, Request $request, ChartBuilderInterface $chartBuilder): Response
    {
        $lastDay = "2023-09-14";
        $lastDayName = date('m/d/Y', strtotime($lastDay));
        // weeks
        //$week = $this->getWeek(); // get week before today
        $week = $this->generateSevenDaysBefore($lastDay); // get week before user chosen day
        //$randomWeek = [date("d_m_y", 12_09_23), date("d_m_y", 13_09_23), date("d_m_y", 14_09_23)];
        $randomWeek = $this->getWeekFormat($week);
        $weekName = $this->getWeekName($week);
        $weekSlash = $this->getWeekSlash($week);
        // choose sn array by client id
        
        $sn_array = $protocolRepository->findSN();
        foreach ($sn_array as $key => $value) {
            $sn_select_array[$sn_array[$key]['sn']] = $sn_array[$key]['sn'];
        }

        $searchForm = $this->createFormBuilder()
            ->add('sn', ChoiceType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => [
                    "Serial Numbers"=>$sn_select_array
                ],
                'placeholder' => 'Serial Numbers',
                
                'placeholder_attr' => [
                    'hidden' => 'hidden',
                    'disable'=> 'disable'
                ]
                
            ])

            ->getForm();

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $task = $searchForm->getData();
            $sn = $task['sn'];
            //$dates = $this->getDate($protocolRepository, $sn);
            $dates = $randomWeek;
    
            foreach ($dates as $key => $value) {
                $protocols = $this->getMode($protocolRepository, $value, $sn);
                //var_dump($protocols['protocol_id'][1]);
                $protocols_array[$weekSlash[$key]] = $protocols;
            }
        }
        else {
            $protocols_array = [];
        }
        $protocols_array = array_reverse($protocols_array);

        // utilisation mode / jour / semaine
        $modeDataset = [];
        foreach ($randomWeek as $key => $value) {
            $modeRandom[$value] = $this->getModeCount($protocolRepository, $value, $sn);
        }
        // get modes to weeks
        $modeByWeekDay = $this->getWeekDay($modeRandom, $randomWeek);
        foreach ($modeByWeekDay as $key => $value) {
            $modeDataset[] = $this->getDataset($this->modes_name[$key]['name'], array_values($modeByWeekDay[$key]), $this->backgroundMode[$key], $this->borderMode[$key]);
        }
        $modeChart = $this->getChart($chartBuilder, $weekName, $modeDataset, 'text', Chart::TYPE_BAR, '');
        $modeByWeek = $this->getWeekPercentage($modeByWeekDay);
        foreach ($modeByWeek as $key => $value) {
            $modeLabels[] = $this->modes_name[$key]['name'];
            $modeData[] = $value;
        }
        // By Week
        $modeDatasetWeek = [$this->getDataset('Usage', $modeData, $this->backgroundMode, $this->borderMode)];
        $modeChartWeek = $this->getChart($chartBuilder, $modeLabels, $modeDatasetWeek, 'text', Chart::TYPE_DOUGHNUT, '', true, false);
        // ========== utilisation ACCESSOIRES ========== //

        // utilisation accessoire / jour / semaine
        $accDataset = [];
        foreach ($randomWeek as $key => $value) {
            $accRandom[$value] = $this->getAccUseRandom();
        }
        $accByWeekDay = $this->getWeekDay($accRandom, $randomWeek);
        
        foreach ($accByWeekDay as $key => $value) {
            if ($key > 6) {
                $accDataset[] = $this->getDataset($key, array_values($accByWeekDay[$key]), $this->backgroundArray[random_int(0, 6)], $this->borderArray[random_int(0, 6)]);
            }
            else {
                $accDataset[] = $this->getDataset($key, array_values($accByWeekDay[$key]), $this->backgroundArray[random_int(0, 6)], $this->borderArray[random_int(0, 6)]);
            }
        }

        $accChart = $this->getChart($chartBuilder, $weekName, $accDataset, 'text', Chart::TYPE_BAR);
        // utilisation accessoire / semaine
        //$accByWeek = $this->getWeekPercentage($accByWeekDay);
        $accByWeek = $this->getAccUseRandom();
        foreach ($accByWeek as $key => $value) {
            $accLabels[] = $key;
            $accData[] = $value;
        }
        $accDatasetWeek = [$this->getDataset('Usage', $accData, $this->backgroundArray, $this->borderArray)];
        $accChartWeek = $this->getChart($chartBuilder, $accLabels, $accDatasetWeek, '', Chart::TYPE_DOUGHNUT, '', true, false);
        // number of treatments
        foreach ($randomWeek as $key => $value) {
            $treatmentCount[$value] = $this->getTreatmentCount($protocolRepository, $value, $sn);
            $treatmentDuration[$value] = array_sum(array_values($this->getTreatmentDuration($protocolRepository, $value, $sn)));
        }

        // Treatment duration + total treatments in week
        $treatmentDataset = [$this->getDataset("number of treatments", array_values($treatmentCount), $this->backgroundArray[0], $this->borderArray[0])];
        $treatmentChart = $this->getChart($chartBuilder, array_values($weekName), $treatmentDataset, 'text', Chart::TYPE_BAR, "", false);
        $totalTreatmentCount = array_sum(array_values($treatmentCount));
        $totalTreatmentDuration = intval(array_sum(array_values($treatmentDuration))/60);


        return $this->render('protocol/index.html.twig', [
            'protocols' => $protocols_array,
            'searchForm' => $searchForm,
            'modeChart'=> $modeChart,
            'modeChartWeek'=> $modeChartWeek,
            'accChart'=> $accChart,
            'accChartWeek'=>$accChartWeek,
            'treatmentChart'=> $treatmentChart,
            'totalTreatmentCount'=> $totalTreatmentCount,
            'totalTreatmentDuration'=> $totalTreatmentDuration,
            'lastDayName'=>$lastDayName,
        ]);
    }

    function getWeek() {
        //find 7 days before today
        $i = 0;
        $date_array = [];
        while ($i <= 7) {
            $date = strtotime("-{$i} day");
            $date_array[] = date('Y-m-d', $date);
            $i++;
        }
        $date_array = array_reverse($date_array);
        return $date_array;
    }

    function generateSevenDaysBefore($date) {
        $sevenDaysBefore = [];
        // Convert the date string to a DateTime object
        $dateObj = new \DateTime($date);
   
        // Loop to generate seven days before the given date
        for ($i = 0; $i < 7; $i++) {
            $sevenDaysBefore[] = $dateObj->format('Y-m-d');
            $dateObj->modify("-1 day");
        }
        // Reverse the array to have the days in ascending order
        $sevenDaysBefore = array_reverse($sevenDaysBefore);
    
        return $sevenDaysBefore;
    
    }

    function getWeekFormat($date) {
        $dateName = [];
        foreach ($date as $key => $value) {
            $dateName[] = date('d_m_y', strtotime($value));
        }
        return $dateName;
    }

    function getWeekName($date) {
        $dateName = [];
        foreach ($date as $key => $value) {
            $dateName[] = date('D', strtotime($value));
        }
        return $dateName;
    }
    function getWeekSlash($date) {
        $dateName = [];
        foreach ($date as $key => $value) {
            $dateName[] = date('m/d/Y', strtotime($value));
        }
        return $dateName;
    }

    public function getDate(ProtocolRepository $protocolRepository, $sn) {
        $date_array = $protocolRepository->findAllDate($sn);
        return $date_array;
    }

    public function getMode(ProtocolRepository $protocolRepository, $date, $sn) {
        $modeObj = $protocolRepository->findMode($sn, $date);
        
        foreach ($modeObj as $key => $value) {
            $mode_obj["protocol_id"][$value->getProtocolId()]["step_id"][$value->getStepId()]["way_id"][$value->getWayId()]["mode_id"][$this->modes_name[$value->getModeId()]['name']] = array(
                "param1" => $value->getParam1(),
                "param2" => $this->modes_name[$value->getModeId()]['param2'][$value->getParam2()],
                "param3" => $this->modes_name[$value->getModeId()]['param3'][$value->getParam3()],
                //"time_tot" => $value->getTimeTot());
            );
            if (intval($value->getTimeTot())<60) {
                $mode_obj["protocol_id"][$value->getProtocolId()]["step_id"][$value->getStepId()]["time_tot"] = $value->getTimeTot()."''";
            }
            else {
                $mode_obj["protocol_id"][$value->getProtocolId()]["step_id"][$value->getStepId()]["time_tot"] = intval($value->getTimeTot()/60)."'";
            }
            
        }
        
        foreach ($mode_obj as $protocolKey => $protocolValues) {
            foreach ($protocolValues as $key => $value) {
                //var_dump($value['step_id'][0]['way_id'][1]);
                if ($key != sizeof($value['step_id'])) {
                    foreach ($value['step_id'][$key]['way_id'] as $wayKey => $wayValue) {
                        //var_dump($wayKey);
                        if ($value['step_id'][$key]['way_id'][$wayKey]['mode_id'] == $value['step_id'][$key+1]['way_id'][$wayKey]['mode_id']) {
                            //var_dump($value['step_id'][$key]['way_id'][1]);
                            //var_dump($value['step_id'][$key+1]['way_id'][1]);
                        }
                    }

                }
            }
            //var_dump($protocolValues['step_id']);
        }
        
        return $mode_obj;
    }

    public function getTreatmentCount(ProtocolRepository $protocolRepository, $date, $sn) {
        $modeObj = $protocolRepository->findMode($sn, $date);
        
        foreach ($modeObj as $key => $value) {
            $mode_obj[$value->getProtocolId()] = $value->getProtocolId();
        }
        if (!empty($mode_obj)) {
            return count(array_values($mode_obj));
        }
        else {
            return 0;
        }
        
    }
    public function getTreatmentDuration(ProtocolRepository $protocolRepository, $date, $sn) {
        $modeObj = $protocolRepository->findMode($sn, $date);
        $result = [];
        foreach ($modeObj as $key => $value) {
            $mode_obj[$value->getProtocolId()][$value->getStepId()] = $value->getTimeTot();
        }
        foreach ($mode_obj as $mode_objKey => $mode_objValue) {
            $result[$mode_objKey] = array_sum(array_values($mode_objValue));
        }
        return $result;
    }

    public function getModeCount(ProtocolRepository $protocolRepository, $date, $sn) {
        $modeObj = $protocolRepository->findMode($sn, $date);
        
        foreach ($modeObj as $key => $value) {
            if ($value->getModeId() != 6) {
                $mode_obj[] = $value->getModeId();
                $mode_objCount = $this->countOccurences($mode_obj);
            }
        }
        if (!empty($mode_obj)) {
            $totalCount = count($mode_obj);
            foreach ($mode_objCount as $modeKey => $modeValue) {
                $mode_objPerc[$modeKey] = intval(($modeValue/$totalCount) * 100);
            }
        }
        else {
            foreach ($mode_objCount as $modeKey => $modeValue) {
                $mode_objPerc[$modeKey] = 0;
            }
        }

        return $mode_objPerc;
    }

    function getWeekDay($array, $week) {
        $byWeekDay = [];
        foreach ($array as $key => $value) {
            foreach ($value as $modeKey => $modeValue) {
                foreach ($week as $dayKey => $dayValue) {
                    $byWeekDay[$modeKey][$dayValue] = 0;
                }
            }
        }
        foreach ($array as $key => $value) {
            foreach ($value as $modeKey => $modeValue) {
                    $byWeekDay[$modeKey][$key]=$modeValue;
            }
        }
        return $byWeekDay;
    }
    function getWeekPercentage($byWeekDay) {
        $byWeek = [];
        // count by week
        foreach ($byWeekDay as $key => $value) {
            $byWeek[$key] = array_sum($value);
        }
        // count percentage
        $total_percent = array_sum(array_values($byWeek));
        foreach ($byWeek as $key => $value) {
            $byWeek[$key] = ceil(($value / $total_percent) * 100);
        }
        return $byWeek;
    }
    function getModeUseRandom() {
        $week = $this->getWeek();
        $modeRandom = [];

        foreach ($this->modes_name as $keyMode => $valueMode) {
            foreach ($week as $key => $value) {
                $modeRandom[$keyMode][$valueMode['name']][$value] = random_int(0, 50);
            }
        }
        return $modeRandom;
    }

    function getModeUseRandomWeek() {
        $modeRandom = [];
        foreach ($this->modes_name as $keyMode => $valueMode) {
                $modeRandom[$keyMode][$valueMode['name']] = random_int(0, 50);
        }
        return $modeRandom;
    }

    function getAccUseRandom() {
        $week = $this->getWeek();
        $accRandom = [];
        //$acc_name = array_rand($this->acc_name, sizeof($week));
        // get random array of id occurrences
        $random_values = array();
        for ($i=0; $i <= 5; $i++) {
            $random_values[] = rand(1, 35);
        }
        $num_occurrences = array();
        foreach ($random_values as $value) {
            $num_occurrences[$this->acc_name[$value]] = random_int(1, 100);
        }
        $random_occurrences = array();
        foreach ($num_occurrences as $value => $count) {
            for ($i=0; $i < $count; $i++) { 
                $random_occurrences[] = $value;
            }
        }
        //count Occurences
        $count_occurrences = $this->countOccurences($random_occurrences);
        $totalCount = array_sum($count_occurrences);
        foreach ($count_occurrences as $key => $value) {
            $accPerc[$key] = intval(($value / $totalCount) * 100);
        }
        return $accPerc;
    }

    function getTreatmentRandom() {
        $week = $this->getWeek();
        foreach ($week as $key => $value) {

            $modeRandom[$value] = random_int(0, 50);
        }
        return $modeRandom;
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
    function getChart(ChartBuilderInterface $chartBuilder, $labels, $datasets, $text, $chartType, $textY='percen', $displayLeg=true, $displayY=true) {
        $chart = $chartBuilder->createChart($chartType);
        $chart->setData([
            'labels' => $labels,
            'datasets' => $datasets,    
        ]);
    
        $chart->setOptions([
            
            'plugins'=> [
                'title'=> [
                    'display'=> true,
                    //'text'=> $text
                ],
                'legend'=>[
                    'display'=>$displayLeg,
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => true,
            'scales'=> [
                'y'=> [
                    'beginAtZero'=> true,
                    'display'=>$displayY,
                    'text'=>$textY
                ]
            ]
        ]);

        return $chart;
    }

    function getDataset($label, $dataArray, $backgroundArray, $borderArray) {
        //$backgroundArray = $this->backgroundArray;
        //$borderArray = $this->borderArray;
        $dataset =
            [
                'label' => $label,
                'backgroundColor' => $backgroundArray,
                'borderColor' => $borderArray,
                'data' => $dataArray,
            ];
        return $dataset;

    }

    function countOccurences($inputArray) {
        $occurences = array_count_values($inputArray);
        return $occurences;
    }
}