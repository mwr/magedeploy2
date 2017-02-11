<?php

namespace Mwltr\MageDeploy2\Robo\Task;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Mwltr\MageDeploy2\Config\ConfigAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;
use Robo\Task\BaseTask as RoboBaseTask;

/**
 * AbstractTask
 */
abstract class AbstractTask
    extends RoboBaseTask
    implements BuilderAwareInterface, IOAwareInterface, ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoadAllTasks; // uses TaskAccessor, which uses BuilderAwareTrait
    use IO;

}