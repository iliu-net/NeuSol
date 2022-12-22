# NeuSol

![status](https://github.com/iliu-net/NeuSol/actions/workflows/static-checks.yaml/badge.svg)
![status](https://github.com/iliu-net/NeuSol/actions/workflows/release.yaml/badge.svg)

Basic expense tracking software.  This is my first project using the [Fat-Free Framework][3].

It uses the [Fat Free Framework][f3], a lightweight PHP framework, and follows the example in [Fat-Free CRUD with MVC][f3crud]

It also makes use of the following components:

- [parsecsv-for-php][parsecsv]: For parsing csv file in the Amex importing module
- [sorttable][sorttable]: HTML5/Javascript table with sorting capabilities.  It is quite handy
  when working with data.
- [phpGraph][phpgraph]: SVG charts from PHP data
- [Pikaday][pikaday]: Calendar/date picker control
- [moment.js][moment]: Dependancy for [Pikaday][pikaday] so that it uses the fight date format.

The code is structured as follows:

- app : Actual application code.  Follows an MVC arrangement.
  - classes : Misc classes that are autoloaded by the Framework.
    1. Fm : Form shortcuts
    2. phpGraph : PHP SVG graph class
    3. Sc : Misc. Shorcuts (to use in view templates and reduce typing)
  - models : Data Access Objects.
  - controllers : Main classes that do stuff.  Some classes worth mentioning:
    1. AppMain : Main window (shows the welcome dashboard)
    2. BackupController : Handles backups and restores
    3. ImportController : Manages bulk imports
    4. RptSummary : Generate year summary reports
  - views : Mostly PHP templates as HTML with embedded <?= PHP ?> codes.  But also JavaScript is
    in there.
- config : [FatFree][f3] configuration files.
- data : contains data that will be editable from the web interface.
  - rules.php : Used to assign categories to posting date.
  - triggers.php : Used to generate postings automatically (i.e. to balance accounts)
  - Importers/ : Contains classes used when importing bank data
- lib : symlink to PHP libraries
- scripts : utility scripts for bootstraping and maintenance
  - init.sql : Use `php index.php /restore scripts/init.sql` to initialize the MySQL database.
  - newtest : `./scripts/newtest ../NeuSol ../NeuDev` to copy production to test.
  - secfg : Apply `selinux` contexts and UNIX permissions.
  - upgrade.sql : Upgrade schema.
- submodules : where all the git modules are registered.
- ui : static files that used for rendering the user interface.

## Requirements

- Web Server (Tested on Apache 2.4.52)
- PHP (Tested on 7.4.26)
- MariaDB (Tested on 10.3.32)

The web server must have `mod_rewrite` enabled and allow `.htaccess` overrides.

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
   - php index.php /orestore scripts/init.sql
7. Point your web-browser to your app directory.

## Customizations

- More in-depth business rules can be created in:
  - data/rules.php
  - data/triggers.php
- Custom importers can be added in:
  - data/Importers/*.php


## TODO

- Pending
  - The DAO for postings is not a clean encapsulation and uses SQL direct statements.
  - Add unit testing using [f3 unit-testing][f3testing]
  - Sc::render -- add support for adding pre-conditions and post-conditions
    For example, right now pikaday requires adding stuff in header, footer, in addition
    to the actual control placement.
    - Convert pikaday and others to Sc::render
- [ ] Create a field for "asset" accounts.  Don't show this in postings/balance?
      or just show them after.  Or have the option to hide them.
- [ ] When positions are updated, an adjustment "Non-relized-value" is recorded for
      asset accounts
- [ ] summary page can show/hide asset account transactions.
- [ ] Add a "inactive" accounts.  These accounts are also either hidden for
      entry fields, or put later in the list.  Or just use 'zz prefix'
- [ ] read xlsx
  - https://github.com/PHPOffice/PhpSpreadsheet
  - https://github.com/box/spout (Now dead?)
  - https://www.nidup.io/blog/manipulate-excel-files-in-php

## ChangeLog

- 1,3,6:
  - UI tweaks and bugfixes
  - Bug Fix search (select account+all categories)
- 1.3.5:
  - switching to fatfree-core submodule
- 1.3.4:
  - Upgrading f3 to 3.8.0
  - Fixed bugs due to PHP8 testing
- 1.3.3:
  - last version supporting php5+
- 1.3.2:
  - handle numbers with thousands and different decimal separators
  - Internal API tweaks
- 1.3.1:
  - positions show account numbers
  - fixed php7.4 compatibility
  - reverse proxy fixes
  - ... etc...
- 1.3.0:
  - updated sub modules
  - search postings
  - dynamic rules
  - month navigation
  - misc improvements
- 1.2.3: balance
  - Add controls to make it easier to balance accounts
- 1.2.2: 2017.03
  - rpt_positions: Show asset/liabilities totals
  - Clean-ups
- 1.2.1: Positions
  - Equity Positions
  - Tweaking summary, adding pie charts
  - Data Entry, Postings: Shows running total
- 1.2.0: Backups
  - Backup improvements
- 1.1.0: First full production version
  - Added CategoryTypes
  - Updated FatFreeFramework
  - Summary Reporting
  - This version was working quite well for a few months
- 1.0.0 : First public release

## Notes

- Balance is the amount at the end of the day.

## Learning points

OK, so this is my first try at an [FatFree][f3] MVC web application.  So somethings I learned:

1. We need to dwelve better into [FatFree][f3]'s autoloading capabilities and how namespaces
   are handled.
2. In `routes.ini` we declare `route => {controller}->{method}`, I think it would be cleaner
   if we had: `route => {controller}->view`, and then in the `view` function we declare:
   `function view($f3,$params)`.  Well, `$params[0]` has the route itself, which actually should
   refer to a `view` in the `view` folder.
3. The models are actually `DAO`.  We should name these accordingly with a DAO suffix.
   (This has to do with the fact that we are using a flat namespace)
4. There ought to be a good way to abstract how reports are done.  We are not there yet.
5. When [FatFree][f3] makes the Hive available in templates as variables, these variables are
   already escaped.  If you want to use the raw variables (unscaped) you should use the `$f3->get`
   method to read the Hive directly.

* * *

   [f3]: http://fatfreeframework.com/home
   [f3testing]: http://fatfreeframework.com/unit-testing
   [f3crud]: https://foysalmamun.wordpress.com/2013/03/27/fat-free-crud-with-mvc-tutorial/
   [moment]: https://github.com/moment/moment
   [parsecsv]: https://github.com/parsecsv/parsecsv-for-php
   [phpgraph]: https://github.com/jerrywham/phpGraph
   [pikaday]: https://github.com/dbushell/Pikaday
   [sorttable]: https://github.com/stuartlangridge/sorttable
