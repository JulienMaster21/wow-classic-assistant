<?php

namespace App\Controller;

use App\Entity\Faction;
use App\Form\Entity\FactionFormType;
use App\Repository\CharacterRepository;
use App\Repository\FactionRepository;
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
 * Class FactionController
 * @package App\Controller
 */
class FactionController extends AbstractController {

    /**
     * @Route("/factions", name="factionIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Name'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('factionCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('faction/index.html.twig', [
            'title'         => 'Factions',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/factions/{id}", name="factionShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param FactionRepository $factionRepository
     * @param int $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         FactionRepository $factionRepository,
                         $id) {

        // Get faction
        $faction = $factionRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('factionIndex');
        $editRoute = $this->generateUrl('factionEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('factionDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('faction/show.html.twig', [
            'name'          => $faction->getName(),
            'faction'       => $faction,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/factions/create", name="factionCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_FACTION_CREATE')) {

            // Create form
            $form = $this->createForm(FactionFormType::class, null, [
                'action' => $this->generateUrl('factionStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('factionIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('faction/create.html.twig', [
                'className'     => 'faction',
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
     * @Route("/factions", name="factionStore", methods={"PUT"})
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
        if ($this->isGranted('ROLE_FACTION_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('faction_form');
            $faction = new Faction();
            $faction->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($faction);

            // Validate relations
            // Characters
            if (array_key_exists('characters', $form)) {
                foreach ($form['characters'] as $id) {
                    $character = $characterRepository->find($id);

                    // Check if character exists
                    if ($character) {
                        $faction->addCharacter($character);
                    }
                    else {
                        $invalidCharacterConstraint = new ConstraintViolation(  'You selected a character that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $faction,
                                                                                'characters',
                                                                                $id);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($faction);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the faction.');
                return $this->redirect($this->generateUrl('factionShow', ['id' => $faction->getId()]));
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
     * @Route("/factions/edit/{id}", name="factionEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param FactionRepository $factionRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         FactionRepository $factionRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_FACTION_EDIT')) {

            // Get faction
            $faction = $factionRepository->find($id);

            // Get relations
            $characters = $faction->getCharacters();

            // Create form
            $form = $this->createForm(FactionFormType::class, $faction, [
                'action' => $this->generateUrl('factionUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('factionShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('faction/edit.html.twig', [
                'className'     => 'faction',
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
     * @Route("/factions/{id}", name="factionUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param FactionRepository $factionRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           FactionRepository $factionRepository,
                           CharacterRepository $characterRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_FACTION_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('faction_form');
            $faction = $factionRepository->find($id);
            $faction->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($faction);

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
                                                                                $faction,
                                                                                'characters',
                                                                                $character);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new characters
                foreach ($faction->getCharacters() as $character) {
                    $faction->removeCharacter($character);
                }
                if (array_key_exists('characters', $form)) {
                    foreach ($form['characters'] as $character) {
                        $faction->addCharacter($characterRepository->find($character));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the faction.');
                return $this->redirect($this->generateUrl('factionShow', ['id' => $id]));
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
     * @Route("/factions/{id}", name="factionDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param FactionRepository $factionRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(FactionRepository $factionRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_FACTION_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get faction
            $faction = $factionRepository->find($id);

            // Remove all characters
            foreach ($faction->getCharacters() as $character) {
                $faction->removeCharacter($character);
            }

            // Update database
            $entityManager->remove($faction);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The faction has been successfully deleted.');
            return $this->redirect($this->generateUrl('factionIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/factions", name="factionApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param FactionRepository $factionRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             FactionRepository $factionRepository,
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
                $start = $factionRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $factionRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $factionRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'factionRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/factions/{id}", name="factionApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param FactionRepository $factionRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            FactionRepository $factionRepository,
                            $id) {

        // Get faction
        $entity = $factionRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'factionRelations'
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
     * @Route("/api/factions/row", name="factionApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param FactionRepository $factionRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           FactionRepository $factionRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $factionRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('factionShow', ['id' => $id]);
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
                'factionRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}
