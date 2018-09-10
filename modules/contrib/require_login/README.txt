---------------------
- INTRODUCTION      -
---------------------

Require user authentication (login) on ALL pages, regardless of user role
permission settings. Features:

* Require user login to access any site pages or content
* Exclude customizable paths from login requirement
* Configure access denied message and login redirect path


---------------------
- INSTALLATION      -
---------------------

1. Install and enable "Require Login" module
2. Set permissions for "Require Login" administration
3. Set configurations: Admin > Config > People > Require Login


---------------------
- ALTER HOOK        -
---------------------

The alter hook, hook_require_login_authcheck_alter(), can be used to modify
access restriction checks performed by this module. This allows developers to
implement site-specific access conditions. Example:

/**
 * Implements hook_require_login_authcheck_alter().
 *
 * Alters the require login access checks. The $checks variable is a
 * one-dimensional array consisting of key/value pairs. Each key value should
 * be either TRUE or FALSE. Where TRUE allows access and FALSE denies access.
 */
function mymodule_require_login_authcheck_alter(&$checks) {

  // Allow access if $myvariable_1 equals $myvariable_2.
  $checks[] = ($myvariable_1 == $myvariable_2);
}
