<?php

namespace Drupal\role_based_theme_switcher\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the active theme on admin pages.
 */
class RoleNegotiator implements ThemeNegotiatorInterface {

  // Protected theme variable to set the default theme if no theme is selected.
  protected $theme = NULL;

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use this theme on a certain route.
    $change_theme = TRUE;
    $route = \Drupal::routeMatch()->getRouteObject();
    $is_admin_route = \Drupal::service('router.admin_context')->isAdminRoute($route);
    $current_user = \Drupal::currentUser();
    $user_roles = $current_user->getRoles();
    $has_admin_role = FALSE;
    if (in_array("administrator", $user_roles)) {
      $has_admin_role = TRUE;
    }
    if ($is_admin_route === TRUE && $has_admin_role === TRUE) {
      $change_theme = FALSE;
    }
    // Here you return the actual theme name.
    $roleThemes = \Drupal::config('role_based_theme_switcher.settings')->get('roletheme');
    // Get current roles a user has.
    $roles = \Drupal::currentUser()->getRoles();
    // Get highest role.
    $theme_role = $this->getPriorityRole($roles);
    $this->theme = $roleThemes[$theme_role]['id'];

    return $change_theme;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   The name of the theme, or NULL if other negotiators, like the configured
   *   default one, should be used instead.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

  /**
   * Function to get roles array and return highest priority role.
   *
   * @param array $roles
   *   Array of roles.
   *
   * @return string $theme
   *   Return role.
   */
  public function getPriorityRole($roles) {
    $roleThemes = \Drupal::config('role_based_theme_switcher.settings');
    foreach ($roleThemes->get('roletheme') as $key => $value) {
      if (in_array($key, $roles)) {
        $theme[$key] = $value['weight'];
      }
    }
    $theme = array_search(max($theme), $theme);
    return $theme;
  }

}
