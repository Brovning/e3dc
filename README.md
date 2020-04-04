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
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Dieses Modul erstellt anhand der Konfiguration der E3DC Instanz den nötigen Client Socket und das dazugehörige Modbus Gateway. Sofern diese bereits vorhanden sind, werden keine weiteren Client Sockets oder Modbus Gateways erstellt.
Unterhalb der E3DC Instanz werden die Modbus Adressen für den E3/DC-Simple Mode erstellt.


### 2. Vorraussetzungen

* IP-Symcon ab Version 5.0
* Der E3DC Stromspeicher muss Modbus TCP unterstützen!
* Im Konfigurationsmenü des E3DC Stromspeichers muss je nach Version folgendes aktiviert werden:

entweder unter Hauptmenü > Funktionen > Funktion Modbus > Modbus und Modbus TCP mit Protokoll 'E3/DC Simple-Mode' aktivieren

![alt text](./docs/E3DC%20-%20Hauptmenue%20-%20Funktionen%20-%20Funktion%20Modbus.png?raw=true "E3DC > Hauptmenü > Funktionen > Funktion Modbus")

oder unter Hauptmenü > Smart-Funktionen > Smart Home > Modbus > erst Modbus aktivieren, dann auf den Pfeil nach Rechts klicken und Modbus TCP mit Protokoll 'E3/DC' aktivieren

![alt text](./docs/E3DC%20-%20Hauptmenue%20-%20%20Smart-Funktionen%20%20-%20Smart%20Home%20%20-%20Modbus%20-%20Modbus.jpg?raw=true "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Modbus > Modbus")

![alt text](./docs/E3DC%20-%20Hauptmenue%20-%20%20Smart-Funktionen%20%20-%20Smart%20Home%20%20-%20Modbus%20-%20Modbus%20TCP.jpg?raw=true "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Modbus > Modbus TCP")


### 3. Software-Installation

* Über den Module Store das 'E3DC'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen: https://github.com/Brovning/e3dc


### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' ist das 'S10 mini'-, 'S10 E'- und 'S10 E Pro'-Modul unter dem Hersteller 'E3DC' aufgeführt.

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
Open | Schalter zum aktivieren und deaktivieren der Instanz. Default: aus
IP | IP-Adresse des E3DC-Stromspeichers im lokalen Netzwerk
Port | Port, welcher im E3DC unter dem Menüpunkt Modbus angegeben wurde. Default: 502
Geräte Id | Modbus Geräte ID, welche im E3DC Menü gesetzt werden kann. Default: 1
Batteriekapazität | Welche Batteriekapazität in Kilo-Watt-Stunden (kWh) ist im E3DC installiert (bspw. 6.5, 10, 13, 15, 19.5). Diese Angabe wird optional benötigt, um die Reichweite der Batterie berechnen zu können. Default: 0
externer Einspeiser | Schalter, um die Variable für einen zusätzlichen Einspeiser (bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) einzulesen. Default: aus
Wallbox 0 - 7 | 8 Schalter zum Aktivieren und Deaktivieren der Wallbox-Variablen. Default: aus
Notstromversorgung | Schalter, um angeben zu können, ob eine Notstromversorgung im E3DC verbaut ist. Default: aus
Notstrom-Reserve | Bei vorhandener Notstromversorgung kann am E3DC unter dem Menüpunkt Notstrom > Einstellungen eine Reserve angegeben werden, bis zu welcher die Batterie maximal entladen werden soll. Diese Angabe wird optional benötigt, um die Reichweite der Batterie korrekt berechnen zu können. Default: 0
DC String Informationen | Schalter, um die Variablen für V, A und W der DC-Strings einzulesen (erst ab Release S10_2017_02 verfügbar). Default: aus
Variablen-Logging | Für welche Variablen soll das Logging aktiviert werden? Zur Auswahl stehen Leistungsvariablen in W, Leistungsvariablen in kW (bei Auswahl werden zusätzliche Variablen in kW erstellt), Batterie SOC (Ladezustand) in %, Autarkie in %, Eigenverbrauch in %
Tageswerte der Wirkarbeit | Sollen die Tageswerte in Wh oder kWh berechnet werden? Bei Auswahl werden zusätzliche Variablen in Wh oder kWh erstellt.
Wirkarbeit loggen | Sollen für die Tageswerte in Wh oder kWh das Logging aktiviert werden? Nur möglich, sofern zuvor die Tageswerte-Berechnung auch aktiviert wurde!
Abfrage-Intervall	| Intervall (in Sekunden) in welchem die Modbus-Adressen abgefragt werden sollen. Achtung: Die Berechnung der Wirkarbeit (Wh/kWh) wird exakter, je kleiner der Abfarge-Intervall gewählt wird. Jedoch je kleiner der Abfrage-Intervall, um so höher die Systemlast und auch die Archiv-Größe bei Logging! Default: 60 Sekunden


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusregister
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


