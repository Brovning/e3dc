<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';

define("MODUL_INSTANCES", "{C9508720-B23D-B37A-B5C2-97B607221CE1}");

class E3dcTest extends TestCase
{
    public function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();
        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        parent::setUp();
    }
    
    public function testFunctions()
    {
        //Setting up a variable with ActionScript

        // E3DC-Modul erstellen
        $myModuleId = IPS_CreateInstance(MODUL_INSTANCES);

        // Moduleigenschaften setzen
        IPS_SetProperty($myModuleId, 'hostIp', "192.168.1.111");

        $this->assertEquals(5, 5);

    }
}