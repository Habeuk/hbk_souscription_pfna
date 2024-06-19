<?php
declare(strict_types = 1);

namespace Drupal\hbk_souscription_pfna\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\hbk_souscription_pfna\Services\ManageSouscription;

/**
 * Returns responses for hbk_souscription_pfna routes.
 */
final class HbkSouscriptionPfnaController extends ControllerBase {
  /**
   *
   * @var ManageSouscription
   */
  protected $ManageSouscription;
  
  /**
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $LoggerChannel;
  
  /**
   * The controller constructor.
   */
  public function __construct(LoggerChannel $LoggerChannel, ManageSouscription $ManageSouscription) {
    $this->LoggerChannel = $LoggerChannel;
    $this->ManageSouscription = $ManageSouscription;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('logger.channel.hbk_souscription_pfna'), $container->get('hbk_souscription_pfna.manage_souscription'));
  }
  
  /**
   * Builds the response.
   */
  public function __invoke(Request $request, $id_offre) {
    return $this->ManageSouscription->souscription($request, $id_offre);
  }
  
  /**
   * Recupere les souscriptions de l'utiliateurs courrant.
   */
  public function MySouscriptions() {
    //
    return [];
  }
}

