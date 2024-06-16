<?php
declare(strict_types = 1);

namespace Drupal\hbk_souscription_pfna\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for hbk_souscription_pfna routes.
 */
final class HbkSouscriptionPfnaController extends ControllerBase {
  
  /**
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $LoggerChannel;
  
  /**
   * The controller constructor.
   */
  public function __construct(EntityTypeManagerInterface $EntityTypeManager, LoggerChannel $LoggerChannel) {
    $this->entityTypeManager = $EntityTypeManager;
    $this->LoggerChannel = $LoggerChannel;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('entity_type.manager'), $container->get('logger.channel.hbk_souscription_pfna'));
  }
  
  /**
   * Builds the response.
   */
  public function __invoke(Request $request, $id_offre) {
    $url = $request->getPathInfo();
    $this->saveReferrerUrl($request, $id_offre, $url);
    if ($this->currentUser()->isAnonymous()) {
      return $this->redirect("user.page");
    }
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Processus de souscription')
    ];
    return $build;
  }
  
  protected function saveReferrerUrl(Request $request, $id_offre, $url) {
    $name = 'hbk_id_offre';
    $session = $request->getSession();
    if (!$session->has($name) || ($session->get($name) != $id_offre)) {
      $this->messenger()->addMessage($this->t("You must log in or create an account if you are a new user"), "infos");
      $session->set('hbk_return_url', $url);
      $session->set($name, $id_offre);
    }
  }
}
