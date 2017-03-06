# NeuSol

Basic expense tracking software.

## Requirements

- Web Server (Tested on Apache 2.4.6)
- PHP 5.4
- MariaDB (Tested on 5.5.47)

The web server must have mod_rewrite enable and allow .htaccess overrides.


## Installation

1. Create a directory in your web server, e.g. `NeuSol`.  
   The name `NeuDev` is special for a test instance.  In that case the
   `nonprod-config.ini` is also read and needs to be configured.
2. git clone --recursive https://github.com/iliu-net/NeuSol.git NeuSol
3. If on a SELINUX system you may need to run:
   - scripts/secfg
4. Create a database and a database user
   - create database pfm;
   - GRANT ALL PRIVILEGES ON pfm.* TO 'pfm'@'localhost' IDENTIFIED BY 'mypass';
5. Configure this in config/config.ini
6. Initialize the database schema:
   - php index.php /restore scripts/init.sql
7. Point your web-browser to your app directory.

## ChangeLog

- 1.2.0: WIP
  - Backup improvements
- 1.1.0: First full production version
  - Added CategoryTypes
  - Updated FatFreeFramework
  - Summary Reporting
  - This version was working quite well for a few months
- 1.0.0 : First public release
