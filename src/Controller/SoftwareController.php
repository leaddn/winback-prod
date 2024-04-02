<?php
namespace App\Controller;

use App\Entity\Main\DeviceFamily;
use App\Entity\Main\Software;
use App\Form\SearchSoftwareType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

use App\Form\SoftwareType;
use App\Repository\DeviceFamilyRepository;
use App\Repository\SoftwareRepository;
use App\Services\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

use Symfony\Component\Finder\Finder;
use App\Server\DbRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class SoftwareController extends AbstractController
{
    /**
     * @Route("/{_locale<%app.supported_locales%>}/user/software/", name="software")
     */    
    public function index(SoftwareRepository $softwareRepository, DeviceFamilyRepository $deviceFamilyRepository, Request $request, DbRequest $dbRequest, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $software = null;
        $softwares = $softwareRepository->findAll();
        $families = $deviceFamilyRepository->findBy(array(), array('name' => 'ASC'));

        $searchform = $this->createForm(SearchSoftwareType::class);
        $search = $searchform->handleRequest($request);
        
        if($searchform->isSubmitted() && $searchform->isValid()) {
            // On recherche les annonces correspondant aux mots clefs
            $software_name = $search->get('value')->getData();
            $family_name = $search->get('category')->getData();
            $softwares = $softwareRepository->search(
                $software_name,
                $family_name,
            );
            if ($softwares == null) {
                $this->addFlash(
                    'errorSoftware', 'Software '.$software_name.' not found, please try again !'
                );
                return $this->redirectToRoute('software');
            }
            if ($family_name != null) {
                $families = $deviceFamilyRepository->findFamilyByNameAll($family_name);
            }
        }
        
        $this->addDirectorySoftware($dbRequest);
        return $this->render('software.html.twig', [
            'softwares' => $softwares,
            'software' => $software,
            'families' => $families,
            'searchform' => $searchform->createView(),
            'ressource_path' => $_ENV["RESSOURCE_PATH"],
        ]);
    }

    function insertNewSoftware($name, $devType, $version, $date){
        //$req = "INSERT INTO ".SOFTWARE_TABLE." (".NAME.", ".FAMILY_TYPE.", ".SOFT_VERSION.", ".SOFT_CREATED_AT.") VALUES ('".$name.", ".$devType.", ".$version.", ".$date."')";
        $req = "INSERT IGNORE INTO ".SOFTWARE_TABLE." (".NAME.", ".FAMILY_TYPE.", ".SOFT_VERSION.", ".SOFT_CREATED_AT.") VALUES ('".$name."', '".$devType."', '".$version."', '".$date."')"; //ON DUPLICATE KEY UPDATE ".UPDATED_AT."= '".date("Y-m-d | H:i:s")."'";
        //$req = "INSERT INTO ".SOFTWARE_TABLE." (".NAME.", ".FAMILY_TYPE.", ".SOFT_VERSION.", ".SOFT_CREATED_AT.") VALUES ('".$name."', '".$devType."', '".$version."', '".$date."') ON DUPLICATE KEY UPDATE ".UPDATED_AT."= '".date("Y-m-d | H:i:s")."'";
        //$req = "INSERT INTO ".SOFTWARE_TABLE." (".NAME.", ".FAMILY_TYPE.", ".SOFT_VERSION.", ".SOFT_CREATED_AT.") VALUES ('".$name."', '".$devType."', '".$version."', '".$date."')"; //ON DUPLICATE KEY UPDATE ".UPDATED_AT."= '".date("Y-m-d | H:i:s")."'";
        return $req;
    }
	
    function initSoftwareInDB($name, $devType, $version, $date, DbRequest $request){
        $req = $this->insertNewSoftware($name, $devType, $version, $date);
        $res = $request->sendRq($req);
        
        return false;
    }

    /*
    function updateNewSoftware($name, $devType, $version, $date){
        $req = "INSERT INTO ".SOFTWARE_TABLE." (".NAME.", ".FAMILY_TYPE.", ".SOFT_VERSION.", ".SOFT_CREATED_AT.") VALUES ('".$name."', '".$devType."', '".$version."', '".$date."') ON DUPLICATE KEY UPDATE ".SOFT_VERSION."= '".$version."',".UPDATED_AT."= '".date("Y-m-d | H:i:s")."'";
        return $req;
    }
	
    function updateSoftwareInDB($name, $devType, $version, $date, DbRequest $request){
        $req = $this->updateNewSoftware($name, $devType, $version, $date);
        $res = $request->sendRq($req);
        
        return false;
    }
    */

    /**
     * Check Directory is copied in db
     * @param DbRequest $request
     * @return void
     */
    public function addDirectorySoftware(DbRequest $request)
    {
        // for each device type in device type array
        foreach (DEVICE_TYPE_ARRAY as $key => $deviceType) {
            if (file_exists($_ENV['REL_PACK_PATH'].$deviceType)) {
                $arrayVersion[$deviceType] = array_diff(scandir($_ENV['REL_PACK_PATH'].$deviceType), array('.'));
                array_shift($arrayVersion[$deviceType]);
                //if record not in db, add record
                // for each file in device type folder, create record in db if not exists
                foreach ($arrayVersion[$deviceType] as $key => $file) {
                    if (str_ends_with($file, ".bin")) {
                        $fileArray = explode("v", $file);
                        $version = substr($fileArray[1], -11, 7);
                        $deviceTypeId = $request->getDeviceType(array_search($deviceType, DEVICE_TYPE_ARRAY), DEVICE_TYPE_ID);
                        $this->initSoftwareInDB($name=$file, $devType=$deviceTypeId, $version, $date=date("Y-m-d | H:i:s"), $request);
                    }
                }
            }
        }
    }

    /**
     * @Route("/{_locale<%app.supported_locales%>}/user/software/add", name="software_add")
     * Upload new software with form
    */
    public function addSoftware(Request $request, DbRequest $dbRequest, ManagerRegistry $doctrine, FileUploader $fileUploader, DeviceFamilyRepository $deviceFamilyRepository, LoggerInterface $logger): Response
    {
        $software = new Software;
        $user = $this->getUser();
        $userRoles = $user->getRoles();
        $form = $this->createForm(SoftwareType::class, $software);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $softwareFile = $form->get('file')->getData();
            if ($softwareFile) {
                $fileName = $form->get('file')->getData()->getClientOriginalName();
                $deviceType = substr($fileName, 7, 2);
                $family = $deviceFamilyRepository->findOneBy(array('numberId'=>$deviceType));
                //$targetDirectory = $fileUploader->getTargetDirectory();
                //var_dump($family->getName());
                //var_dump($user->getRoles());
                if ($userRoles[0]==="ROLE_GOE") {
                    if ($family->getName()=="BACK3TE") {
                        $originalFilename = $fileUploader->upload($softwareFile, "package/".$family->getName().'/');
                        $softwareVersion = substr($fileName, -11, 7);
                        $pattern2 = '/-/i';
                        $softwareVersionModified = preg_replace($pattern2, '.', $softwareVersion);
                        $pattern3 = "/^0{1,2}/";
                        $pattern4 = "/\.0{1,2}/";
                        $softwareVersionModified2 = preg_replace($pattern3, '', $softwareVersionModified);
                        $softwareVersionModified3 = preg_replace($pattern4, '.', $softwareVersionModified2);
                        
                        $software->setDeviceFamily($family);
                        $software->setName($fileName);
                        $software->setVersion($softwareVersionModified3);
                        
                        $user = $this->getUser();
                        $logger->info($user." has uploaded ".$fileName);
                        
                        $dbRequest->updateSoftwareInDB($name=$fileName, $devType=$family->getId(), $version=$softwareVersionModified3, $date=date("Y-m-d | H:i:s"));
                        $this->addFlash('infoSoftware', 'Software '.$fileName.' added with success !');
                    }
                    else {
                        $this->addFlash('infoSoftware', 'Error: Software '.$fileName.' not added. Admin Role required.');
                    }
                }
                else {

                    $originalFilename = $fileUploader->upload($softwareFile, "package/".$family->getName().'/');
                    $softwareVersion = substr($fileName, -11, 7);
                    $pattern2 = '/-/i';
                    $softwareVersionModified = preg_replace($pattern2, '.', $softwareVersion);
                    $pattern3 = "/^0{1,2}/";
                    $pattern4 = "/\.0{1,2}/";
                    $softwareVersionModified2 = preg_replace($pattern3, '', $softwareVersionModified);
                    $softwareVersionModified3 = preg_replace($pattern4, '.', $softwareVersionModified2);
                    
                    $software->setDeviceFamily($family);
                    $software->setName($fileName);
                    $software->setVersion($softwareVersionModified3);
                    
                    $user = $this->getUser();
                    $logger->info($user." has uploaded ".$fileName);
                    
                    $dbRequest->updateSoftwareInDB($name=$fileName, $devType=$family->getId(), $version=$softwareVersionModified3, $date=date("Y-m-d | H:i:s"));
                    $this->addFlash('infoSoftware', 'Software '.$fileName.' added with success !');
                }
                
            }
            
            return $this->redirectToRoute('software');
        }
        return $this->render('software/add.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{_locale<%app.supported_locales%>}/admin/software/edit/{id}", name="software_edit")
    */
    public function editSoftware(Request $request, ManagerRegistry $doctrine, Software $software): Response
    {
        
        $form = $this->createForm(SoftwareType::class, $software);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em = $doctrine->getManager('default');
            $software = $form->getData();
            $em->persist($software);
            $em->flush();

            return $this->redirectToRoute('software');
        }

        return $this->render('software/add.html.twig', [
            'form' => $form,
            'software' => $software
        ]);
    }

    /**
     * @Route("/{_locale<%app.supported_locales%>}/user/software/delete/{deviceFamily}/{id}", name="software_delete")
    */    
    public function deleteSoftware(SoftwareRepository $softwareRepository, ManagerRegistry $doctrine, LoggerInterface $logger, DeviceFamily $deviceFamily, $id)
    {
        //$filesystem = new Filesystem();
        // récupère le nom du software
        //$softwares = $deviceFamily->getSoftwares();
        /*
        $indexOf = $softwares->indexOf($software);
        echo($indexOf);
        $soft = $softwares->get($indexOf);
        */
        //$soft = $software;
        $soft = $softwareRepository->findOneBy(array('id' => $id));
        $name = $soft->getName();
        $deviceType = $soft->getDeviceFamily()->getName();
        if (file_exists($this->getParameter('uploads_directory').'package/'.$deviceType."/".$name)) {
            unlink($this->getParameter('uploads_directory').'package/'.$deviceType."/".$name);
        }
        if (file_exists($this->getParameter('uploads_directory').'archive/'.'package/'.$deviceType."/".$name)) {
            unlink($this->getParameter('uploads_directory').'archive/'.'package/'.$deviceType."/".$name);
        }

        $em = $doctrine->getManager('default');
        $deviceFamily->removeSoftware($soft);
        $em->remove($soft);
        $em->flush();

        $user = $this->getUser();
        $logger->info($user." has deleted ".$name);
        $this->addFlash('message', 'Software '.$name.' deleted with success !');
        return $this->redirectToRoute('software');
    }

    /**
     * @Route("/getSoftId/{id}", name="get_soft_id")
     */
    public function getSoftId(SoftwareRepository $softwareRepository, $id) {
        $software = $softwareRepository->findOneBy(array('id' => $id));
        return new Response($software->getName());
    }

    /**
     * @Route("/addUpdateComment/{id}/{comment}", name="add_update_comment")
     */
    public function addUpdateComment(ManagerRegistry $doctrine, SoftwareRepository $softwareRepository, $id, $comment, LoggerInterface $logger) {
        $user = $this->getUser();
        $software = $softwareRepository->findOneBy(array('id' => $id));
        $comment = str_replace(' - ', "\r\n", $comment);
        if ($comment == "null") {
            $comment = "";
            $logger->info($user." has deleted comment.");
            $this->addFlash('message', 'Comment deleted with success !');
        }
        else {
            $logger->info($user." has added comment ".$comment);
            $this->addFlash('message', 'Comment '.$comment.' added with success !');
        }
        $software->setUpdateComment($comment);

        $em = $doctrine->getManager('default');
        $em->persist($software);
        $em->flush();
        

        //return new Response("true");
        return $this->redirectToRoute('software');
        
    }

    /**
     * @Route("/addActualVersion/{id}/{version}/", name="add_actual_version")
     */
    public function addActualVersion(ManagerRegistry $doctrine, DeviceFamilyRepository $deviceFamilyRepository, SoftwareRepository $softwareRepository, $id, $version, LoggerInterface $logger) {
        $user = $this->getUser();
        $deviceFamily = $deviceFamilyRepository->findOneBy(array('id' => $id));
        $software = $softwareRepository->findOneBy(array('deviceFamily' => $id, 'version' => $version));
        if ($version == "null") {
            $version = "";
            $logger->info($user." has deleted actual version ".$version." for device family ".$deviceFamily);
            $this->addFlash('message', 'Actual Version deleted with success !');
        }
        else {
            $logger->info($user." has added updated actual version ".$version." for device family ".$deviceFamily);
            $this->addFlash('message', 'Actual Version '.$version." updated with success for device family ".$deviceFamily."!");
        }
        $deviceFamily->setActualVersion($software);

        $em = $doctrine->getManager('default');
        $em->persist($deviceFamily);
        $em->flush();
        

        //return new Response("true");
        return $this->redirectToRoute('software');
        
    }
}