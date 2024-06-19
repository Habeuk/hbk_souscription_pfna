<?php
declare(strict_types = 1);

namespace Drupal\hbk_souscription_pfna\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Url;

class ManageSouscription extends ControllerBase {
  
  /**
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $LoggerChannel;
  
  /**
   * The controller constructor.
   */
  public function __construct(LoggerChannel $LoggerChannel) {
    $this->LoggerChannel = $LoggerChannel;
  }
  
  /**
   * Builds the response.
   */
  public function souscription(Request $request, $id_offre) {
    $url = $request->headers->get('referer');
    $this->saveReferrerUrl($request, $id_offre, $url);
    if ($this->currentUser()->isAnonymous()) {
      $this->messenger()->addMessage($this->t("You must log in or create an account if you are a new user"), "infos");
      return $this->redirect("user.page");
    }
    return $this->runSouscription($request, $id_offre);
  }
  
  /**
   * Builds the response.
   */
  protected function runSouscription(Request $request, $id_offre) {
    $this->sendMails($id_offre);
    $this->messageDeValidation($id_offre);
    return $this->returnUserByReferrer($request, $id_offre);
  }
  
  /**
   * Permet de lancer le processus de souscription si necessaire après
   * connection.
   */
  public function afterLogin(Request $request) {
    $datas = $this->getReferrerUrl($request);
    if ($datas['hbk_id_offre']) {
      $this->runSouscription($request, $datas['hbk_id_offre']);
    }
  }
  
  /**
   * On envoie les mails à l'administrateur et aussi au client.
   */
  protected function sendMails($id_offre) {
    $this->buildMail($id_offre);
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
   *
   * @param Request $request
   * @return NULL[]|mixed[]
   */
  protected function getReferrerUrl(Request $request) {
    $datas = [
      'hbk_id_offre' => NULL,
      'hbk_return_url' => NULL
    ];
    $name = 'hbk_id_offre';
    $session = $request->getSession();
    //
    if ($session->has($name)) {
      $datas['hbk_id_offre'] = $session->get('hbk_id_offre');
      $datas['hbk_return_url'] = $session->get('hbk_return_url');
    }
    return $datas;
  }
  
  protected function clearReferrerUrl(Request $request) {
    $name = 'hbk_id_offre';
    $session = $request->getSession();
    if ($session->has($name)) {
      $session->remove('hbk_return_url');
      $session->remove($name);
    }
  }
  
  /**
   * On retourne l'utilisateur sur la page de provenance.
   */
  protected function returnUserByReferrer(Request $request, $id_offre) {
    $name = 'hbk_id_offre';
    $session = $request->getSession();
    $options['absolute'] = TRUE;
    if ($session->has($name)) {
      $uri = $session->get('hbk_return_url');
      if (!empty($uri)) {
        $this->clearReferrerUrl($request);
        return new RedirectResponse($uri . '?open_bar=offres_selected');
      }
      else
        $urlObject = Url::fromRoute('<front>', [], $options);
    }
    else {
      $urlObject = Url::fromRoute('<front>', [], $options);
    }
    return new RedirectResponse($urlObject->toString());
  }
  
  /**
   * On affiche un message de validation.
   */
  protected function messageDeValidation($id_offre) {
    $offre = \Drupal\node\Entity\Node::load($id_offre);
    if ($offre) {
      $message = "Votre souscription à l'offre « ";
      $message .= $offre->label();
      $message .= " », a bien été prise en compte. merci, Nous vous recontacterons dans moins de 24h. ";
      $this->messenger()->addStatus($this->t($message));
    }
  }
}