#### Statusvariablen
Variablenname | Type | Units | Description
---- | ---- | ----- | -----------
Gesamtproduktion-Leistung | Int | W | aktuelle Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) in Watt (W)
Gesamtproduktion-Leistung_kW | Float | kW | aktuelle Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) in Kilo-Watt (kW)
Gesamtproduktion-Wirkarbeit_Wh | Int | Wh | Gesamtproduktions-Energie (Ext-Energie + PV-Energie) des aktuellen Tages in Watt-Stunden (Wh)
Gesamtproduktion-Wirkarbeit_kWh | Float | kWh | Gesamtproduktions-Energie (Ext-Energie + PV-Energie) des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Batterie-Entlade-Wirkarbeit_Wh | Int | Wh | Batterie-Entlade-Energie des aktuellen Tages in Watt-Stunden (Wh)
Batterie-Entlade-Wirkarbeit_kWh | Float | kWh | Batterie-Entlade-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Batterie-Lade-Wirkarbeit_Wh | Int | Wh | Batterie-Lade-Energie des aktuellen Tages in Watt-Stunden (Wh)
Batterie-Lade-Wirkarbeit_kWh | Float | kWh | Batterie-Lade-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Ext-Wirkarbeit_Wh | Int | Wh | Ext-Energie des aktuellen Tages in Watt-Stunden (Wh)
Ext-Wirkarbeit_kWh | Float | kWh | Ext-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Netz-Bezug-Wirkarbeit_Wh | Int | Wh | Netz-Bezugs-Energie des aktuellen Tages in Watt-Stunden (Wh)
Netz-Bezug-Wirkarbeit_kWh | Float | kWh | Netz-Bezugs-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Netz-Einspeisung-Wirkarbeit_Wh | Int | Wh | Netz-Einspeise-Energie des aktuellen Tages in Watt-Stunden (Wh)
Netz-Einspeisung-Wirkarbeit_kWh | Float | kWh | Netz-Einspeise-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
PV-Wirkarbeit_Wh | Int | Wh | PV-Energie des aktuellen Tages in Watt-Stunden (Wh)
PV-Wirkarbeit_kWh | Float | kWh | PV-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Verbrauchs-Wirkarbeit_Wh | Int | Wh | Verbrauchs-Energie des aktuellen Tages in Watt-Stunden (Wh)
Verbrauchs-Wirkarbeit_kWh | Float | kWh | Verbrauchs-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Wallbox-Solar-Wirkarbeit_Wh | Int | Wh | Wallbox-Solar-Energie des aktuellen Tages in Watt-Stunden (Wh)
Wallbox-Solar-Wirkarbeit_kWh | Float | kWh | Wallbox-Solar-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)
Wallbox-Wirkarbeit_Wh | Int | Wh | Wallbox-Energie des aktuellen Tages in Watt-Stunden (Wh)
Wallbox-Wirkarbeit_kWh | Float | kWh | Wallbox-Energie des aktuellen Tages in Kilo-Watt-Stunden (kWh)


#### Profile

Name   | Typ
------ | -------
E3DC.Emergency-Power.Int | Integer
E3DC.Watt.Int | Integer
E3DC.Ampere.Int | Integer
E3DC.Electricity.Int | Integer
E3DC.Volt.Int | Integer


### 6. WebFront

Aktuell kein WebFront umgesetzt.


### 7. PHP-Befehlsreferenz

#### Empfehlung
Sofern nur eine Instanz des E3DC-Moduls im Einsatz ist, sollte die $InstanzID wie folgt dynamisch ermittelt werden und nicht statisch gesetzt werden, da somit ein Löschen und Neuinstallieren der E3DC-Instanz keine Auswirkung auf andere Skripte hat:

`$InstanzID = IPS_GetInstanceListByModuleID("{C9508720-B23D-B37A-B5C2-97B607221CE1}")[0];`


#### Funktionen

##### Allgemeines

`int E3DC_GetAutarky(int $InstanzID)`

Gibt den aktuellen Autarkie-Wert der E3DC-Instanz $InstanzID als Integer in Prozent zurück.


