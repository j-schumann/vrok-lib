<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Authentication\Adapter;

use DateTime;
use Vrok\Entity\User as UserEntity;

/**
 * Checks the database for a match of the given identity and validates the users
 * state and loginKey.
 */
class Cookie extends Doctrine
{
    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        if (! is_array($this->credential) || count($this->credential) !== 3) {
            return $this->getResult(self::MSG_INVALIDCREDENTIAL);
        }

        $repository = $this->entityManager->getRepository(UserEntity::class);
        $user = $repository->find((int)$this->credential[0]);
        /* @var $user UserEntity */

        if (! $user) {
            return $this->getResult(self::MSG_IDENTITYNOTFOUND);
        }

        if (! $user->getIsActive()) {
            return $this->getResult(self::MSG_USERNOTACTIVE, $user->getId());
        }

        if (! $user->getIsValidated()) {
            return $this->getResult(self::MSG_USERNOTVALIDATED, $user->getId());
        }

        // force login by password if pw version is outdated
        if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT)) {
            return $this->getResult(self::MSG_UNCATEGORIZED, $user->getId());
        }

        // @todo TempBans checken

        $found = null;
        $now = new DateTime();
        foreach ($user->getLoginKeys() as $loginKey) {
            if ($loginKey->getExpirationDate() <= $now) {
                continue;
            }

            if ($loginKey->getToken() === $this->credential[1]
                || $loginKey->getToken() === $this->credential[2]
            ) {
                $found = $loginKey;
                break;
            }
        }

        if (! $found) {
            return $this->getResult(self::MSG_INVALIDCREDENTIAL, $user->getId());
        }

        return $this->getResult(self::MSG_SUCCESS, $user->getId());
    }
}
