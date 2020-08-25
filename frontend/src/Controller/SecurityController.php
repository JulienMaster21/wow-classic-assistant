<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginFormType;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use App\Service\FlashMessageCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

/**
 * Class SecurityController
 * @package App\Controller
 */
class SecurityController extends AbstractController {

    /**
     * @Route("/login", name="login")
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function login(FlashMessageCollector $flashMessageCollector): Response {

        // Create login form
        $form = $this->createForm(LoginFormType::class, null, [
            'csrf_token_id' => 'authenticate'
        ]);
        $formView = $form->createView();

        // Get all flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('security/login.html.twig', [
            'form' => $formView,
            'messages' => $messages
        ]);
    }

    /**
     * @Route("/register", name="register")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param GuardAuthenticatorHandler $guardHandler
     * @param LoginFormAuthenticator $authenticator
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function register(Request $request,
                             UserPasswordEncoderInterface $passwordEncoder,
                             GuardAuthenticatorHandler $guardHandler,
                             LoginFormAuthenticator $authenticator,
                             FlashMessageCollector $flashMessageCollector): Response {

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUsername($form->get('username')->getData());
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_USER']);

            // encode the password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Save user
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            // TODO verification email

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }
        $formView = $form->createView();

        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('security/register.html.twig', [
            'messages' => $messages,
            'form' => $formView,
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout() {

        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