`int E3DC_GetSelfConsumption(int $InstanzID)`

Gibt den aktuellen Eigenverbrauch-Wert der E3DC-Instanz $InstanzID als Integer in Prozent zurück.


##### Batterie

`int E3DC_GetBatterySoc(int $InstanzID)`

Gibt den aktuelle Batterie-State-Of-Charge (SOC) der E3DC-Instanz $InstanzID als Integer in Prozent (%) zurück.


`int E3DC_GetBatteryPowerW(int $InstanzID)` bzw. `float E3DC_GetBatteryPowerKw(int $InstanzID)`

Gibt die aktuelle Batterie-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetBatteryPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetBatteryPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Batterie-Leistung der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int GetBatteryChargeEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetBatteryChargeEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Batterie-Lade-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int GetBatteryDischargeEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetBatteryDischargeEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Batterie-Entlade-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int E3DC_GetBatteryRangeWh(int $InstanzID)` bzw. `float E3DC_GetBatteryRangeKwh(int $InstanzID)`

Gibt die aktuelle Batterie-Reichweite der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Dieser Wert wird berechnet aus dem SOC, der in der Instanz angegebenen Batterie-Kapazität und der maximal nutzbaren Entladetiefe. Sofern eine Notstrom-Funktion installiert ist, wird auch die in der Instanz angegebene Notstromreserve berücksichtigt.



##### Leistungswerte PV, ExtGen und Haus

`int E3DC_GetGridPowerW(int $InstanzID)` bzw. `float E3DC_GetGridPowerKw(int $InstanzID)`

Gibt die aktuelle Netz-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetGridPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetGridPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Netz-Leistung der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int GetGridConsumptionEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetGridConsumptionEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Netz-Bezugs-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int GetGridFeedEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetGridFeedEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Netz-Einspeise-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int E3DC_GetHomePowerW(int $InstanzID)` bzw. `float E3DC_GetHomePowerKw(int $InstanzID)`

Gibt die aktuelle Verbrauchs-Leistung (Hausverbrauch) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetHomePowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetHomePowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Verbrauchs-Leistung (Hausverbrauch) der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int GetHomeEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetHomeEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Haus-Verbrauchs-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int E3DC_GetPvPowerW(int $InstanzID)` bzw. `float E3DC_GetPvPowerKw(int $InstanzID)`

Gibt die aktuelle PV-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetPvPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetPvPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte PV-Leistung der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int GetPvEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetPvEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die PV-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int E3DC_GetExtPowerW(int $InstanzID)` bzw. `float E3DC_GetExtPowerKw(int $InstanzID)`

