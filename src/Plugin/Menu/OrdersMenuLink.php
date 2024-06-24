<?php

namespace Drupal\hbk_souscription_pfna\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkBase;

/**
 * Defines menu links provided by views.
 *
 * @see \Drupal\views\Plugin\Derivative\ViewsMenuLink
 */
class OrdersMenuLink extends MenuLinkBase {
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Menu\MenuLinkInterface::getDescription()
   */
  public function getDescription() {
    return '';
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Menu\MenuLinkInterface::getTitle()
   */
  public function getTitle() {
    return $this->t('My orders');
  }
  
  public function getRouteParameters() {
    return [
      'user' => \Drupal::currentUser()->id()
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Menu\MenuLinkInterface::updateLink()
   */
  public function updateLink(array $new_definition_values, $persist) {
  }
}