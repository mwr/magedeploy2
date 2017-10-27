# CHANGELOG

## next

- [FIX] git checkout for tags works again (no longer using Robo to fetch tags)
- [FEATURE] add composer autoload dump to optimize performance

## 2.1.2

- [FIX] parallelMode has to be accessed with 'parallel'

## 2.1.1

- [TASK] pass $opts to deploy to prevent notice on execute

## 2.1.0

- [TASK] detect host:port combination in db-host parameter (HEAD -> develop, origin/develop)

## 2.0.0

- [TASK] update to robo-magento2 2.0.0
- [TASK] more documentation for commands
    - deploy
    - deploy:magento-setup
    - deploy:deploy

## 1.6.0

- add support for .env files (using vlucas/phpdotenv)
- load .env file if it is present in root-dir of the deploy project
- add support for deployer parallel command execution mode
- prevent repositories without tags from failing
- Added Version Badge (pull request #1 from @DavidLambauer)
- Added external links for deployer and robo (pull request #2 from @DavidLambauer)
- Changed printed() by printOutput() (pull request #5 from @osrecio)

## 1.5.0

- mysql only drop database if it exists

## 1.4.1

- fix mysql password parameter handover

## 1.4.0

- add mysql-bin setting to env
- add automatic create of build db using the build/db configuration
- add drop-database option (only works with mysql-bin setting)
- skip mysql database drop and create if mysql_bin is not set
- initalize mysql-bin during config-init

## 1.3.0

- set job number for static content deploy to 16
- rename assets to artifacts to unify the naming here (update of magedeploy2.php needed)
- config:init can be run multiple times
- introduce environment variable usage (re-generate your config by using config:init)
- improve config writing: use template to generate basic file content
- improve config reading: fallback to default file if necessary
- remove default config from config object
- artifacts array keys are used as artifact names
- FIX issue during exporting artifacts to magedeploy2.php

## 1.2.0

- add artifacts_dir to upload packages from
- run git clone independently if repo has not been cloned

## 1.1.3

- add support for git branch and git tag detection
- optimize source-code update task