Gibt die aktuelle Ext-Leistung (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Dieser Wert ist nur verfügbar, wenn eine externe Generatorquelle angeschlossen ist.


`int E3DC_GetExtPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetExtPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Ext-Leistung (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Dieser Wert ist nur verfügbar, wenn eine externe Generatorquelle angeschlossen ist.


`int GetExtEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetExtEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Ext-Energie (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int E3DC_GetProductionPowerW(int $InstanzID)` bzw. `float E3DC_GetProductionPowerKw(int $InstanzID)`

Gibt die aktuelle Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Sofern keine externe Generatorquelle angeschlossen ist, entspricht dieser Rückgabewert dem Rückgabewert von E3DC_GetPVPowerW() bzw. E3DC_GetPVPowerKw().


`int E3DC_GetProductionPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetProductionPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Sofern keine externe Generatorquelle angeschlossen ist, entspricht dieser Rückgabewert dem Rückgabewert von E3DC_GetPVPowerW() bzw. E3DC_GetPVPowerKw().


`int GetProductionEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetProductionEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Gesamt-Produktions-Energie (Ext-Energie + PV-Energie) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück. Sofern keine externe Generatorquelle angeschlossen ist, entspricht dieser Rückgabewert dem Rückgabewert von GetPvEnergyWh() bzw. GetPvEnergyKwh().


##### Wallbox

`int E3DC_GetWallboxPowerW(int $InstanzID)` bzw. `float E3DC_GetWallboxPowerKw(int $InstanzID)`

Gibt die aktuelle Wallbox-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetWallboxPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetWallboxPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Wallbox-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetWallboxPowerSolarW(int $InstanzID)` bzw. `float E3DC_GetWallboxPowerSolarKw(int $InstanzID)`

Gibt die aktuelle Wallbox-Solar-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int E3DC_GetWallboxPowerSolarIntervalW(int $InstanzID, int $timeIntervalInMinutes)` bzw. `float E3DC_GetWallboxPowerSolarIntervalKw(int $InstanzID, int $timeIntervalInMinutes)`

Gibt die gemittelte Wallbox-Solar-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über die letzen $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


`int GetWallboxEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetWallboxEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Wallbox-Energie (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


`int GetWallboxSolarEnergyWh(int $InstanzID, int $startTime, int $endTime)` bzw. `float GetWallboxSolarEnergyKwh(int $InstanzID, int $startTime, int $endTime)`

Gibt die Wallbox-Solar-Energie (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.



### 8. Versionshistorie

#### v1.0
- Feature Requests: #4 Tageswerte loggen
- pollCycle von ms auf Sekunden umgestellt
- intern umstrukturiert, interne Variablen umbenannt, interne Funktionen hinzugefügt,...
- Public Funktionen hinzugefügt: GetBatteryChargeEnergyWh(), GetBatteryChargeEnergyKwh(), GetBatteryDischargeEnergyWh(), GetBatteryDischargeEnergyKwh(), GetExtEnergyWh(), GetExtEnergyKwh(), GetProductionEnergyWh(), GetProductionEnergyKwh(), GetGridConsumptionEnergyWh(), GetGridConsumptionEnergyKwh(), GetGridFeedEnergyWh(), GetGridFeedEnergyKwh(), GetPvEnergyWh(), GetPvEnergyKwh(), GetHomeEnergyWh(), GetHomeEnergyKwh(), GetWallboxEnergyWh(), GetWallboxEnergyKwh(), GetWallboxSolarEnergyWh(), GetWallboxSolarEnergyKwh()
- Behobene Fehler: #6

#### v0.5
- Veraltete Funktionen (deutsche Funktionsnamen) entfernt
- Funktionen zur Mittelwertausgabe der Leistungen in W und kW für ein Zeit-Intervall in Minuten hinzugefügt für GetBatteryPower, GetExtPower, GetProductionPower, GetGridPower, GetPvPower, GetHomePower, GetWallboxPower und GetWallboxPowerSolar
- Funktion GetBatteryRange in Wh und kWh hinzugefügt und hierfür nötige Konfigurationsfelder Batteriekapazität und Notstromversorgung
- Fehlermeldung 201 bei fehlendem Archiv hinzugefügt
- Fix für: GetExtPowerW(), GetProductionPowerW(), GetWallboxPowerSolarW() and GetWallboxPowerW()
- Review-Findings für Stable Kanal eingearbeitet

#### v0.4
- Konfigurationsoption für Variablen-Logging hinzugefügt
- Logging für Leistungswerte in kW hinzugefügt
- Berechnung und Logging der Gesamt-Produktionsleistung mit Ext-Generator in W und kW hinzugefügt
- Public Funktionen in Englisch umbenannt (siehe Beschreibung unter Veraltete Funktionen)
- Public Funktionen hinzugefügt: E3DC_GetExtPowerW(), E3DC_GetExtPowerKw(), E3DC_GetProductionPowerW(), E3DC_GetProductionPowerKw(), E3DC_GetWallboxPowerW(), E3DC_GetWallboxPowerKw(), E3DC_GetWallboxPowerSolarW(), E3DC_GetWallboxPowerSolarKw()
- Fix für: Postfix war nicht bei allen Variablen-Profilen hinzugefügt
- Review-Findings für Stable Kanal eingearbeitet

#### v0.3
- Rechtschreibfehler ClientSocket behoben
- Swap LSW/MSW for 32Bit/64Bit aktiviert
- Modbus Geräte ID zum Konfigurationsformular hinzugefügt
- alte ClientSockets und Modbus-Gateways werden beim ändern der IP oder Port gelöscht
- Performance-Optimierungen
- Postfix zu allen Variablen-Profilen hinzugefügt
- Public Funktionen hinzugefügt: E3DC_GetAutarkie(), E3DC_GetEigenverbrauch(), E3DC_GetEigenverbrauch(), E3DC_GetBatterieLeistungW(), E3DC_GetBatterieLeistungKW(), E3DC_GetBatterieSOC(), E3DC_GetNetzLeistungW(), E3DC_GetNetzLeistungKW(), E3DC_GetPvLeistungW(), E3DC_GetPvLeistungKW(), E3DC_GetVerbrauchsLeistungW(), E3DC_GetVerbrauchsLeistungKW()
- Fix für Fehlerticket #1, #2
