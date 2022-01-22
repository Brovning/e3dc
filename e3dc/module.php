<?php

declare(strict_types=1);

require_once __DIR__.'/../libs/myFunctions.php';  // globale Funktionen

define("DEVELOPMENT", false);

// Modul Prefix
if (!defined('MODUL_PREFIX'))
{
	define("MODUL_PREFIX", "E3DC");
}

// Offset von Register (erster Wert 1) zu Adresse (erster Wert 0) ist -1
if (!defined('MODBUS_REGISTER_TO_ADDRESS_OFFSET'))
{
	define("MODBUS_REGISTER_TO_ADDRESS_OFFSET", -1);
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
	define("IMR_SF", 7);
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
			$this->RegisterPropertyString('wallbox0name', '0');
			$this->RegisterPropertyString('wallbox1name', '1');
			$this->RegisterPropertyString('wallbox2name', '2');
			$this->RegisterPropertyString('wallbox3name', '3');
			$this->RegisterPropertyString('wallbox4name', '4');
			$this->RegisterPropertyString('wallbox5name', '5');
			$this->RegisterPropertyString('wallbox6name', '6');
			$this->RegisterPropertyString('wallbox7name', '7');
			$this->RegisterPropertyBoolean('readPowermeter0', 'false');
			$this->RegisterPropertyBoolean('readPowermeter1', 'false');
			$this->RegisterPropertyBoolean('readPowermeter2', 'false');
			$this->RegisterPropertyBoolean('readPowermeter3', 'false');
			$this->RegisterPropertyBoolean('readPowermeter4', 'false');
			$this->RegisterPropertyBoolean('readPowermeter5', 'false');
			$this->RegisterPropertyBoolean('readPowermeter6', 'false');
			$this->RegisterPropertyBoolean('readPowermeter7', 'false');
			$this->RegisterPropertyString('powermeter0name', '0');
			$this->RegisterPropertyString('powermeter1name', '1');
			$this->RegisterPropertyString('powermeter2name', '2');
			$this->RegisterPropertyString('powermeter3name', '3');
			$this->RegisterPropertyString('powermeter4name', '4');
			$this->RegisterPropertyString('powermeter5name', '5');
			$this->RegisterPropertyString('powermeter6name', '6');
			$this->RegisterPropertyString('powermeter7name', '7');
			$this->RegisterPropertyBoolean('readEmergencyPower', 'false');
			$this->RegisterPropertyFloat('emergencyPowerBuffer', '0');
			$this->RegisterPropertyBoolean('readDcString', 'false');
			$this->RegisterPropertyString('string1name', '1');
			$this->RegisterPropertyString('string2name', '2');
			$this->RegisterPropertyString('string3name', '3');
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

		public function GetConfigurationForm()
		{
			$formElements = array();
			$formElements[] = array(
				'type' => "Label",
				'label' => "Im E3DC Stromspeicher muss Modbus TCP aktiviert sein!",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Im Konfigurationsmenü des E3DC Stromspeichers muss je nach Version \n- unter Hauptmenü > Funktionen > Funktion Modbus > Feld Protokoll das Registermapping 'E3/DC Simple-Mode'\n- ODER: unter Hauptmenü > Smart-Funktionen > Smart Home > Modbus\naktiviert werden.",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Open",
				'name' => "active",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "IP",
				'name' => "hostIp",
				'validate' => "^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$",
			);
			$formElements[] = array(
				'type' => "NumberSpinner",
				'caption' => "Port (Standard: 502)",
				'name' => "hostPort",
				'digits' => 0,
				'minimum' => 1,
				'maximum' => 65535,
			);
			$formElements[] = array(
				'type' => "NumberSpinner",
				'caption' => "Geräte ID (Standard: 1)",
				'name' => "hostmodbusDevice",
				'digits' => 0,
				'minimum' => 1,
				'maximum' => 255,
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "In welchem Zeitintervall sollen die Modbus-Werte abgefragt werden (Empfehlung: 10 Sekunden)?",
			);
			$formElements[] = array(
				'type' => "NumberSpinner",
				'caption' => "Abfrage-Intervall (in Sekunden)",
				'name' => "pollCycle",
				'minimum' => 1,
				'maximum' => 3600,
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Achtung: Die Berechnung der Wirkarbeit (Wh/kWh) wird exakter, je kleiner der Abfarge-Intervall gewählt wird.\nABER: Je kleiner der Abfrage-Intervall, um so höher die Systemlast und auch die Archiv-Größe bei aktiviertem Logging!",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Welche Batteriekapazität in kWh ist installiert?",
			);
			$formElements[] = array(
				'type' => "NumberSpinner",
				'caption' => "Batteriekapazität (bspw. 6.5, 10, 13, 15, 19.5,...)",
				'name' => "batterySize",
				'digits' => 1,
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Ist ein zusätzlicher Einspeiser (bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) angeschlossen?",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "externer Einspeiser",
				'name' => "readExtLeistung",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Wie viele bzw. welche Wallbox IDs sind am E3DC in Verwendung?",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 0",
				'name' => "readWallbox0",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 0 (Standard: 0)",
				'name' => "wallbox0name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 1",
				'name' => "readWallbox1",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 1 (Standard: 1)",
				'name' => "wallbox1name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 2",
				'name' => "readWallbox2",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 2 (Standard: 2)",
				'name' => "wallbox2name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 3",
				'name' => "readWallbox3",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 3 (Standard: 3)",
				'name' => "wallbox3name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 4",
				'name' => "readWallbox4",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 4 (Standard: 4)",
				'name' => "wallbox4name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 5",
				'name' => "readWallbox5",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 5 (Standard: 5)",
				'name' => "wallbox5name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 6",
				'name' => "readWallbox6",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 6 (Standard: 6)",
				'name' => "wallbox6name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Wallbox 7",
				'name' => "readWallbox7",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Wallbox 7 (Standard: 7)",
				'name' => "wallbox7name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Wie viele bzw. welche Leistungsmesser/Powermeter IDs sind am E3DC in Verwendung?",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 0",
				'name' => "readPowermeter0",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 0 (Standard: 0)",
				'name' => "powermeter0name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 1",
				'name' => "readPowermeter1",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 1 (Standard: 1)",
				'name' => "powermeter1name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 2",
				'name' => "readPowermeter2",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 2 (Standard: 2)",
				'name' => "powermeter2name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 3",
				'name' => "readPowermeter3",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 3 (Standard: 3)",
				'name' => "powermeter3name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 4",
				'name' => "readPowermeter4",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 4 (Standard: 4)",
				'name' => "powermeter4name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 5",
				'name' => "readPowermeter5",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 5 (Standard: 5)",
				'name' => "powermeter5name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 6",
				'name' => "readPowermeter6",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 6 (Standard: 6)",
				'name' => "powermeter6name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsmesser 7",
				'name' => "readPowermeter7",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von Leistungsmesser 7 (Standard: 7)",
				'name' => "powermeter7name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Ist im E3DC eine Notstromversorgung installiert?",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Notstromversorgung",
				'name' => "readEmergencyPower",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Wenn Notstromversorgung vorhanden, welche Notstrom-Reserve in kWh wurde im Speicher eingestellt (siehe Notstrom -> Einstellungen)?",
			);
			$formElements[] = array(
				'type' => "NumberSpinner",
				'caption' => "Notstrom-Reserve (in kWh)",
				'name' => "emergencyPowerBuffer",
				'digits' => 3,
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Sollen V, A und W der DC-Strings ausgelesen werden? (verfügbar ab Release S10_2017_02)",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "DC String Informationen",
				'name' => "readDcString",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von MPP-Tracker 1, entspricht String 1.1+1.2 (Standard: 1)",
				'name' => "string1name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von MPP-Tracker 2, entspricht String 2.1+2.2 (Standard: 2)",
				'name' => "string2name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "ValidationTextBox",
				'caption' => "Name von MPP-Tracker 3, entspricht String 3.1+3.2 (Standard: 3)",
				'name' => "string3name",
				'validate' => "^[a-zA-Z0-9_-]+$",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Für welche Variablen soll das Logging aktiviert werden?",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsvariablen in W",
				'name' => "loggingPowerW",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Leistungsvariablen in kW",
				'name' => "loggingPowerKw",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Batterie SOC (Ladezustand) in %",
				'name' => "loggingBatterySoc",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Autarkie in %",
				'name' => "loggingAutarky",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Eigenverbrauch in %",
				'name' => "loggingSelfconsumption",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Sollen die Tageswerte der Wirkarbeit (in Wh/kWh) berechnet werden?",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Tageswerte in Wh",
				'name' => "calcWh",
			);
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Tageswerte in kWh",
				'name' => "calcKwh",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => "Achtung: Hierbei handelt es sich lediglich um eine näherungsweise Berechnung anhand der geloggten Leistungswerte, da die exakten Werte des Leistungsmessers von E3DC nicht per Modbus zur Verfügung gestellt werden!",
			);
			$formElements[] = array(
				'type' => "Label",
				'label' => " ",
			);
			if (defined('DEVELOPMENT') && DEVELOPMENT)
			{
				$formElements[] = array(
					'type' => "Label",
					'label' => "Sollen die Werte der Wirkarbeit (in Wh/kWh) gelogged werden (nur 1 Max-Wert pro Tag)?",
				);
			}
			else
			{
				$formElements[] = array(
					'type' => "Label",
					'label' => "Sollen die Werte der Wirkarbeit (in Wh/kWh) gelogged werden?",
				);
			}
			$formElements[] = array(
				'type' => "CheckBox",
				'caption' => "Logging von Wh/kWh",
				'name' => "loggingWirkarbeit",
			);
			if (defined('DEVELOPMENT') && DEVELOPMENT)
			{
				$formElements[] = array(
					'type' => "Label",
					'label' => " ",
				);
				$formElements[] = array(
					'type' => "Label",
					'label' => "Soll die Menge der geloggeten Werte, die älter als ein Tag sind auf 1 Wert pro Minute reduziert werden (=geringerer Speicherbedarf des Archivs)?",
				);
				$formElements[] = array(
					'type' => "CheckBox",
					'caption' => "Logging auf 1 Wert pro Minute reduzieren",
					'name' => "reduceLogsize",
				);
			}
			$formActions = array();

			$formStatus = array();
			$formStatus[] = array(
				'code' => 200,
				'icon' => "error",
				'caption' => "IP oder Port sind nicht erreichtbar",
			);
			$formStatus[] = array(
				'code' => 201,
				'icon' => "error",
				'caption' => "Archiv nicht gefunden",
			);
			return json_encode(array('elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus));
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
			$wallbox0name = $this->ReadPropertyString('wallbox0name');
			$wallbox1name = $this->ReadPropertyString('wallbox1name');
			$wallbox2name = $this->ReadPropertyString('wallbox2name');
			$wallbox3name = $this->ReadPropertyString('wallbox3name');
			$wallbox4name = $this->ReadPropertyString('wallbox4name');
			$wallbox5name = $this->ReadPropertyString('wallbox5name');
			$wallbox6name = $this->ReadPropertyString('wallbox6name');
			$wallbox7name = $this->ReadPropertyString('wallbox7name');
			$readPowermeter0 = $this->ReadPropertyBoolean('readPowermeter0');
			$readPowermeter1 = $this->ReadPropertyBoolean('readPowermeter1');
			$readPowermeter2 = $this->ReadPropertyBoolean('readPowermeter2');
			$readPowermeter3 = $this->ReadPropertyBoolean('readPowermeter3');
			$readPowermeter4 = $this->ReadPropertyBoolean('readPowermeter4');
			$readPowermeter5 = $this->ReadPropertyBoolean('readPowermeter5');
			$readPowermeter6 = $this->ReadPropertyBoolean('readPowermeter6');
			$readPowermeter7 = $this->ReadPropertyBoolean('readPowermeter7');
			$powermeter0name = $this->ReadPropertyString('powermeter0name');
			$powermeter1name = $this->ReadPropertyString('powermeter1name');
			$powermeter2name = $this->ReadPropertyString('powermeter2name');
			$powermeter3name = $this->ReadPropertyString('powermeter3name');
			$powermeter4name = $this->ReadPropertyString('powermeter4name');
			$powermeter5name = $this->ReadPropertyString('powermeter5name');
			$powermeter6name = $this->ReadPropertyString('powermeter6name');
			$powermeter7name = $this->ReadPropertyString('powermeter7name');
			$readEmergencyPower = $this->ReadPropertyBoolean('readEmergencyPower');
			$emergencyPowerBuffer = $this->ReadPropertyFloat('emergencyPowerBuffer');
			$readDcString = $this->ReadPropertyBoolean('readDcString');
			$string1name = $this->ReadPropertyString('string1name');
			$string2name = $this->ReadPropertyString('string2name');
			$string3name = $this->ReadPropertyString('string3name');
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

			// Workaround für "InstanceInterface not available" Fehlermeldung beim Server-Start...
			if (KR_READY != IPS_GetKernelRunlevel())
			{
				// --> do nothing
				// Verursacht scheinbar an dieser Stelle Probleme. Manchmal werden die Timer nicht mehr ausgeführt, daher explizit aktivieren...

				if ($loggingPowerKw || $readExtLeistung)
				{
					// Erstellt einen Timer mit einem Intervall von $pollCycle/2 in Millisekunden.
					$this->SetTimerInterval("Update-ValuesKw", $pollCycle / 2);
				}
				else
				{
					// Deaktiviert einen Timer
					$this->SetTimerInterval("Update-ValuesKw", 0);
				}

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-Autarkie-Eigenverbrauch", 5000);

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-EMS-Status", 5000);

				// Erstellt einen Timer mit einem Intervall von 1 Minuten.
				if ($calcWh || $calcKwh)
				{
					$this->SetTimerInterval("Wh-Berechnung", 60 * 1000);
				}

				// Erstellt einen Timer mit einem Intervall von 6 Stunden
				if ($loggingWirkarbeit)
				{
					$this->SetTimerInterval("HistoryCleanUp", 6 * 60 * 60 * 1000);
				}

				if ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7)
				{
					// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
					$this->SetTimerInterval("Update-WallBox_X_CTRL", 5000);
				}
				else
				{
					// deaktiviert einen Timer
					$this->SetTimerInterval("Update-WallBox_X_CTRL", 0);
				}
			}
			// IP-Adresse nicht konfiguriert
			elseif ("" == $hostIp)
			{
				// keine IP --> inaktiv
				$this->SetStatus(IS_INACTIVE);

				$this->SendDebug("Module-Status", "ERROR: ".MODUL_PREFIX." IP not set!", 0);
			}
			// Instanzen nur mit Konfigurierter IP erstellen
			else
			{
				$this->checkProfiles();
				list($gatewayId_Old, $interfaceId_Old) = $this->readOldModbusGateway();
				list($gatewayId, $interfaceId) = $this->checkModbusGateway($hostIp, $hostPort, $hostmodbusDevice, $hostSwapWords);

				$parentId = $this->InstanceID;

				/*
					Quelle: Modbus/TCP-Schnittstelle der E3/DC GmbH (HagerEnergy GmbH)
					07.07.2021 Version: V1.80
				 */

				$categoryId = $parentId;

				$inverterModelRegister_array = array(
					// ********** Identifikationsblock **************************************************************************
					//					array(40001, 1, 3, "Magicbyte", "UInt16", "", "Magicbyte - S10 ModBus ID (Immer 0xE3DC)"),
					//					array(40002, 1, 3, "ModBus-Firmware", "UInt8+UInt8", "", "S10 ModBus-Firmware-Version"),
					array(40003, 1, 3, "Register", "UInt16", "", "Anzahl unterstützter Register"),
					array(40004, 16, 3, "Hersteller", "String", "", "Hersteller: 'E3/DC GmbH'"),
					array(40020, 16, 3, "Modell", "String", "", "Modell, z. B.: 'S10 E AIO'"),
					array(40036, 16, 3, "Seriennummer", "String", "", "Seriennummer, z. B.: 'S10-12345678912'"),
					array(40052, 16, 3, "Firmware", "String", "", "S10 Firmware Release, z. B.: 'S10-2015_08'"),
				);
				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);


				$inverterModelRegister_array = array(
					// ********** Leistungsdaten ************************************************************************
					array(40068, 2, 3, "PV-Leistung", "Int32", "W", "Photovoltaik-Leistung in Watt"),
					array(40070, 2, 3, "Batterie-Leistung", "Int32", "W", "Batterie-Leistung in Watt (negative Werte = Entladung)"),
					array(40072, 2, 3, "Verbrauchs-Leistung", "Int32", "W", "Hausverbrauchs-Leistung in Watt"),
					array(40074, 2, 3, "Netz-Leistung", "Int32", "W", "Leistung am Netzübergabepunkt in Watt (negative Werte = Einspeisung)"),
				);
				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				// Logging setzen
				foreach ($inverterModelRegister_array as $inverterModelRegister)
				{
					$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					if (false !== $varId && false !== $archiveId)
					{
						AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW || $calcWh || $calcKwh);
					}
				}

				// Variablen für kW-Logging erstellen, sofern nötig
				foreach ($inverterModelRegister_array as $inverterModelRegister)
				{
					$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);

					$varId = $this->MaintainInstanceVariable("Value_kW", IPS_GetName($varId)."_kW", VARIABLETYPE_FLOAT, "~Power", 0, $loggingPowerKw, $instanceId, $inverterModelRegister[IMR_NAME]." in kW");
					if (false !== $varId && false !== $archiveId)
					{
						AC_SetLoggingStatus($archiveId, $varId, $loggingPowerKw);
					}
				}

				if ($loggingPowerKw || $readExtLeistung)
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
				foreach ($inverterModelRegister_array as $inverterModelRegister)
				{
					$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					if (false !== $varId && false !== $archiveId)
					{
						AC_SetLoggingStatus($archiveId, $varId, $loggingBatterySoc);
					}
				}

				$inverterModelRegister_array = array(
					array(40084, 1, 3, "Emergency-Power", "Uint16", "enumerated_emergency-power", "Emergency-Power Status:
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
					array(40137, 1, 3, "SG Ready-Status", "Uint16", "enumerated_sg-ready-status", "SG Ready-Status:
- Betriebszustand 1 (Sperrbetrieb):Dieser Betriebszustand ist abwärtskompatibel zur häufig zufesten Uhrzeiten geschalteten EVU-Sperre und umfasstmaximal 2 Stunden „harte“ Sperrzeit.
- Betriebszustand 2 (Normalbetrieb):In dieser Schaltung läuft die Wärmepumpe imenergieeffizienten Normalbetrieb mit anteiligerWärmespeicher-Füllung für die maximal zweistündige EVU-Sperre.
- Betriebszustand 3 (PV-Überschussbetrieb): In diesem Betriebszustand läuft die Wärmepumpe innerhalb des Reglers im verstärkten Betrieb für Raumheizung und Warmwasserbereitung. Es handelt sich dabei nicht um einen definitiven Anlaufbefehl, sondern um eine Einschaltempfehlung entsprechend der heutigen Anhebung.
- Betriebszustand 4 (Betrieb für Abregelung): Hierbei handelt es sich um einen definitiven Anlaufbefehl, insofern dieser im Rahmen der Regeleinstellungen möglich ist."),
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
					array('varName' => "Wetterbasiertes Laden", 'varProfile' => "~Switch", 'varInfo' => "Bit 3: Wetterbasiertes Laden: 1 = Es wird Ladekapazität zurückgehalten, damit der erwartete Sonnenschein maximal ausgenutzt werden kann. Dies ist nötig, wenn die maximale Einspeisung begrenzt ist.;        0 = Es wird keine Ladekapazität zurückgehalten    R"),
					array('varName' => "Abregelungs-Status", 'varProfile' => "~Alert", 'varInfo' => "Bit 4: Abregelungs-Status: 1 = Die Ausgangsleistung des S10 Hauskraftwerks wird abgeregelt, da die maximale Einspeisung erreicht ist;    0 = Dieser Fall ist nicht eingetreten    R"),
					array('varName' => "Ladesperrzeit", 'varProfile' => "~Switch", 'varInfo' => "Bit 5: 1 = Ladesperrzeit aktiv: Den Zeitraum für die Ladesperrzeit geben Sie in der Funktion SmartCharge ein.;    0 = keine Ladesperrzeit    R"),
					array('varName' => "Entladesperrzeit", 'varProfile' => "~Switch", 'varInfo' => "Bit 6: 1 = Entladesperrzeit aktiv: Den Zeitraum für die Entladesperrzeit geben Sie in der Funktion SmartCharge ein.;    0 = keine Entladesperrzeit    R"),
				);

				foreach ($bitArray as $bit)
				{
					$varId = $this->MaintainInstanceVariable($this->removeInvalidChars($bit['varName']), $bit['varName'], VARIABLETYPE_BOOLEAN, $bit['varProfile'], 0, true, $instanceId, $bit['varInfo']);
				}

				// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
				$this->SetTimerInterval("Update-EMS-Status", 5000);



				$inverterModelRegister_array = array(
					array(40076, 2, 3, "Ext-Leistung", "Int32", "W", "Leistung aller zusätzlichen Einspeiser in Watt"),
				);

				if ($readExtLeistung)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
					// Logging setzen
					foreach ($inverterModelRegister_array as $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
						if (false !== $varId && false !== $archiveId)
						{
							AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW);
						}
					}

					// Variablen für kW-Logging erstellen, sofern nötig
					foreach ($inverterModelRegister_array as $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varIdOrg = IPS_GetObjectIDByIdent("Value", $instanceId);

						$varId = $this->MaintainInstanceVariable("Value_kW", IPS_GetName($varIdOrg)."_kW", VARIABLETYPE_FLOAT, "~Power", 0, $loggingPowerKw, $instanceId, $inverterModelRegister[IMR_NAME]." in kW");
						if (false !== $varId && false !== $archiveId)
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
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingPowerW);
				}

				$varId = $this->myMaintainVariable("GesamtproduktionLeistung_kW", "Gesamtproduktion-Leistung_kW", VARIABLETYPE_FLOAT, "~Power", 0, $readExtLeistung && $loggingPowerKw);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingPowerKw);
				}

				// Wirkarbeit in Wh berechnen
				$varId = $this->myMaintainVariable("BatteryChargingWh", "Batterie-Lade-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("BatteryDischargingWh", "Batterie-Entlade-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("ExtWh", "Ext-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && $readExtLeistung);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GesamtproduktionWh", "Gesamtproduktion-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && $readExtLeistung);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridConsumptionWh", "Netz-Bezug-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridFeedWh", "Netz-Einspeisung-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("PvWh", "PV-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("HomeWh", "Verbrauchs-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxWh", "Wallbox-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxSolarWh", "Wallbox-Solar-Wirkarbeit", VARIABLETYPE_INTEGER, MODUL_PREFIX.".Electricity.Int", 0, $calcWh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}

				// Wirkarbeit in kWh berechnen
				$varId = $this->myMaintainVariable("BatteryChargingKwh", "Batterie-Lade-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("BatteryDischargingKwh", "Batterie-Entlade-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("ExtKwh", "Ext-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && $readExtLeistung);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GesamtproduktionKwh", "Gesamtproduktion-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && $readExtLeistung);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridConsumptionKwh", "Netz-Bezug-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("GridFeedKwh", "Netz-Einspeisung-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("PvKwh", "PV-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("HomeKwh", "Verbrauchs-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh);
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxKwh", "Wallbox-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}
				$varId = $this->myMaintainVariable("WallboxSolarKwh", "Wallbox-Solar-Wirkarbeit_kWh", VARIABLETYPE_FLOAT, "~Electricity", 0, $calcKwh && ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7));
				if (false !== $varId && false !== $archiveId)
				{
					AC_SetLoggingStatus($archiveId, $varId, $loggingWirkarbeit);
				}

				// Erstellt einen Timer mit einem Intervall von 1 Minuten.
				if ($calcWh || $calcKwh)
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

				if ($readWallbox0 || $readWallbox1 || $readWallbox2 || $readWallbox3 || $readWallbox4 || $readWallbox5 || $readWallbox6 || $readWallbox7)
				{
					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					// Logging setzen
					foreach ($inverterModelRegister_array as $inverterModelRegister)
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
					foreach ($inverterModelRegister_array as $inverterModelRegister)
					{
						$instanceId = IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);
						$varIdOrg = IPS_GetObjectIDByIdent("Value", $instanceId);

						$varId = $this->MaintainInstanceVariable("Value_kW", IPS_GetName($varIdOrg)."_kW", VARIABLETYPE_FLOAT, "~Power", 0, $loggingPowerKw, $instanceId, $inverterModelRegister[IMR_NAME]." in kW");
						if (false !== $varId && false !== $archiveId)
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
					array('varName' => "Schukosteckdose", 'varProfile' => "~Switch", 'varInfo' => "Bit 6: Schukosteckdose an (1), Gilt nicht für die Wallbox easy connect!  RW"),
					array('varName' => "Schukostecker gesteckt", 'varProfile' => "~Switch", 'varInfo' => "Bit 7: Schukostecker gesteckt (1), Gilt nicht für die Wallbox easy connect!  R"),
					array('varName' => "Schukostecker verriegelt", 'varProfile' => "~Lock", 'varInfo' => "Bit 8: ukostecker verriegelt (1), Gilt nicht für die Wallbox easy connect!    R"),
					array('varName' => "16A 1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 9: Relais an, 16A 1 Phase, Schukosteckdose, Gilt nicht für die Wallbox easy connect! R"),
					array('varName' => "16A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 10: Relais an, 16A 3 Phasen, Typ 2  R"),
					array('varName' => "32A 3 Phasen", 'varProfile' => "~Switch", 'varInfo' => "Bit 11: Relais an, 32A 3 Phasen, Typ 2  R"),
					array('varName' => "1 Phase", 'varProfile' => "~Switch", 'varInfo' => "Bit 12: Eine Phase aktiv (1) drei Phasen aktiv (0)  RW"),
					//					array('varName' => "", 'varProfile' => "", 'varInfo' => "Bit 13: Nicht belegt"),
				);

				$inverterModelRegister_array = array();
				$inverterModelRegisterDel_array = array();

				if ($readWallbox0)
				{
					$inverterModelRegister_array[] = array(40088, 1, 6, "WallBox_0_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40088, 1, 6, "WallBox_0_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox1)
				{
					$inverterModelRegister_array[] = array(40089, 1, 6, "WallBox_1_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40089, 1, 6, "WallBox_1_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox2)
				{
					$inverterModelRegister_array[] = array(40090, 1, 6, "WallBox_2_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40090, 1, 6, "WallBox_2_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox3)
				{
					$inverterModelRegister_array[] = array(40091, 1, 6, "WallBox_3_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40091, 1, 6, "WallBox_3_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox4)
				{
					$inverterModelRegister_array[] = array(40092, 1, 6, "WallBox_4_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40092, 1, 6, "WallBox_4_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox5)
				{
					$inverterModelRegister_array[] = array(40093, 1, 6, "WallBox_5_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40093, 1, 6, "WallBox_5_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox6)
				{
					$inverterModelRegister_array[] = array(40094, 1, 6, "WallBox_6_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40094, 1, 6, "WallBox_6_CTRL", "Uint16", "", $wallboxDescription);
				}

				if ($readWallbox7)
				{
					$inverterModelRegister_array[] = array(40095, 1, 6, "WallBox_7_CTRL", "Uint16", "", $wallboxDescription);
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40095, 1, 6, "WallBox_7_CTRL", "Uint16", "", $wallboxDescription);
				}

				// Variablen umbenennen, sofern ein Wallbox Name angegeben wurde
				for ($i = 0; $i < count($inverterModelRegister_array); $i++)
				{
					$inverterModelRegister_array[$i][IMR_NAME] = str_replace(array("WallBox_0_", "WallBox_1_", "WallBox_2_", "WallBox_3_", "WallBox_4_", "WallBox_5_", "WallBox_6_", "WallBox_7_"), array("WallBox_".$wallbox0name."_", "WallBox_".$wallbox1name."_", "WallBox_".$wallbox2name."_", "WallBox_".$wallbox3name."_", "WallBox_".$wallbox4name."_", "WallBox_".$wallbox5name."_", "WallBox_".$wallbox6name."_", "WallBox_".$wallbox7name."_"), $inverterModelRegister_array[$i][IMR_NAME]);
				}

				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

				// Entsprechend Wallbox Namen umbenennen, sofern Name nach initialer Erstellung geändert wurde
				foreach ($inverterModelRegister_array as $inverterModelRegister)
				{
					$instanceId = @IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);

					// Modbus-Instanz erstellen, sofern noch nicht vorhanden
					if (false !== $instanceId && IPS_GetName($instanceId) != $inverterModelRegister[IMR_NAME])
					{
						IPS_SetName($instanceId, $inverterModelRegister[IMR_NAME]);
						$this->SendDebug("create Modbus address", "REG_".$inverterModelRegister[IMR_START_REGISTER]." umbenannt in ".$inverterModelRegister[IMR_NAME], 0);
					}
				}

				foreach ($inverterModelRegister_array as $register)
				{
					// Bit 0 - 12 für "WallBox_X_CTRL" erstellen
					$instanceId = IPS_GetObjectIDByIdent($register[IMR_START_REGISTER], $categoryId);
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
					IPS_SetHidden($varId, true);

					foreach ($bitArray as $bit)
					{
						$varId = $this->MaintainInstanceVariable($this->removeInvalidChars($bit['varName']), $bit['varName'], VARIABLETYPE_BOOLEAN, $bit['varProfile'], 0, true, $instanceId, $bit['varInfo']);
					}
				}

				$this->deleteModbusInstancesRecursive($inverterModelRegisterDel_array, $categoryId);



				/* ********** Spezifische Abfragen der Leistungsmesser **************************************
					Hinweis: Die im Folgenden gelisteten Leistungsmesser (Register 40105 bis 40132) werden im Kapitel „Typen von Leistungsmessern“
				 ************************************************************************************************** */
				if ($readPowermeter0 || $readPowermeter1 || $readPowermeter2 || $readPowermeter3 || $readPowermeter4 || $readPowermeter5 || $readPowermeter6 || $readPowermeter7)
				{
					// Erstellt einen Timer mit einem Intervall von 5 Sekunden.
//					$this->SetTimerInterval("Update-Powermeter", 5000);
				}
				else
				{
					// deaktiviert einen Timer
//					$this->SetTimerInterval("Update-Powermeter", 0);
				}

				$inverterModelRegister_array = array();
				$inverterModelRegisterDel_array = array();

				$powermeterTypBeschreibung = "Leistungsmessertyp: Typ Bezeichnung Hinweise
1 Wurzelleistungsmesser Dies ist der Regelpunkt des Systems. Der Regelpunkt entspricht üblicherweise dem Hausanschlusspunkt.
2 Externe Produktion
3 Zweirichtungszähler
4 Externer Verbrauch
5 Farm
6 Wird nicht verwendet
7 Wallbox
8 Externer Leistungsmesser Farm
9 Datenanzeige Wird nicht in die Regelung eingebunden, sondern dient nur der Datenaufzeichnung des Kundenportals.
10 Regelungsbypass Die gemessene Leistung wird nicht in die Batterie geladen, aus der Batterie entladen.";

				if ($readPowermeter0)
				{
					$inverterModelRegister_array[] = array(40105, 1, 3, "Powermeter_0", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40106, 1, 3, "Powermeter_0_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40107, 1, 3, "Powermeter_0_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40108, 1, 3, "Powermeter_0_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40105, 1, 3, "Powermeter_0", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40106, 1, 3, "Powermeter_0_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40107, 1, 3, "Powermeter_0_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40108, 1, 3, "Powermeter_0_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter1)
				{
					$inverterModelRegister_array[] = array(40109, 1, 3, "Powermeter_1", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40110, 1, 3, "Powermeter_1_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40111, 1, 3, "Powermeter_1_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40112, 1, 3, "Powermeter_1_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40109, 1, 3, "Powermeter_1", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40110, 1, 3, "Powermeter_1_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40111, 1, 3, "Powermeter_1_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40112, 1, 3, "Powermeter_1_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter2)
				{
					$inverterModelRegister_array[] = array(40113, 1, 3, "Powermeter_2", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40114, 1, 3, "Powermeter_2_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40115, 1, 3, "Powermeter_2_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40116, 1, 3, "Powermeter_2_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40113, 1, 3, "Powermeter_2", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40114, 1, 3, "Powermeter_2_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40115, 1, 3, "Powermeter_2_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40116, 1, 3, "Powermeter_2_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter3)
				{
					$inverterModelRegister_array[] = array(40117, 1, 3, "Powermeter_3", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40118, 1, 3, "Powermeter_3_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40119, 1, 3, "Powermeter_3_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40120, 1, 3, "Powermeter_3_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40117, 1, 3, "Powermeter_3", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40118, 1, 3, "Powermeter_3_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40119, 1, 3, "Powermeter_3_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40120, 1, 3, "Powermeter_3_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter4)
				{
					$inverterModelRegister_array[] = array(40121, 1, 3, "Powermeter_4", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40122, 1, 3, "Powermeter_4_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40123, 1, 3, "Powermeter_4_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40124, 1, 3, "Powermeter_4_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40121, 1, 3, "Powermeter_4", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40122, 1, 3, "Powermeter_4_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40123, 1, 3, "Powermeter_4_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40124, 1, 3, "Powermeter_4_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter5)
				{
					$inverterModelRegister_array[] = array(40125, 1, 3, "Powermeter_5", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40126, 1, 3, "Powermeter_5_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40127, 1, 3, "Powermeter_5_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40128, 1, 3, "Powermeter_5_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40125, 1, 3, "Powermeter_5", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40126, 1, 3, "Powermeter_5_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40127, 1, 3, "Powermeter_5_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40128, 1, 3, "Powermeter_5_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter6)
				{
					$inverterModelRegister_array[] = array(40129, 1, 3, "Powermeter_6", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40130, 1, 3, "Powermeter_6_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40131, 1, 3, "Powermeter_6_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40132, 1, 3, "Powermeter_6_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40129, 1, 3, "Powermeter_6", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40130, 1, 3, "Powermeter_6_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40131, 1, 3, "Powermeter_6_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40132, 1, 3, "Powermeter_6_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				if ($readPowermeter7)
				{
					$inverterModelRegister_array[] = array(40133, 1, 3, "Powermeter_7", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegister_array[] = array(40134, 1, 3, "Powermeter_7_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegister_array[] = array(40135, 1, 3, "Powermeter_7_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegister_array[] = array(40136, 1, 3, "Powermeter_7_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}
				else
				{
					$inverterModelRegisterDel_array[] = array(40133, 1, 3, "Powermeter_7", "Uint16", "enumerated_powermeter", $powermeterTypBeschreibung);
					$inverterModelRegisterDel_array[] = array(40134, 1, 3, "Powermeter_7_L1", "Int16", "W", "Phasenleistung in Watt L1");
					$inverterModelRegisterDel_array[] = array(40135, 1, 3, "Powermeter_7_L2", "Int16", "W", "Phasenleistung in Watt L2");
					$inverterModelRegisterDel_array[] = array(40136, 1, 3, "Powermeter_7_L3", "Int16", "W", "Phasenleistung in Watt L3");
				}

				// Variablen umbenennen, sofern ein Powermeter/Leistungsmesser Name angegeben wurde
				for ($i = 0; $i < count($inverterModelRegister_array); $i++)
				{
					$inverterModelRegister_array[$i][IMR_NAME] = str_replace(array("Powermeter_0", "Powermeter_1", "Powermeter_2", "Powermeter_3", "Powermeter_4", "Powermeter_5", "Powermeter_6", "Powermeter_7"), array("Powermeter_".$powermeter0name, "Powermeter_".$powermeter1name, "Powermeter_".$powermeter2name, "Powermeter_".$powermeter3name, "Powermeter_".$powermeter4name, "Powermeter_".$powermeter5name, "Powermeter_".$powermeter6name, "Powermeter_".$powermeter7name), $inverterModelRegister_array[$i][IMR_NAME]);
				}

				$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);
				$this->deleteModbusInstancesRecursive($inverterModelRegisterDel_array, $categoryId);

				// Entsprechend Powermeter/Leistungsmesser Namen umbenennen, sofern Name nach initialer Erstellung geändert wurde
				foreach ($inverterModelRegister_array as $inverterModelRegister)
				{
					$instanceId = @IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);

					// Modbus-Instanz erstellen, sofern noch nicht vorhanden
					if (false !== $instanceId && IPS_GetName($instanceId) != $inverterModelRegister[IMR_NAME])
					{
						IPS_SetName($instanceId, $inverterModelRegister[IMR_NAME]);
						$this->SendDebug("create Modbus address", "REG_".$inverterModelRegister[IMR_START_REGISTER]." umbenannt in ".$inverterModelRegister[IMR_NAME], 0);
					}
				}


				/* ********** DC-String **************************************************************************
					Hinweis: Die folgenden Register 40096 bis 40104 koennen ab dem Release S10_2017_02 genutzt werden!
				 */
				$categoryName = "DC_String";
				$categoryId = @IPS_GetObjectIDByIdent($this->removeInvalidChars($categoryName), $parentId);
				if ($readDcString)
				{
					$inverterModelRegister_array = array(
						array(40096, 1, 3, "DC_STRING_1_Voltage", "UInt16", "V", "DC_STRING_1_Voltage"),
						array(40097, 1, 3, "DC_STRING_2_Voltage", "UInt16", "V", "DC_STRING_2_Voltage"),
						array(40098, 1, 3, "DC_STRING_3_Voltage", "UInt16", "V", "DC_STRING_3_Voltage"),
						array(40099, 1, 3, "DC_STRING_1_Current", "UInt16", "A", "DC_STRING_1_Current", 0.01),
						array(40100, 1, 3, "DC_STRING_2_Current", "UInt16", "A", "DC_STRING_2_Current", 0.01),
						array(40101, 1, 3, "DC_STRING_3_Current", "UInt16", "A", "DC_STRING_3_Current", 0.01),
						array(40102, 1, 3, "DC_STRING_1_Power", "UInt16", "W", "DC_STRING_1_Power"),
						array(40103, 1, 3, "DC_STRING_2_Power", "UInt16", "W", "DC_STRING_2_Power"),
						array(40104, 1, 3, "DC_STRING_3_Power", "UInt16", "W", "DC_STRING_3_Power"),
					);

					// Variablen umbenennen, sofern ein DC-String Name angegeben wurde
					for ($i = 0; $i < count($inverterModelRegister_array); $i++)
					{
						$inverterModelRegister_array[$i][IMR_NAME] = str_replace(array("STRING_1_", "STRING_2_", "STRING_3_"), array("STRING_".$string1name."_", "STRING_".$string2name."_", "STRING_".$string3name."_"), $inverterModelRegister_array[$i][IMR_NAME]);
					}

					if (false === $categoryId)
					{
						$categoryId = IPS_CreateCategory();
						IPS_SetIdent($categoryId, $this->removeInvalidChars($categoryName));
						IPS_SetName($categoryId, $categoryName);
						IPS_SetParent($categoryId, $parentId);
						IPS_SetInfo($categoryId, "Hinweis: Die folgenden Register 40096 bis 40104 können ab dem Release S10_2017_02 genutzt werden!");
					}

					$this->createModbusInstances($inverterModelRegister_array, $categoryId, $gatewayId, $pollCycle);

					// Entsprechend String Namen umbenennen, sofern Name nach initialer Erstellung geändert wurde
					foreach ($inverterModelRegister_array as $inverterModelRegister)
					{
						$instanceId = @IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER], $categoryId);

						// Modbus-Instanz erstellen, sofern noch nicht vorhanden
						if (false !== $instanceId && IPS_GetName($instanceId) != $inverterModelRegister[IMR_NAME])
						{
							IPS_SetName($instanceId, $inverterModelRegister[IMR_NAME]);
							$this->SendDebug("create Modbus address", "REG_".$inverterModelRegister[IMR_START_REGISTER]." umbenannt in ".$inverterModelRegister[IMR_NAME], 0);
						}
					}
				}
				else
				{
					if (false !== $categoryId)
					{
						foreach (IPS_GetChildrenIDs($categoryId) as $childId)
						{
							$this->deleteInstanceRecursive($childId);
						}
						IPS_DeleteCategory($categoryId);
					}
				}


				if ($active)
				{
					// Erreichbarkeit von IP und Port pruefen
					$portOpen = false;
					$waitTimeoutInSeconds = 1;
					if (/*Sys_Ping($hostIp, $waitTimeoutInSeconds*1000)*/ $fp = @fsockopen($hostIp, $hostPort, $errCode, $errStr, $waitTimeoutInSeconds))
					{
						// It worked
						$portOpen = true;

						// Client Socket aktivieren
						if (false == IPS_GetProperty($interfaceId, "Open"))
						{
							IPS_SetProperty($interfaceId, "Open", true);
							IPS_ApplyChanges($interfaceId);
							//IPS_Sleep(100);

							$this->SendDebug("ClientSocket-Status", "ClientSocket activated (".$interfaceId.")", 0);
						}

						// aktiv
						$this->SetStatus(IS_ACTIVE);

						$this->SendDebug("Module-Status", MODUL_PREFIX."-module activated", 0);
					}
					else
					{
						// IP oder Port nicht erreichbar
						$this->SetStatus(200);

						$this->SendDebug("Module-Status", "ERROR: ".MODUL_PREFIX." with IP=".$hostIp." and Port=".$hostPort." cannot be reached!", 0);
					}

					// Close fsockopen
					if (isset($fp) && false !== $fp)
					{
						fclose($fp); // nötig für fsockopen!
					}
				}
				else
				{
					// Client Soket deaktivieren
					if (true == IPS_GetProperty($interfaceId, "Open"))
					{
						IPS_SetProperty($interfaceId, "Open", false);
						IPS_ApplyChanges($interfaceId);
						//IPS_Sleep(100);

						$this->SendDebug("ClientSocket-Status", "ClientSocket deactivated (".$interfaceId.")", 0);
					}

					// Timer deaktivieren
					/*
										$this->SetTimerInterval("Update-Autarkie-Eigenverbrauch", 0);
										$this->SetTimerInterval("Update-EMS-Status", 0);
										$this->SetTimerInterval("Update-WallBox_X_CTRL", 0);
										$this->SetTimerInterval("Update-ValuesKw", 0);
										$this->SetTimerInterval("Wh-Berechnung", 0);
										$this->SetTimerInterval("HistoryCleanUp", 0);
					 */
					// inaktiv
					$this->SetStatus(IS_INACTIVE);

					$this->SendDebug("Module-Status", MODUL_PREFIX."-module deactivated", 0);
				}


				// pruefen, ob sich ModBus-Gateway geaendert hat
				if (0 != $gatewayId_Old && $gatewayId != $gatewayId_Old)
				{
					$this->deleteInstanceNotInUse($gatewayId_Old, MODBUS_ADDRESSES);

					$this->SendDebug("ModbusGateway-Status", "ModbusGateway deleted (".$gatewayId_Old.")", 0);
				}

				// pruefen, ob sich ClientSocket Interface geaendert hat
				if (0 != $interfaceId_Old && $interfaceId != $interfaceId_Old)
				{
					$this->deleteInstanceNotInUse($interfaceId_Old, MODBUS_INSTANCES);

					$this->SendDebug("ClientSocket-Status", "ClientSocket deleted (".$interfaceId_Old.")", 0);
				}
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
		private function createModbusInstances($modelRegister_array, $parentId, $gatewayId, $pollCycle, $uniqueIdent = "")
		{
			// Workaround für "InstanceInterface not available" Fehlermeldung beim Server-Start...
			if (KR_READY == IPS_GetKernelRunlevel())
			{
				// Erstelle Modbus Instancen
				foreach ($modelRegister_array as $inverterModelRegister)
				{
					// get datatype
					$datenTyp = $this->getModbusDatatype($inverterModelRegister[IMR_TYPE]);
					if ("continue" == $datenTyp)
					{
						continue;
					}

					// if scale factor is given, variable will be of type float
					if (isset($inverterModelRegister[IMR_SF]) && 10000 >= $inverterModelRegister[IMR_SF])
					{
						$varDataType = MODBUSDATATYPE_REAL;
					}
					else
					{
						$varDataType = $datenTyp;
					}

					// get profile
					if (isset($inverterModelRegister[IMR_UNITS]))
					{
						$profile = $this->getProfile($inverterModelRegister[IMR_UNITS], $varDataType);
					}
					else
					{
						$profile = false;
					}

					$instanceId = @IPS_GetObjectIDByIdent($inverterModelRegister[IMR_START_REGISTER].$uniqueIdent, $parentId);
					$initialCreation = false;

					// Modbus-Instanz erstellen, sofern noch nicht vorhanden
					if (false === $instanceId)
					{
						$this->SendDebug("create Modbus address", "REG_".$inverterModelRegister[IMR_START_REGISTER]." - ".$inverterModelRegister[IMR_NAME]." (modbusDataType=".$datenTyp.", varDataType=".$varDataType.", profile=".$profile.")", 0);

						$instanceId = IPS_CreateInstance(MODBUS_ADDRESSES);

						IPS_SetParent($instanceId, $parentId);
						IPS_SetIdent($instanceId, $inverterModelRegister[IMR_START_REGISTER].$uniqueIdent);
						IPS_SetName($instanceId, $inverterModelRegister[IMR_NAME]);
						IPS_SetInfo($instanceId, $inverterModelRegister[IMR_DESCRIPTION]);

						$initialCreation = true;
					}

					// Gateway setzen
					if (IPS_GetInstance($instanceId)['ConnectionID'] != $gatewayId)
					{
						$this->SendDebug("set Modbus Gateway", "REG_".$inverterModelRegister[IMR_START_REGISTER]." - ".$inverterModelRegister[IMR_NAME]." --> GatewayID ".$gatewayId, 0);

						// sofern bereits eine Gateway verbunden ist, dieses trennen
						if (0 != IPS_GetInstance($instanceId)['ConnectionID'])
						{
							IPS_DisconnectInstance($instanceId);
						}

						// neues Gateway verbinden
						IPS_ConnectInstance($instanceId, $gatewayId);
					}


					// ************************
					// config Modbus-Instance
					// ************************
					// set data type
					if ($datenTyp != IPS_GetProperty($instanceId, "DataType"))
					{
						IPS_SetProperty($instanceId, "DataType", $datenTyp);
					}
					// set emulation state
					if (false != IPS_GetProperty($instanceId, "EmulateStatus"))
					{
						IPS_SetProperty($instanceId, "EmulateStatus", false);
					}
					// set poll cycle
					if ($pollCycle != IPS_GetProperty($instanceId, "Poller"))
					{
						IPS_SetProperty($instanceId, "Poller", $pollCycle);
					}
					// set length for modbus datatype string
					if (MODBUSDATATYPE_STRING == $datenTyp && $inverterModelRegister[IMR_SIZE] != IPS_GetProperty($instanceId, "Length"))
					{ // if string --> set length accordingly
						IPS_SetProperty($instanceId, "Length", $inverterModelRegister[IMR_SIZE]);
					}
					// set scale factor
					if (isset($inverterModelRegister[IMR_SF]) && 10000 >= $inverterModelRegister[IMR_SF] && $inverterModelRegister[IMR_SF] != IPS_GetProperty($instanceId, "Factor"))
					{
						IPS_SetProperty($instanceId, "Factor", $inverterModelRegister[IMR_SF]);
					}

					// Read-Settings
					if ($inverterModelRegister[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET != IPS_GetProperty($instanceId, "ReadAddress"))
					{
						IPS_SetProperty($instanceId, "ReadAddress", $inverterModelRegister[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET);
					}
					if (6 == $inverterModelRegister[IMR_FUNCTION_CODE])
					{
						$ReadFunctionCode = 3;
					}
					elseif ("R" == $inverterModelRegister[IMR_FUNCTION_CODE])
					{
						$ReadFunctionCode = 3;
					}
					elseif ("RW" == $inverterModelRegister[IMR_FUNCTION_CODE])
					{
						$ReadFunctionCode = 3;
					}
					else
					{
						$ReadFunctionCode = $inverterModelRegister[IMR_FUNCTION_CODE];
					}

					if ($ReadFunctionCode != IPS_GetProperty($instanceId, "ReadFunctionCode"))
					{
						IPS_SetProperty($instanceId, "ReadFunctionCode", $ReadFunctionCode);
					}

					// Write-Settings
					if (4 < $inverterModelRegister[IMR_FUNCTION_CODE] && $inverterModelRegister[IMR_FUNCTION_CODE] != IPS_GetProperty($instanceId, "WriteFunctionCode"))
					{
						IPS_SetProperty($instanceId, "WriteFunctionCode", $inverterModelRegister[IMR_FUNCTION_CODE]);
					}

					if (4 < $inverterModelRegister[IMR_FUNCTION_CODE] && $inverterModelRegister[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET != IPS_GetProperty($instanceId, "WriteAddress"))
					{
						IPS_SetProperty($instanceId, "WriteAddress", $inverterModelRegister[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET);
					}

					if (0 != IPS_GetProperty($instanceId, "WriteFunctionCode"))
					{
						IPS_SetProperty($instanceId, "WriteFunctionCode", 0);
					}

					if (IPS_HasChanges($instanceId))
					{
						IPS_ApplyChanges($instanceId);
					}

					// Statusvariable der Modbus-Instanz ermitteln
					$varId = IPS_GetObjectIDByIdent("Value", $instanceId);

					// Profil der Statusvariable initial einmal zuweisen
					if (false != $profile && !IPS_VariableProfileExists($profile))
					{
						$this->SendDebug("Variable-Profile", "Profile ".$profile." does not exist!", 0);
					}
					elseif ($initialCreation && false != $profile)
					{
						// Justification Rule 11: es ist die Funktion RegisterVariable...() in diesem Fall nicht nutzbar, da die Variable durch die Modbus-Instanz bereits erstellt wurde
						// --> Custo Profil wird initial einmal beim Instanz-erstellen gesetzt
						if (!IPS_SetVariableCustomProfile($varId, $profile))
						{
							$this->SendDebug("Variable-Profile", "Error setting profile ".$profile." for VarID ".$varId."!", 0);
						}
					}
				}
			}
		}

		private function getModbusDatatype($type)
		{
			// Datentyp ermitteln
			// 0=Bit (1 bit)
			// 1=Byte (8 bit unsigned)
			if ("uint8" == strtolower($type)
				|| "enum8" == strtolower($type)
				|| "int8" == strtolower($type)
			) {
				$datenTyp = MODBUSDATATYPE_BIT;
			}
			// 2=Word (16 bit unsigned)
			elseif ("uint16" == strtolower($type)
				|| "enum16" == strtolower($type)
				|| "uint8+uint8" == strtolower($type)
			) {
				$datenTyp = MODBUSDATATYPE_WORD;
			}
			// 3=DWord (32 bit unsigned)
			elseif ("uint32" == strtolower($type)
				|| "acc32" == strtolower($type)
				|| "acc64" == strtolower($type)
			) {
				$datenTyp = MODBUSDATATYPE_DWORD;
			}
			// 4=Char / ShortInt (8 bit signed)
			elseif ("sunssf" == strtolower($type))
			{
				$datenTyp = MODBUSDATATYPE_CHAR;
			}
			// 5=Short / SmallInt (16 bit signed)
			elseif ("int16" == strtolower($type))
			{
				$datenTyp = MODBUSDATATYPE_SHORT;
			}
			// 6=Integer (32 bit signed)
			elseif ("int32" == strtolower($type))
			{
				$datenTyp = MODBUSDATATYPE_INT;
			}
			// 7=Real (32 bit signed)
			elseif ("float32" == strtolower($type))
			{
				$datenTyp = MODBUSDATATYPE_REAL;
			}
			// 8=Int64
			elseif ("uint64" == strtolower($type))
			{
				$datenTyp = MODBUSDATATYPE_INT64;
			}
			/* 9=Real64 (32 bit signed)
			elseif ("???" == strtolower($type))
			{
				$datenTyp = MODBUSDATATYPE_REAL64;
			}*/
			// 10=String
			elseif ("string32" == strtolower($type)
				|| "string16" == strtolower($type)
				|| "string8" == strtolower($type)
				|| "string" == strtolower($type)
			) {
				$datenTyp = MODBUSDATATYPE_STRING;
			}
			else
			{
				$this->SendDebug("getModbusDatatype()", "Unbekannter Datentyp '".$type."'! --> skip", 0);

				return "continue";
			}

			return $datenTyp;
		}

		private function getProfile($unit, $datenTyp = -1)
		{
			// Profil ermitteln
			if ("a" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = "~Ampere";
			}
			elseif ("a" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Ampere.Int";
			}
			elseif ("ma" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".MilliAmpere.Int";
			}
			elseif (("ah" == strtolower($unit)
					|| "vah" == strtolower($unit))
				&& MODBUSDATATYPE_REAL == $datenTyp
			) {
				$profile = MODUL_PREFIX.".AmpereHour.Float";
			}
			elseif ("ah" == strtolower($unit)
				|| "vah" == strtolower($unit)
			) {
				$profile = MODUL_PREFIX.".AmpereHour.Int";
			}
			elseif ("v" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = "~Volt";
			}
			elseif ("v" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Volt.Int";
			}
			elseif ("w" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = "~Watt.14490";
			}
			elseif ("w" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Watt.Int";
			}
			elseif ("h" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Hours.Int";
			}
			elseif ("hz" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = "~Hertz";
			}
			elseif ("hz" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Hertz.Int";
			}
			elseif ("l/min" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Volumenstrom.Int";
			}
			// Voltampere fuer elektrische Scheinleistung
			elseif ("va" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = MODUL_PREFIX.".Scheinleistung.Float";
			}
			// Voltampere fuer elektrische Scheinleistung
			elseif ("va" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Scheinleistung.Int";
			}
			// Var fuer elektrische Blindleistung
			elseif ("var" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = MODUL_PREFIX.".Blindleistung.Float";
			}
			// Var fuer elektrische Blindleistung
			elseif ("var" == strtolower($unit) || "var" == $unit)
			{
				$profile = MODUL_PREFIX.".Blindleistung.Int";
			}
			elseif ("%" == $unit && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = "~Valve.F";
			}
			elseif ("%" == $unit)
			{
				$profile = "~Valve";
			}
			elseif ("wh" == strtolower($unit) && (MODBUSDATATYPE_REAL == $datenTyp || MODBUSDATATYPE_INT64 == $datenTyp))
			{
				$profile = MODUL_PREFIX.".Electricity.Float";
			}
			elseif ("wh" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Electricity.Int";
			}
			elseif ((
				"° C" == $unit
					|| "°C" == $unit
					|| "C" == $unit
			) && MODBUSDATATYPE_REAL == $datenTyp
			) {
				$profile = "~Temperature";
			}
			elseif ("° C" == $unit
				|| "°C" == $unit
				|| "C" == $unit
			) {
				$profile = MODUL_PREFIX.".Temperature.Int";
			}
			elseif ("cos()" == strtolower($unit) && MODBUSDATATYPE_REAL == $datenTyp)
			{
				$profile = MODUL_PREFIX.".Angle.Float";
			}
			elseif ("cos()" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Angle.Int";
			}
			elseif ("ohm" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Ohm.Int";
			}
			elseif ("enumerated_id" == strtolower($unit))
			{
				$profile = "SunSpec.ID.Int";
			}
			elseif ("enumerated_chast" == strtolower($unit))
			{
				$profile = "SunSpec.ChaSt.Int";
			}
			elseif ("enumerated_st" == strtolower($unit))
			{
				$profile = "SunSpec.StateCodes.Int";
			}
			elseif ("enumerated_stvnd" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".StateCodes.Int";
			}
			elseif ("enumerated_zirkulation" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Zirkulation.Int";
			}
			elseif ("enumerated_betriebsart" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Betriebsart.Int";
			}
			elseif ("enumerated_statsheizkreis" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".StatsHeizkreis.Int";
			}
			elseif ("enumerated_emergency-power" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Emergency-Power.Int";
			}
			elseif ("enumerated_powermeter" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".Powermeter.Int";
			}
			elseif ("enumerated_sg-ready-status" == strtolower($unit))
			{
				$profile = MODUL_PREFIX.".SG-Ready-Status.Int";
			}
			elseif ("secs" == strtolower($unit))
			{
				$profile = "~UnixTimestamp";
			}
			elseif ("registers" == strtolower($unit)
				|| "bitfield" == strtolower($unit)
				|| "bitfield16" == strtolower($unit)
				|| "bitfield32" == strtolower($unit)
			) {
				$profile = false;
			}
			else
			{
				$profile = false;
				if ("" != $unit)
				{
					$this->SendDebug("getProfile()", "ERROR: Profil '".$unit."' unbekannt!", 0);
				}
			}

			return $profile;
		}


		private function checkProfiles()
		{
			$deleteProfiles_array = array();

			$deleteProfiles_array[] = MODUL_PREFIX.".TempFehler.Int";
			/*
						$this->createVarProfile(MODUL_PREFIX.".TempFehler.Int", VARIABLETYPE_INTEGER, '', 0, 2, 1, 0, 0, array(
								array('Name' => "OK", 'Wert' => 0, "OK", 'Farbe' => $this->getRgbColor("green")),
								array('Name' => "Kurzschluss", 'Wert' => 1, "Kurzschlussfehler", 'Farbe' => $this->getRgbColor("red")),
								array('Name' => "Unterbrechung", 'Wert' => 2, "Unterbrechungsfehler", 'Farbe' => $this->getRgbColor("red")),
							)
						);
			 */

			$deleteProfiles_array[] = MODUL_PREFIX.".Betriebsart.Int";
			/*
						$this->createVarProfile(MODUL_PREFIX.".Betriebsart.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "Auto PWM", 'Wert' => 0, "Auto PWM", 'Farbe' => $this->getRgbColor("green")),
								array('Name' => "Hand PWM", 'Wert' => 1, "Hand PWM", 'Farbe' => $this->getRgbColor("yellow")),
								array('Name' => "Auto analog", 'Wert' => 2, "Auto analog", 'Farbe' => $this->getRgbColor("green")),
								array('Name' => "Hand analog", 'Wert' => 3, "Hand analog", 'Farbe' => $this->getRgbColor("yellow")),
								array('Name' => "FEHLER", 'Wert' => 255, "FEHLER", 'Farbe' => $this->getRgbColor("red")),
							)
						);
			 */

			$deleteProfiles_array[] = MODUL_PREFIX.".StatsHeizkreis.Int";
			/*
						$this->createVarProfile(MODUL_PREFIX.".StatsHeizkreis.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "Aus", 'Wert' => 1, "Aus"),
								array('Name' => "Automatik", 'Wert' => 2, "Automatik"),
								array('Name' => "Tagbetrieb", 'Wert' => 3, "Tagbetrieb"),
								array('Name' => "Absenkbetrieb", 'Wert' => 4, "Absenkbetrieb"),
								array('Name' => "Standby", 'Wert' => 5, "Standby"),
								array('Name' => "Eco", 'Wert' => 6, "Eco"),
								array('Name' => "Urlaub", 'Wert' => 7, "Urlaub"),
								array('Name' => "WW Vorrang", 'Wert' => 8, "WW Vorrang"),
								array('Name' => "Frostschutz", 'Wert' => 9, "Frostschutz"),
								array('Name' => "Pumpenschutz", 'Wert' => 10, "Pumpenschutz"),
								array('Name' => "Estrich", 'Wert' => 11, "Estrich"),
								array('Name' => "FEHLER", 'Wert' => 255, "FEHLER", 'Farbe' => $this->getRgbColor("red")),
							)
						);
			 */

			$deleteProfiles_array[] = MODUL_PREFIX.".Zirkulation.Int";
			/*
						$this->createVarProfile(MODUL_PREFIX.".Zirkulation.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "Aus", 'Wert' => 1, "Aus"),
								array('Name' => "Puls", 'Wert' => 2, "Puls"),
								array('Name' => "Temp", 'Wert' => 3, "Temp"),
								array('Name' => "Warten", 'Wert' => 4, "Warten"),
								array('Name' => "FEHLER", 'Wert' => 255, "FEHLER", 'Farbe' => $this->getRgbColor("red")),
							)
						);
			 */
			/*
						$this->createVarProfile("SunSpec.ChaSt.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "N/A", 'Wert' => 0, "Unbekannter Status"),
								array('Name' => "OFF", 'Wert' => 1, "OFF: Energiespeicher nicht verfügbar"),
								array('Name' => "EMPTY", 'Wert' => 2, "EMPTY: Energiespeicher vollständig entladen"),
								array('Name' => "DISCHAGING", 'Wert' => 3, "DISCHARGING: Energiespeicher wird entladen"),
								array('Name' => "CHARGING", 'Wert' => 4, "CHARGING: Energiespeicher wird geladen"),
								array('Name' => "FULL", 'Wert' => 5, "FULL: Energiespeicher vollständig geladen"),
								array('Name' => "HOLDING", 'Wert' => 6, "HOLDING: Energiespeicher wird weder geladen noch entladen"),
								array('Name' => "TESTING", 'Wert' => 7, "TESTING: Energiespeicher wird getestet"),
							)
						);
			 */
			/*
						$this->createVarProfile("SunSpec.ID.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "single phase Inv (i)", 'Wert' => 101, "101: single phase Inverter (int)"),
								array('Name' => "split phase Inv (i)", 'Wert' => 102, "102: split phase Inverter (int)"),
								array('Name' => "three phase Inv (i)", 'Wert' => 103, "103: three phase Inverter (int)"),
								array('Name' => "single phase Inv (f)", 'Wert' => 111, "111: single phase Inverter (float)"),
								array('Name' => "split phase Inv (f)", 'Wert' => 112, "112: split phase Inverter (float)"),
								array('Name' => "three phase Inv (f)", 'Wert' => 113, "113: three phase Inverter (float)"),
								array('Name' => "single phase Meter (i)", 'Wert' => 201, "201: single phase Meter (int)"),
								array('Name' => "split phase Meter (i)", 'Wert' => 202, "202: split phase (int)"),
								array('Name' => "three phase Meter (i)", 'Wert' => 203, "203: three phase (int)"),
								array('Name' => "single phase Meter (f)", 'Wert' => 211, "211: single phase Meter (float)"),
								array('Name' => "split phase Meter (f)", 'Wert' => 212, "212: split phase Meter (float)"),
								array('Name' => "three phase Meter (f)", 'Wert' => 213, "213: three phase Meter (float)"),
								array('Name' => "string combiner (i)", 'Wert' => 403, "403: String Combiner (int)"),
							)
						);
			 */
			/*
						$this->createVarProfile("SunSpec.StateCodes.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "N/A", 'Wert' => 0, "Unbekannter Status"),
								array('Name' => "OFF", 'Wert' => 1, "Wechselrichter ist aus"),
								array('Name' => "SLEEPING", 'Wert' => 2, "Auto-Shutdown"),
								array('Name' => "STARTING", 'Wert' => 3, "Wechselrichter startet"),
								array('Name' => "MPPT", 'Wert' => 4, "Wechselrichter arbeitet normal", 'Farbe' => $this->getRgbColor("green")),
								array('Name' => "THROTTLED", 'Wert' => 5, "Leistungsreduktion aktiv", 'Farbe' => $this->getRgbColor("orange")),
								array('Name' => "SHUTTING_DOWN", 'Wert' => 6, "Wechselrichter schaltet ab"),
								array('Name' => "FAULT", 'Wert' => 7, "Ein oder mehr Fehler existieren, siehe St *oder Evt * Register", 'Farbe' => $this->getRgbColor("red")),
								array('Name' => "STANDBY", 'Wert' => 8, "Standby"),
							)
						);
			 */

			$deleteProfiles_array[] = MODUL_PREFIX.".StateCodes.Int";
			/*
						$this->createVarProfile(MODUL_PREFIX.".StateCodes.Int", VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, 0, array(
								array('Name' => "N/A", 'Wert' => 0, "Unbekannter Status"),
								array('Name' => "OFF", 'Wert' => 1, "Wechselrichter ist aus"),
								array('Name' => "SLEEPING", 'Wert' => 2, "Auto-Shutdown"),
								array('Name' => "STARTING", 'Wert' => 3, "Wechselrichter startet"),
								array('Name' => "MPPT", 'Wert' => 4, "Wechselrichter arbeitet normal", 'Farbe' => $this->getRgbColor("green")),
								array('Name' => "THROTTLED", 'Wert' => 5, "Leistungsreduktion aktiv", 'Farbe' => $this->getRgbColor("orange")),
								array('Name' => "SHUTTING_DOWN", 'Wert' => 6, "Wechselrichter schaltet ab"),
								array('Name' => "FAULT", 'Wert' => 7, "Ein oder mehr Fehler existieren, siehe St * oder Evt * Register", 'Farbe' => $this->getRgbColor("red")),
								array('Name' => "STANDBY", 'Wert' => 8, "Standby"),
								array('Name' => "NO_BUSINIT", 'Wert' => 9, "Keine SolarNet Kommunikation"),
								array('Name' => "NO_COMM_INV", 'Wert' => 10, "Keine Kommunikation mit Wechselrichter möglich"),
								array('Name' => "SN_OVERCURRENT", 'Wert' => 11, "Überstrom an SolarNet Stecker erkannt"),
								array('Name' => "BOOTLOAD", 'Wert' => 12, "Wechselrichter wird gerade upgedatet"),
								array('Name' => "AFCI", 'Wert' => 13, "AFCI Event (Arc-Erkennung)"),
							)
						);
			 */
			$this->createVarProfile(
				MODUL_PREFIX.".Emergency-Power.Int",
				VARIABLETYPE_INTEGER,
				'',
				0,
				0,
				0,
				0,
				0,
				array(
					array('Name' => "nicht unterstützt", 'Wert' => 0, "Notstrom wird nicht von Ihrem Gerät unterstützt", 'Farbe' => 16753920),
					array('Name' => "aktiv", 'Wert' => 1, "Notstrom aktiv (Ausfall des Stromnetzes)", 'Farbe' => $this->getRgbColor("green")),
					array('Name' => "nicht aktiv", 'Wert' => 2, "Notstrom nicht aktiv", 'Farbe' => -1),
					array('Name' => "nicht verfügbar", 'Wert' => 3, "Notstrom nicht verfügbar", 'Farbe' => 16753920),
					array('Name' => "Fehler", 'Wert' => 4, "Der Motorschalter des S10 E befindet sich nicht in der richtigen Position, sondern wurde manuell abgeschaltet oder nicht eingeschaltet.", 'Farbe' => $this->getRgbColor("red")),
				)
			);

			$this->createVarProfile(
				MODUL_PREFIX.".Powermeter.Int",
				VARIABLETYPE_INTEGER,
				'',
				0,
				0,
				0,
				0,
				0,
				array(
					array('Name' => "N/A", 'Wert' => 0),
					array('Name' => "Wurzelleistungsmesser", 'Wert' => 1, "Dies ist der Regelpunkt des Systems. Der Regelpunkt entspricht üblicherweise dem Hausanschlusspunkt."),
					array('Name' => "Externe Produktion", 'Wert' => 2),
					array('Name' => "Zweirichtungszähler", 'Wert' => 3),
					array('Name' => "Externer Verbrauch", 'Wert' => 4),
					array('Name' => "Farm", 'Wert' => 5),
					array('Name' => "Wird nicht verwendet", 'Wert' => 6),
					array('Name' => "Wallbox", 'Wert' => 7),
					array('Name' => "Externer Leistungsmesser Farm", 'Wert' => 8),
					array('Name' => "Datenanzeige", 'Wert' => 9, "Wird nicht in die Regelung eingebunden, sondern dient nur der Datenaufzeichnung des Kundenportals."),
					array('Name' => "Regelungsbypass", 'Wert' => 10, "Die gemessene Leistung wird nicht in die Batterie geladen, aus der Batterie entladen."),
				)
			);

			$this->createVarProfile(
				MODUL_PREFIX.".SG-Ready-Status.Int",
				VARIABLETYPE_INTEGER,
				'',
				0,
				0,
				0,
				0,
				0,
				array(
					array('Name' => "N/A", 'Wert' => 0),
					array('Name' => "Sperrbetrieb", 'Wert' => 1, "Betriebszustand 1 (Sperrbetrieb):Dieser Betriebszustand ist abwärtskompatibel zur häufig zufesten Uhrzeiten geschalteten EVU-Sperre und umfasstmaximal 2 Stunden „harte“ Sperrzeit."),
					array('Name' => "Normalbetrieb", 'Wert' => 2, "Betriebszustand 2 (Normalbetrieb):In dieser Schaltung läuft die Wärmepumpe imenergieeffizienten Normalbetrieb mit anteiligerWärmespeicher-Füllung für die maximal zweistündige EVU-Sperre."),
					array('Name' => "PV-Überschussbetrieb", 'Wert' => 3, "Betriebszustand 3 (PV-Überschussbetrieb): In diesem Betriebszustand läuft die Wärmepumpe innerhalb des Reglers im verstärkten Betrieb für Raumheizung und Warmwasserbereitung. Es handelt sich dabei nicht um einen definitiven Anlaufbefehl, sondern um eine Einschaltempfehlung entsprechend der heutigen Anhebung."),
					array('Name' => "Betrieb für Abregelung", 'Wert' => 4, "Betriebszustand 4 (Betrieb für Abregelung): Hierbei handelt es sich um einen definitiven Anlaufbefehl, insofern dieser im Rahmen der Regeleinstellungen möglich ist."),
					array('Name' => "undefined", 'Wert' => 5),
				)
			);

			$deleteProfiles_array[] = MODUL_PREFIX.".Ampere.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Ampere.Int", VARIABLETYPE_INTEGER, ' A');

			$deleteProfiles_array[] = MODUL_PREFIX.".AmpereHour.Float";
//			$this->createVarProfile(MODUL_PREFIX.".AmpereHour.Float", VARIABLETYPE_FLOAT, ' Ah');

			$deleteProfiles_array[] = MODUL_PREFIX.".AmpereHour.Int";
//			$this->createVarProfile(MODUL_PREFIX.".AmpereHour.Int", VARIABLETYPE_INTEGER, ' Ah');

			$deleteProfiles_array[] = MODUL_PREFIX.".Angle.Float";
//			$this->createVarProfile(MODUL_PREFIX.".Angle.Float", VARIABLETYPE_FLOAT, ' °');

			$deleteProfiles_array[] = MODUL_PREFIX.".Angle.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Angle.Int", VARIABLETYPE_INTEGER, ' °');

			$deleteProfiles_array[] = MODUL_PREFIX.".Blindleistung.Float";
//			$this->createVarProfile(MODUL_PREFIX.".Blindleistung.Float", VARIABLETYPE_FLOAT, ' Var');

			$deleteProfiles_array[] = MODUL_PREFIX.".Blindleistung.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Blindleistung.Int", VARIABLETYPE_INTEGER, ' Var');

			$deleteProfiles_array[] = MODUL_PREFIX.".Electricity.Float";
//			$this->createVarProfile(MODUL_PREFIX.".Electricity.Float", VARIABLETYPE_FLOAT, ' Wh');

			$this->createVarProfile(MODUL_PREFIX.".Electricity.Int", VARIABLETYPE_INTEGER, ' Wh');

			$deleteProfiles_array[] = MODUL_PREFIX.".Hertz.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Hertz.Int", VARIABLETYPE_INTEGER, ' Hz');

			$deleteProfiles_array[] = MODUL_PREFIX.".Hours.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Hours.Int", VARIABLETYPE_INTEGER, ' h');

			$deleteProfiles_array[] = MODUL_PREFIX.".MilliAmpere.Int";
//			$this->createVarProfile(MODUL_PREFIX.".MilliAmpere.Int", VARIABLETYPE_INTEGER, ' mA');

			$deleteProfiles_array[] = MODUL_PREFIX.".Ohm.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Ohm.Int", VARIABLETYPE_INTEGER, ' Ohm');

			$deleteProfiles_array[] = MODUL_PREFIX.".Scheinleistung.Float";
//			$this->createVarProfile(MODUL_PREFIX.".Scheinleistung.Float", VARIABLETYPE_FLOAT, ' VA');

			$deleteProfiles_array[] = MODUL_PREFIX.".Scheinleistung.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Scheinleistung.Int", VARIABLETYPE_INTEGER, ' VA');

			// Temperature.Float: ~Temperature

			$deleteProfiles_array[] = MODUL_PREFIX.".Temperature.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Temperature.Int", VARIABLETYPE_INTEGER, ' °C');

			// Volt.Float: ~Volt

			$this->createVarProfile(MODUL_PREFIX.".Volt.Int", VARIABLETYPE_INTEGER, ' V');

			$deleteProfiles_array[] = MODUL_PREFIX.".Volumenstrom.Int";
//			$this->createVarProfile(MODUL_PREFIX.".Volumenstrom.Int", VARIABLETYPE_INTEGER, ' l/min');

			$this->createVarProfile(MODUL_PREFIX.".Watt.Int", VARIABLETYPE_INTEGER, ' W');

			// delete not used profiles
			foreach ($deleteProfiles_array as $profileName)
			{
				if (IPS_VariableProfileExists($profileName))
				{
					IPS_DeleteVariableProfile($profileName);
				}
			}
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

				$returnValue = GetValue($id);
			}

			return $returnValue;
		}

		/*
			public functions
		 */

		public function GetAutarky(): int
		{
			return $this->GetVariableValue(40082, "Autarkie");
		}

		public function GetSelfConsumption(): int
		{
			return $this->GetVariableValue(40082, "Eigenverbrauch");
		}

		public function GetBatteryPowerW(): int
		{
			return $this->GetBatteryPowerIntervalW(0);
		}

		public function GetBatteryPowerIntervalW(int $timeIntervalInMinutes): int
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

			return (int)round($returnValue);
		}

		public function GetBatteryPowerKw(): float
		{
			return $this->GetBatteryPowerIntervalKw(0);
		}

		public function GetBatteryPowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetBatteryPowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetBatteryChargeEnergyWh(int $startTime, int $endTime): int
		{
			$varIdent = 40070;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 1);

			return (int)round($returnValue);
		}

		public function GetBatteryChargeEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetBatteryChargeEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetBatteryDischargeEnergyWh(int $startTime, int $endTime): int
		{
			$varIdent = 40070;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 2);

			return (int)abs(round($returnValue));
		}

		public function GetBatteryDischargeEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetBatteryDischargeEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetBatterySoc(): int
		{
			return $this->GetVariableValue(40083, "Value");
		}

		public function GetBatteryRangeKwh(): float
		{
			$batterySize = $this->ReadPropertyFloat('batterySize');
			$batteryDischargeMax = BATTERY_DISCHARGE_MAX;
			$readEmergencyPower = $this->ReadPropertyBoolean('readEmergencyPower');
			$emergencyPowerBuffer = $this->ReadPropertyFloat('emergencyPowerBuffer');

			$batteryRange = ($this->GetBatterySoc() / 100) * $batterySize * ($batteryDischargeMax / 100);

			if ($readEmergencyPower)
			{
				$batteryRange = $batteryRange - $emergencyPowerBuffer;
			}

			return $batteryRange;
		}

		public function GetBatteryRangeWh(): int
		{
			return $this->GetBatteryRangeWh() * 1000;
		}

		public function GetExtPowerW(): int
		{
			return $this->GetExtPowerIntervalW(0);
		}

		public function GetExtPowerIntervalW(int $timeIntervalInMinutes): int
		{
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');

			$varIdent = 40076;

			if (false === $readExtLeistung)
			{
				$returnValue = 0;
			}
			elseif (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return (int)abs(round($returnValue));
		}

		public function GetExtPowerKw(): float
		{
			return $this->GetExtPowerIntervalKw(0);
		}

		public function GetExtPowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetExtPowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetExtEnergyWh(int $startTime, int $endTime): int
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

			return (int)abs(round($returnValue));
		}

		public function GetExtEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetExtEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetProductionPowerW(): int
		{
			return $this->GetProductionPowerIntervalW(0);
		}

		public function GetProductionPowerIntervalW(int $timeIntervalInMinutes): int
		{
			$readExtLeistung = $this->ReadPropertyBoolean('readExtLeistung');

			return $readExtLeistung ? $this->GetExtPowerIntervalW($timeIntervalInMinutes) + $this->GetPvPowerIntervalW($timeIntervalInMinutes) : $this->GetPvPowerIntervalW($timeIntervalInMinutes);
		}

		public function GetProductionPowerKw(): float
		{
			return $this->GetProductionPowerIntervalKw(0);
		}

		public function GetProductionPowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetProductionPowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetProductionEnergyWh(int $startTime, int $endTime): int
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

			return (int)round($returnValue);
		}

		public function GetProductionEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetProductionEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetGridPowerW(): int
		{
			return $this->GetGridPowerIntervalW(0);
		}

		public function GetGridPowerIntervalW(int $timeIntervalInMinutes): int
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

			return (int)round($returnValue);
		}

		public function GetGridPowerKw(): float
		{
			return $this->GetGridPowerIntervalKw(0);
		}

		public function GetGridPowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetGridPowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetGridConsumptionEnergyWh(int $startTime, int $endTime): int
		{
			$varIdent = 40074;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 1);

			return (int)round($returnValue);
		}

		public function GetGridConsumptionEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetGridConsumptionEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetGridFeedEnergyWh(int $startTime, int $endTime): int
		{
			$varIdent = 40074;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime, 2);

			return (int)abs(round($returnValue));
		}

		public function GetGridFeedEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetGridFeedEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetPvPowerW(): int
		{
			return $this->GetPvPowerIntervalW(0);
		}

		public function GetPvPowerIntervalW(int $timeIntervalInMinutes): int
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

			return (int)abs(round($returnValue));
		}

		public function GetPvPowerKw(): float
		{
			return $this->GetPvPowerIntervalKw(0);
		}

		public function GetPvPowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetPvPowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetPvEnergyWh(int $startTime, int $endTime): int
		{
			$varIdent = 40068;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);

			return (int)abs(round($returnValue));
		}

		public function GetPvEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetPvEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetHomePowerW()
		{
			return $this->GetHomePowerIntervalW(0);
		}

		public function GetHomePowerIntervalW(int $timeIntervalInMinutes): int
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

		public function GetHomePowerKw(): float
		{
			return $this->GetHomePowerIntervalKw(0);
		}

		public function GetHomePowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetHomePowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetHomeEnergyWh(int $startTime, int $endTime): int
		{
			$varIdent = 40072;

			$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);

			return (int)round($returnValue);
		}

		public function GetHomeEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetHomeEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetWallboxPowerW(): int
		{
			return $this->GetWallboxPowerIntervalW(0);
		}

		public function GetWallboxPowerIntervalW(int $timeIntervalInMinutes): int
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

			if (false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			elseif (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return $returnValue;
		}

		public function GetWallboxPowerKw(): float
		{
			return $this->GetWallboxPowerIntervalKw(0);
		}

		public function GetWallboxPowerIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetWallboxPowerIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetWallboxEnergyWh(int $startTime, int $endTime): int
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

			if (false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			else
			{
				$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);
			}

			return (int)round($returnValue);
		}

		public function GetWallboxEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetWallboxEnergyWh($startTime, $endTime) / 1000;
		}

		public function GetWallboxPowerSolarW(): int
		{
			return $this->GetWallboxPowerSolarIntervalW(0);
		}

		public function GetWallboxPowerSolarIntervalW(int $timeIntervalInMinutes): int
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

			if (false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			elseif (0 < $timeIntervalInMinutes)
			{
				$returnValue = $this->GetLoggedValuesInterval($this->GetVariableId($varIdent, "Value"), $timeIntervalInMinutes);
			}
			else
			{
				$returnValue = $this->GetVariableValue($varIdent, "Value");
			}

			return $returnValue;
		}

		public function GetWallboxPowerSolarKw(): float
		{
			return $this->GetWallboxPowerSolarIntervalKw(0);
		}

		public function GetWallboxPowerSolarIntervalKw(int $timeIntervalInMinutes): float
		{
			return $this->GetWallboxPowerSolarIntervalW($timeIntervalInMinutes) / 1000;
		}

		public function GetWallboxSolarEnergyWh(int $startTime, int $endTime): int
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

			if (false === $readWallbox0 && false === $readWallbox1 && false === $readWallbox2 && false === $readWallbox3 && false === $readWallbox4 && false === $readWallbox5 && false === $readWallbox6 && false === $readWallbox7)
			{
				$returnValue = 0;
			}
			else
			{
				$returnValue = $this->getPowerSumOfLog($this->GetVariableId($varIdent, "Value"), $startTime, $endTime);
			}

			return (int)abs(round($returnValue));
		}

		public function GetWallboxSolarEnergyKwh(int $startTime, int $endTime): float
		{
			return $this->GetWallboxSolarEnergyWh($startTime, $endTime) / 1000;
		}

		/*
		EmergencyPowerState
					$readEmergencyPower = $this->ReadPropertyBoolean('readEmergencyPower');
					$emergencyPowerBuffer = $this->ReadPropertyInteger('emergencyPowerBuffer');
		ErrorState
		ErrorMessage
		 */

		public function IsDerating(): bool
		{
			$modbusAddress = 40085;
			$bitName = "Abregelungs-Status";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function IsChargingLocked(): bool
		{
			$modbusAddress = 40085;
			$bitName = "Batterie laden";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function IsDischargingLocked(): bool
		{
			$modbusAddress = 40085;
			$bitName = "Batterie entladen";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		/* *** Wallbox Status-Bits *** */
		public function GetWallboxAvailable(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Wallbox";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxSolarmode(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Solarbetrieb";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxChargingLocked(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Laden sperren";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxCharging(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Ladevorgang";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxType2Locked(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Typ-2-Stecker verriegelt";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxType2Connected(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Typ-2-Stecker gesteckt";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxSchukoActivated(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Schukosteckdose";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxSchukoConnected(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Schukostecker gesteckt";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallboxSchukoLocked(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "Schukostecker verriegelt";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallbox16A1Phase(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "16A 1 Phase";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallbox16A3Phase(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "16A 3 Phasen";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallbox32A3Phase(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "32A 3 Phasen";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		public function GetWallbox1Phase(int $wallboxId): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitName = "1 Phase";

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$bitId = IPS_GetObjectIDByIdent($this->removeInvalidChars($bitName), $instanceId);
				$bitValue = GetValue($bitId);
			}
			else
			{
				$bitValue = false;
			}

			return $bitValue;
		}

		/* *** Write Functions *** */
		public function SetWallboxSolarmode(int $wallboxId, int $setValue): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitSet = 0b0000000000000010;
			$bitUnset = 0b1111111111111101;

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
				$varValue = GetValue($varId);

				// Unset Bit
				if (0 == (int)$setValue)
				{
					$targetValue = $varValue & $bitUnset;
				}
				// Set Bit
				else
				{
					$targetValue = $varValue | $bitSet;
				}

				$returnValue = ModBus_WriteRegisterWord($instanceId, $targetValue);
			}
			else
			{
				$returnValue = false;
			}

			return $returnValue;
		}

		public function SetWallboxChargingLocked(int $wallboxId, int $setValue): bool
		{
			// ! ! ! ACHTUNG ! ! !
			// Implementierung laut E3DC-Support fehlerhaft!
			// Einzige Antwort, die ich hierzu nach fast 3 Monaten erhalten habe:
			// "Wenn ein einzelnes Bit gesetzt werden soll, dann ist der Vorgang: Lesen des Registers, ändern des Bits in dem Wert des Registers, dann zurückschreiben des Registers."
			// Frage meinerseits: Weshalb soll es hier nicht funktionieren und bei den anderen beiden Wallbox WriteFunctions schon ?!?!
			// --> nie mehr eine Rückmeldung erhalten...
			// Der E3DC Support ist aus meinen Erfahrungen mehr als mangelhaft.
			//
			// Würde mich freuen, wenn jemand den Fehler in meiner Implementierung finden würde!
			// Ansonsten bleibe ich dabei, dass doch E3DC bitte den Fehler beheben sollte...

			$modbusAddress = 40088 + (int)$wallboxId;
			$bitSet = 0b0000000000000100;
			$bitUnset = 0b1111111111111011;

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
				$varValue = GetValue($varId);

				// Unset Bit
				if (0 == (int)$setValue)
				{
					$targetValue = $varValue & $bitUnset;
				}
				// Set Bit
				else
				{
					$targetValue = $varValue | $bitSet;
				}

				$returnValue = ModBus_WriteRegisterWord($instanceId, $targetValue);
			}
			else
			{
				$returnValue = false;
			}

			return $returnValue;
		}

		public function SetWallboxSchukoActivated(int $wallboxId, int $setValue): bool
		{
			// ! ! ! ACHTUNG ! ! !
			// Implementierung laut E3DC-Support fehlerhaft!
			// Einzige Antwort, die ich hierzu nach fast 3 Monaten erhalten habe:
			// "Wenn ein einzelnes Bit gesetzt werden soll, dann ist der Vorgang: Lesen des Registers, ändern des Bits in dem Wert des Registers, dann zurückschreiben des Registers."
			// Frage meinerseits: Weshalb soll es hier nicht funktionieren und bei den anderen beiden Wallbox WriteFunctions schon ?!?!
			// --> nie mehr eine Rückmeldung erhalten...
			// Der E3DC Support ist aus meinen Erfahrungen mehr als mangelhaft.
			//
			// Würde mich freuen, wenn jemand den Fehler in meiner Implementierung finden würde!
			// Ansonsten bleibe ich dabei, dass doch E3DC bitte den Fehler beheben sollte...

			$modbusAddress = 40088 + (int)$wallboxId;
			$bitSet = 0b0000000001000000;
			$bitUnset = 0b1111111110111111;

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
				$varValue = GetValue($varId);

				// Unset Bit
				if (0 == (int)$setValue)
				{
					$targetValue = $varValue & $bitUnset;
				}
				// Set Bit
				else
				{
					$targetValue = $varValue | $bitSet;
				}

				$returnValue = ModBus_WriteRegisterWord($instanceId, $targetValue);
			}
			else
			{
				$returnValue = false;
			}

			return $returnValue;
		}

		public function SetWallbox1Phase(int $wallboxId, int $setValue): bool
		{
			$modbusAddress = 40088 + (int)$wallboxId;
			$bitSet = 0b0001000000000000;
			$bitUnset = 0b1110111111111111;

			$instanceId = @IPS_GetObjectIDByIdent($modbusAddress, $this->InstanceID);

			if (false !== $instanceId)
			{
				$varId = IPS_GetObjectIDByIdent("Value", $instanceId);
				$varValue = GetValue($varId);

				// Unset Bit
				if (0 == (int)$setValue)
				{
					$targetValue = $varValue & $bitUnset;
				}
				// Set Bit
				else
				{
					$targetValue = $varValue | $bitSet;
				}

				$returnValue = ModBus_WriteRegisterWord($instanceId, $targetValue);
			}
			else
			{
				$returnValue = false;
			}

			return $returnValue;
		}
	}
