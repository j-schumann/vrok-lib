<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Authentication\Adapter;

use Vrok\Entity\User as UserEntity;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Checks the database for a match of the given identity and validates the users state
 * and credential.
 *
 * @todo do not use servicelocator but inject the usermanager. (we need the user repo,
 * the em to persist, the temp ban repo)
 */
class Doctrine extends AbstractAdapter implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const MSG_IDENTITYNOTFOUND  = 'message.authentication.identityNotFound';
    const MSG_INVALIDCREDENTIAL = 'message.authentication.invalidCredential';
    const MSG_UNCATEGORIZED     = 'message.authentication.uncategorizedFailure';
    const MSG_USERNOTACTIVE     = 'message.authentication.userNotActive';
    const MSG_USERNOTVALIDATED  = 'message.authentication.userNotValidated';
    const MSG_SUCCESS           = 'message.authentication.success';

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $repository = $em->getRepository('Vrok\Entity\User');

        $user = $repository->findOneBy(array('username' => $this->identity));
        /* @var $user UserEntity */

        if (!$user) {
            // fallback to email address
            $user = $repository->findOneBy(array('email' => $this->identity));
            if (!$user) {
                return $this->getResult(self::MSG_IDENTITYNOTFOUND);
            }
        }

        if (!$user->getIsActive()) {
            return $this->getResult(self::MSG_USERNOTACTIVE, $user->getId());
        }

        if (!$user->getIsValidated()) {
            return $this->getResult(self::MSG_USERNOTVALIDATED, $user->getId());
        }

        // @todo TempBans checken

        if (!$user->checkPassword($this->credential)) {
            return $this->getResult(self::MSG_INVALIDCREDENTIAL, $user->getId());
        }

        // automatically rehash the password, can't be done in the user object,
        // e.g. in checkPassword, as we need the entityManager to persist()
        if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT)) {
            // setPassword resets isRandom and passwordDate so we backup and
            // restore them afterwards as the password itself hasn't changed
            $isRandom = $user->getIsRandomPassword();
            $passwordDate = $user->getPasswordDate();

            $user->setPassword($this->credential);

            $user->setIsRandomPassword($isRandom);
            $user->setPasswordDate($passwordDate);
            $em->persist($user);
        }

        return $this->getResult(self::MSG_SUCCESS, $user->getId());
    }

    /**
     * Creates a new Result instance with the given options.
     *
     * @param string $message
     * @param mixed $identity
     * @return Result
     */
    protected function getResult($message, $identity = null)
    {
        switch($message) {
            case self::MSG_IDENTITYNOTFOUND:
                $type = Result::FAILURE_IDENTITY_NOT_FOUND;
                break;

            case self::MSG_INVALIDCREDENTIAL:
                $type = Result::FAILURE_CREDENTIAL_INVALID;
                break;

            case self::MSG_SUCCESS:
                $type = Result::SUCCESS;
                break;

            case self::MSG_USERNOTACTIVE:
                // no break
            case self::MSG_USERNOTVALIDATED:
                // no break
            default:
                $type = Result::FAILURE;
                break;
        }

        return new Result($type, $identity, array(
            $message
        ));
    }
}
