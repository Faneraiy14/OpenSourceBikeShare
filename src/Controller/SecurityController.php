<?php

declare(strict_types=1);

namespace BikeShare\Controller;

use BikeShare\App\Entity\User;
use BikeShare\App\Security\UserProvider;
use BikeShare\Mail\MailSenderInterface;
use BikeShare\App\Security\RequiresAppUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    use RequiresAppUserTrait;

    public function login(
        bool $isSmsSystemEnabled,
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            [
                'isSmsSystemEnabled' => $isSmsSystemEnabled,
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }

    public function logout(): void
    {
        // controller can be blank: it will never be executed!
        throw new \Exception("Don't forget to activate logout in security.php");
    }

    public function resetPassword(
        bool $isSmsSystemEnabled,
        Request $request,
        MailSenderInterface $mailer,
        UserProvider $userProvider,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator
    ): Response {
        if ($request->isMethod('POST')) {
            $number = $request->request->get('number');

            try {
                // UserProvider itself catches \InvalidArgumentException from
                // phone-number purification and rethrows it as
                // UserNotFoundException, so that's the only case reaching
                // here - not revealing which phones exist is intentional
                // (the generic success flash below covers both cases).
                $user = $userProvider->loadUserByIdentifier($number);
            } catch (UserNotFoundException) {
                $user = null;
            }

            if ($user instanceof User) {
                mt_srand(crc32(microtime()));
                $plainPassword = substr(md5(mt_rand() . microtime() . $user->getUserIdentifier()), 0, 8);
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                );
                $userProvider->upgradePassword($user, $hashedPassword);

                $subject = $translator->trans('Password reset');
                $names = preg_split("/[\s,]+/", $user->getUserIdentifier());
                $firstname = $names[0];
                $message = $translator->trans('Hello') . ' ' . $firstname . ",\n\n" .
                    $translator->trans('Your password has been reset successfully.') . "\n\n" .
                    $translator->trans('Your new password is:') . "\n" . $plainPassword;

                $mailer->sendMail($user->getEmail(), $subject, $message);
            }

            $this->addFlash(
                'success',
                $translator->trans('Your password has been reset successfully.')
                . ' '
                . $translator->trans('Check your email.')
            );

            return $this->redirectToRoute('reset_password');
        }

        return $this->render(
            'security/reset_password.html.twig',
            [
                'isSmsSystemEnabled' => $isSmsSystemEnabled,
            ]
        );
    }
}
