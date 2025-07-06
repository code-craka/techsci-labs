<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\EmailAccount;
use App\Document\Token;
use App\Repository\EmailAccountRepository;
use App\Repository\TokenRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthService
{
    public function __construct(
        private DocumentManager $documentManager,
        private EmailAccountRepository $accountRepository,
        private TokenRepository $tokenRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MercurePublisher $mercurePublisher,
        private NightwatchService $nightwatchService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Authenticate user with email and password
     */
    public function authenticate(string $email, string $password): ?EmailAccount
    {
        try {
            $this->logger->info('Authentication attempt', ['email' => $email]);

            // Find user by email or alias
            $user = $this->findUserByEmailOrAlias($email);
            if (!$user) {
                $this->logger->warning('Authentication failed: user not found', ['email' => $email]);
                $this->nightwatchService->recordAuthEvent('failed_login', [
                    'email' => $email,
                    'reason' => 'user_not_found'
                ]);
                throw new UserNotFoundException('User not found');
            }

            // Verify password
            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                $this->logger->warning('Authentication failed: invalid password', ['email' => $email]);
                $this->nightwatchService->recordAuthEvent('failed_login', [
                    'email' => $email,
                    'reason' => 'invalid_password'
                ]);
                throw new BadCredentialsException('Invalid credentials');
            }

            // Check if account is active and not expired
            if (!$user->canAuthenticate()) {
                $this->logger->warning('Authentication failed: account inactive', ['email' => $email]);
                $this->nightwatchService->recordAuthEvent('failed_login', [
                    'email' => $email,
                    'reason' => 'account_inactive'
                ]);
                throw new BadCredentialsException('Account is inactive or expired');
            }

            // Update last activity
            $this->accountRepository->markAccountAsActive($user);

            // Log successful authentication
            $this->logger->info('Authentication successful', ['email' => $email]);
            $this->nightwatchService->recordAuthEvent('successful_login', [
                'email' => $email,
                'account_id' => $user->getId()
            ]);

            // Publish real-time notification
            $this->mercurePublisher->publishAccountUpdate($user, 'login');

            return $user;

        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Find user by email address or alias
     */
    public function findUserByEmailOrAlias(string $identifier): ?EmailAccount
    {
        // First try direct email lookup
        $user = $this->accountRepository->findByEmail($identifier);
        if ($user) {
            return $user;
        }

        // Try alias lookup
        return $this->accountRepository->findAccountByAlias($identifier);
    }

    /**
     * Create API access token for user
     */
    public function createApiToken(EmailAccount $user, string $name, array $scopes = [], ?\DateTimeInterface $expiresAt = null): Token
    {
        try {
            $token = new Token();
            $token->setEmailAccount($user);
            $token->setType(Token::TYPE_API_ACCESS);
            $token->setName($name);
            $token->setScopes($scopes);
            $token->setExpiresAt($expiresAt ?? new \DateTime('+1 year'));
            $token->generateToken();

            $this->documentManager->persist($token);
            $this->documentManager->flush();

            $this->logger->info('API token created', [
                'user_id' => $user->getId(),
                'token_name' => $name,
                'scopes' => $scopes
            ]);

            $this->nightwatchService->recordAuthEvent('api_token_created', [
                'user_id' => $user->getId(),
                'token_name' => $name,
                'scopes' => $scopes
            ]);

            return $token;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create API token', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate API token and return user
     */
    public function validateApiToken(string $tokenString): ?EmailAccount
    {
        try {
            $token = $this->tokenRepository->findValidToken($tokenString);
            if (!$token || $token->getType() !== Token::TYPE_API_ACCESS) {
                return null;
            }

            // Update token usage
            $token->use();
            $this->documentManager->persist($token);
            $this->documentManager->flush();

            return $token->getEmailAccount();

        } catch (\Exception $e) {
            $this->logger->error('API token validation error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create email verification token
     */
    public function createEmailVerificationToken(EmailAccount $user): Token
    {
        try {
            // Revoke existing verification tokens
            $this->tokenRepository->revokeTokensByType($user, Token::TYPE_EMAIL_VERIFICATION);

            $token = new Token();
            $token->setEmailAccount($user);
            $token->setType(Token::TYPE_EMAIL_VERIFICATION);
            $token->setName('Email Verification');
            $token->setExpiresAt(new \DateTime('+24 hours'));
            $token->generateToken();

            $this->documentManager->persist($token);
            $this->documentManager->flush();

            $this->logger->info('Email verification token created', ['user_id' => $user->getId()]);

            return $token;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create email verification token', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $tokenString): bool
    {
        try {
            $token = $this->tokenRepository->findValidToken($tokenString);
            if (!$token || $token->getType() !== Token::TYPE_EMAIL_VERIFICATION) {
                return false;
            }

            $user = $token->getEmailAccount();
            $user->setIsEmailVerified(true);
            $token->use();

            $this->documentManager->persist($user);
            $this->documentManager->persist($token);
            $this->documentManager->flush();

            $this->logger->info('Email verified successfully', ['user_id' => $user->getId()]);
            $this->nightwatchService->recordAuthEvent('email_verified', [
                'user_id' => $user->getId()
            ]);

            // Publish real-time notification
            $this->mercurePublisher->publishAccountUpdate($user, 'email_verified');

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Email verification error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create password reset token
     */
    public function createPasswordResetToken(string $email): ?Token
    {
        try {
            $user = $this->findUserByEmailOrAlias($email);
            if (!$user) {
                // Don't reveal that user doesn't exist
                $this->logger->info('Password reset requested for non-existent user', ['email' => $email]);
                return null;
            }

            // Revoke existing password reset tokens
            $this->tokenRepository->revokeTokensByType($user, Token::TYPE_PASSWORD_RESET);

            $token = new Token();
            $token->setEmailAccount($user);
            $token->setType(Token::TYPE_PASSWORD_RESET);
            $token->setName('Password Reset');
            $token->setExpiresAt(new \DateTime('+1 hour'));
            $token->generateToken();

            $this->documentManager->persist($token);
            $this->documentManager->flush();

            $this->logger->info('Password reset token created', ['user_id' => $user->getId()]);

            return $token;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create password reset token', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $tokenString, string $newPassword): bool
    {
        try {
            $token = $this->tokenRepository->findValidToken($tokenString);
            if (!$token || $token->getType() !== Token::TYPE_PASSWORD_RESET) {
                return false;
            }

            $user = $token->getEmailAccount();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $token->use();

            // Revoke all other tokens for security
            $this->tokenRepository->revokeAllTokensForAccount($user, 'Password reset');

            $this->documentManager->persist($user);
            $this->documentManager->persist($token);
            $this->documentManager->flush();

            $this->logger->info('Password reset successfully', ['user_id' => $user->getId()]);
            $this->nightwatchService->recordAuthEvent('password_reset', [
                'user_id' => $user->getId()
            ]);

            // Publish real-time notification
            $this->mercurePublisher->publishAccountUpdate($user, 'password_reset');

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Password reset error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Change user password (when authenticated)
     */
    public function changePassword(EmailAccount $user, string $currentPassword, string $newPassword): bool
    {
        try {
            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->logger->warning('Password change failed: invalid current password', [
                    'user_id' => $user->getId()
                ]);
                return false;
            }

            // Hash new password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $this->documentManager->persist($user);
            $this->documentManager->flush();

            $this->logger->info('Password changed successfully', ['user_id' => $user->getId()]);
            $this->nightwatchService->recordAuthEvent('password_changed', [
                'user_id' => $user->getId()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Password change error', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Revoke user token
     */
    public function revokeToken(string $tokenString, string $reason = 'User request'): bool
    {
        try {
            $token = $this->tokenRepository->findValidToken($tokenString);
            if (!$token) {
                return false;
            }

            $token->revoke($reason, 'user');
            $this->documentManager->persist($token);
            $this->documentManager->flush();

            $this->logger->info('Token revoked', [
                'token_id' => $token->getId(),
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Token revocation error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user's active tokens
     */
    public function getUserTokens(EmailAccount $user): array
    {
        return $this->tokenRepository->findActiveTokensByAccount($user);
    }

    /**
     * Cleanup expired tokens (should be run periodically)
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $count = $this->tokenRepository->markExpiredTokens();
            $deletedCount = $this->tokenRepository->cleanupOldTokens();

            $this->logger->info('Token cleanup completed', [
                'expired_count' => $count,
                'deleted_count' => $deletedCount
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            $this->logger->error('Token cleanup error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if user has specific scope access
     */
    public function hasScope(EmailAccount $user, string $scope): bool
    {
        // Admin users have all scopes
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Check user's API tokens for scope
        $tokens = $this->tokenRepository->findApiTokensByAccount($user);
        foreach ($tokens as $token) {
            if (in_array($scope, $token->getScopes())) {
                return true;
            }
        }

        return false;
    }
}