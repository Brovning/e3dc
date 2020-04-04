<?php

declare(strict_types=1);

if (!defined('VARIABLETYPE_BOOLEAN'))
{
    define('VARIABLETYPE_BOOLEAN', 0);
    define('VARIABLETYPE_INTEGER', 1);
    define('VARIABLETYPE_FLOAT', 2);
    define('VARIABLETYPE_STRING', 3);
}

if (!defined('KL_DEBUG'))
{
    define('KL_DEBUG', 10206);		// Debugmeldung (werden ausschlie�lich ins Log geschrieben. Bei Deaktivierung des Spezialschalter "LogfileVerbose" werden diese nichtmal ins Log geschrieben.)
    define('KL_ERROR', 10206);		// Fehlermeldung
    define('KL_MESSAGE', 10201);	// Nachricht
    define('KL_NOTIFY', 10203);		// Benachrichtigung
    define('KL_WARNING', 10204);	// Warnung
}

// ModBus RTU TCP
if (!defined('MODBUS_INSTANCES'))
{
	define("MODBUS_INSTANCES", "{A5F663AB-C400-4FE5-B207-4D67CC030564}");
}
if (!defined('CLIENT_SOCKETS'))
{
	define("CLIENT_SOCKETS", "{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
}
if (!defined('MODBUS_ADDRESSES'))
{
	define("MODBUS_ADDRESSES", "{CB197E50-273D-4535-8C91-BB35273E3CA5}");
}

// Offset von Register (erster Wert 1) zu Adresse (erster Wert 0) ist -1
if (!defined('MODBUS_REGISTER_TO_ADDRESS_OFFSET'))
{
	define("MODBUS_REGISTER_TO_ADDRESS_OFFSET", -1);
}

trait myFunctions
{
	/* Arithmetisches Mittel von Logwerten
	 * ermittelt aus den Logwerten
	 * TimeRange = Zeitintervall in Minuten
	 */
	private function getArithMittelOfLog($archiveId, $logId, $timeRange, $startZeit = false)
	{
		// Startzeit des Intervalls auf aktuelle Zeit setzen, wenn nicht gesetzt
		if (!$startZeit)
		{
			$startZeit = time();
		}

		// Lese Logwerte der TimeRange Minuten beginnend ab StartZeit
		$buffer = AC_GetLoggedValues($archiveId, $logId, ($startZeit - ($timeRange * 60)), $startZeit, 0);
		//print_r($buffer);

		// Keine Logwerte in der TimeRange vorhanden
		if (0 == count($buffer))
		{
			// --> abbrechen
			return false;
		}
		// Logwerte vorhanden
		else
		{
			// Duration der jeweiligen Messung ermitteln
			$buffer[0]['Duration'] = 0;
			for ($i = 1; $i < count($buffer); $i++)
			{
				$buffer[$i]['Duration'] = $buffer[$i - 1]['TimeStamp'] - $buffer[$i]['TimeStamp'];
				//			echo "Buffer[".$i."][Duration]=".$buffer[$i]['Duration']."\n";
			}

			// ermittle die Werte für die Weiterverarbeitung
			$bufferValues = array();
			$bufferDuration = 0;
			for ($i = 0; $i < count($buffer); $i++)
			{
				// Wert mit Gewichtung Multiplizieren
				$bufferValues[$i] = $buffer[$i]['Value'] * $buffer[$i]['Duration'];

				// Summe der Gewichtungen ermitteln
				$bufferDuration += $buffer[$i]['Duration'];
			}
			//      echo "bufferDuration(Sum)=".$bufferDuration."\n";

			// Durchschnittsgewichtung
			$bufferDuration = $bufferDuration / count($buffer);

			//      echo "bufferDuration(Average)=".$bufferDuration."\n";

			if (0 == $bufferDuration)
			{
				return false;
			}
			else
			{
				// ermittle das arithmetische Mittel unter Berücksichtigung der Gewichtung
				return getArithMittel($bufferValues) / $bufferDuration;
			}
		}
	}

    private function readOldModbusGateway()
    {
        $modbusGatewayId_Old = 0;
        $clientSocketId_Old = 0;

        $childIds = IPS_GetChildrenIDs($this->InstanceID);

        foreach($childIds AS $childId)
        {
            $modbusAddressInstanceId = @IPS_GetInstance($childId);

            if(MODBUS_ADDRESSES == $modbusAddressInstanceId['ModuleInfo']['ModuleID'])
            {
                $modbusGatewayId_Old = $modbusAddressInstanceId['ConnectionID'];
                $clientSocketId_Old = @IPS_GetInstance($modbusGatewayId_Old)['ConnectionID'];
                break;
            }
        }
        
        return array($modbusGatewayId_Old, $clientSocketId_Old);
    }

    private function deleteInstanceNotInUse($connectionId_Old, $moduleId)
    {
        if(!IPS_ModuleExists($moduleId))
        {
            echo "ModuleId ".$moduleId." does not exist!\n";
        }
        else
        {
            $inUse = false;

            foreach(IPS_GetInstanceListByModuleID($moduleId) AS $instanceId)
            {
                $instance = IPS_GetInstance($instanceId);

                if($connectionId_Old == $instance['ConnectionID'])
                {
                    $inUse = true;
                    break;
                }
            }

            // L�sche Connection-Instanz (bspw. ModbusAddress, ClientSocket,...), wenn nicht mehr in Verwendung
            if(!$inUse)
            {
                IPS_DeleteInstance($connectionId_Old);
            }
        }
    }

    private function checkModbusGateway($hostIp, $hostPort, $hostmodbusDevice, $hostSwapWords)
    {
        // Splitter-Instance Id des ModbusGateways
        $gatewayId = 0;
        // I/O Instance Id des ClientSockets
        $interfaceId = 0;

        foreach(IPS_GetInstanceListByModuleID(MODBUS_INSTANCES) AS $modbusInstanceId)
        {
            $connectionInstanceId = IPS_GetInstance($modbusInstanceId)['ConnectionID'];

            if(0 != (int)$connectionInstanceId && $hostIp == IPS_GetProperty($connectionInstanceId, "Host") && $hostPort == IPS_GetProperty($connectionInstanceId, "Port"))
            {
                if(DEBUG) echo "ModBus Instance and ClientSocket found: ".$modbusInstanceId.", ".$connectionInstanceId."\n";

                $gatewayId = $modbusInstanceId;
                $interfaceId = $connectionInstanceId;
                break;
            }
        }

        // Modbus-Gateway erstellen, sofern noch nicht vorhanden
        $applyChanges = false;
        if(0 == $gatewayId)
        {
            if(DEBUG) echo "ModBus Instance not found!\n";

            // ModBus Gateway erstellen
            $gatewayId = IPS_CreateInstance(MODBUS_INSTANCES); 
            IPS_SetInfo($gatewayId, MODUL_PREFIX."-Modul: ".date("Y-m-d H:i:s"));
            $applyChanges = true;
        }

        // Modbus-Gateway Einstellungen setzen
        if(MODUL_PREFIX."ModbusGateway" != IPS_GetName($gatewayId))
        {
            IPS_SetName($gatewayId, MODUL_PREFIX."ModbusGateway");
        }
        if(0 != IPS_GetProperty($gatewayId, "GatewayMode"))
        {
            IPS_SetProperty($gatewayId, "GatewayMode", 0);
            $applyChanges = true;
        }
        if($hostmodbusDevice != IPS_GetProperty($gatewayId, "DeviceID"))
        {
            IPS_SetProperty($gatewayId, "DeviceID", $hostmodbusDevice);
            $applyChanges = true;
        }
        if($hostSwapWords != IPS_GetProperty($gatewayId, "SwapWords"))
        {
            IPS_SetProperty($gatewayId, "SwapWords", $hostSwapWords);
            $applyChanges = true;
        }

        if($applyChanges)
        {
            @IPS_ApplyChanges($gatewayId);
            IPS_Sleep(100);
        }

        
        // Hat Modbus-Gateway bereits einen ClientSocket?
        $applyChanges = false;
        $clientSocketId = (int)IPS_GetInstance($gatewayId)['ConnectionID'];
        if(0 == $interfaceId && 0 != $clientSocketId)
        {
            $interfaceId = $clientSocketId;
        }

        // ClientSocket erstellen, sofern noch nicht vorhanden
        if(0 == $interfaceId)
        {
            if(DEBUG) echo "Client Socket not found!\n";

            // Client Soket erstellen
            $interfaceId = IPS_CreateInstance(CLIENT_SOCKETS);
            IPS_SetInfo($interfaceId, MODUL_PREFIX."-Modul: ".date("Y-m-d H:i:s"));

            $applyChanges = true;
        }

        // ClientSocket Einstellungen setzen
        if(MODUL_PREFIX."ClientSocket" != IPS_GetName($interfaceId))
        {
            IPS_SetName($interfaceId, MODUL_PREFIX."ClientSocket");
            $applyChanges = true;
        }
        if($hostIp != IPS_GetProperty($interfaceId, "Host"))
        {
            IPS_SetProperty($interfaceId, "Host", $hostIp);
            $applyChanges = true;
        }
        if($hostPort != IPS_GetProperty($interfaceId, "Port"))
        {
            IPS_SetProperty($interfaceId, "Port", $hostPort);
            $applyChanges = true;
        }
        if(true != IPS_GetProperty($interfaceId, "Open"))
        {
            IPS_SetProperty($interfaceId, "Open", true);
            $applyChanges = true;
        }

        if($applyChanges)
        {
            @IPS_ApplyChanges($interfaceId);
            IPS_Sleep(100);
        }


        // Client Socket mit Gateway verbinden
        if(0 != $clientSocketId)
        {
            IPS_DisconnectInstance($gatewayId);
            IPS_ConnectInstance($gatewayId, $interfaceId);
        }
        
        return array($gatewayId, $interfaceId);
    }
    
    private function createVarProfile($ProfilName, $ProfileType, $Suffix = '', $MinValue = 0, $MaxValue = 0, $StepSize = 0, $Digits = 0, $Icon = 0, $Associations = '')
    {
        if(!IPS_VariableProfileExists($ProfilName))
        {
            IPS_CreateVariableProfile($ProfilName, $ProfileType);
            IPS_SetVariableProfileText($ProfilName, '', $Suffix);
            
            if(in_array($ProfileType, array(VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT)))
            {
                IPS_SetVariableProfileValues($ProfilName, $MinValue, $MaxValue, $StepSize);
                IPS_SetVariableProfileDigits($ProfilName, $Digits);
            }
            
            IPS_SetVariableProfileIcon($ProfilName, $Icon);
            
            if($Associations != '')
            {
                foreach ($Associations as $a)
                {
                    $w = isset($a['Wert']) ? $a['Wert'] : '';
                    $n = isset($a['Name']) ? $a['Name'] : '';
                    $i = isset($a['Icon']) ? $a['Icon'] : '';
                    $f = isset($a['Farbe']) ? $a['Farbe'] : -1;
                    IPS_SetVariableProfileAssociation($ProfilName, $w, $n, $i, $f);
                }
            }

            if(DEBUG) echo "Profil ".$ProfilName." erstellt\n";
        }
    }

    private function removeInvalidChars($input)
    {
        return preg_replace( '/[^a-z0-9]/i', '', $input);
    }

    private function deleteModbusInstancesRecursive($inverterModelRegister_array, $categoryId)
    {
        foreach($inverterModelRegister_array AS $register)
        {
            $instanceId = @IPS_GetObjectIDByIdent($register[IMR_START_REGISTER], $categoryId);
            if(false !== $instanceId)
            {
                $this->deleteInstanceRecursive($instanceId);
            }
        }
    }

    private function deleteInstanceRecursive($instanceId)
    {
        foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
        {
            IPS_DeleteVariable($childChildId);
        }
        IPS_DeleteInstance($instanceId);
    }

    private function MaintainInstanceVariable($Ident, $Name, $Typ, $Profil = "", $Position = 0, $Beibehalten = true, $instanceId, $varInfo = "")
    {
        $varId = @IPS_GetObjectIDByIdent($Ident, $instanceId);
        if(false === $varId && $Beibehalten)
        {
            switch($Typ)
            {
                case VARIABLETYPE_BOOLEAN:
                    $varId = $this->RegisterVariableBoolean($Ident, $Name, $Profil, $Position);
                    break;
                case VARIABLETYPE_FLOAT:
                    $varId = $this->RegisterVariableFloat($Ident, $Name, $Profil, $Position);
                    break;
                case VARIABLETYPE_INTEGER:
                    $varId = $this->RegisterVariableInteger($Ident, $Name, $Profil, $Position);
                    break;
                case VARIABLETYPE_STRING:
                    $varId = $this->RegisterVariableString($Ident, $Name, $Profil, $Position);
                    break;
                default:
                    echo "Variable-Type unknown!";
                    $varId = false;
                    exit;
            }
            IPS_SetParent($varId, $instanceId);
            IPS_SetInfo($varId, $varInfo);
        }
        
        if(!$Beibehalten && false !== $varId)
        {
            IPS_DeleteVariable($varId);
            $varId = false;
        }

        return $varId;
    }
}