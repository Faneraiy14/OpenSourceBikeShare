<?php

declare(strict_types=1);

namespace BikeShare\App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator as BaseFormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @see https://github.com/symfony/symfony/issues/27961
 */
// @phpstan-ignore class.extendsFinalByPhpDoc (intentional workaround, see the linked Symfony issue above)
class FormLoginAuthenticator extends BaseFormLoginAuthenticator
{
    public function authenticate(Request $request): Passport
    {
        try {
            return parent::authenticate($request);
            // @phpstan-ignore catch.neverThrown (intentional, see the linked Symfony issue above)
        } catch (BadRequestHttpException $badRequestHttpException) {
            throw new BadCredentialsException('Bad credentials.', 0, $badRequestHttpException);
        }
    }
}
