<?php

namespace Civi\Angular\Page;

/**
 * This page aggregates data from Angular modules.
 *
 * Example: Aggregate metadata about all modules in JSON format.
 *   civicrm/ajax/angular-modules?format=json
 *
 * Example: Aggregate metadata for crmUi and crmUtil modules.
 *    civicrm/ajax/angular-modules?format=json&modules=crmUi,crmUtil
 *
 * Example: Aggregate *.js files for all modules.
 *   civicrm/ajax/angular-modules?format=js
 *
 * Example: Aggregate *.css files for all modules.
 *   civicrm/ajax/angular-modules?format=css
 */
class Modules extends \CRM_Core_Page {

  /**
   * See class description.
   */
  public function run() {
    /**
     * @var \Civi\Angular\Manager $angular
     */
    $angular = \Civi\Core\Container::singleton()->get('angular');
    $moduleNames = $this->parseModuleNames(\CRM_Utils_Request::retrieve('modules', 'String'), $angular);

    switch (\CRM_Utils_Request::retrieve('format', 'String')) {
      case 'json':
      case '':
        $this->send(
          'application/javascript',
          json_encode($this->getMetadata($moduleNames, $angular))
        );
        break;

      case 'js':
        $this->send(
          'application/javascript',
          \CRM_Utils_File::concat($angular->getResources($moduleNames, 'js', 'path'), "\n")
        );
        break;

      case 'css':
        $this->send(
          'text/css',
          \CRM_Utils_File::concat($angular->getResources($moduleNames, 'css', 'path'), "\n")
        );
        break;

      default:
        \CRM_Core_Error::fatal("Unrecognized format");
    }

    \CRM_Utils_System::civiExit();
  }

  /**
   * @param string $modulesExpr
   *   Comma-separated list of module names.
   * @param \Civi\Angular\Manager $angular
   * @return array
   *   Any well-formed module names. All if moduleExpr is blank.
   */
  public function parseModuleNames($modulesExpr, $angular) {
    if ($modulesExpr) {
      $moduleNames = preg_grep(
        '/^[a-zA-Z0-9\-_\.]+$/',
        explode(',', $modulesExpr)
      );
      return $moduleNames;
    }
    else {
      $moduleNames = array_keys($angular->getModules());
      return $moduleNames;
    }
  }

  /**
   * @param array $moduleNames
   *   List of module names.
   * @param \Civi\Angular\Manager $angular
   * @return array
   */
  public function getMetadata($moduleNames, $angular) {
    $modules = $angular->getModules();
    $result = array();
    foreach ($moduleNames as $moduleName) {
      if (isset($modules[$moduleName])) {
        $result[$moduleName] = array();
        $result[$moduleName]['domain'] = $modules[$moduleName]['ext'];
        $result[$moduleName]['js'] = $angular->getResources($moduleName, 'js', 'rawUrl');
        $result[$moduleName]['css'] = $angular->getResources($moduleName, 'css', 'rawUrl');
        $result[$moduleName]['partials'] = $angular->getPartials($moduleName);
        $result[$moduleName]['strings'] = $angular->getTranslatedStrings($moduleName);
      }
    }
    return $result;
  }

  /**
   * Send a response.
   *
   * @param string $type
   *   Content type.
   * @param string $data
   *   Content.
   */
  public function send($type, $data) {
    // Encourage browsers to cache for a long time - 1 year
    $ttl = 60 * 60 * 24 * 364;
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
    header("Content-Type:	$type");
    header("Cache-Control: max-age=$ttl, public");
    echo $data;
  }

}