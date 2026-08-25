<?php

declare(strict_types=1);

namespace BikeShare\App\Security;

use BikeShare\App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * $this->getUser() is typed to the generic Symfony UserInterface, which
 * does not know about our App\Entity\User getters (getUserId, getCity,
 * etc.) - PHPStan correctly flags those as undefined methods. Every route
 * that reaches these controllers already requires ROLE_USER or stronger
 * (see config/packages/security.php access_control), so a non-User
 * principal here would mean the security config itself is broken, not a
 * case to silently tolerate.
 */
trait RequiresAppUserTrait
{
    protected function getAppUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Expected an authenticated App\Entity\User.');
        }

        return $user;
    }
}
