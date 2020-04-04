<?php

require_once __DIR__ . '/../libs/myFunctions.php';  // globale Funktionen

if (!defined('DEBUG'))
{
	define("DEBUG", false);
}

// Modul Prefix
if (!defined('MODUL_PREFIX'))
{
	define("MODUL_PREFIX", "E3DC");
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

// E3DC settings
if (!defined('BATTERY_DISCHARGE_MAX'))
{
	// Aktuelle E3DC-Modelle können maximal 90% der angegebenen Batterykapazität nutzen
	define("BATTERY_DISCHARGE_MAX", 90);
}

	class E3DC extends IPSModule
	{
		use myFunctions;

		public function Create()
		{
			//Never delete this line!
			parent::Create();


			// *** Properties ***
			$this->RegisterPropertyBoolean('active', 'true');
			$this->RegisterPropertyString('hostIp', '');
			$this->RegisterPropertyInteger('hostPort', '502');
			$this->RegisterPropertyInteger('hostmodbusDevice', '1');
			$this->RegisterPropertyFloat('batterySize', '0');
			$this->RegisterPropertyBoolean('readExtLeistung', 'false');
			$this->RegisterPropertyBoolean('readWallbox0', 'false');
			$this->RegisterPropertyBoolean('readWallbox1', 'false');
			$this->RegisterPropertyBoolean('readWallbox2', 'false');
			$this->RegisterPropertyBoolean('readWallbox3', 'false');
			$this->RegisterPropertyBoolean('readWallbox4', 'false');
			$this->RegisterPropertyBoolean('readWallbox5', 'false');
			$this->RegisterPropertyBoolean('readWallbox6', 'false');
			$this->RegisterPropertyBoolean('readWallbox7', 'false');
			$this->RegisterPropertyBoolean('readEmergencyPower', 'false');
			$this->RegisterPropertyFloat('emergencyPowerBuffer', '0');
			$this->RegisterPropertyBoolean('readDcString', 'false');
			$this->RegisterPropertyBoolean("loggingPowerW", 'true');
			$this->RegisterPropertyBoolean("loggingPowerKw", 'false');
			$this->RegisterPropertyBoolean("loggingBatterySoc", 'true');
			$this->RegisterPropertyBoolean("loggingAutarky", 'true');
			$this->RegisterPropertyBoolean("loggingSelfconsumption", 'true');
			$this->RegisterPropertyBoolean("calcWh", 'false');
			$this->RegisterPropertyBoolean("calcKwh", 'true');
			$this->RegisterPropertyBoolean("loggingWirkarbeit", true);
			$this->RegisterPropertyInteger('pollCycle', '60');


			// *** Erstelle deaktivierte Timer ***
			// Autarkie und Eigenverbrauch
			$this->RegisterTimer("Update-Autarkie-Eigenverbrauch", 0, "\$instanceId = IPS_GetObjectIDByIdent(\"40082\", ".$this->InstanceID.");
\$varId = IPS_GetObjectIDByIdent(\"Value\", \$instanceId);
\$varValue = GetValue(\$varId);
\$Autarkie = (\$varValue >> 8 ) & 0xFF;
\$Eigenverbrauch = (\$varValue & 0xFF);

\$AutarkieId = IPS_GetObjectIDByIdent(\"Autarkie\", \$instanceId);
\$EigenverbrauchId = IPS_GetObjectIDByIdent(\"Eigenverbrauch\", \$instanceId);

if(GetValue(\$AutarkieId) != \$Autarkie)
{
	SetValue(\$AutarkieId, \$Autarkie);
}

if(GetValue(\$EigenverbrauchId) != \$Eigenverbrauch)
{
	SetValue(\$EigenverbrauchId, \$Eigenverbrauch);
}");

			// EMS-Status Bits
			$this->RegisterTimer("Update-EMS-Status", 0, "\$instanceId = IPS_GetObjectIDByIdent(\"40085\", ".$this->InstanceID.");
\$varId = IPS_GetObjectIDByIdent(\"Value\", \$instanceId);
\$varValue = GetValue(\$varId);

\$bitArray = array(\"Batterie laden\", \"Batterie entladen\", \"Notstrommodus\", \"Wetterbasiertes Laden\", \"Abregelungs-Status\", \"Ladesperrzeit\", \"Entladesperrzeit\");

for(\$i = 0; \$i < count(\$bitArray); \$i++)
{
	\$bitId = IPS_GetObjectIDByIdent(removeInvalidChars(\$bitArray[\$i]), \$instanceId);
    \$bitValue = (\$varValue >> \$i ) & 0x1;

	if(GetValue(\$bitId) != \$bitValue)
	{
		SetValue(\$bitId, \$bitValue);
	}
}

function removeInvalidChars(\$input)
{
	return preg_replace( '/[^a-z0-9]/i', '', \$input);
}");

			// WallBox_X_CTRL Bits
			$this->RegisterTimer("Update-WallBox_X_CTRL", 0, "\$modbusAddress_Array = array(40088, 40089, 40090, 40091, 40092, 40093, 40094, 40095);
foreach(\$modbusAddress_Array AS \$modbusAddress)
{
	\$instanceId = @IPS_GetObjectIDByIdent(\$modbusAddress, ".$this->InstanceID.");
	
	if(false !== \$instanceId)
	{
		\$varId = IPS_GetObjectIDByIdent(\"Value\", \$instanceId);
		\$varValue = GetValue(\$varId);

		\$bitArray = array(\"Wallbox\", \"Solarbetrieb\", \"Laden sperren\", \"Ladevorgang\", \"Typ-2-Stecker verriegelt\", \"Typ-2-Stecker gesteckt\", \"Schukosteckdose\", \"Schukostecker gesteckt\", \"Schukostecker verriegelt\", \"16A 1 Phase\", \"16A 3 Phasen\", \"32A 3 Phasen\", \"1 Phase\");

		for(\$i = 0; \$i < count(\$bitArray); \$i++)
		{
			\$bitId = IPS_GetObjectIDByIdent(removeInvalidChars(\$bitArray[\$i]), \$instanceId);
			\$bitValue = (\$varValue >> \$i ) & 0x1;

			if(GetValue(\$bitId) != \$bitValue)
			{
				SetValue(\$bitId, \$bitValue);
			}
		}
	}
}

function removeInvalidChars(\$input)
{
	return preg_replace( '/[^a-z0-9]/i', '', \$input);
}");

			// Berechnung der Leistungs-Werte in kW
			$this->RegisterTimer("Update-ValuesKw", 0, "\$modbusAddress_Array = array(40068, 40070, 40072, 40074, 40078, 40080, 40076);
foreach(\$modbusAddress_Array AS \$modbusAddress)
{
	\$instanceId = @IPS_GetObjectIDByIdent(\$modbusAddress, ".$this->InstanceID.");
	
	if(false !== \$instanceId)
	{
		\$kwId = @IPS_GetObjectIDByIdent(\"Value_kW\", \$instanceId);

		if(false !== \$kwId)
		{
			\$varId = IPS_GetObjectIDByIdent(\"Value\", \$instanceId);
			\$varValue = GetValue(\$varId);

			\$kwValue = \$varValue / 1000;

			if(GetValue(\$kwId) != \$kwValue)
			{
				SetValue(\$kwId, \$kwValue);
			}
		}
		else
		{
			// Abbrechen: Timer wurde wegen Gesamtleistungs-Berechnung aktiviert
			break;
		}
	}
}

\$varId = @IPS_GetObjectIDByIdent(\"GesamtproduktionLeistung\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$varValueCalc = abs(GetValue(IPS_GetObjectIDByIdent(\"Value\", IPS_GetObjectIDByIdent(\"40068\", ".$this->InstanceID.")))) + abs(GetValue(IPS_GetObjectIDByIdent(\"Value\", IPS_GetObjectIDByIdent(\"40076\", ".$this->InstanceID."))));

	if(GetValue(\$varId) != \$varValueCalc)
	{
		SetValue(\$varId, \$varValueCalc);

		\$kwId = @IPS_GetObjectIDByIdent(\"GesamtproduktionLeistung_kW\", ".$this->InstanceID.");
		if(false !== \$kwId)
		{
			\$kwValue = \$varValueCalc / 1000;
			SetValue(\$kwId, \$kwValue);
		}
	}
}
");

			// Berechnung der Energie-Werte in Wh/kWh
			$this->RegisterTimer("Wh-Berechnung", 0, "//require_once '".__DIR__."/../libs/myFunctions.php';

// calculate Wh values
\$startzeit = microtime(true);
\$engergyArray = array();
\$varId = @IPS_GetObjectIDByIdent(\"BatteryChargingWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetBatteryChargeEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"BatteryDischargingWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetBatteryDischargeEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"ExtWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetExtEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"GridConsumptionWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetGridConsumptionEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"GridFeedWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetGridFeedEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"GesamtproduktionWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetProductionEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"PvWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetPvEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"HomeWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetHomeEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"WallboxWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetWallboxEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"WallboxSolarWh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetWallboxSolarEnergyWh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}

// calculate kWh values
\$varId = @IPS_GetObjectIDByIdent(\"BatteryChargingKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetBatteryChargeEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"BatteryDischargingKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetBatteryDischargeEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"ExtKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetExtEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"GridConsumptionKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetGridConsumptionEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"GridFeedKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetGridFeedEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"GesamtproduktionKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetProductionEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"PvKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetPvEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"HomeKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetHomeEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"WallboxKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetWallboxEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
\$varId = @IPS_GetObjectIDByIdent(\"WallboxSolarKwh\", ".$this->InstanceID.");
if(false !== \$varId)
{
	\$engergyArray[] = \$varId;
	SetValue(\$varId, E3DC_GetWallboxSolarEnergyKwh(".$this->InstanceID.", mktime(0,0,0, date(\"m\"), date(\"j\"), date(\"Y\")), time()));
}
");

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
			$hostSwapWords = 1; // E3DC = true
			$batterySize = $this->ReadPropertyFloat('batterySize');
			$batteryDischargeMax = BATTERY_DISCHARGE_MAX;
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');
			$readWallbox0 = $this->ReadPropertyBoolean('readWallbox0');
			$readWallbox1 = $this->ReadPropertyBoolean('readWallbox1');
			$readWallbox2 = $this->ReadPropertyBoolean('readWallbox2');
			$readWallbox3 = $this->ReadPropertyBoolean('readWallbox3');
			$readWallbox4 = $this->ReadPropertyBoolean('readWallbox4');
			$readWallbox5 = $this->ReadPropertyBoolean('readWallbox5');
			$readWallbox6 = $this->ReadPropertyBoolean('readWallbox6');
			$readWallbox7 = $this->ReadPropertyBoolean('readWallbox7');
			$readEmergencyPower = $this->ReadPropertyBoolean('readEmergencyPower');
			$emergencyPowerBuffer = $this->ReadPropertyFloat('emergencyPowerBuffer');
			$readDcString = $this->ReadPropertyBoolean('readDcString');
			$loggingPowerW = $this->ReadPropertyBoolean("loggingPowerW");
			$loggingPowerKw = $this->ReadPropertyBoolean("loggingPowerKw");
			$loggingBatterySoc = $this->ReadPropertyBoolean("loggingBatterySoc");
			$loggingAutarky = $this->ReadPropertyBoolean("loggingAutarky");
			$loggingSelfconsumption = $this->ReadPropertyBoolean("loggingSelfconsumption");
			$calcWh = $this->ReadPropertyBoolean("calcWh");
			$calcKwh = $this->ReadPropertyBoolean("calcKwh");
			$loggingWirkarbeit = $this->ReadPropertyBoolean("loggingWirkarbeit");
			$pollCycle = $this->ReadPropertyInteger('pollCycle') * 1000;

			$archiveId = $this->getArchiveId();
			if (false === $archiveId)
			{
				// no archive found
				$this->SetStatus(201);
			}

			if("" != $hostIp)
			{
				$this->checkProfiles();
				list($gatewayId_Old, $interfaceId_Old) = $this->readOldModbusGateway();
				list($gatewayId, $interfaceId) = $this->checkModbusGateway($hostIp, $hostPort, $hostmodbusDevice, $hostSwapWords);

				$parentId = $this->InstanceID;

				// Quelle: Modbus/TCP-Schnittstelle der E3/DC GmbH 10.04.2017, v1.6

				$categoryId = $parentId;

				$inverterModelRegister_array = array(
				/* ********** Identifikationsblock **************************************************************************/
/*					array(40001, 1, 3, "Magicbyte", "UInt16", "", "Magicbyte - S10 ModBus ID (Immer 0xE3DC)"),
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
				);
				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				// Logging setzen
				foreach($inverterModelRegister_array AS $inverterModelRegister)
				{
					$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					if (false !== $varId && false !== $archiveId)
					{
						AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW || $calcWh || $calcKwh);
					}
				}

				// Variablen für kW-Logging erstellen, sofern nötig                           
				foreach($inverterModelRegister_array AS $inverterModelRegister)
				{
					$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					
					$varId = $this->MaintainInstanceVariable("Value_kW", IPS_GetName($varId)."_kW", VARIABLETYPE_FLOAT, "~Power", 0, $loggingPowerKw, $instanceId, $inverterModelRegister[IMR_NAME]." in kW");
					if(false !== $varId && false !== $archiveId)
					{
						AC_SetLoggingStatus($archiveId, $varId, $loggingPowerKw);
					}
				}

				if($loggingPowerKw || $readExtLeistung)
				{
					// Erstellt einen Timer mit einem Intervall von $pollCycle/2 in Millisekunden.
					$this->SetTimerInterval("Update-ValuesKw", $pollCycle / 2);
				}
				else
				{
					// Deaktiviert einen Timer
					$this->SetTimerInterval("Update-ValuesKw", 0);
				}

				$inverterModelRegister_array = array(
					array(40082, 1, 3, "Autarkie-Eigenverbrauch", "Uint8+Uint8", "", "Autarkie und Eigenverbrauch in Prozent"),
				);
				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

				$inverterModelRegister_array = array(
					array(40083, 1, 3, "Batterie-SOC", "Uint16", "%", "Batterie-SOC in Prozent"),
				);
				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				// Logging setzen
				foreach($inverterModelRegister_array AS $inverterModelRegister)
				{
					$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					if (false !== $varId && false !== $archiveId)
					{
						AC_SetLoggingStatus($archiveId, $varId, $loggingBatterySoc);
					}
				}

				$inverterModelRegister_array = array(
					array(40084, 1, 3, "Emergency-Power", "Uint16", "", "Emergency-Power Status:
0 = Notstrom wird nicht von Ihrem Gerät unterstützt (bei Geräten der älteren Gerätegeneration, z. B. S10-SP40, S10-P5002).
1 = Notstrom aktiv (Ausfall des Stromnetzes)
2 = Notstrom nicht aktiv
3 = Notstrom nicht verfügbar
4 = Der Motorschalter des S10 E befindet sich nicht in der richtigen Position, sondern wurde manuell abgeschaltet oder nicht eingeschaltet.
Hinweis: Falls der Motorschalter nicht bewusst ausgeschaltet wurde, haben Sie eventuell übersehen, den Schieberegler am Motorschalter in die Position 'ON' zu bringen (s. die folgende Abbildung zur Erläuterung)."),
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
				$instanceId = IPS_GetObjectIDByIdent("40082", $categoryId);
				$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
				IPS_SetHidden($varId, true);
				
				$varName = "Autarkie";
				$varId = $this->MaintainInstanceVariable($this->removeInvalidChars($varName), $varName, VARIABLETYPE_INTEGER, "~Valve", 0, true, $instanceId, "Autarkie in Prozent");
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingAutarky);
				}

				$varName = "Eigenverbrauch";
 				$varId = $this->MaintainInstanceVariable($this->removeInvalidChars($varName), $varName, VARIABLETYPE_INTEGER, "~Valve", 0, true, $instanceId, "Eigenverbrauch in Prozent");
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingSelfconsumption);
				}

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-Autarkie-Eigenverbrauch", 5000);


				// Bit 0 - 6 für "EMS-Status" erstellen
				$instanceId = IPS_GetObjectIDByIdent("40085", $categoryId);
				$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
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
					$varId = $this->MaintainInstanceVariable($this->removeInvalidChars($bit['varName']), $bit['varName'], VARIABLETYPE_BOOLEAN, $bit['varProfile'], 0, true, $instanceId, $bit['varInfo']);
				}

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-EMS-Status", 5000);



				$inverterModelRegister_array = array(
					array(40076, 2, 3, "Ext-Leistung", "Int32", "W", "Leistung aller zusätzlichen Einspeiser in Watt"),
				);

				if($readExtLeistung)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
					// Logging setzen
					foreach($inverterModelRegister_array AS $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
						if (false !== $varId && false !== $archiveId)
						{
							AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW);
						}
					}

					// Variablen für kW-Logging erstellen, sofern nötig                           
					foreach($inverterModelRegister_array AS $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varIdOrg = IPS_GetObjectIDByIdent("Value", $instanceId);
						
						$varId = $this->MaintainInstanceVariable("Value_kW", IPS_GetName($varIdOrg)."_kW", VARIABLETYPE_FLOAT, "~Power", 0, $loggingPowerKw, $instanceId, $inverterModelRegister[IMR_NAME]." in kW");
						if(false !== $varId && false !== $archiveId)
						{
							AC_SetLoggingStatus($archiveId, $varId, $loggingPowerKw);
						}
					}
				}
				else
				{
					$this->deleteModbusInstancesRecursive($inverterModelRegister_array, $categoryId);
				}
				
				$varId = $this->myMaintainVariable("GesamtproduktionLeistung", "Gesamtproduktion-Leistung", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Watt.Int", 0, $readExtLeistung);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW);
				}

				$varId = $this->myMaintainVariable("GesamtproduktionLeistung_kW", "Gesamtproduktion-Leistung_kW", VARIABLETYPE_FLOAT, "~Power", 0, $readExtLeistung && $loggingPowerKw);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingPowerKw);
				}
				
				// Wirkarbeit in Wh berechnen
				$varId = $this->myMaintainVariable("BatteryChargingWh", "Batterie-Lade-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("BatteryDischargingWh", "Batterie-Entlade-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("ExtWh", "Ext-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && $readExtLeistung);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GesamtproduktionWh", "Gesamtproduktion-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && $readExtLeistung);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridConsumptionWh", "Netz-Bezug-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridFeedWh", "Netz-Einspeisung-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("PvWh", "PV-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("HomeWh", "Verbrauchs-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxWh", "Wallbox-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxSolarWh", "Wallbox-Solar-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				
				// Wirkarbeit in kWh berechnen
				$varId = $this->myMaintainVariable("BatteryChargingKwh", "Batterie-Lade-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("BatteryDischargingKwh", "Batterie-Entlade-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("ExtKwh", "Ext-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && $readExtLeistung);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GesamtproduktionKwh", "Gesamtproduktion-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && $readExtLeistung);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridConsumptionKwh", "Netz-Bezug-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridFeedKwh", "Netz-Einspeisung-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("PvKwh", "PV-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("HomeKwh", "Verbrauchs-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxKwh", "Wallbox-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxSolarKwh", "Wallbox-Solar-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if(false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}

				// Erstellt einen Timer mit einem Intervall von 1 Minuten.
				if($calcWh || $calcKwh)
				{
					$this->SetTimerInterval("Wh-Berechnung", 60 * 1000);
				}
			

				/* ********** Spezifische Abfragen zur Steuerung der Wallbox **************************************
					Hinweis: Es können nicht alle Bits geschaltet werden. Bereiche, bei denen die aktive Steuerung sinnvoll ist, sind mit RW (= 'Read' und 'Write') gekennzeichnet.
				 ************************************************************************************************** */

				$inverterModelRegister_array = array(
					array(40078, 2, 3, "Wallbox-Leistung", "Int32", "W", "Leistung der Wallbox in Watt"),
					array(40080, 2, 3, "Wallbox-Solarleistung", "Int32", "W", "Solarleistung, die von der Wallbox genutzt wird in Watt"),
				);

				if($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					// Logging setzen
					foreach($inverterModelRegister_array AS $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
						if (false !== $varId && false !== $archiveId)
						{
							AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW);
						}
					}

					// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
					$this->SetTimerInterval("Update-WallBox_X_CTRL", 5000);

					// Variablen fuer kW-Logging erstellen, sofern noetig                           
					foreach($inverterModelRegister_array AS $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varIdOrg = IPS_GetObjectIDByIdent("Value", $instanceId);
						
						$varId = $this->MaintainInstanceVariable("Value_kW", IPS_GetName($varIdOrg)."_kW", VARIABLETYPE_FLOAT, "~Power", 0, $loggingPowerKw, $instanceId, $inverterModelRegister[IMR_NAME]." in kW");
						if(false !== $varId	&& false !== $archiveId)
						{
							AC_SetLoggingStatus($archiveId, $varId, $loggingPowerKw);
						}
					}
				}
				else
				{
					// deaktiviert einen Timer
					$this->SetTimerInterval("Update-WallBox_X_CTRL", 0);

					$this->deleteModbusInstancesRecursive($inverterModelRegister_array, $categoryId);
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
					array('varName' => "Wallbox", 'varProfile' => "~Alert.Reversed", 'varInfo' => "Bit 0: Wallbox vorhanden und verfügbar (1) R"),
					array('varName' => "Solarbetrieb", 'varProfile' => "~Switch", 'varInfo' => "Bit 1: Solarbetrieb aktiv (1) Mischbetrieb aktiv (0)   RW"),
					array('varName' => "Laden sperren", 'varProfile' => "~Lock", 'varInfo' => "Bit 2: Laden abgebrochen (1) Laden freigegeben (0) RW"),
					array('varName' => "Ladevorgang", 'varProfile' => "~Switch", 'varInfo' => "Bit 3: Auto lädt (1) Auto lädt nicht (0)  R"),
					array('varName' => "Typ-2-Stecker verriegelt", 'varProfile' => "~Switch", 'varInfo' => "Bit 4: Typ-2-Stecker verriegelt (1)    R"),
					array('varName' => "Typ-2-Stecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 5: Typ-2-Stecker gesteckt (1)  R"),
					array('varName' => "Schukosteckdose", 'varProfile' => "~Switch", 'varInfo' => "Bit 6: Schukosteckdose an (1)  RW"),
					array('varName' => "Schukostecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 7: Schukostecker gesteckt (1)  R"),
					array('varName' => "Schukostecker verriegelt", 'varProfile' => "~Lock", 'varInfo' => "Bit 8: ukostecker verriegelt (1)    R"),
					array('varName' => "16A 1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 9: Relais an, 16A 1 Phase, Schukosteckdose R"),
					array('varName' => "16A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 10: Relais an, 16A 3 Phasen, Typ 2  R"),
					array('varName' => "32A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 11: Relais an, 32A 3 Phasen, Typ 2  R"),
					array('varName' => "1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 12: Eine Phase aktiv (1) drei Phasen aktiv (0)  RW"),
//					array('varName' => "", 'varProfile' => "", 'varInfo' => "Bit 13: Nicht belegt"),
				);

				$inverterModelRegister_array = array();
				$inverterModelRegisterDel_array = array();

				if($readWallbox0)
				{
					$inverterModelRegister_array[] = array(40088, 1, 3, "WallBox_0_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40088, 1, 3, "WallBox_0_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox1)
				{
					$inverterModelRegister_array[] = array(40089, 1, 3, "WallBox_1_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40089, 1, 3, "WallBox_1_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox2)
				{
					$inverterModelRegister_array[] = array(40090, 1, 3, "WallBox_2_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40090, 1, 3, "WallBox_2_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox3)
				{
					$inverterModelRegister_array[] = array(40091, 1, 3, "WallBox_3_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40091, 1, 3, "WallBox_3_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox4)
				{
					$inverterModelRegister_array[] = array(40092, 1, 3, "WallBox_4_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40092, 1, 3, "WallBox_4_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox5)
				{
					$inverterModelRegister_array[] = array(40093, 1, 3, "WallBox_5_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40093, 1, 3, "WallBox_5_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox6)
				{
					$inverterModelRegister_array[] = array(40094, 1, 3, "WallBox_6_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40094, 1, 3, "WallBox_6_CTRL", "Uint16", "", $wallboxDescription);
				}

				if($readWallbox7)
				{
					$inverterModelRegister_array[] = array(40095, 1, 3, "WallBox_7_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40095, 1, 3, "WallBox_7_CTRL", "Uint16", "", $wallboxDescription);
				}

				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

				foreach($inverterModelRegister_array AS $register)
				{
					// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
					$instanceId = IPS_GetObjectIDByIdent($register[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					IPS_SetHidden($varId, true);
					
					foreach($bitArray AS $bit)
					{
						$varId = $this->MaintainInstanceVariable($this->removeInvalidChars($bit['varName']), $bit['varName'], VARIABLETYPE_BOOLEAN, $bit['varProfile'], 0, true, $instanceId, $bit['varInfo']);
					}
				}

				$this->deleteModbusInstancesRecursive($inverterModelRegisterDel_array, $categoryId);



				$categoryName = "DC_String";
				$categoryId = @IPS_GetObjectIDByIdent($this->removeInvalidChars($categoryName), $parentId);
				if($readDcString)
				{
					$inverterModelRegister_array = array(
					/* ********** DC-String **************************************************************************
						Hinweis: Die folgenden Register 40096 bis 40104 koennen ab dem Release S10_2017_02 genutzt werden!
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
						IPS_SetIdent($categoryId, $this->removeInvalidChars($categoryName));
						IPS_SetName($categoryId, $categoryName);
						IPS_SetParent($categoryId, $parentId);
						IPS_SetInfo($categoryId, "Hinweis: Die folgenden Register 40096 bis 40104 können ab dem Release S10_2017_02 genutzt werden!");
					}

					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				}
				else
				{
					if(false !== $categoryId)
					{
						foreach(IPS_GetChildrenIDs($categoryId) AS $childId)
						{
							$this->deleteInstanceRecursive($childId);
						}
						IPS_DeleteCategory($categoryId);
					}
				}


				if($active)
				{
					// Erreichbarkeit von IP und Port pruefen
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


				// pruefen, ob sich ModBus-Gateway geaendert hat
				if(0 != $gatewayId_Old && $gatewayId != $gatewayId_Old)
				{
					$this->deleteInstanceNotInUse($gatewayId_Old, MODBUS_ADDRESSES);
				}

				// pruefen, ob sich ClientSocket Interface geaendert hat
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
/*
BESCHREIBUNG
Aktiviert die Standardaktion der Statusvariable. Dadurch ist diese in der Visualisierung veraenderbar und kann auch beschrieben werden. Diese Funktion muss aufgerufen werden, da alle Statusvariablen standardmaeßig ohne Standardaktion erstellt werden. Sofern die Standardaktion aktiviert ist, muss auf Aenderungsanfragen innerhalb von RequestAction reagiert werden.

BEISPIEL
// Aktiviert die Standardaktion der Statusvariable
$this->EnableAction("Status");

		public function RequestAction($Ident, $Value)
		{
		 
			switch($Ident) {
				case "TestVariable":
					//Hier wuerde normalerweise eine Aktion z.B. das Schalten ausgefuehrt werden
					//Ausgaben ueber 'echo' werden an die Visualisierung zurueckgeleitet
		 
					//Neuen Wert in die Statusvariable schreiben
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
				default:
					throw new Exception("Invalid Ident");
			}
		 
		}
*/
		private function createModbusInstances($inverterModelRegister_array, $parentId, $gatewayId, $pollCycle)
		{
			// Workaround für "InstanceInterface not available" Fehlermeldung beim Server-Start...
			if (KR_READY == IPS_GetKernelRunlevel())
			{
				// Erstelle Modbus Instancen
				foreach ($inverterModelRegister_array as $inverterModelRegister)
				{
					if (DEBUG)
					{
						echo "REG_".$inverterModelRegister[IMR_START_REGISTER]." - ".$inverterModelRegister[IMR_NAME]."\n";
					}
					// Datentyp ermitteln
					// 0=Bit, 1=Byte, 2=Word, 3=DWord, 4=ShortInt, 5=SmallInt, 6=Integer, 7=Real
					if ("uint16" == strtolower($inverterModelRegister[IMR_TYPE])
						|| "enum16" == strtolower($inverterModelRegister[IMR_TYPE])
						|| "uint8+uint8" == strtolower($inverterModelRegister[IMR_TYPE]))
					{
						$datenTyp = 2;
					}
					elseif ("uint32" == strtolower($inverterModelRegister[IMR_TYPE]))
					{
						$datenTyp = 3;
					}
					elseif ("int16" == strtolower($inverterModelRegister[IMR_TYPE])
						|| "sunssf" == strtolower($inverterModelRegister[IMR_TYPE]))
					{
						$datenTyp = 4;
					}
					elseif ("int32" == strtolower($inverterModelRegister[IMR_TYPE]))
					{
						$datenTyp = 6;
					}
					elseif ("float32" == strtolower($inverterModelRegister[IMR_TYPE]))
					{
						$datenTyp = 7;
					}
					elseif ("string32" == strtolower($inverterModelRegister[IMR_TYPE])
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
					if ("a" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
					{
						$profile = "~Ampere";
					}
					elseif ("a" == strtolower($inverterModelRegister[IMR_UNITS]))
					{
						$profile = MODUL_PREFIX.".Ampere.Int";
					}
					/*				elseif("ah" == strtolower($inverterModelRegister[IMR_UNITS]))
									{
										$profile = MODUL_PREFIX.".AmpereHour.Int";
									}
					 */				elseif ("v" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
					{
						$profile = "~Volt";
					}
					elseif ("v" == strtolower($inverterModelRegister[IMR_UNITS]))
					{
						$profile = MODUL_PREFIX.".Volt.Int";
					}
					elseif ("w" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
					{
						$profile = "~Watt.14490";
					}
					elseif ("w" == strtolower($inverterModelRegister[IMR_UNITS]))
					{
						$profile = MODUL_PREFIX.".Watt.Int";
					}
					elseif ("hz" == strtolower($inverterModelRegister[IMR_UNITS]))
					{
						$profile = "~Hertz";
					}
					/*				// Voltampere fuer elektrische Scheinleistung
									elseif("va" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
									{
										$profile = MODUL_PREFIX.".Scheinleistung.Float";
									}
									// Voltampere fuer elektrische Scheinleistung
									elseif("va" == strtolower($inverterModelRegister[IMR_UNITS]))
									{
										$profile = MODUL_PREFIX.".Scheinleistung.Int";
									}
									// Var fuer elektrische Blindleistung
									elseif("var" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
									{
										$profile = MODUL_PREFIX.".Blindleistung.Float";
									}
									// Var fuer elektrische Blindleistung
									elseif("var" == strtolower($inverterModelRegister[IMR_UNITS]) || "var" == $inverterModelRegister[IMR_UNITS])
									{
										$profile = MODUL_PREFIX.".Blindleistung.Int";
									}
					 */				elseif ("%" == $inverterModelRegister[IMR_UNITS] && 7 == $datenTyp)
					{
						$profile = "~Valve.F";
					}
					elseif ("%" == $inverterModelRegister[IMR_UNITS])
					{
						$profile = "~Valve";
					}
					elseif ("wh" == strtolower($inverterModelRegister[IMR_UNITS]) && 7 == $datenTyp)
					{
						$profile = MODUL_PREFIX.".Electricity.Float";
					}
					/*				elseif("wh" == strtolower($inverterModelRegister[IMR_UNITS]))
									{
										$profile = MODUL_PREFIX.".Electricity.Int";
									}
					 */				elseif ("° C" == $inverterModelRegister[IMR_UNITS])
					{
						$profile = "~Temperature";
					}
					/*				elseif("cos()" == strtolower($inverterModelRegister[IMR_UNITS]))
									{
										$profile = MODUL_PREFIX.".Angle.Int";
									}
									elseif("enumerated_st" == strtolower($inverterModelRegister[IMR_UNITS]))
									{
										$profile = "SunSpec.StateCodes.Int";
									}
									elseif("enumerated_stvnd" == strtolower($inverterModelRegister[IMR_UNITS]))
									{
										$profile = MODUL_PREFIX.".StateCodes.Int";
									}
					 */				elseif ("" == $inverterModelRegister[IMR_UNITS] && "emergency-power" == strtolower($inverterModelRegister[IMR_NAME]))
					{
						$profile = MODUL_PREFIX.".Emergency-Power.Int";
					}
					elseif ("bitfield" == strtolower($inverterModelRegister[IMR_UNITS]))
					{
						$profile = false;
					}
					else
					{
						$profile = false;
						if ("" != $inverterModelRegister[IMR_UNITS])
						{
							echo "Profil '".$inverterModelRegister[IMR_UNITS]."' unbekannt.\n";
						}
					}


					$instanceId = @IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $parentId);
					$initialCreation = false;
					$applyChanges = false;
					// Modbus-Instanz erstellen, sofern noch nicht vorhanden
					if (false === $instanceId)
					{
						$instanceId = IPS_CreateInstance(MODBUS_ADDRESSES);
						IPS_SetIdent($instanceId, $inverterModelRegister[IMR_START_REGISTER]);
						IPS_SetName($instanceId, /*"REG_".$inverterModelRegister[IMR_START_REGISTER]. " - ".*/$inverterModelRegister[IMR_NAME]);
						IPS_SetParent($instanceId, $parentId);
						IPS_SetInfo($instanceId, $inverterModelRegister[IMR_DESCRIPTION]);

						$applyChanges = true;
						$initialCreation = true;
					}

					// Gateway setzen
					if (IPS_GetInstance($instanceId)['ConnectionID'] != $gatewayId)
					{
						if (0 != IPS_GetInstance($instanceId)['ConnectionID'])
						{
							IPS_DisconnectInstance($instanceId);
						}
						IPS_ConnectInstance($instanceId, $gatewayId);
						$applyChanges = true;
					}


					// Modbus-Instanz konfigurieren
					if ($datenTyp != IPS_GetProperty($instanceId, "DataType"))
					{
						IPS_SetProperty($instanceId, "DataType", $datenTyp);
						$applyChanges = true;
					}
					if (false != IPS_GetProperty($instanceId, "EmulateStatus"))
					{
						IPS_SetProperty($instanceId, "EmulateStatus", false);
						$applyChanges = true;
					}
					if ($pollCycle != IPS_GetProperty($instanceId, "Poller"))
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
					if ($inverterModelRegister[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET != IPS_GetProperty($instanceId, "ReadAddress"))
					{
						IPS_SetProperty($instanceId, "ReadAddress", $inverterModelRegister[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET);
						$applyChanges = true;
					}
					if ($inverterModelRegister[IMR_FUNCTION_CODE] != IPS_GetProperty($instanceId, "ReadFunctionCode"))
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
					if (0 != IPS_GetProperty($instanceId, "WriteFunctionCode"))
					{
						IPS_SetProperty($instanceId, "WriteFunctionCode", 0);
						$applyChanges = true;
					}

					if ($applyChanges)
					{
						IPS_ApplyChanges($instanceId);
						//IPS_Sleep(100);
					}

					// Statusvariable der Modbus-Instanz ermitteln
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);

					// Profil der Statusvariable initial einmal zuweisen
					if ($initialCreation && false != $profile)
					{
						// Justification Rule 11: es ist die Funktion RegisterVariable...() in diesem Fall nicht nutzbar, da die Variable durch die Modbus-Instanz bereits erstellt wurde
						// --> Custo Profil wird initial einmal beim Instanz-erstellen gesetzt
						IPS_SetVariableCustomProfile($varId, $profile);
					}
				}
			}
		}
		
		private function checkProfiles()
		{
/*
			$this->createVarProfile("SunSpec.StateCodes.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
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
			$this->createVarProfile(MODUL_PREFIX.".StateCodes.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
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
			$this->createVarProfile(MODUL_PREFIX.".Emergency-Power.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
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
			$this->createVarProfile(MODUL_PREFIX.".Electricity.Float", VARIABLETYPE_FLOAT, ' Wh');
*/
			$this->createVarProfile(MODUL_PREFIX.".Electricity.Int", VARIABLETYPE_INTEGER, ' Wh');
/*
			$this->createVarProfile(MODUL_PREFIX.".AmpereHour.Int", VARIABLETYPE_INTEGER, ' Ah');
*/
			$this->createVarProfile(MODUL_PREFIX.".Volt.Int", VARIABLETYPE_INTEGER, ' V');
		}

		private function GetVariableValue($instanceIdent, $variableIdent = "Value")
		{
			$instanceId = IPS_GetObjectIDByIdent($this->removeInvalidChars($instanceIdent), $this->InstanceID);
			$varId = IPS_GetObjectIDByIdent($this->removeInvalidChars($variableIdent), $instanceId);

			return GetValue($varId);
		}

		private function GetVariableId($instanceIdent, $variableIdent = "Value")
		{
			$instanceId = IPS_GetObjectIDByIdent($this->removeInvalidChars($instanceIdent), $this->InstanceID);
			$varId = IPS_GetObjectIDByIdent($this->removeInvalidChars($variableIdent), $instanceId);

			return $varId;
		}

		private function GetLoggedValuesInterval($id, $minutes)
		{
			$archiveId = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}");
			if (isset($archiveId[0]))
			{
				$archiveId = $archiveId[0];

				$returnValue = $this->getArithMittelOfLog($archiveId, $id, $minutes);
			}
			else
			{
				$archiveId = false;

				// no archive found
				$this->SetStatus(201);
				echo MODUL_PREFIX."_GetBatteryPowerW(): Archive not found!";

				$returnValue = GetValue($id);
			}

			return $returnValue;
		}

		/********************************
			public functions
		  *******************************/
		  
		public function GetAutarky()
		{
			return $this->GetVariableValue(40082, "Autarkie");
		}

		public function GetSelfConsumption()
		{
			return $this->GetVariableValue(40082, "Eigenverbrauch");
		}

		public function GetBatteryPowerW()
		{
			return $this->GetBatteryPowerIntervalW(0);
		}

		public function GetBatteryPowerIntervalW($timeIntervalInMinutes)
		{
			$varIdent = 40070;

			if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return round($returnValue);
		}

		public function GetBatteryPowerKw()
		{
			return $this->GetBatteryPowerIntervalKw(0);
		}

		public function GetBatteryPowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetBatteryPowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetBatteryChargeEnergyWh($startTime, $endTime)
		{
			$varIdent = 40070;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 1);

			return round($returnValue);
		}

		public function GetBatteryChargeEnergyKwh($startTime, $endTime)
		{
			return $this->GetBatteryChargeEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetBatteryDischargeEnergyWh($startTime, $endTime)
		{
			$varIdent = 40070;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 2);

			return abs(round($returnValue));
		}

		public function GetBatteryDischargeEnergyKwh($startTime, $endTime)
		{
			return $this->GetBatteryDischargeEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetBatterySoc()
		{
			return $this->GetVariableValue(40083, "Value");
		}

		public function GetBatteryRangeKwh()
		{
			$batterySize = $this->ReadPropertyFloat('batterySize');
			$batteryDischargeMax = BATTERY_DISCHARGE_MAX;
			$readEmergencyPower = $this->ReadPropertyBoolean('readEmergencyPower');
			$emergencyPowerBuffer = $this->ReadPropertyFloat('emergencyPowerBuffer');

			$batteryRange = ($this->GetBatterySoc() / 100) * $batterySize * ($batteryDischargeMax / 100);

			if($readEmergencyPower)
			{
				$batteryRange = $batteryRange - $emergencyPowerBuffer;
			}

			return $batteryRange;
		}

		public function GetBatteryRangeWh()
		{
			return $this->GetBatteryRangeWh() * 1000;
		}

		public function GetExtPowerW()
		{
			return $this->GetExtPowerIntervalW(0);
		}

		public function GetExtPowerIntervalW($timeIntervalInMinutes)
		{
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');

			$varIdent = 40076;

			if (false === $readExtLeistung)
			{
				$returnValue = 0;
			}
			else if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return abs(round($returnValue));
		}

		public function GetExtPowerKw()
		{
			return $this->GetExtPowerIntervalKw(0);
		}

		public function GetExtPowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetExtPowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetExtEnergyWh($startTime, $endTime)
		{
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');

			$varIdent = 40076;

			if (false === $readExtLeistung)
			{
				$returnValue = 0;
			}
			else
			{
				$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);
			}

			return abs(round($returnValue));
		}

		public function GetExtEnergyKwh($startTime, $endTime)
		{
			return $this->GetExtEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetProductionPowerW()
		{
			return $this->GetProductionPowerIntervalW(0);
		}

		public function GetProductionPowerIntervalW($timeIntervalInMinutes)
		{
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');

			return ($readExtLeistung ? $this->GetExtPowerIntervalW($timeIntervalInMinutes) + $this->GetPvPowerIntervalW($timeIntervalInMinutes) : $this->GetPvPowerIntervalW($timeIntervalInMinutes));
		}

		public function GetProductionPowerKw()
		{
			return $this->GetProductionPowerIntervalKw(0);
		}

		public function GetProductionPowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetProductionPowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetProductionEnergyWh($startTime, $endTime)
		{
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');

			if ($readExtLeistung)
			{
				$returnValue = $this->GetExtEnergyWh($startTime, $endTime) + $this->GetPvEnergyWh($startTime, $endTime);
			}
			else
			{
				$returnValue = $this->GetPvEnergyWh($startTime, $endTime);
			}

			echo "Production: ".(int)$readExtLeistung." -> ".$this->GetExtEnergyWh($startTime, $endTime)." + ".$this->GetPvEnergyWh($startTime, $endTime)." = ".$returnValue."\n";

			return round($returnValue);
		}

		public function GetProductionEnergyKwh($startTime, $endTime)
		{
			return $this->GetProductionEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetGridPowerW()
		{
			return $this->GetGridPowerIntervalW(0);
		}

		public function GetGridPowerIntervalW($timeIntervalInMinutes)
		{
			$varIdent = 40074;

			if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return round($returnValue);
		}

		public function GetGridPowerKw()
		{
			return $this->GetGridPowerIntervalKw(0);
		}

		public function GetGridPowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetGridPowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetGridConsumptionEnergyWh($startTime, $endTime)
		{
			$varIdent = 40074;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 1);

			return round($returnValue);
		}

		public function GetGridConsumptionEnergyKwh($startTime, $endTime)
		{
			return $this->GetGridConsumptionEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetGridFeedEnergyWh($startTime, $endTime)
		{
			$varIdent = 40074;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 2);

			return abs(round($returnValue));
		}

		public function GetGridFeedEnergyKwh($startTime, $endTime)
		{
			return $this->GetGridFeedEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetPvPowerW()
		{
			return $this->GetPvPowerIntervalW(0);
		}

		public function GetPvPowerIntervalW($timeIntervalInMinutes)
		{
			$varIdent = 40068;

			if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return abs(round($returnValue));
		}

		public function GetPvPowerKw()
		{
			return $this->GetPvPowerIntervalKw(0);
		}

		public function GetPvPowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetPvPowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetPvEnergyWh($startTime, $endTime)
		{
			$varIdent = 40068;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);

			return abs(round($returnValue));
		}

		public function GetPvEnergyKwh($startTime, $endTime)
		{
			return $this->GetPvEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetHomePowerW()
		{
			return $this->GetHomePowerIntervalW(0);
		}

		public function GetHomePowerIntervalW($timeIntervalInMinutes)
		{
			$varIdent = 40072;

			if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return $returnValue;
		}

		public function GetHomePowerKw()
		{
			return $this->GetHomePowerIntervalKw(0);
		}

		public function GetHomePowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetHomePowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetHomeEnergyWh($startTime, $endTime)
		{
			$varIdent = 40072;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);

			return round($returnValue);
		}

		public function GetHomeEnergyKwh($startTime, $endTime)
		{
			return $this->GetHomeEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetWallboxPowerW()
		{
			return $this->GetWallboxPowerIntervalW(0);
		}

		public function GetWallboxPowerIntervalW($timeIntervalInMinutes)
		{
			$readWallbox0 = $this->ReadPropertyBoolean('readWallbox0');
			$readWallbox1 = $this->ReadPropertyBoolean('readWallbox1');
			$readWallbox2 = $this->ReadPropertyBoolean('readWallbox2');
			$readWallbox3 = $this->ReadPropertyBoolean('readWallbox3');
			$readWallbox4 = $this->ReadPropertyBoolean('readWallbox4');
			$readWallbox5 = $this->ReadPropertyBoolean('readWallbox5');
			$readWallbox6 = $this->ReadPropertyBoolean('readWallbox6');
			$readWallbox7 = $this->ReadPropertyBoolean('readWallbox7');
			
			$varIdent = 40078;

			if(false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			else if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return $returnValue;
		}

		public function GetWallboxPowerKw()
		{
			return $this->GetWallboxPowerIntervalKw(0);
		}

		public function GetWallboxPowerIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetWallboxPowerIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetWallboxEnergyWh($startTime, $endTime)
		{
			$readWallbox0 = $this->ReadPropertyBoolean('readWallbox0');
			$readWallbox1 = $this->ReadPropertyBoolean('readWallbox1');
			$readWallbox2 = $this->ReadPropertyBoolean('readWallbox2');
			$readWallbox3 = $this->ReadPropertyBoolean('readWallbox3');
			$readWallbox4 = $this->ReadPropertyBoolean('readWallbox4');
			$readWallbox5 = $this->ReadPropertyBoolean('readWallbox5');
			$readWallbox6 = $this->ReadPropertyBoolean('readWallbox6');
			$readWallbox7 = $this->ReadPropertyBoolean('readWallbox7');
			
			$varIdent = 40078;

			if(false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			else
			{
				$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);
			}

			return round($returnValue);
		}

		public function GetWallboxEnergyKwh($startTime, $endTime)
		{
			return $this->GetWallboxEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetWallboxPowerSolarW()
		{
			return $this->GetWallboxPowerSolarIntervalW(0);
		}

		public function GetWallboxPowerSolarIntervalW($timeIntervalInMinutes)
		{
			$readWallbox0 = $this->ReadPropertyBoolean('readWallbox0');
			$readWallbox1 = $this->ReadPropertyBoolean('readWallbox1');
			$readWallbox2 = $this->ReadPropertyBoolean('readWallbox2');
			$readWallbox3 = $this->ReadPropertyBoolean('readWallbox3');
			$readWallbox4 = $this->ReadPropertyBoolean('readWallbox4');
			$readWallbox5 = $this->ReadPropertyBoolean('readWallbox5');
			$readWallbox6 = $this->ReadPropertyBoolean('readWallbox6');
			$readWallbox7 = $this->ReadPropertyBoolean('readWallbox7');

			$varIdent = 40080;

			if(false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			else if (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return $returnValue;
		}

		public function GetWallboxPowerSolarKw()
		{
			return $this->GetWallboxPowerSolarIntervalKw(0);
		}

		public function GetWallboxPowerSolarIntervalKw($timeIntervalInMinutes)
		{
			return ($this->GetWallboxPowerSolarIntervalW($timeIntervalInMinutes) / 1000);
		}

		public function GetWallboxSolarEnergyWh($startTime, $endTime)
		{
			$readWallbox0 = $this->ReadPropertyBoolean('readWallbox0');
			$readWallbox1 = $this->ReadPropertyBoolean('readWallbox1');
			$readWallbox2 = $this->ReadPropertyBoolean('readWallbox2');
			$readWallbox3 = $this->ReadPropertyBoolean('readWallbox3');
			$readWallbox4 = $this->ReadPropertyBoolean('readWallbox4');
			$readWallbox5 = $this->ReadPropertyBoolean('readWallbox5');
			$readWallbox6 = $this->ReadPropertyBoolean('readWallbox6');
			$readWallbox7 = $this->ReadPropertyBoolean('readWallbox7');
			
			$varIdent = 40080;

			if(false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			else
			{
				$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);
			}

			return abs(round($returnValue));
		}

		public function GetWallboxSolarEnergyKwh($startTime, $endTime)
		{
			return $this->GetWallboxSolarEnergyWh($startTime, $endTime) / 1000;
		}

/*
EmergencyPowerState
			$readEmergencyPower = $this->ReadPropertyBoolean('readEmergencyPower');
			$emergencyPowerBuffer = $this->ReadPropertyInteger('emergencyPowerBuffer');
DeratingState
ErrorState
ErrorMessage

		public function Wallbox...($wallboxId)
		{
				IsWallboxAvailable
				IsWallboxLocked
				IsWallboxCharging
				...
			"Wallbox", 'varProfile' => "~Alert.Reversed", 'varInfo' => "Bit 0   Wallbox vorhanden und verfügbar (1) R"),
			"Solarbetrieb", 'varProfile' => "~Switch", 'varInfo' => "Bit 1   Solarbetrieb aktiv (1) Mischbetrieb aktiv (0)   RW"),
			"Laden sperren", 'varProfile' => "~Lock", 'varInfo' => "Bit 2   Laden abgebrochen (1) Laden freigegeben (0) RW"),
			"Ladevorgang", 'varProfile' => "~Switch", 'varInfo' => "Bit 3   Auto lädt (1) Auto lädt nicht (0)  R"),
			"Typ-2-Stecker verriegelt", 'varProfile' => "~Switch", 'varInfo' => "Bit 4   Typ-2-Stecker verriegelt (1)    R"),
			"Typ-2-Stecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 5   Typ-2-Stecker gesteckt (1)  R"),
			"Schukosteckdose", 'varProfile' => "~Switch", 'varInfo' => "Bit 6   Schukosteckdose an (1)  RW"),
			"Schukostecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 7   Schukostecker gesteckt (1)  R"),
			"Schukostecker verriegelt", 'varProfile' => "~Lock", 'varInfo' => "Bit 8   Schukostecker verriegelt (1)    R"),
			"16A 1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 9   Relais an, 16A 1 Phase, Schukosteckdose R"),
			"16A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 10  Relais an, 16A 3 Phasen, Typ 2  R"),
			"32A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 11  Relais an, 32A 3 Phasen, Typ 2  R"),
			"1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 12  Eine Phase aktiv (1) drei Phasen aktiv (0)  RW"),
		}
*/
	}
