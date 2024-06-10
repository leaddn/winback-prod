<?php

namespace App\Controller;

use App\Class\SearchData;
use App\Form\SearchDeviceType;
use App\Form\Market\UploadImageType;
use App\Repository\DeviceRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MarketController extends AbstractController
{
    #[Route('/{_locale<%app.supported_locales%>}/user/market', name: 'app_market')]
    public function index(DeviceRepository $deviceRepository, Request $request, PaginatorInterface $paginator): Response
    {
        
        $data = new SearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchDeviceType::class, $data);
        $form->handleRequest($request);
        $devices = $deviceRepository->findSearch($data, $paginator);
        if ($devices->getItems() == null) {
            $this->addFlash(
                'error', 'Device(s) not found, please try again !'
            );
            return $this->redirectToRoute('app_market');
        }
        
        /* Add upload */
        /*
        $uploadForm = $this->createForm(UploadImageType::class, $device);
        $uploadForm->handleRequest($request);
        */
        return $this->render('market/index.html.twig', [
            'controller_name' => 'MarketController',
            'devices' => $devices,
            'form' => $form->createView(),
            'ressource_path' => $_ENV["RESSOURCE_PATH"],
        ]);
    }
}
