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
Unterhalb der E3DC Instanz werden die Modbus Adressen erstellt.


### 2. Vorraussetzungen

- IP-Symcon ab Version 5.0
- Der E3DC Stromspeicher muss Modbus TCP unterstützen!
- Im Konfigurationsmenü des E3DC Stromspeichers muss unter Hauptmenü > Funktionen > Funktion Modbus > Feld Protokoll das Registermapping 'E3/DC Simple-Mode' aktiviert werden.


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
externer Einspeiser | Schalter, um die Variable für einen zusätzlichen Einspeiser (bspw. zweiter Wechselrichter, Stromgenerator, Brennstoffzelle,...) einzulesen
Wallbox 0 - 7 | 8 Schalter zum Aktivieren und Deaktivieren der Wallbox-Variablen
DC String Informationen | Schalter, um die Variablen für V, A und W der DC-Strings einzulesen (erst ab Release S10_2017_02 verfügbar).
Abfrage-Intervall	| Intervall (in ms) in welchem die Modbus-Adressen abgefragt werden sollen. Achtung: Abfrage-Intervall nicht zu klein wählen, um die Systemlast und auch die Archiv-Größe bei Logging nicht unnötig zu erhöhen! Default: 60000 (=60 Sekunden)


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
       |         |
       |         |

#### Profile

Name   | Typ
------ | -------
       |
       |

### 6. WebFront

Aktuell kein WebFront umgesetzt.


### 7. PHP-Befehlsreferenze

Aktuell keine PHP-Funktionen verfügbar.
