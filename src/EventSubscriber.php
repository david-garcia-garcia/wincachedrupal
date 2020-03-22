<?php

namespace Drupal\wincachedrupal;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Wincache event Subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Response to Kernel Terminate.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    $sh = new WincacheShutdown();
    $sh->shutdown();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['onKernelTerminate', -100];
    return $events;
  }

}
