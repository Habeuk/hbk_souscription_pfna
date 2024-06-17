<?php
declare(strict_types = 1);

namespace Drupal\hbk_souscription_pfna\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;

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
      $this->messenger()->addMessage($this->t("You must log in or create an account if you are a new user"), "infos");
      return $this->redirect("user.page");
    }
    $this->sendMails($id_offre);
    $this->messageDeValidation($id_offre);
    $this->returnUserByReferrer();
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Processus de souscription terminé')
    ];
    return $build;
  }
  
  /**
   * On envoie les mails à l'administrateur et aussi au client.
   */
  protected function sendMails($id_offre) {
    $this->buildMail($id_offre);
  }
  
  /**
   * On retourne l'utilisateur sur la page de provenance.
   */
  protected function returnUserByReferrer() {
  }
  
  /**
   * On affiche un message de validation.
   */
  protected function messageDeValidation($id_offre) {
    $offre = \Drupal\node\Entity\Node::load($id_offre);
    if ($offre) {
      $message = "Votre soumission à l'offre « ";
      $message .= $offre->label();
      $message .= " », a bien été prise en compte. merci, Nous vous recontacterons dans moins de 24h. ";
      $this->messenger()->addStatus($this->t($message));
    }
  }
  
  /**
   *
   * @param Request $request
   * @param int $id_offre
   * @param string $url
   */
  protected function saveReferrerUrl(Request $request, $id_offre, $url) {
    $name = 'hbk_id_offre';
    $session = $request->getSession();
    if (!$session->has($name) || ($session->get($name) != $id_offre)) {
      $session->set('hbk_return_url', $url);
      $session->set($name, $id_offre);
    }
  }
  
  /**
   * Construit le mail.
   * L'envoie de mail se fait par le module webform.
   *
   * @param int $id_offre
   */
  protected function buildMail($id_offre) {
    // More information :
    // https://www.drupal.org/docs/contributed-modules/webform/webform-cookbook/how-to-programmatically-create-and-update-a-submission
    $values = [
      'webform_id' => 'souscription',
      'entity_type' => NULL,
      'entity_id' => NULL,
      'in_draft' => FALSE,
      'uid' => $this->currentUser()->id(),
      'data' => [
        'client' => $this->currentUser()->id(),
        'offre' => $id_offre
      ]
    ];
    // Check webform is open.
    $webform = Webform::load($values['webform_id']);
    $is_open = WebformSubmissionForm::isOpen($webform);
    if ($is_open === TRUE) {
      // Validate submission.
      $errors = WebformSubmissionForm::validateFormValues($values);
      // Check there are no validation errors.
      if (!empty($errors)) {
        $message = "An error has occurred, unable to validate your offer";
        $this->messenger()->addError($this->t($message));
        $this->LoggerChannel->error($message);
        \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'buildMail', true);
      }
      else {
        // Submit values and get submission ID.
        WebformSubmissionForm::submitFormValues($values);
        // dump($webform_submission->id());
      }
    }
    else {
      $message = "An error has occurred, unable to continue subscription";
      $this->messenger()->addError($this->t($message));
      $this->LoggerChannel->error($message);
    }
  }
}

