<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo\Task;

use Mwltr\MageDeploy2\Config\Config;
use Mwltr\MageDeploy2\Robo\RoboFile;
use Robo\Collection\CollectionBuilder;

/**
 * UpdateSourceCodeTask
 */
class UpdateSourceCodeTask extends AbstractTask
{
    protected $branch = '';

    protected $tag = '';

    /**
     * Build Task to update source code to desired branch/tag
     *
     * @return \Robo\Result
     */
    public function run()
    {
        $repo = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_URL);
        $gitDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_DIR);

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();

        if (!is_dir($gitDir)) {
            $task = $collection->taskGitStack();
            $task->cloneRepo($repo, $gitDir);

            $collection->addTask($task);
        } else {
            // git fetch
            $task = $collection->taskGitStack();
            $task->dir($gitDir);
            $task->exec(['fetch', '-vp', 'origin']);

            // git checkout
            $task = $collection->taskGitStack();
            $task->dir($gitDir);
            $task->exec(['checkout', '-f', $this->branch]);

            // git reset
            $task = $collection->taskGitStack();
            $task->dir($gitDir);
            $task->exec(['reset', '--hard', $this->branch]);
            // @todo check if it is a branch or tag
            // exec("git reset --hard origin/$branch");
        }

        return $collection->run();
    }

    /**
     * @param string $branch
     *
     * @return $this
     */
    public function branch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * @param string $tag
     *
     * @return $this
     */
    public function tag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

}