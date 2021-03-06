<?php

namespace Drupal\role_based_theme_switcher\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity;
use Drupal\user\Entity\Role;
use Drupal\Core\Extension\ThemeHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Role Based settings for this site.
 *
 * @category Role_Based_Theme_Switcher
 *
 * @package Role_Based_Theme_Switcher
 *
 * @author pen <author details>
 *
 * @link https://www.drupal.org/sandbox/pen/2760771 description
 */
class AdminSettingsForm extends ConfigFormBase {
  protected $themeGlobal;
  /**
   * {@inheritdoc}
   */
  public function __construct(ThemeHandler $themeGlobal) {
    $this->themeGlobal = $themeGlobal;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'role_based_theme_switcher.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Implements admin settings form.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $themes = $this->themeGlobal->listInfo();
    $themeNames = array('' => '--Select--');
    foreach ($themes as $key => $value) {
      $themeNames[$key] = $key;
    }
    $roles = Role::loadMultiple();
    $roleThemes = $this->config('role_based_theme_switcher.settings');
    $form['role_theme'] = array(
      '#type' => 'table',
      '#header' => array(
        t('Label'),
        t('Themes List'),
        t('Weight'),
      ),
      '#empty' => t('There are no items yet. Add an item.', array()),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically prepended;
      // if there is none, an HTML ID is auto-generated.
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'role_theme-order-weight',
        ),
      ),
    );
    if (!empty($roleThemes->get('roletheme'))) {
      $roleThemes = $roleThemes->get('roletheme');
      if (count($roleThemes) == count($roles)) {
        $roles = $roleThemes;
      }
      elseif (count($roleThemes) != count($roles)) {
        foreach ($roles as $rem_key => $rem_val) {
          if (!array_key_exists($rem_key, $roleThemes)) {
            $merge[$rem_key] = ['id' => '', 'weight' => 10];
            $roleThemes = array_merge($roleThemes, $merge);
          }
        }
        $roles = $roleThemes;
      }
    }
    else {
      $roleThemes = [];
      $i = 0;
      foreach ($roles as $roles_key => $roles_val) {
        if ($roles_key == 'administrator') {
          $roleThemes[$roles_key]['weight'] = $i;
          $roleThemes[$roles_key]['id'] = 'seven';
          $i++;
        }
        else {
          $roleThemes[$roles_key]['weight'] = $i;
          $roleThemes[$roles_key]['id'] = 'bartik';
          $i++;
        }
      }
      $roles = $roleThemes;
    }
    // Build the table rows and columns.
    foreach ($roles as $id => $entity) {
      // TableDrag: Mark the table row as draggable.
      $form['role_theme'][$id]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      // Some table columns containing raw markup.
      $form['role_theme'][$id]['label'] = array(
        '#plain_text' => ucfirst($id) . $this->t(' user'),
      );

      $form['role_theme'][$id]['id'] = array(
        '#type' => 'select',
        '#title' => $this->t('Select Theme'),
        '#options' => $themeNames,
        '#default_value' => (string) $roleThemes[$id]['id'],
      );
      // TableDrag: Weight column element.
      $form['role_theme'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#default_value' => (int) $roleThemes[$id]['weight'],
        // Classify the weight element for #tabledrag.
        '#attributes' => array('class' => array('role_theme-order-weight')),
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rollTheme = $form_state->getValue('role_theme');
    $role_arr = [];
    foreach ($rollTheme as $key => $value) {
      if (in_array((int) $value['weight'], $role_arr)) {
        $form_state->setErrorByName('role_theme', $this->t("Thers is an error in the form."));
      }
      $role_arr[$key] = (int) $value['weight'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rollTheme = $form_state->getValue('role_theme');
    $roll_array = [];
    foreach ($rollTheme as $key => $value) {
      $role_arr[(int) $value['weight']] = ['theme' => $value['id'], 'role' => $key];

      $roles[] = $key;
    }

    ksort($role_arr);

    foreach ($role_arr as $new_key => $new_value) {

      if (in_array($new_value['role'], $roles)) {
        $roll_array[$new_value['role']] = ['id' => $new_value['theme'], 'weight' => $new_key];
      }
    }
    $roleThemes = $this->config('role_based_theme_switcher.settings');
    $roleThemes->set('roletheme', $roll_array);
    $roleThemes->save();
    $this->messenger()->addMessage("Role theme configuration saved succefully");
    // clearing cache for anonymous users.
    drupal_flush_all_caches();
  }

}
