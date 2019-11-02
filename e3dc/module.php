<?php

if (!defined('DEBUG'))
{
	define("DEBUG", false);
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

// Modul Prefix
if (!defined('MODUL_PREFIX'))
{
	define("MODUL_PREFIX", "E3DC");
}

// Offset von Register (erster Wert 1) zu Adresse (erster Wert 0) ist -1
if (!defined('REGISTER_TO_ADDRESS_OFFSET'))
{
	define("REGISTER_TO_ADDRESS_OFFSET", -1);
}

// ArrayOffsets
if (!defined('IMR_START_REGISTER'))
{
	define("IMR_START_REGISTER", 0);
}
/*if (!defined('IMR_END_REGISTER'))
{
	define("IMR_END_REGISTER", 3);
}*/
if (!defined('IMR_SIZE'))
{
	define("IMR_SIZE", 1);
}
/*if (!defined('IMR_RW'))
{
	define("IMR_RW", 3);
}*/
if (!defined('IMR_FUNCTION_CODE'))
{
	define("IMR_FUNCTION_CODE", 2);
}
if (!defined('IMR_NAME'))
{
	define("IMR_NAME", 3);
}
if (!defined('IMR_TYPE'))
{
	define("IMR_TYPE", 4);
}
if (!defined('IMR_UNITS'))
{
	define("IMR_UNITS", 5);
}
if (!defined('IMR_DESCRIPTION'))
{
	define("IMR_DESCRIPTION", 6);
}

// profileAssociation Offsets
if (!defined('PAO_NAME'))
{
	define("PAO_NAME", 0);
}
if (!defined('PAO_VALUE'))
{
	define("PAO_VALUE", 1);
}
if (!defined('PAO_DESCRIPTION'))
{
	define("PAO_DESCRIPTION", 2);
}
if (!defined('PAO_COLOR'))
{
	define("PAO_COLOR", 3);
}


	class E3DC extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();


			// *** Properties ***
			$this->RegisterPropertyBoolean('active', 'true');
			$this->RegisterPropertyString('hostIp', '');
			$this->RegisterPropertyInteger('hostPort', '502');
			$this->RegisterPropertyBoolean('readExtLeistung', 'false');
			$this->RegisterPropertyBoolean('readWallbox0', 'false');
			$this->RegisterPropertyBoolean('readWallbox1', 'false');
			$this->RegisterPropertyBoolean('readWallbox2', 'false');
			$this->RegisterPropertyBoolean('readWallbox3', 'false');
			$this->RegisterPropertyBoolean('readWallbox4', 'false');
			$this->RegisterPropertyBoolean('readWallbox5', 'false');
			$this->RegisterPropertyBoolean('readWallbox6', 'false');
			$this->RegisterPropertyBoolean('readWallbox7', 'false');
			$this->RegisterPropertyBoolean('readDcString', 'false');
			$this->RegisterPropertyInteger('pollCycle', '60000');


			// *** Erstelle deaktivierte Timer ***
			// Autarkie und Eigenverbrauch
			$this->RegisterTimer("Update-Autarkie-Eigenverbrauch", 0, "\$instanceId = IPS_GetInstanceIDByName(\"Autarkie-Eigenverbrauch\", ".$this->InstanceID.");
\$varValue = GetValue(IPS_GetVariableIDByName(\"Value\", \$instanceId));
\$Autarkie = (\$varValue >> 8 ) & 0xFF;
\$Eigenverbrauch = (\$varValue & 0xFF);

\$AutarkieId = IPS_GetVariableIDByName(\"Autarkie\", \$instanceId);
\$EigenverbrauchId = IPS_GetVariableIDByName(\"Eigenverbrauch\", \$instanceId);

if(GetValue(\$AutarkieId) != \$Autarkie)
{
	SetValue(\$AutarkieId, \$Autarkie);
}

if(GetValue(\$EigenverbrauchId) != \$Eigenverbrauch)
{
	SetValue(\$EigenverbrauchId, \$Eigenverbrauch);
}");

			// EMS-Status Bits
			$this->RegisterTimer("Update-EMS-Status", 0, "\$instanceId = IPS_GetInstanceIDByName(\"EMS-Status\", ".$this->InstanceID.");
\$varValue = GetValue(IPS_GetVariableIDByName(\"Value\", \$instanceId));

\$bitArray = array(\"Batterie laden\", \"Batterie entladen\", \"Notstrommodus\", \"Wetterbasiertes Laden\", \"Abregelungs-Status\", \"Ladesperrzeit\", \"Entladesperrzeit\");

for(\$i = 0; \$i < count(\$bitArray); \$i++)
{
	\$bitId = IPS_GetVariableIDByName(\$bitArray[\$i], \$instanceId);
    \$bitValue = (\$varValue >> \$i ) & 0x1;

	if(GetValue(\$bitId) != \$bitValue)
	{
		SetValue(\$bitId, \$bitValue);
	}
}");

			// WallBox_X_CTRL Bits
			$this->RegisterTimer("Update-WallBox_X_CTRL", 0, "for(\$wallbox = 0; \$wallbox <= 7; \$wallbox++)
{
	\$instanceId = @IPS_GetInstanceIDByName(\"WallBox_\".\$wallbox.\"_CTRL\", ".$this->InstanceID.");
	
	if(false !== \$instanceId)
	{
		\$varValue = GetValue(IPS_GetVariableIDByName(\"Value\", \$instanceId));

		\$bitArray = array(\"Wallbox\", \"Solarbetrieb\", \"Laden sperren\", \"Ladevorgang\", \"Typ-2-Stecker verriegelt\", \"Typ-2-Stecker gesteckt\", \"Schukosteckdose\", \"Schukostecker gesteckt\", \"Schukostecker verriegelt\", \"16A 1 Phase\", \"16A 3 Phasen\", \"32A 3 Phasen\", \"1 Phase\");

		for(\$i = 0; \$i < count(\$bitArray); \$i++)
		{
			\$bitId = IPS_GetVariableIDByName(\$bitArray[\$i], \$instanceId);
			\$bitValue = (\$varValue >> \$i ) & 0x1;

			if(GetValue(\$bitId) != \$bitValue)
			{
				SetValue(\$bitId, \$bitValue);
			}
		}
	}
}");


			// *** Erstelle Variablen-Profile ***
			$this->checkProfiles();
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			//Properties
			$active = $this->ReadPropertyBoolean('active');
			$hostIp = $this->ReadPropertyString('hostIp');
			$hostPort = $this->ReadPropertyInteger('hostPort');
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');
			$readWallbox0 = $this->ReadPropertyBoolean('readWallbox0');
			$readWallbox1 = $this->ReadPropertyBoolean('readWallbox1');
			$readWallbox2 = $this->ReadPropertyBoolean('readWallbox2');
			$readWallbox3 = $this->ReadPropertyBoolean('readWallbox3');
			$readWallbox4 = $this->ReadPropertyBoolean('readWallbox4');
			$readWallbox5 = $this->ReadPropertyBoolean('readWallbox5');
			$readWallbox6 = $this->ReadPropertyBoolean('readWallbox6');
			$readWallbox7 = $this->ReadPropertyBoolean('readWallbox7');
			$readDcString = $this->ReadPropertyBoolean('readDcString');
			$pollCycle = $this->ReadPropertyInteger('pollCycle');


			if("" != $hostIp)
			{
				$this->checkProfiles();
				
				// Splitter-Instance
				$gatewayId = 0;
				// I/O Instance
				$interfaceId = 0;

				foreach(IPS_GetInstanceListByModuleID(MODBUS_INSTANCES) AS $modbusInstanceId)
				{
					$connectionInstanceId = IPS_GetInstance($modbusInstanceId)['ConnectionID'];

					if($hostIp == IPS_GetProperty($connectionInstanceId, "Host") && $hostPort == IPS_GetProperty($connectionInstanceId, "Port"))
					{
						if(DEBUG) echo "ModBus Instance and ClientSocket found: ".$modbusInstanceId.", ".$connectionInstanceId."\n";

						$gatewayId = $modbusInstanceId;
						$interfaceId = $connectionInstanceId;
						break;
					}
				}

				if(0 == $gatewayId)
				{
					if(DEBUG) echo "ModBus Instance not found!\n";

					// ModBus Gateway erstellen
					$gatewayId = IPS_CreateInstance(MODBUS_INSTANCES); 
					IPS_SetName($gatewayId, MODUL_PREFIX."ModbusGateway");
					IPS_SetProperty($gatewayId, "GatewayMode", 0);
					IPS_SetProperty($gatewayId, "DeviceID", 1);
					IPS_SetProperty($gatewayId, "SwapWords", 0);
					IPS_ApplyChanges($gatewayId);
					IPS_Sleep(100);
				}

				if(0 == $interfaceId)
				{
					if(DEBUG) echo "Client Socket not found!\n";

					// Client Soket erstellen
					$interfaceId = IPS_CreateInstance(CLIENT_SOCKETS);
					IPS_SetName($interfaceId, MODUL_PREFIX."ClientSoket");
					IPS_SetProperty($interfaceId, "Host", $hostIp);
					IPS_SetProperty($interfaceId, "Port", $hostPort);
					IPS_SetProperty($interfaceId, "Open", true);
					IPS_ApplyChanges($interfaceId);
					IPS_Sleep(100);

					// Client Socket mit Gateway verbinden
					IPS_DisconnectInstance($gatewayId);
					IPS_ConnectInstance($gatewayId, $interfaceId);
				}


				$parentId = $this->InstanceID;


				// Quelle: Modbus/TCP-Schnittstelle der E3/DC GmbH 10.04.2017, v1.6

				$categoryId = $parentId;

				$inverterModelRegister_array = array(
				/* ********** Identifikationsblock **************************************************************************/
/*					array(40001, 1, 3, "Magicbyte", "UInt16", "", "Magicbyte – S10 ModBus ID (Immer 0xE3DC)"),
					array(40002, 1, 3, "ModBus-Firmware", "UInt8+UInt8", "", "S10 ModBus-Firmware-Version"),
					array(40003, 1, 3, "Register", "UInt16", "", "Anzahl unterstützter Register"),
//					array(40004, 16, 3, "Hersteller", "String", "", "Hersteller: 'E3/DC GmbH'"),
//					array(40020, 16, 3, "Modell", "String", "", "Modell, z. B.: 'S10 E AIO'"),
//					array(40036, 16, 3, "Seriennummer", "String", "", "Seriennummer, z. B.: 'S10-12345678912'"),
//					array(40052, 16, 3, "Firmware", "String", "", "S10 Firmware Release, z. B.: 'S10-2015_08'"),
*/				);


				$inverterModelRegister_array = array(
				/* ********** Leistungsdaten ************************************************************************/
					array(40068, 2, 3, "PV-Leistung", "Int32", "W", "Photovoltaik-Leistung in Watt"),
					array(40070, 2, 3, "Batterie-Leistung", "Int32", "W", "Batterie-Leistung in Watt (negative Werte = Entladung)"),
					array(40072, 2, 3, "Verbrauchs-Leistung", "Int32", "W", "Hausverbrauchs-Leistung in Watt"),
					array(40074, 2, 3, "Netz-Leistung", "Int32", "W", "Leistung am Netzübergabepunkt in Watt (negative Werte = Einspeisung)"),
					array(40082, 1, 3, "Autarkie-Eigenverbrauch", "Uint8+Uint8", "", "Autarkie und Eigenverbrauch in Prozent"),
					array(40083, 1, 3, "Batterie-SOC", "Uint16", "%", "Batterie-SOC in Prozent"),
					array(40084, 1, 3, "Emergency-Power", "Uint16", "", "Emergency-Power Status:
0 = Notstrom wird nicht von Ihrem Gerät unterstützt (bei Geräten der älteren Gerätegeneration, z. B. S10-SP40, S10-P5002).
1 = Notstrom aktiv (Ausfall des Stromnetzes)
2 = Notstrom nicht aktiv
3 = Notstrom nicht verfügbar
4 = Der Motorschalter des S10 E befindet sich nicht in der richtigen Position, sondern wurde manuell abgeschaltet oder nicht eingeschaltet.
Hinweis: Falls der Motorschalter nicht bewusst ausgeschaltet wurde, haben Sie eventuell übersehen, den Schieberegler am Motorschalter in die Position „ON“ zu bringen (s. die folgende Abbildung zur Erläuterung)."),
					array(40085, 1, 3, "EMS-Status", "Uint16", "", "EMS-Status: EMS-Register    Beschreibung    Zugriff
Bit 0    Laden der Batterien ist gesperrt (1)    R
Bit 1    Entladen der Batterien ist gesperrt (1)    R
Bit 2    Notstrommodus ist möglich (1) (wenn die Batterien geladen sind)    R
Bit 3    Wetterbasiertes Laden: 1 = Es wird Ladekapazität zurückgehalten, damit der erwartete Sonnenschein maximal ausgenutzt werden kann. Dies ist nötig, wenn die maximale Einspeisung begrenzt ist.;        0 = Es wird keine Ladekapazität zurückgehalten    R
Bit 4    Abregelungs-Status: 1 = Die Ausgangsleistung des S10 Hauskraftwerks wird abgeregelt, da die maximale Einspeisung erreicht ist;    0 = Dieser Fall ist nicht eingetreten    R
Bit 5    1 = Ladesperrzeit aktiv: Den Zeitraum für die Ladesperrzeit geben Sie in der Funktion SmartCharge ein.;    0 = keine Ladesperrzeit    R
Bit 6    1 = Entladesperrzeit aktiv: Den Zeitraum für die Entladesperrzeit geben Sie in der Funktion SmartCharge ein.;    0 = keine Entladesperrzeit    R"),
					array(40086, 1, 3, "EMS Remote Control", "int16", "", "EMS Remote Control"),
					array(40087, 1, 3, "EMS CTRL", "Uint16", "", "EMS CTRL"),
				);
				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

				// Autarkie und Eigenverbrauch aus "Autarkie-Eigenverbrauch" erstellen
				$instanceId = IPS_GetInstanceIDByName("Autarkie-Eigenverbrauch", $categoryId);
				$varId = IPS_GetVariableIDByName("Value", $instanceId);
				IPS_SetHidden($varId, true);
				
				$varName = "Autarkie";
				$varId = @IPS_GetVariableIDByName($varName, $instanceId);
				if(false === $varId)
				{
					$varId = IPS_CreateVariable(1);
					IPS_SetName($varId, $varName);
					IPS_SetParent($varId, $instanceId);
				}
				IPS_SetVariableCustomProfile($varId, "~Valve");
				IPS_SetInfo($varId, "Autarkie in Prozent");

				$varName = "Eigenverbrauch";
				$varId = @IPS_GetVariableIDByName($varName, $instanceId);
				if(false === $varId)
				{
					$varId = IPS_CreateVariable(1);
					IPS_SetName($varId, $varName);
					IPS_SetParent($varId, $instanceId);
				}
				IPS_SetVariableCustomProfile($varId, "~Valve");
				IPS_SetInfo($varId, "Eigenverbrauch in Prozent");

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-Autarkie-Eigenverbrauch", 5000);


				// Bit 0 - 6 für "EMS-Status" erstellen
				$instanceId = IPS_GetInstanceIDByName("EMS-Status", $categoryId);
				$varId = IPS_GetVariableIDByName("Value", $instanceId);
				IPS_SetHidden($varId, true);
				
				$bitArray = array(
					array('varName' => "Batterie laden", 'varProfile' => "~Lock", 'varInfo' => "Bit 0: Laden der Batterien ist gesperrt (1)    R"),
					array('varName' => "Batterie entladen", 'varProfile' => "~Lock", 'varInfo' => "Bit 1: Entladen der Batterien ist gesperrt (1)    R"),
					array('varName' => "Notstrommodus", 'varProfile' => "~Switch", 'varInfo' => "Bit 2: Notstrommodus ist möglich (1) (wenn die Batterien geladen sind)    R"),
					array('varName' => "Wetterbasiertes Laden", 'varProfile' => "~Switch", 'varInfo' =>  "Bit 3: Wetterbasiertes Laden: 1 = Es wird Ladekapazität zurückgehalten, damit der erwartete Sonnenschein maximal ausgenutzt werden kann. Dies ist nötig, wenn die maximale Einspeisung begrenzt ist.;        0 = Es wird keine Ladekapazität zurückgehalten    R"),
					array('varName' => "Abregelungs-Status", 'varProfile' => "~Alert", 'varInfo' => "Bit 4: Abregelungs-Status: 1 = Die Ausgangsleistung des S10 Hauskraftwerks wird abgeregelt, da die maximale Einspeisung erreicht ist;    0 = Dieser Fall ist nicht eingetreten    R"),
					array('varName' => "Ladesperrzeit", 'varProfile' => "~Switch", 'varInfo' => "Bit 5: 1 = Ladesperrzeit aktiv: Den Zeitraum für die Ladesperrzeit geben Sie in der Funktion SmartCharge ein.;    0 = keine Ladesperrzeit    R"),
					array('varName' => "Entladesperrzeit", 'varProfile' => "~Switch", 'varInfo' => "Bit 6: 1 = Entladesperrzeit aktiv: Den Zeitraum für die Entladesperrzeit geben Sie in der Funktion SmartCharge ein.;    0 = keine Entladesperrzeit    R"),
				);

				foreach($bitArray AS $bit)
				{
					$varName = $bit['varName'];
					$varId = @IPS_GetVariableIDByName($varName, $instanceId);
					if(false === $varId)
					{
						$varId = IPS_CreateVariable(0);
						IPS_SetName($varId, $varName);
						IPS_SetParent($varId, $instanceId);
					}
					IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
					IPS_SetInfo($varId, $bit['varInfo']);
				}

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-EMS-Status", 5000);



				$inverterModelRegister_array = array(
					array(40076, 2, 3, "Ext-Leistung", "Int32", "W", "Leistung aller zusätzlichen Einspeiser in Watt"),
				);

				if($readExtLeistung)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}


				/* ********** Spezifische Abfragen zur Steuerung der Wallbox **************************************
					Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.
				 ************************************************************************************************** */

				$inverterModelRegister_array = array(
					array(40078, 2, 3, "Wallbox-Leistung", "Int32", "W", "Leistung der Wallbox in Watt"),
					array(40080, 2, 3, "Wallbox-Solarleistung", "Int32", "W", "Solarleistung, die von der Wallbox genutzt wird in Watt"),
				);

				if($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
					$this->SetTimerInterval("Update-WallBox_X_CTRL", 5000);
				}
				else
				{
					// deaktiviert einen Timer
					$this->SetTimerInterval("Update-WallBox_X_CTRL", 0);

					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$wallboxDescription = "Wallbox_X_CTRL  Beschreibung    Datentyp
Bit 0   Wallbox vorhanden und verfügbar (1) R
Bit 1   Solarbetrieb aktiv (1) Mischbetrieb aktiv (0)   RW
Bit 2   Laden abgebrochen (1) Laden freigegeben (0) RW
Bit 3   Autor lädt (1) Auto lädt nicht (0)  R
Bit 4   Typ-2-Stecker verriegelt (1)    R
Bit 5   Typ-2-Stecker gesteckt (1)  R
Bit 6   Schukosteckdose an (1)  RW
Bit 7   Schukostecker gesteckt (1)  R
Bit 8   Schukostecker verriegelt (1)    R
Bit 9   Relais an, 16A 1 Phase, Schukosteckdose R
Bit 10  Relais an, 16A 3 Phasen, Typ 2  R
Bit 11  Relais an, 32A 3 Phasen, Typ 2  R
Bit 12  Eine Phase aktiv (1) drei Phasen aktiv (0)  RW
Bit 13  Nicht belegt";

				$bitArray = array(
					array('varName' => "Wallbox", 'varProfile' => "~Alert.Reversed", 'varInfo' => "Bit 0   Wallbox vorhanden und verfügbar (1) R"),
					array('varName' => "Solarbetrieb", 'varProfile' => "~Switch", 'varInfo' => "Bit 1   Solarbetrieb aktiv (1) Mischbetrieb aktiv (0)   RW"),
					array('varName' => "Laden sperren", 'varProfile' => "~Lock", 'varInfo' => "Bit 2   Laden abgebrochen (1) Laden freigegeben (0) RW"),
					array('varName' => "Ladevorgang", 'varProfile' => "~Switch", 'varInfo' => "Bit 3   Auto lädt (1) Auto lädt nicht (0)  R"),
					array('varName' => "Typ-2-Stecker verriegelt", 'varProfile' => "~Switch", 'varInfo' => "Bit 4   Typ-2-Stecker verriegelt (1)    R"),
					array('varName' => "Typ-2-Stecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 5   Typ-2-Stecker gesteckt (1)  R"),
					array('varName' => "Schukosteckdose", 'varProfile' => "~Switch", 'varInfo' => "Bit 6   Schukosteckdose an (1)  RW"),
					array('varName' => "Schukostecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 7   Schukostecker gesteckt (1)  R"),
					array('varName' => "Schukostecker verriegelt", 'varProfile' => "~Lock", 'varInfo' => "Bit 8   Schukostecker verriegelt (1)    R"),
					array('varName' => "16A 1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 9   Relais an, 16A 1 Phase, Schukosteckdose R"),
					array('varName' => "16A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 10  Relais an, 16A 3 Phasen, Typ 2  R"),
					array('varName' => "32A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 11  Relais an, 32A 3 Phasen, Typ 2  R"),
					array('varName' => "1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 12  Eine Phase aktiv (1) drei Phasen aktiv (0)  RW"),
//						array('varName' => "", 'varProfile' => "", 'varInfo' => "Bit 13  Nicht belegt"),
				);

				$inverterModelRegister_array = array(
					array(40088, 1, 3, "WallBox_0_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox0)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40089, 1, 3, "WallBox_1_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox1)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40090, 1, 3, "WallBox_2_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox2)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40091, 1, 3, "WallBox_3_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox3)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40092, 1, 3, "WallBox_4_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox4)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40093, 1, 3, "WallBox_5_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox5)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40094, 1, 3, "WallBox_6_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox6)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$inverterModelRegister_array = array(
					array(40095, 1, 3, "WallBox_7_CTRL", "Uint16", "", $wallboxDescription),
				);
				if($readWallbox7)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					foreach($inverterModelRegister_array AS $register)
					{
						// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
						$instanceId = IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						$varId = IPS_GetVariableIDByName("Value", $instanceId);
						IPS_SetHidden($varId, true);
						
						foreach($bitArray AS $bit)
						{
							$varName = $bit['varName'];
							$varId = @IPS_GetVariableIDByName($varName, $instanceId);
							if(false === $varId)
							{
								$varId = IPS_CreateVariable(0);
								IPS_SetName($varId, $varName);
								IPS_SetParent($varId, $instanceId);
							}
							IPS_SetVariableCustomProfile($varId, $bit['varProfile']);
							IPS_SetInfo($varId, $bit['varInfo']);
						}
					}
				}
				else
				{
					foreach($inverterModelRegister_array AS $register)
					{
						$instanceId = @IPS_GetInstanceIDByName($register[IMR_NAME], $categoryId);
						if(false !== $instanceId)
						{
							foreach(IPS_GetChildrenIDs($instanceId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($instanceId);
						}
					}
				}



				$categoryName = "DC_String";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readDcString)
				{
					$inverterModelRegister_array = array(
					/* ********** DC-String **************************************************************************
						Hinweis: Die folgenden Register 40096 bis 40104 können ab dem Release S10_2017_02 genutzt werden!
					 *************************************************************************************************/
						array(40096, 1, 3, "DC_STRING_1_Voltage", "UInt16", "V", "DC_STRING_1_Voltage"),
						array(40097, 1, 3, "DC_STRING_2_Voltage", "UInt16", "V", "DC_STRING_2_Voltage"),
						array(40098, 1, 3, "DC_STRING_3_Voltage", "UInt16", "V", "DC_STRING_3_Voltage"),
						array(40099, 1, 3, "DC_STRING_1_Current", "UInt16", "A", "DC_STRING_1_Current"),
						array(40100, 1, 3, "DC_STRING_2_Current", "UInt16", "A", "DC_STRING_2_Current"),
						array(40101, 1, 3, "DC_STRING_3_Current", "UInt16", "A", "DC_STRING_3_Current"),
						array(40102, 1, 3, "DC_STRING_1_Power", "UInt16", "W", "DC_STRING_1_Power"),
						array(40103, 1, 3, "DC_STRING_2_Power", "UInt16", "W", "DC_STRING_2_Power"),
						array(40104, 1, 3, "DC_STRING_3_Power", "UInt16", "W", "DC_STRING_3_Power"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Hinweis: Die folgenden Register 40096 bis 40104 können ab dem Release S10_2017_02 genutzt werden!");

					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				}
				else
				{
					if(false !== $categoryId)
					{
						foreach(IPS_GetChildrenIDs($categoryId) AS $childId)
						{
							foreach(IPS_GetChildrenIDs($childId) AS $childChildId)
							{
								IPS_DeleteVariable($childChildId);
							}
							IPS_DeleteInstance($childId);
						}
						IPS_DeleteCategory($categoryId);
					}
				}


				if($active)
				{
					// Erreichbarkeit von IP und Port prüfen
					$portOpen = false;
					$waitTimeoutInSeconds = 1; 
					if($fp = @fsockopen($hostIp, $hostPort, $errCode, $errStr, $waitTimeoutInSeconds))
					{   
						// It worked
						$portOpen = true;
						fclose($fp);

						// Client Soket aktivieren
						IPS_SetProperty($interfaceId, "Open", true);
						IPS_ApplyChanges($interfaceId);
						IPS_Sleep(100);

						// aktiv
						$this->SetStatus(102);
					}
					else
					{
						// IP oder Port nicht erreichbar
						$this->SetStatus(200);
					}
				}
				else
				{
					// Client Soket deaktivieren
					IPS_SetProperty($interfaceId, "Open", false);
					IPS_ApplyChanges($interfaceId);
					IPS_Sleep(100);

					// inaktiv
					$this->SetStatus(104);
				}
			}
			else
			{
				// keine IP --> inaktiv
				$this->SetStatus(104);
			}
		}

		private function createModbusInstances($inverterModelRegister_array, $parentId, $gatewayId, $pollCycle)
		{
			// Erstelle Modbus Instancen
			foreach($inverterModelRegister_array AS $inverterModelRegister)
			{
				if(DEBUG) echo "REG_".$inverterModelRegister[IMR_START_REGISTER]. " - ".$inverterModelRegister[IMR_NAME]."\n";
				// Datentyp ermitteln
				// 0=Bit, 1=Byte, 2=Word, 3=DWord, 4=ShortInt, 5=SmallInt, 6=Integer, 7=Real
				if("uint16" == strtolower($inverterModelRegister[IMR_TYPE])
					|| "enum16" == strtolower($inverterModelRegister[IMR_TYPE])
					|| "uint8+uint8" == strtolower($inverterModelRegister[IMR_TYPE]))
				{
					$datenTyp = 2;
				}
				elseif("uint32" == strtolower($inverterModelRegister[IMR_TYPE]))
				{
					$datenTyp = 3;
				}
				elseif("int16" == strtolower($inverterModelRegister[IMR_TYPE])
					|| "sunssf" == strtolower($inverterModelRegister[IMR_TYPE]))
				{
					$datenTyp = 4;
				}
				elseif("int32" == strtolower($inverterModelRegister[IMR_TYPE]))
				{
					$datenTyp = 6;
				}
				elseif("float32" == strtolower($inverterModelRegister[IMR_TYPE]))
				{
					$datenTyp = 7;
				}
				elseif("string32" == strtolower($inverterModelRegister[IMR_TYPE])
					|| "string16" == strtolower($inverterModelRegister[IMR_TYPE])
					|| "string" == strtolower($inverterModelRegister[IMR_TYPE]))
				{
					echo "Datentyp '".$inverterModelRegister[IMR_TYPE]."' wird von Modbus in IPS nicht unterstützt! --> skip\n";
					continue;
				}
				else
				{
					echo "Fehler: Unbekannter Datentyp '".$inverterModelRegister[IMR_TYPE]."'! --> skip\n";
					continue;
				}

				// Profil ermitteln
				if("a" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
				{
					$profile = "~Ampere";
				}
				elseif("a" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".Ampere.Int";
				}
/*				elseif("ah" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".AmpereHour.Int";
				}
*/				elseif("v" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
				{
					$profile = "~Volt";
				}
				elseif("v" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".Volt.Int";
				}
				elseif("w" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
				{
					$profile = "~Watt.14490";
				}
				elseif("w" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".Watt.Int";
				}
				elseif("hz" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = "~Hertz";
				}
/*				// Voltampere für elektrische Scheinleistung
				elseif("va" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
				{
					$profile = MODUL_PREFIX.".Scheinleistung.Float";
				}
				// Voltampere für elektrische Scheinleistung
				elseif("va" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".Scheinleistung";
				}
				// Var für elektrische Blindleistung
				elseif("var" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
				{
					$profile = MODUL_PREFIX.".Blindleistung.Float";
				}
				// Var für elektrische Blindleistung
				elseif("var" == strtolower($inverterModelRegister[IMR_UNITS]) || "var" == $inverterModelRegister[IMR_UNITS])
				{
					$profile = MODUL_PREFIX.".Blindleistung";
				}
*/				elseif("%" == $inverterModelRegister[IMR_UNITS] && 7 == $datenTyp)
				{
					$profile = "~Valve.F";
				}
				elseif("%" == $inverterModelRegister[IMR_UNITS])
				{
					$profile = "~Valve";
				}
				elseif("wh" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
				{
					$profile = "~Electricity.HM";
				}
/*				elseif("wh" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".Electricity.Int";
				}
*/				elseif("° C" == $inverterModelRegister[IMR_UNITS])
				{
					$profile = "~Temperature";
				}
/*				elseif("cos()" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = MODUL_PREFIX.".Angle";
				}
				elseif("enumerated" == strtolower($inverterModelRegister[IMR_UNITS]) && "st" == strtolower($inverterModelRegister[IMR_NAME]))
				{
					$profile = "SunSpec.StateCodes";
				}
				elseif("enumerated" == strtolower($inverterModelRegister[IMR_UNITS]) && "stvnd" == strtolower($inverterModelRegister[IMR_NAME]))
				{
					$profile = MODUL_PREFIX.".StateCodes";
				}
*/				elseif("" == $inverterModelRegister[IMR_UNITS] && "emergency-power" == strtolower($inverterModelRegister[IMR_NAME]))
				{
					$profile = MODUL_PREFIX.".Emergency-Power";
				}
				elseif("bitfield" == strtolower($inverterModelRegister[IMR_UNITS]))
				{
					$profile = false;
				}
				else
				{
					$profile = false;
					if("" != $inverterModelRegister[IMR_UNITS])
					{
						echo "Profil '".$inverterModelRegister[IMR_UNITS]."' unbekannt.\n";
					}
				}


				$instanceId = @IPS_GetInstanceIDByName(/*"REG_".$inverterModelRegister[IMR_START_REGISTER]. " - ".*/$inverterModelRegister[IMR_NAME], $parentId);
				if(false === $instanceId)
				{
					$instanceId = IPS_CreateInstance(MODBUS_ADDRESSES);
					IPS_SetParent($instanceId, $parentId);
					IPS_SetName($instanceId, /*"REG_".$inverterModelRegister[IMR_START_REGISTER]. " - ".*/$inverterModelRegister[IMR_NAME]);

					// Gateway setzen
					IPS_DisconnectInstance($instanceId);
					IPS_ConnectInstance($instanceId, $gatewayId);
				}
				IPS_SetInfo($instanceId, $inverterModelRegister[IMR_DESCRIPTION]);

				IPS_SetProperty($instanceId, "DataType",  $datenTyp);
				IPS_SetProperty($instanceId, "EmulateStatus", false);
				IPS_SetProperty($instanceId, "Poller", $pollCycle);
			//    IPS_SetProperty($instanceId, "Factor", 0);
				IPS_SetProperty($instanceId, "ReadAddress", $inverterModelRegister[IMR_START_REGISTER] + REGISTER_TO_ADDRESS_OFFSET);
				IPS_SetProperty($instanceId, "ReadFunctionCode", $inverterModelRegister[IMR_FUNCTION_CODE]);
			//    IPS_SetProperty($instanceId, "WriteAddress", );
				IPS_SetProperty($instanceId, "WriteFunctionCode", 0);

				IPS_ApplyChanges($instanceId);

				IPS_Sleep(100);

				// Profil der Child-Variable zuweisen
				if(false != $profile)
				{
					$variableId = IPS_GetChildrenIDs($instanceId)[0];
					IPS_SetVariableCustomProfile($variableId, $profile);
				}
			}
		}
		
		private function checkProfiles()
		{
/*			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "SunSpec.StateCodes";
			if(!IPS_VariableProfileExists($profileName))
			{
				$profileAssociation_array = array(
					array("N/A", 0, "Unbekannter Status", "-1"),
					array("OFF", 1, "Wechselrichter ist aus", "-1"),
					array("SLEEPING", 2, "Auto-Shutdown", "-1"),
					array("STARTING", 3, "Wechselrichter startet", "-1"),
					array("MPPT", 4, "Wechselrichter arbeitet normal", 65280),
					array("THROTTLED", 5, "Leistungsreduktion aktiv", 16744448),
					array("SHUTTING_DOWN", 6, "Wechselrichter schaltet ab", "-1"),
					array("FAULT", 7, "Ein oder mehr Fehler existieren, siehe St *oder Evt * Register", 16711680),
					array("STANDBY", 8, "Standby", "-1"),
				);

				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);

				foreach($profileAssociation_array AS $profileAssociation)
				{
					IPS_SetVariableProfileAssociation($profileName, $profileAssociation[PAO_VALUE], $profileAssociation[PAO_NAME], "", $profileAssociation[PAO_COLOR]);
				}

				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".StateCodes";
			if(!IPS_VariableProfileExists($profileName))
			{
				$profileAssociation_array = array(
					array("N/A", 0, "Unbekannter Status", "-1"),
					array("OFF", 1, "Wechselrichter ist aus", "-1"),
					array("SLEEPING", 2, "Auto-Shutdown", "-1"),
					array("STARTING", 3, "Wechselrichter startet", "-1"),
					array("MPPT", 4, "Wechselrichter arbeitet normal", 65280),
					array("THROTTLED", 5, "Leistungsreduktion aktiv", 16744448),
					array("SHUTTING_DOWN", 6, "Wechselrichter schaltet ab", "-1"),
					array("FAULT", 7, "Ein oder mehr Fehler existieren, siehe St * oder Evt * Register", 16711680),
					array("STANDBY", 8, "Standby", "-1"),
					array("NO_BUSINIT", 9, "Keine SolarNet Kommunikation", "-1"),
					array("NO_COMM_INV", 10, "Keine Kommunikation mit Wechselrichter möglich", "-1"),
					array("SN_OVERCURRENT", 11, "Überstrom an SolarNet Stecker erkannt", "-1"),
					array("BOOTLOAD", 12, "Wechselrichter wird gerade upgedatet", "-1"),
					array("AFCI", 13, "AFCI Event (Arc-Erkennung)", "-1"),
				);

				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);

				foreach($profileAssociation_array AS $profileAssociation)
				{
					IPS_SetVariableProfileAssociation($profileName, $profileAssociation[PAO_VALUE], $profileAssociation[PAO_NAME], "", $profileAssociation[PAO_COLOR]);
				}

				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
*/

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Emergency-Power";
			if(!IPS_VariableProfileExists($profileName))
			{
				$profileAssociation_array = array(
					array("nicht unterstützt", 0, "Notstrom wird nicht von Ihrem Gerät unterstützt", 16753920),
					array("aktiv", 1, "Notstrom aktiv (Ausfall des Stromnetzes)", 65280),
					array("nicht aktiv", 2, "Notstrom nicht aktiv", "-1"),
					array("nicht verfügbar", 3, "Notstrom nicht verfügbar", 16753920),
					array("Fehler", 4, "Der Motorschalter des S10 E befindet sich nicht in der richtigen Position, sondern wurde manuell abgeschaltet oder nicht eingeschaltet.", 16711680),
				);

				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);

				foreach($profileAssociation_array AS $profileAssociation)
				{
					IPS_SetVariableProfileAssociation($profileName, $profileAssociation[PAO_VALUE], $profileAssociation[PAO_NAME], "", $profileAssociation[PAO_COLOR]);
				}

				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
						
/*						
			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Scheinleistung";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " VA");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Scheinleistung.Float";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 2);
				IPS_SetVariableProfileText($profileName, "", " VA");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Blindleistung";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " Var");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Blindleistung.Float";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 2);
				IPS_SetVariableProfileText($profileName, "", " Var");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
			
			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Angle";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " °");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
*/
			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Watt.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " W");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Ampere.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " A");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
/*
			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Electricity.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " A");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".AmpereHour.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " Ah");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
*/
			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = MODUL_PREFIX.".Volt.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " V");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
		}
	}