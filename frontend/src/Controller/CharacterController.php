<?php

namespace App\Controller;

use App\Entity\Character;
use App\Form\Entity\CharacterFormType;
use App\Repository\CharacterRepository;
use App\Repository\FactionRepository;
use App\Repository\PlayableClassRepository;
use App\Repository\ProfessionRepository;
use App\Repository\RecipeRepository;
use App\Repository\UserRepository;
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
 * Class CharacterController
 * @package App\Controller
 */
class CharacterController extends AbstractController {

    /**
     * @Route("/characters", name="characterIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        if ($this->isGranted('ROLE_CHARACTER_INDEX')) {

            // Define columns and get rows
            $columns = [
                'Name',
                'User',
                'Faction',
                'Class',
                '# of Professions'
            ];

            // Generate routes
            $createRoute = $this->generateUrl('characterCreate');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('character/index.html.twig', [
                'title'         => 'Characters',
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
     * @Route("/characters/{id}", name="characterShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param CharacterRepository $characterRepository
     * @param int $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         CharacterRepository $characterRepository,
                         $id) {

        if ($this->isGranted('ROLE_CHARACTER_SHOW')) {

            // Get character
            $character = $characterRepository->find($id);

            // Generate routes
            $indexRoute = $this->generateUrl('characterIndex');
            $editRoute = $this->generateUrl('characterEdit', ['id' => $id]);
            $deleteRoute = $this->generateUrl('characterDelete', ['id' => $id]);

            // Get character recipes by profession
            $characterRecipes = [];
            foreach ($character->getRecipes() as $recipe) {
                $professionName = $recipe->getProfession()->getName();
                if (!isset($characterRecipes[$professionName])) {
                    $characterRecipes[$professionName] = [$recipe];
                }
                else {
                    array_push($characterRecipes[$professionName], $recipe);
                }
            }

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('character/show.html.twig', [
                'name'              => $character->getName(),
                'character'         => $character,
                'characterRecipes'  => $characterRecipes,
                'indexRoute'        => $indexRoute,
                'editRoute'         => $editRoute,
                'deleteRoute'       => $deleteRoute,
                'messages'          => $messages
            ]);
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/characters/create", name="characterCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_CHARACTER_CREATE')) {

            // Create form
            $form = $this->createForm(CharacterFormType::class, null, [
                'action' => $this->generateUrl('characterStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('characterIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('character/create.html.twig', [
                'className'     => 'character',
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
     * @Route("/characters", name="characterStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param UserRepository $userRepository
     * @param FactionRepository $factionRepository
     * @param PlayableClassRepository $playableClassRepository
     * @param ProfessionRepository $professionRepository
     * @param RecipeRepository $recipeRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          UserRepository $userRepository,
                          FactionRepository $factionRepository,
                          PlayableClassRepository $playableClassRepository,
                          ProfessionRepository $professionRepository,
                          RecipeRepository $recipeRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_CHARACTER_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('character_form');
            $character = new Character();
            $character->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($character);

            // Validate relations
            // User
            if (array_key_exists('user', $form)) {
                $user = $userRepository->find($form['user']);

                // Check if user exists
                if ($user) {
                    $character->setUser($user);
                }
                else {
                    $invalidUserConstraint = new ConstraintViolation(   'You selected a user that doesn\'t exist',
                                                                        null,
                                                                        [],
                                                                        $character,
                                                                        'user',
                                                                        $form['user']);
                    $errors->add($invalidUserConstraint);
                }
            }

            // Faction
            if (array_key_exists('faction', $form)) {
                $faction = $factionRepository->find($form['faction']);

                // Check if faction exists
                if ($faction) {
                    $character->setFaction($faction);
                }
                else {
                    $invalidFactionConstraint = new ConstraintViolation('You selected a faction that doesn\'t exist',
                                                                        null,
                                                                        [],
                                                                        $character,
                                                                        'faction',
                                                                        $form['faction']);
                    $errors->add($invalidFactionConstraint);
                }
            }

            // Playable Class
            if (array_key_exists('playableClass', $form)) {
                $playableClass = $playableClassRepository->find($form['playableClass']);

                // Check if playable class exists
                if ($playableClass) {
                    $character->setPlayableClass($playableClass);
                }
                else {
                    $invalidPlayableClassConstraint = new ConstraintViolation(  'You selected a class that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $character,
                                                                                'playableClass',
                                                                                $form['playableClass']);
                    $errors->add($invalidPlayableClassConstraint);
                }
            }

            // Professions
            if (array_key_exists('professions', $form)) {
                foreach ($form['professions'] as $id) {
                    $profession = $professionRepository->find($id);

                    // Check if profession exists
                    if ($profession) {
                        $character->addProfession($profession);
                    }
                    else {
                        $invalidProfessionConstraint = new ConstraintViolation( 'You selected a profession that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $character,
                                                                                'professions',
                                                                                $id);
                        $errors->add($invalidProfessionConstraint);
                    }
                }
            }

            // Recipes
            if (array_key_exists('recipes', $form)) {
                foreach ($form['recipes'] as $id) {
                    $recipe = $recipeRepository->find($id);

                    // Check if recipe exists
                    if ($recipe) {
                        $character->addRecipe($recipe);
                    }
                    else {
                        $invalidRecipeConstraint = new ConstraintViolation( 'You selected a recipe that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $character,
                                                                            'recipes',
                                                                            $id);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($character);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the character.');
                return $this->redirect($this->generateUrl('characterShow', ['id' => $character->getId()]));
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
     * @Route("/characters/edit/{id}", name="characterEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param CharacterRepository $characterRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         CharacterRepository $characterRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_CHARACTER_EDIT')) {

            // Get character
            $character = $characterRepository->find($id);

            // Get relations
            $professions = $character->getProfessions();
            $recipes = $character->getRecipes();

            // Create form
            $form = $this->createForm(CharacterFormType::class, $character, [
                'action' => $this->generateUrl('characterUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('characterShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('character/edit.html.twig', [
                'className'     => 'character',
                'form'          => $formView,
                'professions'   => $professions,
                'recipes'       => $recipes,
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
     * @Route("/characters/{id}", name="characterUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param CharacterRepository $characterRepository
     * @param UserRepository $userRepository
     * @param FactionRepository $factionRepository
     * @param PlayableClassRepository $playableClassRepository
     * @param ProfessionRepository $professionRepository
     * @param RecipeRepository $recipeRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           CharacterRepository $characterRepository,
                           UserRepository $userRepository,
                           FactionRepository $factionRepository,
                           PlayableClassRepository $playableClassRepository,
                           ProfessionRepository $professionRepository,
                           RecipeRepository $recipeRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_CHARACTER_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('character_form');
            $character = $characterRepository->find($id);
            $character->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($character);

            // Validate relations
            // User
            $userValid = false;
            if (array_key_exists('user', $form)) {
                $user = $userRepository->find($form['user']);

                // Check if user exists
                if ($user) {
                    $userValid = true;
                }
                else {
                    $invalidUserConstraint = new ConstraintViolation(   'You selected a user that doesn\'t exist',
                                                                        null,
                                                                        [],
                                                                        $character,
                                                                        'user',
                                                                        $user);
                    $errors->add($invalidUserConstraint);
                }
            }

            // Faction
            $factionValid = false;
            if (array_key_exists('faction', $form)) {
                $faction = $factionRepository->find($form['faction']);

                // Check if faction exists
                if ($faction) {
                    $factionValid = true;
                }
                else {
                    $invalidFactionConstraint = new ConstraintViolation('You selected a faction that doesn\'t exist',
                                                                        null,
                                                                        [],
                                                                        $character,
                                                                        'faction',
                                                                        $faction);
                    $errors->add($invalidFactionConstraint);
                }
            }

            // Playable class
            $playableClassValid = false;
            if (array_key_exists('playableClass', $form)) {
                $playableClass = $playableClassRepository->find($form['playableClass']);

                // Check if playable class exists
                if ($playableClass) {
                    $playableClassValid = true;
                }
                else {
                    $invalidPlayableClassConstraint = new ConstraintViolation(  'You selected a playableClass that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $character,
                                                                                'playableClass',
                                                                                $playableClass);
                    $errors->add($invalidPlayableClassConstraint);
                }
            }

            // Professions
            $validProfessions = [];
            if (array_key_exists('professions', $form)) {
                foreach ($form['professions'] as $profession) {
                    $profession = $professionRepository->find($profession);

                    // Check if profession exists
                    if ($profession) {
                        array_push($validProfessions, $profession);
                    }
                    else {
                        $invalidProfessionConstraint = new ConstraintViolation( 'You selected a profession that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $character,
                                                                                'professions',
                                                                                $profession);
                        $errors->add($invalidProfessionConstraint);
                    }
                }
            }

            // Recipes
            $validRecipes = [];
            if (array_key_exists('recipes', $form)) {
                foreach ($form['recipes'] as $recipe) {
                    $recipe = $recipeRepository->find($recipe);

                    // Check if recipe exists
                    if ($recipe) {
                        array_push($validRecipes, $recipe);
                    }
                    else {
                        $invalidRecipeConstraint = new ConstraintViolation( 'You selected a recipe that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $character,
                                                                            'recipes',
                                                                            $recipe);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0 and
                $userValid and
                $factionValid and
                $playableClassValid) {

                // Assign single relations
                $character->setUser($user);
                $character->setFaction($faction);
                $character->setPlayableClass($playableClass);

                // Remove all and then add new professions
                foreach ($character->getProfessions() as $profession) {
                    $character->removeProfession($profession);
                }
                if (array_key_exists('professions', $form)) {
                    foreach ($form['professions'] as $profession) {
                        $character->addProfession($professionRepository->find($profession));
                    }
                }

                // Remove all and then add new recipes
                foreach ($character->getRecipes() as $recipe) {
                    $character->removeRecipe($recipe);
                }
                if (array_key_exists('recipes', $form)) {
                    foreach ($form['recipes'] as $recipe) {
                        $character->addRecipe($recipeRepository->find($recipe));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the character.');
                return $this->redirect($this->generateUrl('characterShow', ['id' => $id]));
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
     * @Route("/characters/{id}", name="characterDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param CharacterRepository $characterRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(CharacterRepository $characterRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_CHARACTER_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get character
            $character = $characterRepository->find($id);

            // Remove all professions
            foreach ($character->getProfessions() as $profession) {
                $character->removeProfession($profession);
            }

            // Remove all recipes
            foreach ($character->getRecipes() as $recipe) {
                $character->removeRecipe($recipe);
            }

            // Update database
            $entityManager->remove($character);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The character has been successfully deleted.');
            return $this->redirect($this->generateUrl('characterIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/characters", name="characterApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param CharacterRepository $characterRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             CharacterRepository $characterRepository,
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
                $start = $characterRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $characterRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $characterRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'characterRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/characters/{id}", name="characterApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param CharacterRepository $characterRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            CharacterRepository $characterRepository,
                            $id) {

        // Get character
        $entity = $characterRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'characterRelations'
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
     * @Route("/api/characters/row", name="characterApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param CharacterRepository $characterRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           CharacterRepository $characterRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $characterRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('characterShow', ['id' => $id]);
            $user = $entity['user']['username'];
            $faction = $entity['faction']['name'];
            $playableClass = $entity['playableClass']['name'];
            $amountOfProfessions = count($entity['professions']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $user . '</td>' .
                '<td class="align-middle">' . $faction . '</td>' .
                '<td class="align-middle">' . $playableClass . '</td>' .
                '<td class="align-middle">' . $amountOfProfessions . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }

        return new Response($serializer->serialize($result, 'json', [
            'groups' => [
                'attributes',
                'characterRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}
