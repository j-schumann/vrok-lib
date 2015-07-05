<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Vrok\Entity\AbstractTodo;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

/**
 * Used to translate Doctrine events to ZF2 events.
 *
 * @IgnoreAnnotation("triggers")
 */
class TodoListener implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    const EVENT_TODO_POSTUPDATE   = 'todo.postUpdate';

    protected $changeset = [];

    /**
     * Caches the changeset as it is only available in the preUpdate event, not postUpdate.
     *
     * @param AbstractTodo $todo
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(AbstractTodo $todo, PreUpdateEventArgs $event)
    {
        $this->changeset = $event->getEntityChangeSet();
    }

    /**
     * Triggers an event for every update (even automatically induced updates,
     * e.g. of status!).
     *
     * @param AbstractTodo $todo
     * @param LifecycleEventArgs $event
     * @triggers todo.postUpdate
     */
    public function postUpdate(AbstractTodo $todo, LifecycleEventArgs $event)
    {
        $this->getEventManager()->trigger(
            self::EVENT_TODO_POSTUPDATE,
            $todo,
            [
                'changeset' => $this->changeset,
                'event'     => $event,
            ]
        );
    }
}
