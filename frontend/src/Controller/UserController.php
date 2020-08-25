<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Entity\UserFormType;
use App\Repository\CharacterRepository;
use App\Repository\UserRepository;
use App\Service\FlashMessageCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController {

    /**
     * @Route("/profile/{username}", name="userProfile", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param UserRepository $userRepository
     * @param $username
     * @return RedirectResponse|Response
     */
    public function profile(FlashMessageCollector $flashMessageCollector,
                            UserRepository $userRepository,
                            $username) {

        // Get the requested user and the current user
        $requestedUser = $userRepository->findOneBy(['username' => $username]);
        $currentUser = $this->getUser();

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        if ($requestedUser === $currentUser or $this->isGranted('ROLE_ADMIN')) {

            return $this->render('user/profile.html.twig', [
                'user' => $requestedUser,
                'messages' => $messages
            ]);
        }

        // Add redirect message
        $this->addFlash('error', 'You tried to look at a different user\'s account.
                                        If this is incorrect, check the spelling or
                                        <a class="alert-link" href="' .
                                        $this->generateUrl('contact') .
                                        '">contact us</a>.');

        return new RedirectResponse($this->generateUrl('home'));
    }

    /**
     * @Route("/{username}/characters", name="userCharacters", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param $username
     * @return Response
     */
    public function characters(FlashMessageCollector $flashMessageCollector,
                               $username) {

        // TODO implement characters per user view
        // Authorisation

        // Define columns and get rows
        $columns = [
            'Name',
            'Faction',
            'Class',
            '# of Professions'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('userCharacterCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('user/characters.html.twig', [
            'username'    => $username,
            'columns'     => $columns,
            'createRoute' => $createRoute,
            'messages'    => $messages
        ]);
    }

    /**
     * @Route("/users", name="userIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_INDEX')) {

            // Define columns and get rows
            $columns = [
                'Username',
                'Email',
                'Roles',
                '# of Characters'
            ];

            // Generate routes
            $createRoute = $this->generateUrl('userCreate');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('user/index.html.twig', [
                'title'         => 'Users',
                'columns'       => $columns,
                'createRoute'   => $createRoute,
                'messages'      => $messages
            ]);
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/users/{id}", name="userShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param UserRepository $userRepository
     * @param int $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         UserRepository $userRepository,
                         $id) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_SHOW')) {

            // Get user
            $user = $userRepository->find($id);

            // Generate routes
            $indexRoute = $this->generateUrl('userIndex');
            $editRoute = $this->generateUrl('userEdit', ['id' => $id]);
            $deleteRoute = $this->generateUrl('userDelete', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('user/show.html.twig', [
                'name' => $user->getUsername(),
                'user' => $user,
                'indexRoute' => $indexRoute,
                'editRoute' => $editRoute,
                'deleteRoute' => $deleteRoute,
                'messages' => $messages
            ]);
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/users/create", name="userCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_CREATE')) {

            // Create form
            $form = $this->createForm(UserFormType::class, null, [
                'action' => $this->generateUrl('userStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('userIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('user/create.html.twig', [
                'className'     => 'user',
                'form'          => $formView,
                'indexRoute'    => $indexRoute,
                'messages'      => $messages,
                'errors'        => $errors
            ]);
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/users", name="userStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          UserPasswordEncoderInterface $passwordEncoder,
                          CharacterRepository $characterRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('user_form');
            $user = new User();
            $user->setUsername($form['username']);
            $user->setEmail($form['email']);
            $password = $passwordEncoder->encodePassword($user, $form['password']);
            $user->setPassword($password);
            $user->setRoles($form['roles']);

            // Validate properties
            $errors = $validator->validate($user);

            // Validate relations
            // Characters
            if (array_key_exists('characters', $form)) {
                foreach ($form['characters'] as $id) {
                    $character = $characterRepository->find($id);

                    // Check if character exists
                    if ($character) {
                        $user->addCharacter($character);
                    }
                    else {
                        $invalidCharacterConstraint = new ConstraintViolation(  'You selected a character that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $user,
                                                                                'characters',
                                                                                $id);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($user);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the user.');
                return $this->redirect($this->generateUrl('userShow', ['id' => $user->getId()]));
            }
            else {
                return $this->create($flashMessageCollector, $errors);
            }
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/users/edit/{id}", name="userEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param UserRepository $userRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         UserRepository $userRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_EDIT')) {

            // Get user
            $user = $userRepository->find($id);

            // Get relations
            $characters = $user->getCharacters();

            // Create form
            $form = $this->createForm(UserFormType::class, $user, [
                'action' => $this->generateUrl('userUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('userShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('user/edit.html.twig', [
                'className' => 'user',
                'form'      => $formView,
                'characters'   => $characters,
                'showRoute' => $showRoute,
                'messages'  => $messages,
                'errors'    => $errors
            ]);
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/users/{id}", name="userUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserRepository $userRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           UserPasswordEncoderInterface $passwordEncoder,
                           UserRepository $userRepository,
                           CharacterRepository $characterRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('user_form');
            $user = $userRepository->find($id);
            $user->setUsername($form['username']);
            $user->setEmail($form['email']);
            $password = $passwordEncoder->encodePassword($user, $form['password']);
            $user->setPassword($password);
            $user->setRoles($form['roles']);

            // Validate properties
            $errors = $validator->validate($user);

            // Validate relations
            // Characters
            $validCharacters = [];
            if (array_key_exists('characters', $form)) {
                foreach ($form['characters'] as $character) {
                    $character = $characterRepository->find($character);

                    // Check if character exists
                    if ($character) {
                        array_push($validCharacters, $character);
                    }
                    else {
                        $invalidCharacterConstraint = new ConstraintViolation(  'You selected a character that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $user,
                                                                                'characters',
                                                                                $character);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new characters
                foreach ($user->getCharacters() as $character) {
                    $user->removeCharacter($character);
                }
                if (array_key_exists('characters', $form)) {
                    foreach ($form['characters'] as $character) {
                        $user->addCharacter($characterRepository->find($character));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the user.');
                return $this->redirect($this->generateUrl('userShow', ['id' => $id]));
            }
            else {
                return $this->edit($flashMessageCollector, $id, $errors);
            }
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/users/{id}", name="userDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param UserRepository $userRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(UserRepository $userRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_USER_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get user
            $user = $userRepository->find($id);

            // Remove all characters
            foreach ($user->getCharacters() as $character) {
                $user->removeCharacter($character);
            }

            // Update database
            $entityManager->remove($user);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The user has been successfully deleted.');
            return $this->redirect($this->generateUrl('userIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/users", name="userApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param UserRepository $userRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             UserRepository $userRepository,
                             $start = null,
                             $length = null,
                             $end = null) {

        // Check if start is valid
        $startParameter = intval($request->query->get('start'));
        if ($startParameter > 0) {
            $start = $startParameter;
        }

        // Check if length is valid
        $lengthParameter = intval($request->query->get('length'));
        if ($lengthParameter > 0) {
            $length = $lengthParameter;
        }

        // Check if end is valid
        $endParameter = intval($request->query->get('end'));
        if ($endParameter > 0) {
            $end = $endParameter;
        }

        // If start doesn't have a value, then give it one
        if ($start === null) {
            // If length and end are defined then use those
            if ($length !== null and $end !== null) {
                $start = $end - $length;
            }
            // Otherwise get the first id
            else {
                $start = $userRepository->findOneBy([], ['id' => 'ASC'])->getId();
            }
        }

        // Define the end condition
        if ($length !== null) {
            $endCondition = $start + $length;
        }
        else if ($end !== null) {
            $endCondition = $end + 1;
        }
        else {
            $endCondition = $start + $userRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $userRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'userRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/users/{id}", name="userApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param UserRepository $userRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            UserRepository $userRepository,
                            $id) {

        // Get user
        $entity = $userRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'userRelations'
                ]
            ]), 200, [
                'Content-Type' => 'application/json'
            ]);
        }
        else {
            return new Response($serializer->serialize([
                'Response' => 'We couldn\'t find a user with that id.'
            ],
                'json'
            ),
                404, [
                    'Content-Type' => 'application/json'
            ]);
        }
    }

    /**
     * @Route("/api/users/row", name="userApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param UserRepository $userRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           UserRepository $userRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $userRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('userShow', ['id' => $id]);
            $charactersPath = $this->generateUrl('userCharacters', ['username' => $entity['username']]);
            // Join roles into a string
            $roles = join(', ', $entity['roles']);
            // Remove the ROLE_ prefix
            $roles = str_replace('ROLE_', '', $roles);
            // Apply to lowercase
            $roles = strtolower($roles);
            $amountOfCharacters = count($entity['characters']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['username'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $entity['email'] . '</td>' .
                '<td class="align-middle">' . $roles . '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $charactersPath . '">' . $amountOfCharacters . '</a>' .
                '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }

        return new Response($serializer->serialize($result, 'json', [
            'groups' => [
                'attributes',
                'userRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}