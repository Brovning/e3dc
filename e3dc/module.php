<?php

if (!defined('DEBUG'))
{
	define("DEBUG", false);
}

// ModBus RTU TCP
if (!defined('modbusInstances'))
{
	define("modbusInstances", "{A5F663AB-C400-4FE5-B207-4D67CC030564}");
}
if (!defined('clientSockets'))
{
	define("clientSockets", "{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
}
if (!defined('modbusAddresses'))
{
	define("modbusAddresses", "{CB197E50-273D-4535-8C91-BB35273E3CA5}");
}
if (!defined('e3dcInstances'))
{
	define("e3dcInstances", "{C9508720-B23D-B37A-B5C2-97B607221CE1}");
}


	class E3DC extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			//Properties
			$this->RegisterPropertyString('hostIp', '');
			$this->RegisterPropertyInteger('hostPort', '502');
			$this->RegisterPropertyInteger('pollCycle', '60000');
			$this->RegisterPropertyBoolean('readNameplate', 'false');

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
			$hostIp = $this->ReadPropertyString('hostIp');
			$hostPort = $this->ReadPropertyInteger('hostPort');
			$pollCycle = $this->ReadPropertyInteger('pollCycle');
			$readNameplate = $this->ReadPropertyBoolean('readNameplate');

			$portOpen = false;
			$waitTimeoutInSeconds = 1; 
			if($fp = @fsockopen($hostIp, $hostPort, $errCode, $errStr, $waitTimeoutInSeconds))
			{   
				// It worked
				$portOpen = true;
				fclose($fp);
			}
	
			if($portOpen)
			{
				$this->checkProfiles();
				
				// Splitter-Instance
				$gatewayId = 0;
				// I/O Instance
				$interfaceId = 0;

				foreach(IPS_GetInstanceListByModuleID(modbusInstances) AS $modbusInstanceId)
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
					$gatewayId = IPS_CreateInstance(modbusInstances); 
					IPS_SetName($gatewayId, "FroniusModbusGateway");
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
					$interfaceId = IPS_CreateInstance(clientSockets);
					IPS_SetName($interfaceId, "FroniusClientSoket");
					IPS_SetProperty($interfaceId, "Host", $hostIp);
					IPS_SetProperty($interfaceId, "Port", $hostPort);
					IPS_SetProperty($interfaceId, "Open", true);
					IPS_ApplyChanges($interfaceId);
					IPS_Sleep(100);

					// Client Socket mit Gateway verbinden
					IPS_DisconnectInstance($gatewayId);
					IPS_ConnectInstance($gatewayId, $interfaceId);
				}


				$parentId = IPS_GetInstance(IPS_GetInstanceListByModuleID(froniusInstances)[0])['InstanceID'];



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
					array(40076, 2, 3, "Ext-Leistung", "Int32", "W", "Leistung aller zusätzlichen Einspeiser in Watt"),
					array(40078, 2, 3, "Wallbox-Leistung", "Int32", "W", "Leistung der Wallbox in Watt"),
					array(40080, 2, 3, "Wallbox-Solarleistung", "Int32", "W", "Solarleistung, die von der Wallbox genutzt wird in Watt"),
					array(40082, 1, 3, "Autarkie-Eigenverbrauch", "Uint8+Uint8", "%", "Autarkie und Eigenverbrauch in Prozent"),
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



				/* ********** Spezifische Abfragen zur Steuerung der Wallbox **************************************
					Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.
				 ************************************************************************************************** */

				$categoryName = "Wallbox_0";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox0)
				{
					$inverterModelRegister_array = array(
						array(40088, 1, 3, "WallBox_0_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_1";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox1)
				{
					$inverterModelRegister_array = array(
						array(40089, 1, 3, "WallBox_1_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_2";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox2)
				{
					$inverterModelRegister_array = array(
						array(40090, 1, 3, "WallBox_2_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_3";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox3)
				{
					$inverterModelRegister_array = array(
						array(40091, 1, 3, "WallBox_3_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_4";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox4)
				{
					$inverterModelRegister_array = array(
						array(40092, 1, 3, "WallBox_4_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_5";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox5)
				{
					$inverterModelRegister_array = array(
						array(40093, 1, 3, "WallBox_5_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_6";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox6)
				{
					$inverterModelRegister_array = array(
						array(40094, 1, 3, "WallBox_6_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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



				$categoryName = "Wallbox_7";
				$categoryId = @IPS_GetCategoryIDByName($categoryName, $parentId);
				if($readwallbox7)
				{
					$inverterModelRegister_array = array(
						array(40095, 1, 3, "WallBox_7_CTRL", "Uint16", "", "Wallbox_X_CTRL  Beschreibung    Datentyp
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
							Bit 13  Nicht belegt"),
					);

					if(false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetParent($categoryId, $parentId);
						IPS_SetName($categoryId, $categoryName);
					}
					IPS_SetInfo($categoryId, "Spezifische Abfragen zur Steuerung der Wallbox
						Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= „Read“ und „Write“) gekennzeichnet.");

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
			}
		}

		private function createModbusInstances($inverterModelRegister_array, $parentId, $gatewayId, $pollCycle)
		{
			// Offset von Register (erster Wert 1) zu Adresse (erster Wert 0) ist -1
			$registerToAdressOffset = -1;

			// ArrayOffsets
			$IMR_StartRegister = 0;
			//$IMR_EndRegister = 1;
			$IMR_Size = 1;
			//$IMR_RW = 3;
			$IMR_FunctionCode = 2;
			$IMR_Name = 3;
			$IMR_Type = 4;
			$IMR_Units = 5;
			$IMR_Description = 6;

			// Erstelle Modbus Instancen
			foreach($inverterModelRegister_array AS $inverterModelRegister)
			{
				if(DEBUG) echo "REG_".$inverterModelRegister[$IMR_StartRegister]. " - ".$inverterModelRegister[$IMR_Name]."\n";
				// Datentyp ermitteln
				// 0=Bit, 1=Byte, 2=Word, 3=DWord, 4=ShortInt, 5=SmallInt, 6=Integer, 7=Real
				if("uint16" == $inverterModelRegister[$IMR_Type]
					|| "enum16" == $inverterModelRegister[$IMR_Type])
				{
					$datenTyp = 2;
				}
				elseif("int16" == $inverterModelRegister[$IMR_Type]
					|| "sunssf" == $inverterModelRegister[$IMR_Type])
				{
					$datenTyp = 4;
				}
				elseif("uint32" == $inverterModelRegister[$IMR_Type])
				{
					$datenTyp = 6;
				}
				elseif("float32" == $inverterModelRegister[$IMR_Type])
				{
					$datenTyp = 7;
				}
				elseif("String32" == $inverterModelRegister[$IMR_Type] || "String16" == $inverterModelRegister[$IMR_Type])
				{
					echo "Datentyp ".$inverterModelRegister[$IMR_Type]." wird von Modbus nicht unterstützt! --> skip\n";
					continue;
				}
				else
				{
					echo "Fehler: Unbekannter Datentyp ".$inverterModelRegister[$IMR_Type]."! --> skip\n";
					continue;
				}

				// Profil ermitteln
/*				if("A" == $inverterModelRegister[$IMR_Units] && "uint16" == $inverterModelRegister[$IMR_Type])
				{
					$profile = "Fronius.Ampere.Int";
				}
				else*/if("A" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Ampere";
				}
/*				elseif("AH" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "Fronius.AmpereHour.Int";
				}
*/				elseif("V" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Volt";
				}
/*				elseif("W" == $inverterModelRegister[$IMR_Units] && "uint16" == $inverterModelRegister[$IMR_Type])
				{
					$profile = "Fronius.Watt.Int";
				}
*/				elseif("W" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Watt.14490";
				}
				elseif("Hz" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Hertz";
				}
/*				// Voltampere für elektrische Scheinleistung
				elseif("VA" == $inverterModelRegister[$IMR_Units] && "float32" == $inverterModelRegister[$IMR_Type])
				{
					$profile = "Fronius.Scheinleistung.Float";
				}
				// Voltampere für elektrische Scheinleistung
				elseif("VA" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "Fronius.Scheinleistung";
				}
				// Var für elektrische Blindleistung
				elseif(("VAr" == $inverterModelRegister[$IMR_Units] || "var" == $inverterModelRegister[$IMR_Units]) && "float32" == $inverterModelRegister[$IMR_Type])
				{
					$profile = "Fronius.Blindleistung.Float";
				}
				// Var für elektrische Blindleistung
				elseif("VAr" == $inverterModelRegister[$IMR_Units] || "var" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "Fronius.Blindleistung";
				}
*/				elseif("%" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Valve.F";
				}
				elseif("Wh" == $inverterModelRegister[$IMR_Units] && "uint16" == $inverterModelRegister[$IMR_Type])
				{
					$profile = "Fronius.Electricity.Int";
				}
				elseif("Wh" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Electricity.HM";
				}
				elseif("° C" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "~Temperature";
				}
/*				elseif("cos()" == $inverterModelRegister[$IMR_Units])
				{
					$profile = "Fronius.Angle";
				}
				elseif("Enumerated" == $inverterModelRegister[$IMR_Units] && "St" == $inverterModelRegister[$IMR_Name])
				{
					$profile = "SunSpec.StateCodes";
				}
				elseif("Enumerated" == $inverterModelRegister[$IMR_Units] && "StVnd" == $inverterModelRegister[$IMR_Name])
				{
					$profile = "Fronius.StateCodes";
				}
*/				elseif("Bitfield" == $inverterModelRegister[$IMR_Units])
				{
					$profile = false;
				}
				else
				{
					$profile = false;
					if("" != $inverterModelRegister[$IMR_Units])
					{
						echo "Profil '".$inverterModelRegister[$IMR_Units]."' unbekannt.\n";
					}
				}


				$instanceId = @IPS_GetInstanceIDByName(/*"REG_".$inverterModelRegister[$IMR_StartRegister]. " - ".*/$inverterModelRegister[$IMR_Name], $parentId);
				if(false === $instanceId)
				{
					$instanceId = IPS_CreateInstance(modbusAddresses);
					IPS_SetParent($instanceId, $parentId);
					IPS_SetName($instanceId, /*"REG_".$inverterModelRegister[$IMR_StartRegister]. " - ".*/$inverterModelRegister[$IMR_Name]);

					// Gateway setzen
					IPS_DisconnectInstance($instanceId);
					IPS_ConnectInstance($instanceId, $gatewayId);
				}
				IPS_SetInfo($instanceId, $inverterModelRegister[$IMR_Description]);

				IPS_SetProperty($instanceId, "DataType",  $datenTyp);
				IPS_SetProperty($instanceId, "EmulateStatus", false);
				IPS_SetProperty($instanceId, "Poller", $pollCycle);
			//    IPS_SetProperty($instanceId, "Factor", 0);
				IPS_SetProperty($instanceId, "ReadAddress", $inverterModelRegister[$IMR_StartRegister] + $registerToAdressOffset);
				IPS_SetProperty($instanceId, "ReadFunctionCode", $inverterModelRegister[$IMR_FunctionCode]);
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
/*			// profileAssociation Offsets
			$PAO_name = 0;
			$PAO_value = 1;
			$PAO_description = 2;
			$PAO_color = 3;

			// Erstelle Profil, sofern noch nicht vorhanden
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
					IPS_SetVariableProfileAssociation($profileName, $profileAssociation[$PAO_value], $profileAssociation[$PAO_name], "", $profileAssociation[$PAO_color]);
				}

				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.StateCodes";
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
					IPS_SetVariableProfileAssociation($profileName, $profileAssociation[$PAO_value], $profileAssociation[$PAO_name], "", $profileAssociation[$PAO_color]);
				}

				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Scheinleistung";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " VA");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Scheinleistung.Float";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 2);
				IPS_SetVariableProfileText($profileName, "", " VA");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}


			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Blindleistung";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " Var");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Blindleistung.Float";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 2);
				IPS_SetVariableProfileText($profileName, "", " Var");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
			
			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Angle";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " °");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Watt.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " W");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Ampere.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " A");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.Electricity.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " A");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}

			// Erstelle Profil, sofern noch nicht vorhanden
			$profileName = "Fronius.AmpereHour.Int";
			if(!IPS_VariableProfileExists($profileName))
			{
				// 	Wert: 0 Boolean, 1 Integer, 2 Float, 3 String
				IPS_CreateVariableProfile($profileName, 1);
				IPS_SetVariableProfileText($profileName, "", " Ah");
				
				if(DEBUG) echo "Profil ".$profileName." erstellt\n";
			}
*/		}
	}