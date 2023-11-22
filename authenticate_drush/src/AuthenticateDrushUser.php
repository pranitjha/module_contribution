<?php

namespace Drupal\authenticate_drush;

use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a service to authenticate drush users.
 */
class AuthenticateDrushUser {

  /**
   * The user authentication.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * AuthenticateDrushUser constructor.
   *
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The service to check the user authentication.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(UserAuthInterface $user_auth, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->userAuth = $user_auth;
  }

  /**
   * Authenticates user by email.
   *
   * @param string $command
   *   Executed drush command.
   * @param string $email
   *   User account email.
   * @param string $password
   *   User password to authenticate the account.
   *
   * @return void
   */
  public function authenticateDrushUser(string $command, string $email, string $password) {
    $user = user_load_by_mail($email);
    if ($user instanceof UserInterface && $user->isActive()) {
      // If active user, authenticate the current drush command.
      if ($this->userAuth->authenticate($user->getAccountName(), $password)) {
        $this->logger->info('User with email @email authenticated successfully for command: @command.', [
          '@email' => $user->getEmail(),
          '@command' => $command,
        ]);

        return;
      }

      // For user not available or inactive, log failed attempt in the system.
      $this->logger->info('User with email @email authentication failed for command: @command.', [
        '@email' => $user->getEmail(),
        '@command' => $command,
      ]);
    }
    else {
      $this->logger->info('User with email: @email either not found or blocked for command: @command.', [
        '@email' => $email,
        '@command' => $command,
      ]);
    }

    throw new AccessDeniedHttpException('Unable to authenticate.');
  }

}
