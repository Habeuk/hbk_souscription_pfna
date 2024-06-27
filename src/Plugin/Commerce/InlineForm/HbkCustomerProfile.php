<?php

namespace Drupal\hbk_souscription_pfna\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides an inline form for managing a customer profile.
 *
 * Allows copying values to and from the customer's address book.
 *
 * Supports two modes, based on the profile type setting:
 * - Single: The customer can have only a single profile of this type.
 * - Multiple: The customer can have multiple profiles of this type.
 *
 * @CommerceInlineForm(
 *   id = "hbk_custom_profile",
 *   label = @Translation("Hbk custom profile"),
 * )
 */
class HbkCustomerProfile extends EntityInlineFormBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_mode' => 'default',
      'skip_save' => FALSE
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);
    assert($this->entity instanceof ProfileInterface);
    
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->configuration['form_mode']);
    $form_display->buildForm($this->entity, $inline_form, $form_state);
    
    return $inline_form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function submitInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::submitInlineForm($inline_form, $form_state);
    assert($this->entity instanceof ProfileInterface);
    
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->configuration['form_mode']);
    $form_display->extractFormValues($this->entity, $inline_form, $form_state);
    
    if (empty($this->configuration['skip_save'])) {
      $this->entity->save();
    }
  }
}