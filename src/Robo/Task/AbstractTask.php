<?php

namespace Mwltr\MageDeploy2\Robo\Task;

use Mwltr\MageDeploy2\Config\ConfigAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Task\BaseTask as RoboBaseTask;

/**
 * AbstractTask
 */
abstract class AbstractTask
    extends RoboBaseTask
    implements BuilderAwareInterface, IOAwareInterface
{
    use ConfigAwareTrait;
    use BuilderAwareTrait;
    use IO;

}