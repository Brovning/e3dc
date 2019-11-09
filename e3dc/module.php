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
//	define("IMR_END_REGISTER", 3);
	define("IMR_SIZE", 1);
//	define("IMR_RW", 3);
	define("IMR_FUNCTION_CODE", 2);
	define("IMR_NAME", 3);
	define("IMR_TYPE", 4);
	define("IMR_UNITS", 5);
	define("IMR_DESCRIPTION", 6);
}


if (!defined('VARIABLETYPE_BOOLEAN'))
{
    define('VARIABLETYPE_BOOLEAN', 0);
    define('VARIABLETYPE_INTEGER', 1);
    define('VARIABLETYPE_FLOAT', 2);
    define('VARIABLETYPE_STRING', 3);
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
			$this->RegisterPropertyInteger('hostmodbusDevice', '1');
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
\$varId = @IPS_GetVariableIDByName(\"Value\", \$instanceId);
if(false === \$varId)
{
	\$varId = IPS_GetVariableIDByName(\"Wert\", \$instanceId);
}
\$varValue = GetValue(\$varId);
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
\$varId = @IPS_GetVariableIDByName(\"Value\", \$instanceId);
if(false === \$varId)
{
	\$varId = IPS_GetVariableIDByName(\"Wert\", \$instanceId);
}
\$varValue = GetValue(\$varId);

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
		\$varId = @IPS_GetVariableIDByName(\"Value\", \$instanceId);
		if(false === \$varId)
		{
			\$varId = IPS_GetVariableIDByName(\"Wert\", \$instanceId);
		}
		\$varValue = GetValue(\$varId);

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
			$hostmodbusDevice = $this->ReadPropertyInteger('hostmodbusDevice');
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
				list($gatewayId_Old, $interfaceId_Old) = $this->readOldModbusGateway();
				list($gatewayId, $interfaceId) = $this->checkModbusGateway($hostIp, $hostPort, $hostmodbusDevice);

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
				$varId = @IPS_GetVariableIDByName("Value", $instanceId);
				if(false === $varId)
				{
					$varId = IPS_GetVariableIDByName("Wert", $instanceId);
				}
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
				$varId = @IPS_GetVariableIDByName("Value", $instanceId);
				if(false === $varId)
				{
					$varId = IPS_GetVariableIDByName("Wert", $instanceId);
				}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						$varId = @IPS_GetVariableIDByName("Value", $instanceId);
						if(false === $varId)
						{
							$varId = IPS_GetVariableIDByName("Wert", $instanceId);
						}
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
						//IPS_Sleep(100);

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
					//IPS_Sleep(100);

					// inaktiv
					$this->SetStatus(104);
				}


				// prüfen, ob sich ModBus-Gateway geändert hat
				if(0 != $gatewayId_Old && $gatewayId != $gatewayId_Old)
				{
					$this->deleteInstanceNotInUse($gatewayId_Old, MODBUS_ADDRESSES);
				}

				// prüfen, ob sich ClientSocket Interface geändert hat
				if(0 != $interfaceId_Old && $interfaceId != $interfaceId_Old)
				{
					$this->deleteInstanceNotInUse($interfaceId_Old, MODBUS_INSTANCES);
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
				$applyChanges = false;
				// Instanz erstellen
				if(false === $instanceId)
				{
					$instanceId = IPS_CreateInstance(MODBUS_ADDRESSES);
					IPS_SetParent($instanceId, $parentId);
					IPS_SetName($instanceId, /*"REG_".$inverterModelRegister[IMR_START_REGISTER]. " - ".*/$inverterModelRegister[IMR_NAME]);
					$applyChanges = true;
				}

				// Gateway setzen
				if(IPS_GetInstance($instanceId)['ConnectionID'] != $gatewayId)
				{
					if(0 != IPS_GetInstance($instanceId)['ConnectionID'])
					{
						IPS_DisconnectInstance($instanceId);
					}
					IPS_ConnectInstance($instanceId, $gatewayId);
					$applyChanges = true;
				}

				if($inverterModelRegister[IMR_DESCRIPTION] != IPS_GetObject($instanceId)['ObjectInfo'])
				{
					IPS_SetInfo($instanceId, $inverterModelRegister[IMR_DESCRIPTION]);
				}
				
				// Ident der Modbus-Instanz setzen
				IPS_SetIdent($instanceId, $inverterModelRegister[IMR_START_REGISTER]);

				// Modbus-Instanz konfigurieren
				if($datenTyp != IPS_GetProperty($instanceId, "DataType"))
				{
					IPS_SetProperty($instanceId, "DataType",  $datenTyp);
					$applyChanges = true;
				}
				if(false != IPS_GetProperty($instanceId, "EmulateStatus"))
				{
					IPS_SetProperty($instanceId, "EmulateStatus", false);
					$applyChanges = true;
				}
				if($pollCycle != IPS_GetProperty($instanceId, "Poller"))
				{
					IPS_SetProperty($instanceId, "Poller", $pollCycle);
					$applyChanges = true;
				}
	/*
				if(0 != IPS_GetProperty($instanceId, "Factor"))
				{
					IPS_SetProperty($instanceId, "Factor", 0);
					$applyChanges = true;
				}
	*/
				if($inverterModelRegister[IMR_START_REGISTER] + REGISTER_TO_ADDRESS_OFFSET != IPS_GetProperty($instanceId, "ReadAddress"))
				{
					IPS_SetProperty($instanceId, "ReadAddress", $inverterModelRegister[IMR_START_REGISTER] + REGISTER_TO_ADDRESS_OFFSET);
					$applyChanges = true;
				}
				if($inverterModelRegister[IMR_FUNCTION_CODE] != IPS_GetProperty($instanceId, "ReadFunctionCode"))
				{
					IPS_SetProperty($instanceId, "ReadFunctionCode", $inverterModelRegister[IMR_FUNCTION_CODE]);
					$applyChanges = true;
				}
	/*
				if( != IPS_GetProperty($instanceId, "WriteAddress"))
				{
					IPS_SetProperty($instanceId, "WriteAddress", );
					$applyChanges = true;
				}
	*/
				if(0 != IPS_GetProperty($instanceId, "WriteFunctionCode"))
				{
					IPS_SetProperty($instanceId, "WriteFunctionCode", 0);
					$applyChanges = true;
				}

				if($applyChanges)
				{
					IPS_ApplyChanges($instanceId);
					//IPS_Sleep(100);
				}

				$varId = @IPS_GetVariableIDByName("Value", $instanceId);
				if(false === $varId)
				{
					$varId = IPS_GetVariableIDByName("Wert", $instanceId);
				}

				// Profil der Statusvariable zuweisen
				if(false != $profile && $profile != IPS_GetVariable($varId)['VariableCustomProfile'])
				{
					IPS_SetVariableCustomProfile($varId, $profile);
				}
			}
		}
		
		private function checkProfiles()
		{
	/*
			$this->createVarProfile("SunSpec.StateCodes.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, array(
					array('Name' => "N/A", 'Wert' => 0, "Unbekannter Status"),
					array('Name' => "OFF", 'Wert' => 1, "Wechselrichter ist aus"),
					array('Name' => "SLEEPING", 'Wert' => 2, "Auto-Shutdown"),
					array('Name' => "STARTING", 'Wert' => 3, "Wechselrichter startet"),
					array('Name' => "MPPT", 'Wert' => 4, "Wechselrichter arbeitet normal", 'Farbe' => 65280),
					array('Name' => "THROTTLED", 'Wert' => 5, "Leistungsreduktion aktiv", 'Farbe' => 16744448),
					array('Name' => "SHUTTING_DOWN", 'Wert' => 6, "Wechselrichter schaltet ab"),
					array('Name' => "FAULT", 'Wert' => 7, "Ein oder mehr Fehler existieren, siehe St *oder Evt * Register", 'Farbe' => 16711680),
					array('Name' => "STANDBY", 'Wert' => 8, "Standby"),
				)
			);
			$this->createVarProfile(MODUL_PREFIX.".StateCodes.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, array(
					array('Name' => "N/A", 'Wert' => 0, "Unbekannter Status"),
					array('Name' => "OFF", 'Wert' => 1, "Wechselrichter ist aus"),
					array('Name' => "SLEEPING", 'Wert' => 2, "Auto-Shutdown"),
					array('Name' => "STARTING", 'Wert' => 3, "Wechselrichter startet"),
					array('Name' => "MPPT", 'Wert' => 4, "Wechselrichter arbeitet normal", 'Farbe' => 65280),
					array('Name' => "THROTTLED", 'Wert' => 5, "Leistungsreduktion aktiv", 'Farbe' => 16744448),
					array('Name' => "SHUTTING_DOWN", 'Wert' => 6, "Wechselrichter schaltet ab"),
					array('Name' => "FAULT", 'Wert' => 7, "Ein oder mehr Fehler existieren, siehe St * oder Evt * Register", 'Farbe' => 16711680),
					array('Name' => "STANDBY", 'Wert' => 8, "Standby"),
					array('Name' => "NO_BUSINIT", 'Wert' => 9, "Keine SolarNet Kommunikation"),
					array('Name' => "NO_COMM_INV", 'Wert' => 10, "Keine Kommunikation mit Wechselrichter möglich"),
					array('Name' => "SN_OVERCURRENT", 'Wert' => 11, "Überstrom an SolarNet Stecker erkannt"),
					array('Name' => "BOOTLOAD", 'Wert' => 12, "Wechselrichter wird gerade upgedatet"),
					array('Name' => "AFCI", 'Wert' => 13, "AFCI Event (Arc-Erkennung)"),
				)
			);
	*/
			$this->createVarProfile(MODUL_PREFIX.".Emergency-Power.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, array(
					array('Name' => "nicht unterstützt", 'Wert' => 0, "Notstrom wird nicht von Ihrem Gerät unterstützt", 'Farbe' => 16753920),
					array('Name' => "aktiv", 'Wert' => 1, "Notstrom aktiv (Ausfall des Stromnetzes)", 'Farbe' => 65280),
					array('Name' => "nicht aktiv", 'Wert' => 2, "Notstrom nicht aktiv", 'Farbe' => -1),
					array('Name' => "nicht verfügbar", 'Wert' => 3, "Notstrom nicht verfügbar", 'Farbe' => 16753920),
					array('Name' => "Fehler", 'Wert' => 4, "Der Motorschalter des S10 E befindet sich nicht in der richtigen Position, sondern wurde manuell abgeschaltet oder nicht eingeschaltet.", 'Farbe' => 16711680),
				)
			);
	/*						
			$this->createVarProfile(MODUL_PREFIX.".Scheinleistung.Int", VARIABLETYPE_INTEGER, ' VA');
			$this->createVarProfile(MODUL_PREFIX.".Scheinleistung.Float", VARIABLETYPE_FLOAT, ' VA');
			$this->createVarProfile(MODUL_PREFIX.".Blindleistung.Int", VARIABLETYPE_INTEGER, ' Var');
			$this->createVarProfile(MODUL_PREFIX.".Blindleistung.Float", VARIABLETYPE_FLOAT, ' Var');
			$this->createVarProfile(MODUL_PREFIX.".Angle.Int", VARIABLETYPE_INTEGER, ' °');
	*/
			$this->createVarProfile(MODUL_PREFIX.".Watt.Int", VARIABLETYPE_INTEGER, ' W');
			$this->createVarProfile(MODUL_PREFIX.".Ampere.Int", VARIABLETYPE_INTEGER, ' A');
	/*
			$this->createVarProfile(MODUL_PREFIX.".Electricity.Int", VARIABLETYPE_INTEGER, ' Wh');
			$this->createVarProfile(MODUL_PREFIX.".AmpereHour.Int", VARIABLETYPE_INTEGER, ' Ah');
	*/
			$this->createVarProfile(MODUL_PREFIX.".Volt.Int", VARIABLETYPE_INTEGER, ' V');
		}

		private function readOldModbusGateway()
		{
			$modbusGatewayId_Old = 0;
			$clientSocketId_Old = 0;

			$childIds = IPS_GetChildrenIDs($this->InstanceID);

			foreach($childIds AS $childId)
			{
				$modbusAddressInstanceId = @IPS_GetInstance($childId);

				if("{CB197E50-273D-4535-8C91-BB35273E3CA5}" == $modbusAddressInstanceId['ModuleInfo']['ModuleID'])
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

				// Lösche Connection-Instanz (bspw. ModbusAddress, ClientSocket,...), wenn nicht mehr in Verwendung
				if(!$inUse)
				{
					IPS_DeleteInstance($connectionId_Old);
				}
			}
		}

		private function checkModbusGateway($hostIp, $hostPort, $hostmodbusDevice)
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
			if(1 != IPS_GetProperty($gatewayId, "SwapWords"))
			{
				IPS_SetProperty($gatewayId, "SwapWords", 1);
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

		private function GetVariableValue($instanceIdent, $variableIdent = "Value")
		{
			$instanceId = IPS_GetInstanceIDByIdent($instanceIdent, $this->InstanceID);
			$varId = IPS_GetVariableIDByIdent($variableIdent, $instanceId);

			return GetValue($varId);
		}

		public function GetAutarkie()
		{
			return GetVariableValue("Autarkie-Eigenverbrauch", "Autarkie");
		}

		public function GetEigenverbrauch()
		{
			return GetVariableValue("Autarkie-Eigenverbrauch", "Eigenverbrauch");
		}
	}