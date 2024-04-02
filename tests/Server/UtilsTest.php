<?php
namespace App\Tests\Entity;

use App\Entity\Main\DeviceFamily;
use App\Repository\DeviceFamilyRepository;
use App\Server\DbRequest;
use App\Server\Utils;
use Exception;
//use Monolog\Test\TestCase;

//use Monolog\Test\TestCase as TestTestCase;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{

    public function testDefault()
    {

        $utils = new Utils();
        /* GET FILE CONTENT */
        $rshock_soft_list = array_diff(scandir($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[10]), array('..', '.'));
        $cryo_soft_list = array_diff(scandir($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[11]), array('..', '.'));
        $back4_soft_list = array_diff(scandir($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[12]), array('..', '.'));
        $bio_soft_list = array_diff(scandir($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[13]), array('..', '.'));
        $back3_soft_list = array_diff(scandir($_ENV['PACK_PATH'].DEVICE_TYPE_ARRAY[10]), array('..', '.'));

        foreach ($back3_soft_list as $soft) {
            $this->assertSame(true, $utils->getFileContentTest('14', $soft));
        }
        foreach ($back4_soft_list as $soft) {
            $this->assertSame(true, $utils->getFileContentTest('12', $soft));
        }
        //$this->assertSame(true, $utils->getFileContentTest('12', "WLE256_12_2_v003.015.bin")); //Check when filename is not present, if device get last version
        foreach ($bio_soft_list as $soft) {
            $this->assertSame(true, $utils->getFileContentTest('13', $soft));
        }
        foreach ($cryo_soft_list as $soft) {
            $this->assertSame(true, $utils->getFileContentTest('11', $soft));
        }
        foreach ($rshock_soft_list as $soft) {
            $this->assertSame(true, $utils->getFileContentTest('10', $soft));
        }
        
        /*
        foreach (DEVICE_TYPE_ARRAY as $deviceType) {
            $soft_list = array_diff(scandir(PACK_PATH.DEVICE_TYPE_ARRAY[$deviceType]), array('..', '.'));
            foreach ($soft_list as $soft) {
                $this->assertSame(true, $utils->getFileContentCheck($deviceType, $soft));
            }
        }
        */
    }
}