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

            if(isset($modbusAddressInstanceId['ModuleInfo']['ModuleID']) && MODBUS_ADDRESSES == $modbusAddressInstanceId['ModuleInfo']['ModuleID'])
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
            $this->SendDebug("deleteInstanceNotInUse()", "ERROR: ModuleId ".$moduleId." does not exist!", 0);
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
        $foundGatewayId = 0;
        // I/O Instance Id des ClientSockets
        $foundClientSocketId = 0;

// Erst die ClientSockets durchsuchen
// --> ClientSocketId merken (somit kann es keine doppelten ClientSockets mehr geben!!!)

// danach die dazugehörige GatewayId ermitteln und merken

        foreach(IPS_GetInstanceListByModuleID(MODBUS_INSTANCES) AS $modbusInstanceId)
        {
            $connectionInstanceId = IPS_GetInstance($modbusInstanceId)['ConnectionID'];

            // check, if hostIp and hostPort of currenct ClientSocket is matching new settings
            if(0 != (int)$connectionInstanceId && $hostIp == IPS_GetProperty($connectionInstanceId, "Host") && $hostPort == IPS_GetProperty($connectionInstanceId, "Port"))
            {
                $foundClientSocketId = $connectionInstanceId;

                // check, if "Geraete-ID" of currenct ModbusGateway is matching new settings
                if ($hostmodbusDevice == IPS_GetProperty($modbusInstanceId, "DeviceID"))
                {
                    $foundGatewayId = $modbusInstanceId;
                }

                $this->SendDebug("ModBusInstance and ClientSocket", "found: ModBusInstance=".$foundGatewayId.", ClientSocket=".$foundClientSocketId, 0);

                break;
            }
        }

        // Modbus-Gateway erstellen, sofern noch nicht vorhanden
        $applyChanges = false;
        $currentGatewayId = 0;
        if(0 == $foundGatewayId)
        {
            $this->SendDebug("ModBusInstance and ClientSocket", "not found!", 0);

            // ModBus Gateway erstellen
            $currentGatewayId = IPS_CreateInstance(MODBUS_INSTANCES); 
            IPS_SetInfo($currentGatewayId, MODUL_PREFIX."-Modul: ".date("Y-m-d H:i:s"));
            $applyChanges = true;

            // Achtung: ClientSocket wird immer mit erstellt
            $clientSocketId = (int)IPS_GetInstance($currentGatewayId)['ConnectionID'];
            IPS_SetInfo($clientSocketId, MODUL_PREFIX."-Modul: ".date("Y-m-d H:i:s"));
            IPS_SetName($clientSocketId, MODUL_PREFIX."ClientSocket_Temp");

            $this->SendDebug("ModBusInstance and ClientSocket", "created: ModBusInstance=".$currentGatewayId.", ClientSocket=".$clientSocketId, 0);
        }
        else
        {
            $currentGatewayId = $foundGatewayId;
        }

        // Modbus-Gateway Einstellungen setzen
        if(MODUL_PREFIX."ModbusGateway" != IPS_GetName($currentGatewayId))
        {
            IPS_SetName($currentGatewayId, MODUL_PREFIX."ModbusGateway".$hostmodbusDevice);
        }
        if(0 != IPS_GetProperty($currentGatewayId, "GatewayMode"))
        {
            IPS_SetProperty($currentGatewayId, "GatewayMode", 0);
            $applyChanges = true;
        }
        if($hostmodbusDevice != IPS_GetProperty($currentGatewayId, "DeviceID"))
        {
            IPS_SetProperty($currentGatewayId, "DeviceID", $hostmodbusDevice);
            $applyChanges = true;
        }
        if($hostSwapWords != IPS_GetProperty($currentGatewayId, "SwapWords"))
        {
            IPS_SetProperty($currentGatewayId, "SwapWords", $hostSwapWords);
            $applyChanges = true;
        }

        if($applyChanges)
        {
            @IPS_ApplyChanges($currentGatewayId);
            IPS_Sleep(100);
        }

        
        // Hat Modbus-Gateway bereits einen ClientSocket?
        $applyChanges = false;
        $clientSocketId = (int)IPS_GetInstance($currentGatewayId)['ConnectionID'];
        $currentClientSocketId = 0;
        // wenn ja und noch kein Interface vorhanden, dann den neuen ClientSocket verwenden
        if(0 == $foundClientSocketId && 0 != $clientSocketId)
        {
            // neuen ClientSocket als Interface merken
            $currentClientSocketId = $clientSocketId;
        }
        // wenn ja und bereits ein Interface vorhanden, dann den neuen ClientSocket löschen
        else if(0 != $foundClientSocketId/* && 0 != $clientSocketId*/)
        {
            // bereits vorhandenen ClientSocket weiterverwenden
            $currentClientSocketId = $foundClientSocketId;
        }
        // ClientSocket erstellen, sofern noch nicht vorhanden
        else /*if (0 == $currentClientSocketId)*/
        {
            $this->SendDebug("ModBusInstance and ClientSocket", "ModBusInstance=".$currentGatewayId.", ClientSocket not found!", 0);

            // Client Soket erstellen
            $currentClientSocketId = IPS_CreateInstance(CLIENT_SOCKETS);
            IPS_SetInfo($currentClientSocketId, MODUL_PREFIX."-Modul: ".date("Y-m-d H:i:s"));

            $this->SendDebug("ModBusInstance and ClientSocket", "ClientSocket=".$currentClientSocketId." created", 0);

            $applyChanges = true;
        }

        // ClientSocket Einstellungen setzen
        if(MODUL_PREFIX."ClientSocket" != IPS_GetName($currentClientSocketId))
        {
            IPS_SetName($currentClientSocketId, MODUL_PREFIX."ClientSocket");
            $applyChanges = true;
        }
        if($hostIp != IPS_GetProperty($currentClientSocketId, "Host"))
        {
            IPS_SetProperty($currentClientSocketId, "Host", $hostIp);
            $applyChanges = true;
        }
        if($hostPort != IPS_GetProperty($currentClientSocketId, "Port"))
        {
            IPS_SetProperty($currentClientSocketId, "Port", $hostPort);
            $applyChanges = true;
        }
        if(true != IPS_GetProperty($currentClientSocketId, "Open"))
        {
            IPS_SetProperty($currentClientSocketId, "Open", true);
            $applyChanges = true;
        }

        if($applyChanges)
        {
            @IPS_ApplyChanges($currentClientSocketId);
            IPS_Sleep(100);
        }


        // Client Socket mit Gateway verbinden
        // sofern bereits ein ClientSocket mit dem Gateway verbunden ist, dieses vom Gateway trennen und löschen
        $oldClientSocket = (int)IPS_GetInstance($currentGatewayId)['ConnectionID'];
        if($oldClientSocket != $currentClientSocketId)
        {
            if(0 != $oldClientSocket)
            {
                IPS_DisconnectInstance($currentGatewayId);
                $this->deleteInstanceNotInUse($oldClientSocket, CLIENT_SOCKETS);
            }

            // neuen ClientSocket mit Gateway verbinden
            IPS_ConnectInstance($currentGatewayId, $currentClientSocketId);

            $this->SendDebug("ModBusInstance and ClientSocket", "remove old ClientSocket=".$oldClientSocket." and connect new ClientSocket=".$currentClientSocketId." with ModBusInstance=".$currentGatewayId, 0);
        }
        
        return array($currentGatewayId, $currentClientSocketId);
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

            $this->SendDebug("Variable-Profile", "Profile ".$ProfilName." created", 0);
        }
    }

    private function removeInvalidChars($input)
    {
        return preg_replace( '/[^a-z0-9]/i', '', $input);
    }

    private function deleteModbusInstancesRecursive($inverterModelRegister_array, $categoryId, $uniqueIdent="")
    {
        foreach($inverterModelRegister_array AS $register)
        {
            $instanceId = @IPS_GetObjectIDByIdent($register[IMR_START_REGISTER].$uniqueIdent, $categoryId);
            if(false !== $instanceId)
            {
                $this->deleteInstanceRecursive($instanceId);

                $this->SendDebug("delete Modbus address", "REG_".$register[IMR_START_REGISTER]." - ".$register[IMR_NAME].", ID=".$instanceId, 0);
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
                    $this->SendDebug("MaintainInstanceVariable", "ERROR: Variable-Type unknown!", 0);
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

    private function myMaintainVariable($Ident, $Name, $Typ, $Profil = "", $Position = 0, $Beibehalten = true)
    {
        $this->MaintainVariable($Ident, $Name, $Typ, $Profil, $Position, $Beibehalten);

        if($Beibehalten)
        {
			$varId = IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
        }
        else
        {
            $varId = false;
        }

        return $varId;
    }

    private function getPowerSumOfLog($logId, $startTime, $endTime, $mode=0)
    {
        $archiveId = $this->getArchiveId();
        $bufferSum = 0;

        // Lese Logwerte der TimeRange Minuten beginnend ab StartZeit
        $buffer = AC_GetLoggedValues($archiveId, $logId, $startTime, $endTime, 0);

        // Keine Logwerte in der TimeRange vorhanden
        if(0 == count($buffer))
        {
            // --> abbrechen
            $bufferSum = 0;
        }
        // Zu viele Logwerte in der TimeRange vorhanden
        else if(10000 <= count($buffer))
        {
            $intervallEdge = $startTime + ($endTime - $startTime) / 2;
            $bufferSum = $this->getPowerSumOfLog($logId, $startTime, $intervallEdge, $mode) + $this->getPowerSumOfLog($logId, $intervallEdge, $endTime, $mode);
        }
        // Logwerte vorhanden
        else
        {
            // Duration der jeweiligen Messung ermitteln
            $buffer[0]['Duration'] = 0;
            for($i = 1; $i < count($buffer); $i++)
            {
                $buffer[$i]['Duration'] = $buffer[$i-1]['TimeStamp'] - $buffer[$i]['TimeStamp'];
            }

            // ermittle die Werte für die Weiterverarbeitung
            for($i = 0; $i < count($buffer); $i++)
            {
                // Wert mit Gewichtung Multiplizieren
                if(0 == $mode)
                {
                    // --> alle Werte aufsummieren
                    $bufferSum += ($buffer[$i]['Value'] * $buffer[$i]['Duration'] / 3600);
                }
                else if(1 == $mode && 0 <= $buffer[$i]['Value'])
                {
                    // --> nur positive Werte aufsummieren
                    $bufferSum += ($buffer[$i]['Value'] * $buffer[$i]['Duration'] / 3600);
                }
                else if(2 == $mode && 0 > $buffer[$i]['Value'])
                {
                    // --> nur negative Werte aufsummieren
                    $bufferSum += ($buffer[$i]['Value'] * $buffer[$i]['Duration'] / 3600);
                }
                else if(2 < $mode)
                {
                    $this->SendDebug("getPowerSumOfLog()", "ERROR: Mode '".$mode."' unkown!", 0);
                }
            }
        }

        return $bufferSum;
    }

    // Inspired by module SymconTest/HookServe
    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    // Inspired by module SymconTest/HookServe
    private function GetMimeType($extension)
    {
        $lines = file(IPS_GetKernelDirEx() . 'mime.types');
        foreach ($lines as $line) {
            $type = explode("\t", $line, 2);
            if (count($type) == 2) {
                $types = explode(' ', trim($type[1]));
                foreach ($types as $ext) {
                    if ($ext == $extension) {
                        return $type[0];
                    }
                }
            }
        }
        return 'text/plain';
    }

    // ermittelt die InstanzId des Archive Controls (Datanbank des Variablen-Loggings)
    private function getArchiveId()
    {
        $archiveId = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}");
        if (isset($archiveId[0]))
        {
            $archiveId = $archiveId[0];
        }
        else
        {
            $archiveId = false;

            $this->SendDebug("getArchiveId()", "ERROR: archive of IP-Symcon not found!", 0);
        }

        return $archiveId;
    }

    // Reduce LogSize by keeping the newest value and removing all older values per Intervall $aggregation (=minute, hour, day)
    function RecordReducing($ID, $MStartDate, $MEndDate, $aggregation = "i")
    {
/* !!! ACHTUNG: Aktuell noch Fehlerhaft ! ! !
        $ah_ID = $this->getArchiveId();
        if(false === $ah_ID)
        {
            return false;
        }

        if("i" != $aggregation // Minute
            && "G" != $aggregation // Stunde
            && "j" != $aggregation // Tag
        )
        {
            return false;
        }

        // Definition "Reducing-Zeitraum"
        $p_ts = $MStartDate;  // Angabe Startzeitpunkt Reducing-Periode
        $p_te = $MEndDate;  // Angabe Startzeitpunkt Reducing-Periode
    
        $i_max = (int)round(($p_te - $p_ts)/(60*60*24), 0);
    
        // Tagesschleife
        for($i=0; $i<$i_max; $i++)
        {
            // Datensätze für einen Tag aus AC holen
            $ts = mktime(0,0,0,date("m", $p_ts),date("d", $p_ts) + $i,date("Y", $p_ts));
            $te = mktime(23,59,59,date("m", $p_ts),date("d", $p_ts) + $i,date("Y", $p_ts));
//    echo "#$ID i=$i, i_max=$i_max,  ts: ".date("d.m.Y H:i:s", $ts)."\n";
            $Data = AC_GetLoggedValues($ah_ID,$ID,$ts,$te,5000);
    
            // human Date in Datenarray einfügen
            foreach($Data as $key=>$v)
            {
               $Data[$key]['TimeStamp_humanDate'] = date("d.m.Y H:i:s", $v['TimeStamp']);
            }
            $Raw = array_reverse($Data);
            //print_r($Raw);
    
            // Datensätze für einen Tag zwischen den Intervallen löschen
            $RawCount = count($Raw)-1;
            foreach($Raw as $key=>$v)
            {
                //echo "Key = ".$key;
                if(0 == $key)
                {
                    // bei erstem Durchlauf Startwerte setzen
                    $Count = 0;
                    $i_Flag = date($aggregation, $v['TimeStamp']);
                    $i_TimeStart = $v['TimeStamp'] + 1;
                    $i_TimeEnd = $v['TimeStamp'];
                    //echo " --> init ".$i_Flag."\n";
                }
    
                // ab 2. Durchlauf Werte vergleichen und Endzeitpunkt setzen
                if(0 < $key)
                {
                    //echo " --> ".$i_Flag." == ".date($aggregation, $v['TimeStamp'])."\n";
                   // wenn gleiche Minute, $i_TimeEnd erhöhen
                   if($i_Flag == date($aggregation, $v['TimeStamp']))
                   {
                      $Count++;
                      $i_TimeEnd = $v['TimeStamp'];
                   }
                   else
                   {
                        // wenn nächte Minute erreicht, Datensätze des vorangegangenen Intervalls löschen
                        if(0 < $Count)
                        {
                            AC_DeleteVariableData($ah_ID,$ID, $i_TimeStart, $i_TimeEnd);
                            //IPS_LogMessage("RecordReducer", "ID $ID: Tag $i, #$Count Werte zwischen ".date("d.m.Y, H:i:s", $i_TimeStart)." und ".date("d.m.Y, H:i:s", $i_TimeEnd)." gelöscht\n");
//echo "Tag $i, #$Count Werte zwischen ".date("d.m.Y, H:i:s", $i_TimeStart)." und ".date("d.m.Y, H:i:s", $i_TimeEnd)." gelöscht\n";
                        }
    
                        // Startwerte für neues Intervall setzen
                        $Count = 0;
                        $i_Flag	= date($aggregation, $v['TimeStamp']);
                        $i_TimeStart = $v['TimeStamp'] + 1;
                        $i_TimeEnd = $v['TimeStamp'];
    
                    }
    
                    // letztes Intervall im Array löschen
                    if(($Count > 0) && ($RawCount == $key))
                    {
                        $i_TimeEnd = $v['TimeStamp'];
                        AC_DeleteVariableData($ah_ID,$ID, $i_TimeStart, $i_TimeEnd);
// IPS_LogMessage("RS Record Reducer", "ID $ID: #$Count Werte letztes Intervall zwischen ".date("d.m.Y, H:i:s", $i_TimeStart)." und ".date("d.m.Y, H:i:s", $i_TimeEnd)." gelöscht\n");
//echo "#$Count Werte letztes Intervall zwischen ".date("d.m.Y, H:i:s", $i_TimeStart)." und ".date("d.m.Y, H:i:s", $i_TimeEnd)." gelöscht\n";
                    }
                }
            }
        }
*/
        return true;
    }
}