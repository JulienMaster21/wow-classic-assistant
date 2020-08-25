<?php

namespace App\Controller;

use App\Entity\PlayableClass;
use App\Form\Entity\PlayableClassFormType;
use App\Repository\CharacterRepository;
use App\Repository\PlayableClassRepository;
use App\Service\FlashMessageCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class PlayableClassController
 * @package App\Controller
 */
class PlayableClassController extends AbstractController {

    /**
     * @Route("/playable-classes", name="playableClassIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Name'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('playableClassCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('playableClass/index.html.twig', [
            'title'         => 'Playable Classes',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/playable-classes/{id}", name="playableClassShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param PlayableClassRepository $playableClassRepository
     * @param int $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         PlayableClassRepository $playableClassRepository,
                         $id) {

        // Get playable class
        $playableClass = $playableClassRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('playableClassIndex');
        $editRoute = $this->generateUrl('playableClassEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('playableClassDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('playableClass/show.html.twig', [
            'name'          => $playableClass->getName(),
            'playableClass' => $playableClass,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/playable-classes/create", name="playableClassCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_PLAYABLE_CLASS_CREATE')) {

            // Create form
            $form = $this->createForm(PlayableClassFormType::class, null, [
                'action' => $this->generateUrl('playableClassStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('playableClassIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('playableClass/create.html.twig', [
                'className'     => 'playable class',
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
     * @Route("/playable-classes", name="playableClassStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          CharacterRepository $characterRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_PLAYABLE_CLASS_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('playable_class_form');
            $playableClass = new PlayableClass();
            $playableClass->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($playableClass);

            // Validate relations
            // Characters
            if (array_key_exists('characters', $form)) {
                foreach ($form['characters'] as $id) {
                    $character = $characterRepository->find($id);

                    // Check if character exists
                    if ($character) {
                        $playableClass->addCharacter($character);
                    }
                    else {
                        $invalidCharacterConstraint = new ConstraintViolation(  'You selected a character that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $playableClass,
                                                                                'characters',
                                                                                $id);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($playableClass);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the playable class.');
                return $this->redirect($this->generateUrl('playableClassShow', ['id' => $playableClass->getId()]));
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
     * @Route("/playable-classes/edit/{id}", name="playableClassEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param PlayableClassRepository $playableClassRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         PlayableClassRepository $playableClassRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_PLAYABLE_CLASS_EDIT')) {

            // Get playable class
            $playableClass = $playableClassRepository->find($id);

            // Get relations
            $characters = $playableClass->getCharacters();

            // Create form
            $form = $this->createForm(PlayableClassFormType::class, $playableClass, [
                'action' => $this->generateUrl('playableClassUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('playableClassShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('playableClass/edit.html.twig', [
                'className'     => 'playable class',
                'form'          => $formView,
                'characters'    => $characters,
                'showRoute'     => $showRoute,
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
     * @Route("/playable-classes/{id}", name="playableClassUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param PlayableClassRepository $playableClassRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           PlayableClassRepository $playableClassRepository,
                           CharacterRepository $characterRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_PLAYABLE_CLASS_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('playable_class_form');
            $playableClass = $playableClassRepository->find($id);
            $playableClass->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($playableClass);

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
                                                                                $playableClass,
                                                                                'characters',
                                                                                $character);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new characters
                foreach ($playableClass->getCharacters() as $character) {
                    $playableClass->removeCharacter($character);
                }
                if (array_key_exists('characters', $form)) {
                    foreach ($form['characters'] as $character) {
                        $playableClass->addCharacter($characterRepository->find($character));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the playable class.');
                return $this->redirect($this->generateUrl('playableClassShow', ['id' => $id]));
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
     * @Route("/playable-classes/{id}", name="playableClassDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param PlayableClassRepository $playableClassRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(PlayableClassRepository $playableClassRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_PLAYABLE_CLASS_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get playable class
            $playableClass = $playableClassRepository->find($id);

            // Remove all characters
            foreach ($playableClass->getCharacters() as $character) {
                $playableClass->removeCharacter($character);
            }

            // Update database
            $entityManager->remove($playableClass);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The playable class has been successfully deleted.');
            return $this->redirect($this->generateUrl('playableClassIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/playable-classes", name="playableClassApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param PlayableClassRepository $playableClassRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             PlayableClassRepository $playableClassRepository,
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
                $start = $playableClassRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $playableClassRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $playableClassRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'playableClassRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/playable-classes/{id}", name="playableClassApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param PlayableClassRepository $playableClassRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            PlayableClassRepository $playableClassRepository,
                            $id) {

        // Get playable class
        $entity = $playableClassRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'playableClassRelations'
                ]
            ]), 200, [
                'Content-Type' => 'application/json'
            ]);
        }
        else {
            return new Response($serializer->serialize([
                'Response' => 'We couldn\'t find an item with that id.'
            ],
                'json'
            ),
                404, [
                    'Content-Type' => 'application/json'
            ]);
        }
    }

    /**
     * @Route("/api/playable-classes/row", name="playableClassApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param PlayableClassRepository $playableClassRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           PlayableClassRepository $playableClassRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $playableClassRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('playableClassShow', ['id' => $id]);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }

        return new Response($serializer->serialize($result, 'json', [
            'groups' => [
                'attributes',
                'playableClassRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}
