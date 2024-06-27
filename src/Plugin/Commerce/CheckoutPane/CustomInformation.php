<?php

namespace Drupal\hbk_souscription_pfna\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce\InlineFormManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "custom_information",
 *   label = @Translation("Custom information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class CustomInformation extends CheckoutPaneBase implements CheckoutPaneInterface {
  
  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;
  
  /**
   * Constructs a new BillingInformation object.
   *
   * @param array $configuration
   *        A configuration array containing information about the plugin
   *        instance.
   * @param string $plugin_id
   *        The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *        The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *        The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *        The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *        The inline form manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    
    $this->inlineFormManager = $inline_form_manager;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static($configuration, $plugin_id, $plugin_definition, $checkout_flow, $container->get('entity_type.manager'), $container->get('plugin.manager.commerce_inline_form'));
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface::buildPaneForm()
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $customProfile = null;
    // On recupere le custom profile, s'il existe.
    $id_profile = $this->order->get("hbk_custom_profile")->target_id;
    if ($id_profile) {
      $customProfile = $this->entityTypeManager->getStorage("profile")->load($id_profile);
    }
    if (!$customProfile) {
      
      $profile_storage = $this->entityTypeManager->getStorage("profile");
      // On verifie si profile existe deja.
      $customProfiles = $this->entityTypeManager->getStorage("profile")->loadByProperties([
        'uid' => \Drupal::currentUser()->id(),
        'type' => 'informations_personnelles'
      ]);
      if ($customProfiles) {
        $customProfile = reset($customProfiles);
      }
      else {
        $customProfile = $profile_storage->create([
          'type' => 'informations_personnelles',
          'uid' => \Drupal::currentUser()->id()
        ]);
      }
    }
    /**
     *
     * @var \Drupal\hbk_souscription_pfna\Plugin\Commerce\InlineForm\HbkCustomerProfile $inline_form
     */
    $inline_form = $this->inlineFormManager->createInstance('hbk_custom_profile', [
      'form_mode' => 'default',
      'skip_save' => FALSE
    ], $customProfile);
    
    $pane_form['custom_profile'] = [
      '#parents' => array_merge($pane_form['#parents'], [
        'custom_profile'
      ]),
      '#inline_form' => $inline_form
    ];
    $pane_form['custom_profile'] = $inline_form->buildInlineForm($pane_form['custom_profile'], $form_state);
    $pane_form['custom_profile']['#type'] = 'details';
    $pane_form['custom_profile']['#title'] = 'Mettre à jour les informations personnelles';
    return $pane_form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // $values = $form_state->getValue($pane_form['#parents']);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\hbk_souscription_pfna\Plugin\Commerce\InlineForm\HbkCustomerProfile $inline_form */
    $inline_form = $pane_form['custom_profile']['#inline_form'];
    // on met à jour l'entité profile.
    $inline_form->submitInlineForm($pane_form['custom_profile'], $form_state);
    /**
     *
     * @var \Drupal\profile\Entity\Profile $profile
     */
    $profile = $inline_form->getEntity();
    //
    // $values = $form_state->getValue($pane_form['#parents']);
    $this->order->set('hbk_custom_profile', $profile->id());
  }
}