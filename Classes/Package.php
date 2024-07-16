<?php
namespace Flowpack\JobQueue\Common;

use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\PersistenceManagerInterface;

class Package extends BasePackage
{

    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        if (PHP_SAPI === 'cli') {
            $dispatcher = $bootstrap->getSignalSlotDispatcher();

            $dispatcher->connect(JobManager::class, 'messageFinished', function (QueueInterface $queue, Message $message) use ($bootstrap) {
                /** @var PersistenceManagerInterface $persistenceManager */
                $persistenceManager = $bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);

                if ($persistenceManager->hasUnpersistedChanges()) {
                    $persistenceManager->persistAll();
                }
            });
        }
    }
}
