# E3DC
IP-Symcon (IPS) Modul für E3DC Stromspeicher mit TCP Modbus Unterstützung (bspw. S10 mini, S10 E und S10 E Pro).


### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

Dieses Modul erstellt anhand der Konfiguration der E3DC Instanz den nötigen Client Socket und das dazugehörige Modbus Gateway. Sofern diese bereits vorhanden sind, werden keine weiteren Client Sockets oder Modbus Gateways erstellt.
Unterhalb der E3DC Instanz werden die Modbus Adressen für den E3/DC-Simple Mode erstellt.


### 2. Vorraussetzungen

* IP-Symcon ab Version 5.0
* Der E3DC Stromspeicher muss Modbus TCP unterstützen!
* Im Konfigurationsmenü des E3DC Stromspeichers muss je nach Version folgendes aktiviert werden:

entweder unter Hauptmenü > Funktionen > Funktion Modbus > Modbus und Modbus TCP mit Protokoll 'E3/DC Simple-Mode' aktivieren
![alt text](https://github.com/Brovning/e3dc/blob/master/docs/E3DC%20-%20Hauptmen%C3%BC%20-%20Funktionen%20-%20Funktion%20Modbus.png "E3DC > Hauptmenü > Funktionen > Funktion Modbus")

oder unter Hauptmenü > Smart-Funktionen > Smart Home > Modbus > erst Modbus aktivieren, dann auf den Pfeil nach Rechts klicken und Modbus TCP mit Protokoll 'E3/DC' aktivieren
![alt text](https://github.com/Brovning/e3dc/blob/master/docs/E3DC%20-%20Hauptmen%C3%BC%20-%20%20Smart-Funktionen%20%20-%20Smart%20Home%20%20-%20Modbus%20-%20Modbus.jpg "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Modbus > Modbus")
![alt text](https://github.com/Brovning/e3dc/blob/master/docs/E3DC%20-%20Hauptmen%C3%BC%20-%20%20Smart-Funktionen%20%20-%20Smart%20Home%20%20-%20Modbus%20-%20Modbus%20TCP.jpg "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Modbus > Modbus TCP")


### 3. Software-Installation

* Über den Module Store das 'E3DC'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen: https://github.com/Brovning/e3dc


### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' ist das 'S10 mini'-, 'S10 E'- und 'S10 E Pro'-Modul unter dem Hersteller 'E3DC' aufgeführt.

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
Open | Schalter zum aktivieren und deaktivieren der Instanz
IP | IP-Adresse des E3DC-Stromspeichers im lokalen Netzwerk
Port | Port, welcher im E3DC unter dem Menüpunkt Modbus angegeben wurde. Default: 502
Geräte Id | Modbus Geräte ID, welche im E3DC Menü gesetzt werden kann. Default: 1
externer Einspeiser | Schalter, um die Variable für einen zusätzlichen Einspeiser (bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) einzulesen
Wallbox 0 - 7 | 8 Schalter zum Aktivieren und Deaktivieren der Wallbox-Variablen
DC String Informationen | Schalter, um die Variablen für V, A und W der DC-Strings einzulesen (erst ab Release S10_2017_02 verfügbar).
Variablen-Logging | Für welche Variablen soll das Logging aktiviert werden? Zur Auswahl stehen Leistungsvariablen in W, Leistungsvariablen in kW (bei Auswahl werden zusätzliche Variablen in kW erstellt), Batterie SOC (Ladezustand) in %, Autarkie in %, Eigenverbrauch in %
Abfrage-Intervall	| Intervall (in ms) in welchem die Modbus-Adressen abgefragt werden sollen. Achtung: Abfrage-Intervall nicht zu klein wählen, um die Systemlast und auch die Archiv-Größe bei Logging nicht unnötig zu erhöhen! Default: 60000 (=60 Sekunden)


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen
Der E3/DC-Simple Mode ermöglicht den einfachen und schnellen Zugriff auf die wichtigsten und am häufigsten benötigten Daten.

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40068 | 2 | 3 | PV-Leistung | Int32 | W | Photovoltaik-Leistung in Watt
40070 | 2 | 3 | Batterie-Leistung | Int32 | W | Batterie-Leistung in Watt (negative Werte = Entladung)
40072 | 2 | 3 | Verbrauchs-Leistung | Int32 | W | Hausverbrauchs-Leistung in Watt
40074 | 2 | 3 | Netz-Leistung | Int32 | W | Leistung am Netzübergabepunkt in Watt (negative Werte = Einspeisung)
40082 | 1 | 3 | Autarkie-Eigenverbrauch | Uint8+Uint8 |  | Autarkie und Eigenverbrauch in Prozent
40083 | 1 | 3 | Batterie-SOC | Uint16 | % | Batterie-SOC in Prozent
40084 | 1 | 3 | Emergency-Power | Uint16 |  | Emergency-Power Status (seaparates Profil vorhanden)
40085 | 1 | 3 | EMS-Status | Uint16 | Bitfield | EMS-Status Bits (werden einzeln ausgewertet)
40086 | 1 | 3 | EMS Remote Control | int16 |  | EMS Remote Control
40087 | 1 | 3 | EMS CTRL | Uint16 |  | EMS CTRL


##### optional: externer Einspeiser

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40076 | 2 | 3 | Ext-Leistung | Int32 | W | Leistung aller zusätzlichen Einspeiser in Watt

##### optional: Wallbox 0 - 7

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40078 | 2 | 3 | Wallbox-Leistung | Int32 | W | Leistung der Wallbox in Watt
40080 | 2 | 3 | Wallbox-Solarleistung | Int32 | W | Solarleistung, die von der Wallbox genutzt wird in Watt
40088 | 1 | 3 | WallBox_0_CTRL | Uint16 | Bitfield | Bits der WallBox 0 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_1_CTRL | Uint16 | Bitfield | Bits der WallBox 1 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_2_CTRL | Uint16 | Bitfield | Bits der WallBox 2 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_3_CTRL | Uint16 | Bitfield | Bits der WallBox 3 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_4_CTRL | Uint16 | Bitfield | Bits der WallBox 4 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_5_CTRL | Uint16 | Bitfield | Bits der WallBox 5 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_6_CTRL | Uint16 | Bitfield | Bits der WallBox 6 (werden einzeln ausgewertet)
40089 | 1 | 3 | WallBox_7_CTRL | Uint16 | Bitfield | Bits der WallBox 7 (werden einzeln ausgewertet)

##### optional: DC-String Informationen
Hinweis: Die folgenden Register 40096 bis 40104 können ab dem Release S10_2017_02 genutzt werden!

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40096 | 1 | 3 | DC_STRING_1_Voltage | UInt16 | V | DC_STRING_1_Voltage
40097 | 1 | 3 | DC_STRING_2_Voltage | UInt16 | V | DC_STRING_2_Voltage
40098 | 1 | 3 | DC_STRING_3_Voltage | UInt16 | V | DC_STRING_3_Voltage
40099 | 1 | 3 | DC_STRING_1_Current | UInt16 | A | DC_STRING_1_Current
40100 | 1 | 3 | DC_STRING_2_Current | UInt16 | A | DC_STRING_2_Current
40101 | 1 | 3 | DC_STRING_3_Current | UInt16 | A | DC_STRING_3_Current
40102 | 1 | 3 | DC_STRING_1_Power | UInt16 | W | DC_STRING_1_Power
40103 | 1 | 3 | DC_STRING_2_Power | UInt16 | W | DC_STRING_2_Power
40104 | 1 | 3 | DC_STRING_3_Power | UInt16 | W | DC_STRING_3_Power


#### Profile

Name   | Typ
------ | -------
E3DC.Emergency-Power.Int | Integer
E3DC.Watt.Int | Integer
E3DC.Ampere.Int | Integer
E3DC.Volt.Int | Integer


### 6. WebFront

Aktuell kein WebFront umgesetzt.


### 7. PHP-Befehlsreferenz

#### Empfehlung
Sofern nur eine Instanz des E3DC-Moduls im Einsatz ist, sollte die $InstanzID wie folgt dynamisch ermittelt werden und nicht statisch gesetzt werden, da somit ein Löschen und Neuinstallieren der E3DC-Instanz keine Auswirkung auf andere Skripte hat:

`$InstanzID = IPS_GetInstanceListByModuleID("{C9508720-B23D-B37A-B5C2-97B607221CE1}")[0];`


#### Funktionen
`int E3DC_GetAutarky(int $InstanzID)`

Gibt den aktuellen Autarkie-Wert der E3DC-Instanz $InstanzID als Integer in Prozent zurück


`int E3DC_GetSelfConsumption(int $InstanzID)`

Gibt den aktuellen Eigenverbrauch-Wert der E3DC-Instanz $InstanzID als Integer in Prozent zurück


`int E3DC_GetBatteryPowerW(int $InstanzID)`

Gibt die aktuelle Batterie-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetBatteryPowerKw(int $InstanzID)`

Gibt die aktuelle Batterie-Leistung der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetBatterySoc(int $InstanzID)`

Gibt den aktuelle Batterie-State-Of-Charge (SOC) der E3DC-Instanz $InstanzID als Integer in Prozent (%) zurück


`int E3DC_GetExtPowerW(int $InstanzID)`

Gibt die aktuelle Ext-Leistung (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetExtPowerKw(int $InstanzID)`

Gibt die aktuelle Ext-Leistung (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetGridPowerW(int $InstanzID)`

Gibt die aktuelle Netz-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetGridPowerKw(int $InstanzID)`

Gibt die aktuelle Netz-Leistung der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetHomePowerW(int $InstanzID)`

Gibt die aktuelle Verbrauchs-Leistung (Hausverbrauch) der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetHomePowerKw(int $InstanzID)`

Gibt die aktuelle Verbrauchs-Leistung (Hausverbrauch) der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetProductionPowerW(int $InstanzID)`

Gibt die aktuelle Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetProductionPowerKw(int $InstanzID)`

Gibt die aktuelle Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetPvPowerW(int $InstanzID)`

Gibt die aktuelle PV-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetPvPowerKw(int $InstanzID)`

Gibt die aktuelle PV-Leistung der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetWallboxPowerW(int $InstanzID)`

Gibt die aktuelle Wallbox-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetWallboxPowerKw(int $InstanzID)`

Gibt die aktuelle Wallbox-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück


`int E3DC_GetWallboxPowerSolarW(int $InstanzID)`

Gibt die aktuelle Wallbox-Solar-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Integer in Watt (W) zurück


`float E3DC_GetWallboxPowerSolarKw(int $InstanzID)`

Gibt die aktuelle Wallbox-Solar-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Float in Kilo-Watt (kW) zurück



#### veraltete Funktionen

Folgende Funktionen werden mit dem nächsten Stable-Release entfernt.
Bitte auf die neuen Funktionsnamen umstellen.

- E3DC_GetAutarkie()
- E3DC_GetEigenverbrauch()
- E3DC_GetBatterieLeistungW()
- E3DC_GetBatterieLeistungKW()
- E3DC_GetBatterieSOC()
- E3DC_GetNetzLeistungW()
- E3DC_GetNetzLeistungKW()
- E3DC_GetPvLeistungW()
- E3DC_GetPvLeistungKW()
- E3DC_GetVerbrauchsLeistungW()
- E3DC_GetVerbrauchsLeistungKW()
