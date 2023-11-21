<?php

namespace Drupal\authenticate_drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\authenticate_drush\AuthenticateDrushUser;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Provides a way to log attemps made by users for critical drush commands.
 *
 * @package Drupal\authenticate_drush\Commands
 */
class AuthenticateCommands extends DrushCommands {

  use LoggerChannelTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * Drush User Auth service.
   *
   * @var \Drupal\authenticate_drush\AuthenticateDrushUser
   */
  private $drushUserAuth;

  /**
   * Constructor for AuthenticateCommands.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\authenticate_drush\AuthenticateDrushUser $drush_user_auth
   *   Drush User Auth service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AuthenticateDrushUser $drush_user_auth) {
    $this->configFactory = $config_factory;
    $this->drushUserAuth = $drush_user_auth;
  }

  /**
   * Throws exception when user:login command is used.
   *
   * @hook post-command user:login
   *
   * @throws \Exception
   */
  public function postUserLogin(): never {
    throw new \Exception('Use of this command is not allowed.');
  }

  /**
   * Ensure user is authenticated for executing security drush commands.
   *
   * @hook pre-command *
   *
   * @throws \Exception
   */
  public function preCommandAuthenticate(CommandData $commandData): void {
    // Check if the command is executed via cron.
    // We need to by-pass the security check.
    $ssh_user = shell_exec('who -m');
    if (empty($ssh_user)) {
      return;
    }
    // Executed drush command.
    $command = $commandData->annotationData()->get('command');
    $this->getLogger('drush_authenticate')->info('Command @command executed by SSH User: @user', [
      '@command' => $command,
      '@user' => shell_exec('who -m'),
    ]);

    // Get critical drush commands from settings.
    $config = $this->configFactory->get('authenticate_drush_config.settings');
    $critical_commands = $config->get('drush_commands');
    // If current command is one of the critical commands,
    // prompt for email & password.
    if (in_array($command, $critical_commands)) {
      $email = $this->io()->ask('Please enter your mail.');
      $password = $this->io()->askHidden('Please enter your password.');
      if (empty($email) || empty($password)) {
        throw new UserAbortException();
      }
      // Authenticate user data & log successful/failed attempts.
      $this->drushUserAuth->authenticateDrushUser($command, $email, $password);
    }
  }

}
