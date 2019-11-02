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

Einschränkung: Aktuell kann nur eine Instanz des E3DC-Moduls erstellt werden!


### 2. Vorraussetzungen

- IP-Symcon ab Version 5.0


### 3. Software-Installation

* Über den Module Store das 'E3DC'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen: https://github.com/Brovning/e3dc


### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' ist das 'S10 mini'-, 'S10 E'- und 'S10 E Pro'-Modul unter dem Hersteller 'E3DC' aufgeführt.

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
IP | IP-Adresse des E3DC-Stromspeichers im lokalen Netzwerk
Port | Port, welcher im E3DC unter dem Menüpunkt Modbus angegeben wurde. Default: 502
... | ...


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
