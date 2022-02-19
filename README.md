![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3C-green.svg)](https://community.symcon.de/t/ip-symcon-5-0-verfuegbar/47528)
[![GitHub Stars](https://img.shields.io/github/stars/Brovning/e3dc.svg?logo=github)](https://github.com/Brovning/e3dc/stargazers)
[![GitHub Release](https://img.shields.io/github/v/release/Brovning/e3dc.svg?logo=github)](https://github.com/Brovning/e3dc/releases)


# E3DC
IP-Symcon (IPS) Modul für E3DC Stromspeicher mit Modbus TCP Unterstützung (bspw. S10 mini, S10 E, S10 E Pro und Quattroporte).


### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Modul-Installation](#3-modul-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Dieses Modul erstellt anhand der Konfiguration der E3DC Instanz den nötigen Client Socket und das dazugehörige Modbus Gateway. Sofern diese bereits vorhanden sind, werden keine weiteren Client Sockets oder Modbus Gateways erstellt.
Unterhalb der E3DC Instanz werden die Modbus Adressen für den E3/DC-Simple Mode erstellt.


### 2. Voraussetzungen

* IP-Symcon ab Version 5.0
* Der E3DC Stromspeicher muss Modbus TCP unterstützen!
* Im Konfigurationsmenü des E3DC Stromspeichers muss je nach Version folgendes aktiviert werden:

entweder unter Hauptmenü > Funktionen > Funktion Modbus > Modbus und Modbus TCP mit Protokoll 'E3/DC Simple-Mode' aktivieren

![alt text](./docs/E3DC%20-%20Hauptmenue%20-%20Funktionen%20-%20Funktion%20Modbus.png?raw=true "E3DC > Hauptmenü > Funktionen > Funktion Modbus")

oder unter Hauptmenü > Smart-Funktionen > Smart Home > Modbus erst Modbus aktivieren, dann auf den Pfeil nach rechts klicken und Modbus TCP mit Protokoll 'E3/DC' aktivieren

![alt text](./docs/E3DC%20-%20Hauptmenue%20-%20%20Smart-Funktionen%20%20-%20Smart%20Home%20%20-%20Modbus%20-%20Modbus.jpg?raw=true "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Modbus > Modbus")

![alt text](./docs/E3DC%20-%20Hauptmenue%20-%20%20Smart-Funktionen%20%20-%20Smart%20Home%20%20-%20Modbus%20-%20Modbus%20TCP.jpg?raw=true "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Modbus > Modbus TCP")

und beim Quattroporte unter Hauptmenü > Smart-Funktionen > Smart Home > Funktion Modbus erst Modbus aktivieren, dann das Protokoll 'E3/DC' aktivieren

![alt text](./docs/E3DC_Quattroporte_-_Modbus_On.jpg?raw=true "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Funktion Modbus")

![alt text](./docs/E3DC_Quattroporte_-_Modbus_TCP.jpg?raw=true "E3DC > Hauptmenü > Smart-Funktionen > Smart Home > Funktion Modbus TCP")


### 3. Modul-Installation

#### Variante 1 (empfohlen): Modulverwaltung (Module Control)

Über das in der IP Symcon Console (unter Core Instances/Kerninstanzen) enthaltene Module Control die URL https://github.com/Brovning/e3dc manuell hinzufügen.

![alt text](./docs/symcon_module-control.jpg?raw=true "Symcon Console > Module Control > URL hinzufuegen")

Anschließend steht das Modul zur Verfügung und eine E3DC Instanz kann hinzugefügt werden.

Anmerkung: Per Klick auf das Zahnradsymbol kann in der Modulverwaltung ganz einfach für Tests zwischen "main" (stabile und getestete Versionen) und "beta" (enthält neuste Funktionen, welche teils getest und ggf. fehlerbehaftet sind) hin- und hergewechselt werden.


#### Variante 2: Module Store

Über den in der IP Symcon Console integrierten Module Store das 'E3DC'-Modul installieren:

![alt text](./docs/symcon_module-store.jpg?raw=true "Symcon > Module Store > 'E3DC'-Modul")

Anschließend steht das Modul zur Verfügung und eine E3DC Instanz kann hinzugefügt werden.


### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' ist das 'S10 mini'-, 'S10 E'-, 'S10 E Pro'- und 'Quattroporte'-Modul unter dem Hersteller 'E3DC' aufgeführt.

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
Open | Schalter zum Aktivieren und Deaktivieren der Instanz. Default: aus
IP | IP-Adresse des E3DC-Stromspeichers im lokalen Netzwerk (IPv4)
Port | Port, welcher im E3DC unter dem Menüpunkt Modbus angegeben wurde. Default: 502
Geräte Id | Modbus Geräte ID, welche im E3DC Menü gesetzt werden kann. Default: 1
Abfrage-Intervall	| Intervall (in Sekunden) in welchem die Modbus-Adressen abgefragt werden sollen. Achtung: Die Berechnung der Wirkarbeit (Wh/kWh) wird exakter, je kleiner der Abfrage-Intervall gewählt wird. Jedoch je kleiner der Abfrage-Intervall, umso höher die Systemlast und auch die Archiv-Größe bei Logging! Default: 60 Sekunden
Batteriekapazität | Welche Batteriekapazität in Kilo-Watt-Stunden (kWh) ist im E3DC installiert (bspw. 6.5, 10, 13, 15, 19.5). Diese Angabe wird optional benötigt, um die Reichweite der Batterie berechnen zu können. Default: 0
externer Einspeiser | Schalter, um die Variable für einen zusätzlichen Einspeiser (bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) einzulesen. Default: aus
Wallbox 0 - 7 | 8 Schalter zum Aktivieren und Deaktivieren der Wallbox-Variablen und je ein Textfeld zum Vergeben eines individuellen Variablen-Namens (bspw. Fertiggarage). Default: aus
Leistungsmesser 0 - 7 | 8 Schalter zum Aktivieren und Deaktivieren der Leistungsmesser/Powermeter Variablen und je ein Textfeld zum Vergeben eines individuellen Variablen-Namens (bspw. extWechselrichter). Default: aus
Notstromversorgung | Schalter, um angeben zu können, ob eine Notstromversorgung im E3DC verbaut ist. Default: aus
Notstrom-Reserve | Bei vorhandener Notstromversorgung kann am E3DC unter dem Menüpunkt Notstrom > Einstellungen eine Reserve angegeben werden, bis zu welcher die Batterie maximal entladen werden soll. Diese Angabe wird optional benötigt, um die Reichweite der Batterie korrekt berechnen zu können. Default: 0
MPP-Tracker 1 - 3 | Schalter, um die Variablen für V, A und W der bis zu 3 MPP-Tracker (entspricht String 1.1+1.2, 2.1+2.2 und 3.1+3.2) einzulesen und je MPP-Tracker ein Textfeld zum Vergeben eines individuellen Variablen-Namens (bspw. Sued) (verfügbar ab Release S10_2017_02). Default: aus
Wechselrichter 0 - 7 | 8 Schalter zum Aktivieren und Deaktivieren der Wechselrichter/Inverter Variablen und je ein Textfeld zum Vergeben eines individuellen Variablen-Namens (bspw. Ost). Default: aus
Variablen-Logging | Für welche Variablen soll das Logging aktiviert werden? Zur Auswahl stehen Leistungsvariablen in W, Leistungsvariablen in kW (bei Auswahl werden zusätzliche Variablen in kW erstellt), Batterie SOC (Ladezustand) in %, Autarkie in %, Eigenverbrauch in %
Tageswerte der Wirkarbeit | Sollen die Tageswerte in Wh oder kWh berechnet werden? Bei Auswahl werden zusätzliche Variablen in Wh oder kWh erstellt.
Wirkarbeit loggen | Sollen für die Tageswerte in Wh oder kWh das Logging aktiviert werden? Nur möglich, sofern zuvor die Tageswerte-Berechnung auch aktiviert wurde!


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusregister
Der E3/DC-Simple Mode ermöglicht den einfachen und schnellen Zugriff auf die wichtigsten und am häufigsten benötigten Daten.

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40003 | 1 | 3 | Register | UInt16 | | Anzahl unterstützter Register
40004 | 16 | 3 | Hersteller | String | | Hersteller: 'E3/DC GmbH'
40020 | 16 | 3 | Modell | String | | Modell | z. B.: 'S10 E AIO'
40036 | 16 | 3 | Seriennummer | String | | Seriennummer | z. B.: 'S10-12345678912'
40052 | 16 | 3 | Firmware | String | | S10 Firmware Release | z. B.: 'S10-2015_08'
40068 | 2 | 3 | PV-Leistung | Int32 | W | Photovoltaik-Leistung in Watt
40070 | 2 | 3 | Batterie-Leistung | Int32 | W | Batterie-Leistung in Watt (negative Werte = Entladung)
40072 | 2 | 3 | Verbrauchs-Leistung | Int32 | W | Hausverbrauchs-Leistung in Watt
40074 | 2 | 3 | Netz-Leistung | Int32 | W | Leistung am Netzübergabepunkt in Watt (negative Werte = Einspeisung)
40082 | 1 | 3 | Autarkie-Eigenverbrauch | Uint8+Uint8 |  | Autarkie und Eigenverbrauch in Prozent
40083 | 1 | 3 | Batterie-SOC | Uint16 | % | Batterie-SOC in Prozent
40084 | 1 | 3 | Emergency-Power | Uint16 |  | Emergency-Power Status (separates Profil vorhanden)
40085 | 1 | 3 | EMS-Status | Uint16 | Bitfield | EMS-Status Bits (werden einzeln ausgewertet)
40086 | 1 | 3 | EMS Remote Control | int16 |  | EMS Remote Control
40087 | 1 | 3 | EMS CTRL | Uint16 |  | EMS CTRL
40137 | 1 | 3 | SG Ready-Status | Uint16 |  | SG Ready-Status


##### optional: externer Einspeiser

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40076 | 2 | 3 | Ext-Leistung | Int32 | W | Leistung aller zusätzlichen Einspeiser in Watt


##### optional: Wallbox 0 - 7

StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40078 | 2 | 3 | Wallbox-Leistung | Int32 | W | Leistung der Wallbox in Watt
40080 | 2 | 3 | Wallbox-Solarleistung | Int32 | W | Solarleistung, die von der Wallbox genutzt wird in Watt
40088 | 1 | 6 | WallBox_0_CTRL | Uint16 | Bitfield | Bits der WallBox 0 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_1_CTRL | Uint16 | Bitfield | Bits der WallBox 1 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_2_CTRL | Uint16 | Bitfield | Bits der WallBox 2 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_3_CTRL | Uint16 | Bitfield | Bits der WallBox 3 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_4_CTRL | Uint16 | Bitfield | Bits der WallBox 4 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_5_CTRL | Uint16 | Bitfield | Bits der WallBox 5 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_6_CTRL | Uint16 | Bitfield | Bits der WallBox 6 (werden einzeln ausgewertet)
40089 | 1 | 6 | WallBox_7_CTRL | Uint16 | Bitfield | Bits der WallBox 7 (werden einzeln ausgewertet)


##### optional: Leistungsmesser/Powermeter 0 - 7
StartRegister | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
40105 | 1 | 3 | Powermeter_0 | Uint16 | Powermeter | Leistungsmessertyp
40106 | 1 | 3 | Powermeter_0_L1 | Int16 | W | Phasenleistung in Watt L1
40107 | 1 | 3 | Powermeter_0_L2 | Int16 | W | Phasenleistung in Watt L2
40108 | 1 | 3 | Powermeter_0_L3 | Int16 | W | Phasenleistung in Watt L3
40109 | 1 | 3 | Powermeter_1 | Uint16 | Powermeter | Leistungsmessertyp
40110 | 1 | 3 | Powermeter_1_L1 | Int16 | W | Phasenleistung in Watt L1
40111 | 1 | 3 | Powermeter_1_L2 | Int16 | W | Phasenleistung in Watt L2
40112 | 1 | 3 | Powermeter_1_L3 | Int16 | W | Phasenleistung in Watt L3
40113 | 1 | 3 | Powermeter_2 | Uint16 | Powermeter | Leistungsmessertyp
40114 | 1 | 3 | Powermeter_2_L1 | Int16 | W | Phasenleistung in Watt L1
40115 | 1 | 3 | Powermeter_2_L2 | Int16 | W | Phasenleistung in Watt L2
40116 | 1 | 3 | Powermeter_2_L3 | Int16 | W | Phasenleistung in Watt L3
40117 | 1 | 3 | Powermeter_3 | Uint16 | Powermeter | Leistungsmessertyp
40118 | 1 | 3 | Powermeter_3_L1 | Int16 | W | Phasenleistung in Watt L1
40119 | 1 | 3 | Powermeter_3_L2 | Int16 | W | Phasenleistung in Watt L2
40120 | 1 | 3 | Powermeter_3_L3 | Int16 | W | Phasenleistung in Watt L3
40121 | 1 | 3 | Powermeter_4 | Uint16 | Powermeter | Leistungsmessertyp
40122 | 1 | 3 | Powermeter_4_L1 | Int16 | W | Phasenleistung in Watt L1
40123 | 1 | 3 | Powermeter_4_L2 | Int16 | W | Phasenleistung in Watt L2
40124 | 1 | 3 | Powermeter_4_L3 | Int16 | W | Phasenleistung in Watt L3
40125 | 1 | 3 | Powermeter_5 | Uint16 | Powermeter | Leistungsmessertyp
40126 | 1 | 3 | Powermeter_5_L1 | Int16 | W | Phasenleistung in Watt L1
40127 | 1 | 3 | Powermeter_5_L2 | Int16 | W | Phasenleistung in Watt L2
40128 | 1 | 3 | Powermeter_5_L3 | Int16 | W | Phasenleistung in Watt L3
40129 | 1 | 3 | Powermeter_6 | Uint16 | Powermeter | Leistungsmessertyp
40130 | 1 | 3 | Powermeter_6_L1 | Int16 | W | Phasenleistung in Watt L1
40131 | 1 | 3 | Powermeter_6_L2 | Int16 | W | Phasenleistung in Watt L2
40132 | 1 | 3 | Powermeter_6_L3 | Int16 | W | Phasenleistung in Watt L3
40133 | 1 | 3 | Powermeter_7 | Uint16 | Powermeter | Leistungsmessertyp
40134 | 1 | 3 | Powermeter_7_L1 | Int16 | W | Phasenleistung in Watt L1
40135 | 1 | 3 | Powermeter_7_L2 | Int16 | W | Phasenleistung in Watt L2
40136 | 1 | 3 | Powermeter_7_L3 | Int16 | W | Phasenleistung in Watt L3

Je Leistungsmesser X (wobei X von 0 bis 7) wird der Leistungsmessertyp in Powermeter_X wie folgt angegeben:
Typ | Bezeichnung | Hinweise
--- | ----------- | --------
1 | Wurzelleistungsmesser | Dies ist der Regelpunkt des Systems. Der Regelpunkt entspricht üblicherweise dem Hausanschlusspunkt.
2 | Externe Produktion | externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...
3 | Zweirichtungszähler
4 | Externer Verbrauch
5 | Farm
6 | - | Wird nicht verwendet
7 | Wallbox
8 | Externer Leistungsmesser Farm
9 | Datenanzeige | Wird nicht in die Regelung eingebunden, sondern dient nur der Datenaufzeichnung des Kundenportals.
10 | Regelungsbypass | Die gemessene Leistung wird nicht in die Batterie geladen, aus der Batterie entladen.


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


##### optional: Wechselrichter 0 - 7
Hinweis: Die folgenden Register können ab dem Release S10_2022_02 genutzt werden!

Je Wechselrichter X (wobei X von 0 bis 7) werden nachfolgende Register eingelesen.

Offset | Size | FunctionCode | Name | Type | Units | Description
------------- | ---- | ------------ | ---- | ---- | ----- | -----------
~~0~~ 1 | 2 | 3 | WR_X_Scheinleistung_L1 | ~~int32~~ int16 | W | Scheinleistung in Watt L1
~~2~~ 3 | 2 | 3 | WR_X_Scheinleistung_L2 | ~~int32~~ int16 | W | Scheinleistung in Watt L2
~~4~~ 5 | 2 | 3 | WR_X_Scheinleistung_L2 | ~~int32~~ int16 | W | Scheinleistung in Watt L2
~~6~~ 7 | 2 | 3 | WR_X_Wirkleistung_L1 | ~~int32~~ int16 | W | Wirkleistung in Watt L1
~~8~~ 9 | 2 | 3 | WR_X_Wirkleistung_L2 | ~~int32~~ int16 | W | Wirkleistung in Watt L2
~~10~~ 11 | 2 | 3 | WR_X_Wirkleistung_L3 | ~~int32~~ int16 | W | Wirkleistung in Watt L3
~~12~~ 13 | 2 | 3 | WR_X_Blindleistung_L1 | ~~int32~~ int16 | W | Blindleistung in Watt L1
~~14~~ 15 | 2 | 3 | WR_X_Blindleistung_L2 | ~~int32~~ int16 | W | Blindleistung in Watt L2
~~16~~ 17 | 2 | 3 | WR_X_Blindleistung_L3 | ~~int32~~ int16 | W | Blindleistung in Watt L3
18 | 1 | 3 | WR_X_AC-Spannung_L1 | int16 | V | AC-Spannung in Volt L1
19 | 1 | 3 | WR_X_AC-Spannung_L2 | int16 | V | AC-Spannung in Volt L2
20 | 1 | 3 | WR_X_AC-Spannung_L3 | int16 | V | AC-Spannung in Volt L3
21 | 1 | 3 | WR_X_AC-Strom_L1 | int16 | A | AC-Strom in Ampere L1
22 | 1 | 3 | WR_X_AC-Strom_L2 | int16 | A | AC-Strom in Ampere L2
23 | 1 | 3 | WR_X_AC-Strom_L3 | int16 | A | AC-Strom in Ampere L3
24 | 1 | 3 | WR_X_Phasen-Frequenz_L1 | int16 | Hz | Phasen-Frequenz in Hertz L1
25 | 1 | 3 | WR_X_DC-Leistung_L1 | int16 | W | DC-Leistung in Watt L1
26 | 1 | 3 | WR_X_DC-Leistung_L2 | int16 | W | DC-Leistung in Watt L2
27 | 1 | 3 | WR_X_DC-Leistung_L3 | int16 | W | DC-Leistung in Watt L3 (wird nicht verwendet)
28 | 1 | 3 | WR_X_DC-Spannung_L1 | int16 | V | DC-Spannung in Volt L1
29 | 1 | 3 | WR_X_DC-Spannung_L2 | int16 | V | DC-Spannung in Volt L2
30 | 1 | 3 | WR_X_DC-Spannung_L3 | int16 | V | DC-Spannung in Volt L3 (wird nicht verwendet)
31 | 1 | 3 | WR_X_DC-Strom_L1 | int16 | A | DC-Strom in Ampere L1
32 | 1 | 3 | WR_X_DC-Strom_L2 | int16 | A | DC-Strom in Ampere L2
33 | 1 | 3 | WR_X_DC-Strom_L3 | int16 | A | DC-Strom in Ampere L3 (wird nicht verwendet)


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
E3DC.Electricity.Int | Integer
E3DC.Emergency-Power.Int | Integer
E3DC.Powermeter.Int | Integer
E3DC.SG-Ready-Status.Int | Integer
E3DC.Volt.Int | Integer
E3DC.Watt.Int | Integer


### 6. WebFront

Aktuell kein WebFront umgesetzt.


### 7. PHP-Befehlsreferenz

#### Empfehlung
Sofern nur eine Instanz des E3DC-Moduls im Einsatz ist, sollte die $InstanzID wie folgt dynamisch ermittelt werden und nicht statisch gesetzt werden, da somit ein Löschen und Neuinstallieren der E3DC-Instanz keine Auswirkung auf andere Skripte hat:

```PHP
$InstanzID = IPS_GetInstanceListByModuleID("{C9508720-B23D-B37A-B5C2-97B607221CE1}")[0];
```


#### Funktionen

##### Allgemeines

```PHP
int E3DC_GetAutarky(int $InstanzID)
```

Gibt den aktuellen Autarkie-Wert der E3DC-Instanz $InstanzID als Integer in Prozent zurück.


```PHP
int E3DC_GetSelfConsumption(int $InstanzID)
```

Gibt den aktuellen Eigenverbrauch-Wert der E3DC-Instanz $InstanzID als Integer in Prozent zurück.


```PHP
bool E3DC_IsDerating(int $InstanzID)
```

Gibt den aktuellen Abregelungs-Status der E3DC-Instanz $InstanzID als Bool zurück.


##### Batterie

```PHP
int E3DC_GetBatterySoc(int $InstanzID)
```

Gibt den aktuelle Batterie-State-Of-Charge (SOC) der E3DC-Instanz $InstanzID als Integer in Prozent (%) zurück.


```PHP
int E3DC_GetBatteryPowerW(int $InstanzID)
```
bzw.
```PHP
float E3DC_GetBatteryPowerKw(int $InstanzID)
```

Gibt die aktuelle Batterie-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetBatteryPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
```
bzw.

```PHP
float E3DC_GetBatteryPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Batterie-Leistung der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int GetBatteryChargeEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetBatteryChargeEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Batterie-Lade-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int GetBatteryDischargeEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetBatteryDischargeEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Batterie-Entlade-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int E3DC_GetBatteryRangeWh(int $InstanzID)
```
bzw.

```PHP
float E3DC_GetBatteryRangeKwh(int $InstanzID)
```

Gibt die aktuelle Batterie-Reichweite der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Dieser Wert wird berechnet aus dem SOC, der in der Instanz angegebenen Batterie-Kapazität und der maximal nutzbaren Entladetiefe. Sofern eine Notstrom-Funktion installiert ist, wird auch die in der Instanz angegebene Notstromreserve berücksichtigt.


```PHP
bool E3DC_IsChargingLocked(int $InstanzID)
```

Gibt den aktuellen Batterie-Lade-Status der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true, wird das Batterieladen bspw. durch die standortbezogene Wetterprognose oder durch eine manuelle Vorgabe gesperrt.


```PHP
bool E3DC_IsDischargingLocked(int $InstanzID)
```

Gibt den aktuellen Batterie-Entlade-Status der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true, wird das Batterieentladen bspw. durch eine manuelle Vorgabe gesperrt.



##### PV, ExtGen und Haus

```PHP
int E3DC_GetGridPowerW(int $InstanzID)
```
bzw.

```PHP
float E3DC_GetGridPowerKw(int $InstanzID)
```

Gibt die aktuelle Netz-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetGridPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
```
bzw. 
```PHP
float E3DC_GetGridPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Netz-Leistung der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int GetGridConsumptionEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetGridConsumptionEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Netz-Bezugs-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int GetGridFeedEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetGridFeedEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Netz-Einspeise-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int E3DC_GetHomePowerW(int $InstanzID)
```
bzw. 
```PHP
float E3DC_GetHomePowerKw(int $InstanzID)
```

Gibt die aktuelle Verbrauchs-Leistung (Hausverbrauch) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetHomePowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
```
bzw. 
```PHP
float E3DC_GetHomePowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Verbrauchs-Leistung (Hausverbrauch) der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int GetHomeEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetHomeEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Haus-Verbrauchs-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int E3DC_GetPvPowerW(int $InstanzID)
```
bzw. 
```PHP
float E3DC_GetPvPowerKw(int $InstanzID)
```

Gibt die aktuelle PV-Leistung der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetPvPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
``` 
bzw. 
```PHP
float E3DC_GetPvPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte PV-Leistung der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int GetPvEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetPvEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die PV-Energie der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int E3DC_GetExtPowerW(int $InstanzID)
```
bzw. 
```PHP
float E3DC_GetExtPowerKw(int $InstanzID)
```

Gibt die aktuelle Ext-Leistung (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Dieser Wert ist nur verfügbar, wenn eine externe Generatorquelle angeschlossen ist.


```PHP
int E3DC_GetExtPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
```
bzw. 
```PHP
float E3DC_GetExtPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Ext-Leistung (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Dieser Wert ist nur verfügbar, wenn eine externe Generatorquelle angeschlossen ist.


```PHP
int GetExtEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetExtEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Ext-Energie (externe Generatorquelle bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int E3DC_GetProductionPowerW(int $InstanzID)
```
bzw. 
```PHP
float E3DC_GetProductionPowerKw(int $InstanzID)
```

Gibt die aktuelle Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Sofern keine externe Generatorquelle angeschlossen ist, entspricht dieser Rückgabewert dem Rückgabewert von E3DC_GetPVPowerW() bzw. E3DC_GetPVPowerKw().


```PHP
int E3DC_GetProductionPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
```
bzw. 
```PHP
float E3DC_GetProductionPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Gesamt-Produktions-Leistung (Ext-Leistung + PV-Leistung) der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück. Sofern keine externe Generatorquelle angeschlossen ist, entspricht dieser Rückgabewert dem Rückgabewert von E3DC_GetPVPowerW() bzw. E3DC_GetPVPowerKw().


```PHP
int GetProductionEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetProductionEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Gesamt-Produktions-Energie (Ext-Energie + PV-Energie) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück. Sofern keine externe Generatorquelle angeschlossen ist, entspricht dieser Rückgabewert dem Rückgabewert von GetPvEnergyWh() bzw. GetPvEnergyKwh().


##### Wallbox

```PHP
int E3DC_GetWallboxPowerW(int $InstanzID)
```
bzw. 
```PHP
float E3DC_GetWallboxPowerKw(int $InstanzID)
```

Gibt die aktuelle Wallbox-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetWallboxPowerIntervalW(int $InstanzID, int $timeIntervalInMinutes)
``` 
bzw. 
```PHP
float E3DC_GetWallboxPowerIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Wallbox-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetWallboxPowerSolarW(int $InstanzID)
```
bzw. 
```PHP
float E3DC_GetWallboxPowerSolarKw(int $InstanzID)
```

Gibt die aktuelle Wallbox-Solar-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int E3DC_GetWallboxPowerSolarIntervalW(int $InstanzID, int $timeIntervalInMinutes)
```
bzw. 
```PHP
float E3DC_GetWallboxPowerSolarIntervalKw(int $InstanzID, int $timeIntervalInMinutes)
```

Gibt die gemittelte Wallbox-Solar-Leistung (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über die letzten $timeIntervalInMinutes Minuten als Integer in Watt (W) bzw. als Float in Kilo-Watt (kW) zurück.


```PHP
int GetWallboxEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetWallboxEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Wallbox-Energie (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
int GetWallboxSolarEnergyWh(int $InstanzID, int $startTime, int $endTime)
```
bzw. 
```PHP
float GetWallboxSolarEnergyKwh(int $InstanzID, int $startTime, int $endTime)
```

Gibt die Wallbox-Solar-Energie (aller Wallboxen in Summe) der E3DC-Instanz $InstanzID über den Zeitraum von $startTime bis $endTime (Unix-Time) als Integer in Watt-Stunden (Wh) bzw. als Float in Kilo-Watt-Stunden (kWh) zurück.


```PHP
bool E3DC_GetWallboxAvailable(int $InstanzID, int $WallboxId)
```

Gibt die Verfügbarkeit der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist die Wallbox verfügbar.


```PHP
bool E3DC_GetWallboxSolarmode(int $InstanzID, int $WallboxId)
```

Gibt den Status des Solarbetriebs der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der Solarbetrieb aktiviert.


```PHP
bool E3DC_GetWallboxChargingLocked(int $InstanzID, int $WallboxId)
```

Gibt den Ladesperr-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist das Laden gesperrt.


```PHP
bool E3DC_GetWallboxCharging(int $InstanzID, int $WallboxId)
```

Gibt den Lade-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true wird gerade geladen.


```PHP
bool E3DC_GetWallboxType2Locked(int $InstanzID, int $WallboxId)
```

Gibt den Typ2-Verriegelungs-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der Typ2-Stecker verriegelt.


```PHP
bool E3DC_GetWallboxType2Connected(int $InstanzID, int $WallboxId)
```

Gibt den Typ2-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist ein Typ2-Stecker angesteckt.


```PHP
bool E3DC_GetWallboxSchukoActivated(int $InstanzID, int $WallboxId)
```

Gibt den Schuko-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der Schuko-Anschluss aktiviert.


```PHP
bool E3DC_GetWallboxSchukoConnected(int $InstanzID, int $WallboxId)
```

Gibt den Schuko-Stecker-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist ein Schuko-Stecker am Anschluss eingesteckt und es wird Strom bezogen.


```PHP
bool E3DC_GetWallboxSchukoLocked(int $InstanzID, int $WallboxId)
```

Gibt den Schuko-Sperr-Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der Schuko-Anschluss gesperrt.


```PHP
bool E3DC_GetWallbox16A1Phase(int $InstanzID, int $WallboxId)
```

Gibt den 1-phasigen 16 A Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der 1-phasige Lademodus mit 16A aktiviert (3,5 kW Ladeleistung).


```PHP
bool E3DC_GetWallbox16A3Phase(int $InstanzID, int $WallboxId)
```

Gibt den 3-phasigen 16 A Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der 3-phasige Lademodus mit 16A aktiviert (11 kW Ladeleistung).


```PHP
bool E3DC_GetWallbox32A3Phase(int $InstanzID, int $WallboxId)
```

Gibt den 3-phasigen 32 A Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der 3-phasige Lademodus mit 32A aktiviert (22 kW Ladeleistung).


```PHP
bool E3DC_GetWallbox1Phase(int $InstanzID, int $WallboxId)
```

Gibt den Phasen Status der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als Bool zurück. Ist der Wert true ist der 1-phasige Lademodus aktiviert und bei false ist der 3-phasige Lademodus aktiviert.


```PHP
bool E3DC_SetWallboxSolarmode(int $InstanzID, int $WallboxId, bool $SetValue)
```

Setzt den Solar-Modus der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als $SetValue Bool. Wird der Wert true gesetzt, wird der Solar-Lade-Modus aktiviert. Zurückgegeben wird der Rückgabewert des Modbus-Befehls als Bool. Ist der Wert true war das Schreiben erfolgreich.


```PHP
bool E3DC_SetWallbox1Phase(int $InstanzID, int $WallboxId, bool $SetValue)
```

Setzt den 1-phasigen Lademodus der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als $SetValue Bool. Wird der Wert true gesetzt, wird der 1-phasige Lademodus aktiviert (bspw. zum Herunterregeln der Ladeleistung). Zurückgegeben wird der Rückgabewert des Modbus-Befehls als Bool. Ist der Wert true war das Schreiben erfolgreich.

! ! ! ACHTUNG ! ! !
E3DC_SetWallboxChargingLocked() und E3DC_SetWallboxSchukoActivated() funktionieren leider nicht!
Implementierung laut E3DC-Support fehlerhaft. Einzige Antwort, die ich hierzu nach fast 3 Monaten erhalten habe:
"Wenn ein einzelnes Bit gesetzt werden soll, dann ist der Vorgang: Lesen des Registers, ändern des Bits in dem Wert des Registers, dann zurückschreiben des Registers."
Frage meinerseits: Weshalb soll es hier nicht funktionieren und bei den anderen beiden Wallbox WriteFunctions schon ?!?!
--> nie mehr eine Rückmeldung erhalten...
Der E3DC Support ist aus meinen Erfahrungen mehr als mangelhaft.
Würde mich freuen, wenn jemand den Fehler in meiner Implementierung finden würde!


```PHP
bool E3DC_SetWallboxChargingLocked(int $InstanzID, int $WallboxId, bool $SetValue)
```

Deaktiviert!

Sperrt das Laden der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als $SetValue Bool. Wird der Wert true gesetzt, wird das Laden gesperrt. Zurückgegeben wird der Rückgabewert des Modbus-Befehls als Bool. Ist der Wert true war das Schreiben erfolgreich.


```PHP
bool E3DC_SetWallboxSchukoActivated(int $InstanzID, int $WallboxId, bool $SetValue)
```

Deaktiviert!

Aktiviert die Schuko-Steckdose der Wallbox $WallboxId (von 0 bis 7) der E3DC-Instanz $InstanzID als $SetValue Bool. Wird der Wert true gesetzt, wird die Schuko-Steckdose aktiviert. Zurückgegeben wird der Rückgabewert des Modbus-Befehls als Bool. Ist der Wert true war das Schreiben erfolgreich.



### 8. Versionshistorie

#### v1.6, 19.02.2022
- Register für Wechselrichter hinzugefügt (ab SW-Version >=S10_2022_02) 
- Maximal nutzbare Batteriekapazität konfigurierbar (Alterung kann berücksichtigt werden)
- DC-String Information einzeln aktivierbar
- kW Wert zu DC-String Information hinzugefügt
- Layout des Konfigurationsformulars überarbeitet

#### v1.5, 20.01.2022
- bugfix #12: Wrong Modbus datatype used for powermeter values in W
- bugfix #13: Factor of 0.01 not considered for DC-String current
- String Modbus instances added (Firmware, Hersteller, Modell)
- added profile for SG-Ready-Status
- renamed String 1,2,3 to MPP-Tracker 1,2,3
- delete unused variable profiles: removed profile Ampere.Int
- Revert "Nutzbare Batteriekapazität von 90% auf 80% reduziert"
- added more debug output (e.g. if var profile cannot be set)

#### v1.4, 04.10.2021
- Bugfix #9: "Die Instanz hat noch ungespeicherte Änderungen" auch wenn nichts geändert wurde

#### v1.3, 03.10.2021
- Feature Request #10: Individuelle Benennung von DC Strings, Wallboxen und Leistungsmesser
- Leistungsmesser 7 und SG Ready entsprechend Modbus-Doku v1.8 hinzugefügt
- Debug-Ausgaben für Modul-Debugging hinzugefügt
- Eingabefelder werden nun auf Plausibilität überprüft (bspw. IP, Geräte ID,...)
- Nutzbare Batteriekapazität von 90% auf 80% reduziert, da dies mehr der Realität entspricht

#### v1.2, 30.10.2020
- Fix für #7: Fehlermeldungen mit IPS 5.5 - Trying to access array offset on value of type bool
- E3DC_SetWallboxChargingLocked() und E3DC_SetWallboxSchukoActivated() funktionieren leider nicht! Ob Fehler bei E3DC oder in meiner Implementierung ist noch unklar. Trotzdem aktiviert für Tests.

#### v1.1, 09.08.2020
- Quattroporte hinzugefügt
- Powermeter (Leistungsmesser) hinzugefügt
- Variablenprofil für Leistungsmesser hinzugefügt
- Public Funktionen hinzugefügt: Wallbox Statusfunktionen hinzugefügt (E3DC_GetWallboxAvailable(), E3DC_GetWallboxSolarmode(), E3DC_GetWallboxChargingLocked(), E3DC_GetWallboxCharging(), E3DC_GetWallboxType2Locked(), E3DC_GetWallboxType2Connected(), E3DC_GetWallboxSchukoActivated(), E3DC_GetWallboxSchukoConnected(), E3DC_GetWallboxSchukoLocked(), E3DC_GetWallbox16A1Phase(), E3DC_GetWallbox16A3Phase(), E3DC_GetWallbox32A3Phase(), E3DC_GetWallbox1Phase()), E3DC Statusfunktionen hinzugefügt (E3DC_IsDerating(), E3DC_IsChargingLocked(), E3DC_IsDischargingLocked()), Wallbox Schreib-Funktionen hinzugefügt (E3DC_SetWallboxSolarmode(), E3DC_SetWallbox1Phase())
- E3DC_SetWallboxChargingLocked() und E3DC_SetWallboxSchukoActivated() funktionieren leider nicht! Ob Fehler bei E3DC oder in meiner Implementierung ist noch unklar...
- intern umstrukturiert, interne Funktionen hinzugefügt,...

#### v1.0, 04.04.2020
- Feature Requests: #4 Tageswerte loggen
- pollCycle von ms auf Sekunden umgestellt
- intern umstrukturiert, interne Variablen umbenannt, interne Funktionen hinzugefügt,...
- Public Funktionen hinzugefügt: GetBatteryChargeEnergyWh(), GetBatteryChargeEnergyKwh(), GetBatteryDischargeEnergyWh(), GetBatteryDischargeEnergyKwh(), GetExtEnergyWh(), GetExtEnergyKwh(), GetProductionEnergyWh(), GetProductionEnergyKwh(), GetGridConsumptionEnergyWh(), GetGridConsumptionEnergyKwh(), GetGridFeedEnergyWh(), GetGridFeedEnergyKwh(), GetPvEnergyWh(), GetPvEnergyKwh(), GetHomeEnergyWh(), GetHomeEnergyKwh(), GetWallboxEnergyWh(), GetWallboxEnergyKwh(), GetWallboxSolarEnergyWh(), GetWallboxSolarEnergyKwh()
- Behobene Fehler: #6

#### v0.5, 20.11.2019
- Veraltete Funktionen (deutsche Funktionsnamen) entfernt
- Funktionen zur Mittelwertausgabe der Leistungen in W und kW für ein Zeit-Intervall in Minuten hinzugefügt für GetBatteryPower, GetExtPower, GetProductionPower, GetGridPower, GetPvPower, GetHomePower, GetWallboxPower und GetWallboxPowerSolar
- Funktion GetBatteryRange in Wh und kWh hinzugefügt und hierfür nötige Konfigurationsfelder Batteriekapazität und Notstromversorgung
- Fehlermeldung 201 bei fehlendem Archiv hinzugefügt
- Fix für: GetExtPowerW(), GetProductionPowerW(), GetWallboxPowerSolarW() and GetWallboxPowerW()
- Review-Findings für Stable Kanal eingearbeitet

#### v0.4, 28.11.2019
- Konfigurationsoption für Variablen-Logging hinzugefügt
- Logging für Leistungswerte in kW hinzugefügt
- Berechnung und Logging der Gesamt-Produktionsleistung mit Ext-Generator in W und kW hinzugefügt
- Public Funktionen in Englisch umbenannt (siehe Beschreibung unter Veraltete Funktionen)
- Public Funktionen hinzugefügt: E3DC_GetExtPowerW(), E3DC_GetExtPowerKw(), E3DC_GetProductionPowerW(), E3DC_GetProductionPowerKw(), E3DC_GetWallboxPowerW(), E3DC_GetWallboxPowerKw(), E3DC_GetWallboxPowerSolarW(), E3DC_GetWallboxPowerSolarKw()
- Fix für: Postfix war nicht bei allen Variablen-Profilen hinzugefügt
- Review-Findings für Stable Kanal eingearbeitet

#### v0.3, 09.11.2019
- Rechtschreibfehler ClientSocket behoben
- Swap LSW/MSW for 32Bit/64Bit aktiviert
- Modbus Geräte ID zum Konfigurationsformular hinzugefügt
- alte ClientSockets und Modbus-Gateways werden beim Ändern der IP oder Port gelöscht
- Performance-Optimierungen
- Postfix zu allen Variablen-Profilen hinzugefügt
- Public Funktionen hinzugefügt: E3DC_GetAutarkie(), E3DC_GetEigenverbrauch(), E3DC_GetEigenverbrauch(), E3DC_GetBatterieLeistungW(), E3DC_GetBatterieLeistungKW(), E3DC_GetBatterieSOC(), E3DC_GetNetzLeistungW(), E3DC_GetNetzLeistungKW(), E3DC_GetPvLeistungW(), E3DC_GetPvLeistungKW(), E3DC_GetVerbrauchsLeistungW(), E3DC_GetVerbrauchsLeistungKW()
- Fix für Fehlerticket #1, #2

#### v0.2, 02.11.2019
- first release ready for public testing

#### v0.1, 31.10.2019
- initial release
