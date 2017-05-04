# MageDeploy2

Magento2 Deployment Setup using Robo and Deployer.
This repository contains tasks, a base Robo file, configuration etc.

## Getting Started


### Requirements

 * [robo](http://robo.li/)
 * [deployer](https://deployer.org/)

### Prerequisites

MageDeploy2 requires deployer and robo to be available on your system.

Those Tools can be used globaly or added as a requirement to your local composer.json.

The path to those tools can be configured in the magedeploy2.php

### Installation

Install using composer

```
composer require mwltr/magedeploy2
```

Installing robo and deployer on a project basis

```
composer require consolidation/robo
composer require deployer/deployer
```

### Configuration

After the installation you need to add a magedeploy2.php file return the configuration array.

## Versioning

We use [SemVer](http://semver.org/) for versioning. 
For the versions available, see the [tags on this repository](https://github.com/mwr/magedeploy2/tags). 

## Authors

* **Matthias Walter** - *Initial work* - [mwr](https://github.com/mwr)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Todo list
 
- [ ] add more documentation on how to get startet

