<?php
declare(strict_types = 1);

namespace Drupal\hbk_souscription_pfna\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Utility\TableSort;

class CollectionSouscription extends ControllerBase {
  /**
   * Sort direction.
   *
   * @var string
   */
  protected $direction = 'desc';
  
  /**
   * Sort by.
   *
   * @var string
   */
  protected $sort = 'created';
  
  /**
   * The entity storage class.
   *
   * @var \Drupal\webform\WebformSubmissionStorage
   */
  protected $storage;
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildHeader() {
    if (isset($this->header)) {
      return $this->header;
    }
    $header = [];
    foreach ($this->columns as $column_name => $column) {
      $header[$column_name] = $this->buildHeaderColumn($column);
      // Apply custom sorting to header.
      if ($column_name === $this->sort) {
        $header[$column_name]['sort'] = $this->direction;
      }
    }
    $this->header = $header;
    return $this->header;
  }
  
  /**
   *
   * @return \Drupal\webform\WebformSubmissionStorage
   */
  public function getStorage() {
    if (!$this->storage) {
      $this->storage = \Drupal::entityTypeManager()->getStorage("webform_submission");
    }
    return $this->storage;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getSubmissionsColumns() {
    if (!$this->columns)
      $this->columns = $this->getStorage()->getColumns();
    return $this->columns;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getQuery($this->keys, $this->state, $this->sourceEntityTypeId);
    $query->pager($this->limit);
    
    $header = $this->buildHeader();
    $order = TableSort::getOrder($header, $this->request);
    $direction = TableSort::getSort($header, $this->request);
    
    // If query is order(ed) by 'element__*' we need to build a custom table
    // sort using hook_query_TAG_alter().
    // @see webform_query_webform_submission_list_builder_alter()
    if (!empty($order['sql']) && strpos($order['sql'], 'element__') === 0) {
      $name = $order['sql'];
      $column = $this->columns[$name];
      $query->addTag('webform_submission_list_builder')->addMetaData('webform_submission_element_name', $column['key'])->addMetaData('webform_submission_element_property_name',
        $column['property_name'])->addMetaData('webform_submission_element_direction', $direction);
      $result = $query->execute();
      // Must manually initialize the pager because the DISTINCT clause in the
      // query is breaking the row counting.
      // @see webform_query_alter()
      $this->pagerManager->createPager($this->total, $this->limit);
      return $result;
    }
    else {
      if ($order && $order['sql']) {
        $query->tableSort($header);
      }
      else {
        // If no order is specified, make sure the first column is sortable,
        // else default sorting to the sid.
        // @see \Drupal\Core\Entity\Query\QueryBase::tableSort
        // @see tablesort_get_order()
        $default = reset($header);
        if (isset($default['specified'])) {
          $query->tableSort($header);
        }
        else {
          $query->sort('sid', 'DESC');
        }
      }
      return $query->execute();
    }
  }
  
  protected function getQuery($keys = '', $state = '', $source_entity = ''): QueryInterface {
    //
  }
  
  /**
   * Build table header column.
   *
   * @param array $column
   *        The column.
   *        
   * @return array A renderable array containing a table header column.
   *        
   * @throws \Exception Throw exception if table header column is not found.
   */
  protected function buildHeaderColumn(array $column) {
    $name = $column['name'];
    if ($this->format['header_format'] === 'key') {
      $title = $column['key'] ?? $column['name'];
    }
    else {
      $title = $column['title'];
    }
    
    switch ($name) {
      case 'notes':
      case 'sticky':
      case 'locked':
        return [
          'data' => new FormattableMarkup('<span class="webform-icon webform-icon-@name webform-icon-@name--link"></span><span class="visually-hidden">@title</span> ', [
            '@name' => $name,
            '@title' => $title
          ]),
          'class' => [
            'webform-results-table__icon'
          ],
          'field' => $name,
          'specifier' => $name
        ];
      
      default:
        if (isset($column['sort']) && $column['sort'] === FALSE) {
          return [
            'data' => $title
          ];
        }
        else {
          return [
            'data' => $title,
            'field' => $name,
            'specifier' => $name
          ];
        }
    }
  }
}