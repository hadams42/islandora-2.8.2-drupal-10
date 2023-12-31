<?php

declare(strict_types = 1);

namespace Drupal\matomo\Plugin\migrate\process;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Prefixes paths with a slash.
 *
 * @MigrateProcessPlugin(
 *     id = "matomo_visibility_pages"
 * )
 */
class MatomoVisibilityPages extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal 6 and 7 value when PHP controlled visibility.
   */
  public const BLOCK_VISIBILITY_PHP = 2;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The migration process plugin.
   *
   * Configured for lookups in the d6_user_role and d7_user_role migrations.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * Skip PHP setting.
   *
   * Whether or not to skip Matomo that uses PHP for visibility. Only applies if
   * the PHP module is not enabled.
   *
   * @var bool
   */
  protected $skipPHP = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->migrationPlugin = $migration_plugin;

    if (isset($configuration['skip_php'])) {
      $this->skipPHP = $configuration['skip_php'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $migration_configuration = [
      'migration' => [
        'd6_user_role',
        'd7_user_role',
      ],
    ];
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$old_visibility, $pages] = $value;

    $request_path_pages = '';

    if ($pages) {
      if ($old_visibility == self::BLOCK_VISIBILITY_PHP) {
        // If the PHP module is present, migrate the visibility code unaltered.
        if ($this->moduleHandler->moduleExists('php')) {
          $request_path_pages = $pages;
        }
        // Skip the row if we're configured to. If not, we don't need to do
        // anything else -- the block will simply have no PHP or request_path
        // visibility configuration.
        elseif ($this->skipPHP) {
          throw new MigrateSkipRowException();
        }
      }
      else {
        $paths = \preg_split("(\r\n?|\n)", $pages);
        foreach ($paths as $key => $path) {
          $paths[$key] = $path === '<front>' ? $path : '/' . \ltrim($path, '/');
        }
        $request_path_pages = \implode("\n", $paths);
      }
    }

    return $request_path_pages;
  }

}
