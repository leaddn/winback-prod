<?php

namespace App\Controller;

use App\Class\SearchSn;
use App\Form\SearchSnType;
use App\Repository\SnRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class SnController extends AbstractController
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

    #[Route('/{_locale<%app.supported_locales%>}/sn', name: 'app_sn')]
    public function index(SnRepository $snRepository, Request $request, ChartBuilderInterface $chartBuilder): Response
    {
        $data = new SearchSn();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchSnType::class, $data);
        $form->handleRequest($request);
        $sn = $snRepository->findSearch($data);

        /* Data Count */
        $subCount_array = $this->getSubCount($snRepository);

        /* Chart Creation */
        $labels = array_keys($subCount_array);
        $label = "My First dataset";
        $dataArray = array_values($subCount_array);
        $text = "";
        $subCount = [];
        $chart = $this->getChart($chartBuilder, Chart::TYPE_DOUGHNUT, $labels, $label, $dataArray, $text);

        //instead of one chart, do array of chart with device type as index and chart as value
        // for each label, count the number of device
        foreach ($labels as $key => $value) {
            $deviceCount = $this->getDeviceCount($snRepository, $value);
            $subCount[$value] = $deviceCount;
        }

        foreach ($subCount as $key => $value) {
            $chart2 = $this->getChart($chartBuilder, Chart::TYPE_DOUGHNUT, array_keys($value), $key, array_values($value), '');
            $chartArray[$key] = $chart2;
        }

        return $this->render('sn.html.twig', [
            'sn' => $sn,
            'form' => $form->createView(),
            'snChart' => $chart,
            'chartArray' => $chartArray
        ]);
    }

    public function getChart(ChartBuilderInterface $chartBuilder, $chartType, $labels, $label, $dataArray, $text)
    {
        $backgroundArray = $this->backgroundArray;
        $borderArray = $this->borderArray;
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
            'animation' => false
        ]);
        
        return $chart;
    }

    public function getSubCount(SnRepository $snRepository) {
        $devicesFamily = $snRepository->findAllSubType();
        foreach ($devicesFamily as $key => $value) {
            //print_r($value['device']);
            $deviceCountArray[$value['subtype']] = count($snRepository->findBySubtype($value['subtype']));
        }

        return $deviceCountArray;
    }

    public function getDeviceCount(SnRepository $snRepository, $subtype) {
        $devicesFamily = $snRepository->findAllDeviceType($subtype);
        $deviceCountArray = [];
        foreach ($devicesFamily as $key => $value) {
            //print_r($value['device']);
            //$deviceCountArray[] = $value['device'];
            $deviceCountArray[$value['device']] = count($snRepository->findByDeviceType($value['device'], $subtype));
        }
        
        return $deviceCountArray;
        //return $devicesFamily;
    }

    #[Route('/sn_check', name: 'sn_check')]
    public function snCheck(SnRepository $snRepository): JsonResponse
    {
        $devices = $snRepository->findAll();
        $data = [];

        foreach ($devices as $device) {
            $data[] = [
                'serial_number' => $device->getSn(),
                'device_type' => $device->getDevice(),
                'main_type' => $device->getSubtype(),
                'country' => $device->getCountry(),
                'creation_date' => $device->getCreationDate(),
                'code_client' => $device->getClientCode()
            ];
        }
        return $this->json($data);
    }


    #[Route('/sn_check/{sn}', name: 'sn_check{sn}')]
    public function snCheckOneCopy(SnRepository $snRepository, $sn)
    {
        
        $device = $snRepository->findOneBy(["sn"=>$sn]);
        $data = '';

        if ($device!=null) {
            $data = [
                'serial_number' => $device->getSn(),
                'device_type' => $device->getDevice(),
                'main_type' => $device->getSubtype(),
                'country' => $device->getCountry(),
                'creation_date' => $device->getCreationDate(),
                'code_client' => $device->getClientCode()
            ];
            return $this->json($data);
        }
        else {
            if($pos=strpos($sn,'-',0)){
				$sn=str_replace('-','',$sn);				
				$device = $snRepository->findOneBy(["sn"=>$sn]);
                $data = [
                    'serial_number' => $device->getSn(),
                    'device_type' => $device->getDevice(),
                    'main_type' => $device->getSubtype(),
                    'country' => $device->getCountry(),
                    'creation_date' => $device->getCreationDate(),
                    'code_client' => $device->getClientCode()
                ];
                return $this->json($data);
			}else if($pos=strpos($sn,'_',0)){
				$sn=str_replace('_','',$sn);				
				$device = $snRepository->findOneBy(["sn"=>$sn]);
                $data = [
                    'serial_number' => $device->getSn(),
                    'device_type' => $device->getDevice(),
                    'main_type' => $device->getSubtype(),
                    'country' => $device->getCountry(),
                    'creation_date' => $device->getCreationDate(),
                    'code_client' => $device->getClientCode()
                ];
                return $this->json($data);
			}else{
				for($i=1;$i<strlen($sn);$i++){
            		$snTemp=substr($sn,0,$i).'-'.substr($sn,$i,strlen($sn));				
                    $device = $snRepository->findOneBy(["sn"=>$snTemp]);
					if ($device!=null) {
                        $data = [
                            'serial_number' => $device->getSn(),
                            'device_type' => $device->getDevice(),
                            'main_type' => $device->getSubtype(),
                            'country' => $device->getCountry(),
                            'creation_date' => $device->getCreationDate(),
                            'code_client' => $device->getClientCode()
                        ];
                        return $this->json($data);
                        break;
                    }
        		}
				if ($device==null){
					for($i=1;$i<strlen($sn);$i++){
						$snTemp=substr($sn,0,$i).'_'.substr($sn,$i,strlen($sn));				
						$device = $snRepository->findOneBy(["sn"=>$snTemp]);
						if ($device!=null) {
                            $data = [
                                'serial_number' => $device->getSn(),
                                'device_type' => $device->getDevice(),
                                'main_type' => $device->getSubtype(),
                                'country' => $device->getCountry(),
                                'creation_date' => $device->getCreationDate(),
                                'code_client' => $device->getClientCode()
                            ];
                            return $this->json($data);
                            break;
                        }
					}
				}
			}
            return new Response(
                '<html><body>Serial Number not present in database: '.$sn.' <br>Please contact the technical teams.</body></html>'
            );
        }

    }
}
