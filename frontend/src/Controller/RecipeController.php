<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\Entity\RecipeFormType;
use App\Repository\CharacterRepository;
use App\Repository\CraftableItemRepository;
use App\Repository\ProfessionRepository;
use App\Repository\ReagentRepository;
use App\Repository\RecipeItemRepository;
use App\Repository\RecipeRepository;
use App\Repository\TrainerRepository;
use App\Service\FlashMessageCollector;
use App\Service\UnitConverter;
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
 * Class RecipeController
 * @package App\Controller
 */
class RecipeController extends AbstractController {

    /**
     * @Route("/recipes", name="recipeIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Icon',
            'Name',
            'Amount created',
            'Difficulty requirement',
            'Training cost',
            '# of Trainers'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('recipeCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('recipe/index.html.twig', [
            'title'         => 'Recipes',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/recipes/{id}", name="recipeShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param RecipeRepository $recipeRepository
     * @param UnitConverter $unitConverter
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         RecipeRepository $recipeRepository,
                         UnitConverter $unitConverter,
                         $id) {

        // Get recipe
        $recipe = $recipeRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('recipeIndex');
        $editRoute = $this->generateUrl('recipeEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('recipeDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('recipe/show.html.twig', [
            'unitConverter' => $unitConverter,
            'name'          => $recipe->getName(),
            'recipe'        => $recipe,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/recipes/create", name="recipeCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_CREATE')) {

            // Create form
            $form = $this->createForm(RecipeFormType::class, null, [
                'action' => $this->generateUrl('recipeStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('recipeIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('recipe/create.html.twig', [
                'className'     => 'recipe',
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
     * @Route("/recipes", name="recipeStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param RecipeItemRepository $recipeItemRepository
     * @param CraftableItemRepository $craftableItemRepository
     * @param ProfessionRepository $professionRepository
     * @param ReagentRepository $reagentRepository
     * @param TrainerRepository $trainerRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          RecipeItemRepository $recipeItemRepository,
                          CraftableItemRepository $craftableItemRepository,
                          ProfessionRepository $professionRepository,
                          ReagentRepository $reagentRepository,
                          TrainerRepository $trainerRepository,
                          CharacterRepository $characterRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('recipe_form');
            $recipe = new Recipe();
            $recipe->setName($form['name']);
            $recipe->setRecipeLinkUrl($form['recipeLinkUrl']);
            $recipe->setIconLinkUrl($form['iconLinkUrl']);
            $recipe->setDifficultyRequirement($form['difficultyRequirement']);
            $recipe->setDifficultyCategory1($form['difficultyCategory1']);
            $recipe->setDifficultyCategory2($form['difficultyCategory2']);
            $recipe->setDifficultyCategory3($form['difficultyCategory3']);
            $recipe->setDifficultyCategory4($form['difficultyCategory4']);
            $recipe->setMinimumAmountCreated($form['minimumAmountCreated']);
            $recipe->setMaximumAmountCreated($form['maximumAmountCreated']);
            $recipe->setTrainingCost($form['trainingCost']);

            // Validate properties
            $errors = $validator->validate($recipe);

            // Validate relations
            // Recipe item
            if (array_key_exists('recipeItem', $form)) {
                $recipeItem = $recipeItemRepository->find($form['recipeItem']);

                // Check if recipe item exists
                if ($recipeItem) {
                    $recipe->setRecipeItem($recipeItem);
                }
                else {
                    $invalidRecipeItemConstraint = new ConstraintViolation( 'You selected a recipe item that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'recipeItem',
                                                                            $form['recipeItem']);
                    $errors->add($invalidRecipeItemConstraint);
                }
            }

            // Craftable item
            if (array_key_exists('craftableItem', $form)) {
                $craftableItem = $craftableItemRepository->find($form['craftableItem']);

                // Check if craftable item exists
                if ($craftableItem) {
                    $recipe->setCraftableItem($craftableItem);
                }
                else {
                    $invalidCraftableItemConstraint = new ConstraintViolation(  'You selected a craftableItem that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $recipe,
                                                                                'craftableItem',
                                                                                $form['craftableItem']);
                    $errors->add($invalidCraftableItemConstraint);
                }
            }

            // Profession
            if (array_key_exists('profession', $form)) {
                $profession = $professionRepository->find($form['profession']);

                // Check if profession exists
                if ($profession) {
                    $recipe->setProfession($profession);
                }
                else {
                    $invalidProfessionConstraint = new ConstraintViolation( 'You selected a profession that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'profession',
                                                                            $form['profession']);
                    $errors->add($invalidProfessionConstraint);
                }
            }

            // Reagents
            if (array_key_exists('reagents', $form)) {
                foreach ($form['reagents'] as $id) {
                    $reagent = $reagentRepository->find($id);

                    // Check if reagent exists
                    if ($reagent) {
                        $recipe->addReagent($reagent);
                    }
                    else {
                        $invalidReagentConstraint = new ConstraintViolation('You selected a reagent that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'reagents',
                                                                            $id);
                        $errors->add($invalidReagentConstraint);
                    }
                }
            }

            // Validate relations
            // Trainers
            if (array_key_exists('trainers', $form)) {
                foreach ($form['trainers'] as $id) {
                    $trainer = $trainerRepository->find($id);

                    // Check if trainer exists
                    if ($trainer) {
                        $recipe->addTrainer($trainer);
                    }
                    else {
                        $invalidTrainerConstraint = new ConstraintViolation('You selected a trainer that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'trainers',
                                                                            $id);
                        $errors->add($invalidTrainerConstraint);
                    }
                }
            }

            // Validate relations
            // Characters
            if (array_key_exists('characters', $form)) {
                foreach ($form['characters'] as $id) {
                    $character = $characterRepository->find($id);

                    // Check if character exists
                    if ($character) {
                        $recipe->addCharacter($character);
                    }
                    else {
                        $invalidCharacterConstraint = new ConstraintViolation(  'You selected a character that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $recipe,
                                                                                'characters',
                                                                                $id);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($recipe);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the recipe.');
                return $this->redirect($this->generateUrl('recipeShow', ['id' => $recipe->getId()]));
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
     * @Route("/recipes/edit/{id}", name="recipeEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param RecipeRepository $recipeRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         RecipeRepository $recipeRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_EDIT')) {

            // Get recipe
            $recipe = $recipeRepository->find($id);

            // Get relations
            $reagents = $recipe->getReagents();
            $trainers = $recipe->getTrainers();
            $characters = $recipe->getCharacters();

            // Create form
            $form = $this->createForm(RecipeFormType::class, $recipe, [
                'action' => $this->generateUrl('recipeUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('recipeShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('recipe/edit.html.twig', [
                'className'     => 'recipe',
                'form'          => $formView,
                'reagents'      => $reagents,
                'trainers'      => $trainers,
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
     * @Route("/recipes/{id}", name="recipeUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param RecipeRepository $recipeRepository
     * @param RecipeItemRepository $recipeItemRepository
     * @param CraftableItemRepository $craftableItemRepository
     * @param ProfessionRepository $professionRepository
     * @param ReagentRepository $reagentRepository
     * @param TrainerRepository $trainerRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           RecipeRepository $recipeRepository,
                           RecipeItemRepository $recipeItemRepository,
                           CraftableItemRepository $craftableItemRepository,
                           ProfessionRepository $professionRepository,
                           ReagentRepository $reagentRepository,
                           TrainerRepository $trainerRepository,
                           CharacterRepository $characterRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('recipe_form');
            $recipe = $recipeRepository->find($id);
            $recipe->setName($form['name']);
            $recipe->setRecipeLinkUrl($form['recipeLinkUrl']);
            $recipe->setIconLinkUrl($form['iconLinkUrl']);
            $recipe->setDifficultyRequirement($form['difficultyRequirement']);
            $recipe->setDifficultyCategory1($form['difficultyCategory1']);
            $recipe->setDifficultyCategory2($form['difficultyCategory2']);
            $recipe->setDifficultyCategory3($form['difficultyCategory3']);
            $recipe->setDifficultyCategory4($form['difficultyCategory4']);
            $recipe->setMinimumAmountCreated($form['minimumAmountCreated']);
            $recipe->setMaximumAmountCreated($form['maximumAmountCreated']);
            $recipe->setTrainingCost($form['trainingCost']);

            // Validate properties
            $errors = $validator->validate($recipe);

            // Validate relations
            // Recipe item
            $recipeItemValid = false;
            if (array_key_exists('recipeItem', $form)) {
                $recipeItem = $recipeItemRepository->find($form['recipeItem']);

                // Check if recipe item exists
                if ($recipeItem) {
                    $recipeItemValid = true;
                }
                else {
                    $invalidRecipeItemConstraint = new ConstraintViolation( 'You selected a recipe item that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'recipeItem',
                                                                            $recipeItem);
                    $errors->add($invalidRecipeItemConstraint);
                }
            }

            // Craftable item
            $craftableItemValid = false;
            if (array_key_exists('craftableItem', $form)) {
                $craftableItem = $craftableItemRepository->find($form['craftableItem']);

                // Check if craftable item exists
                if ($craftableItem) {
                    $craftableItemValid = true;
                }
                else {
                    $invalidCraftableItemConstraint = new ConstraintViolation(  'You selected a craftable item that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $recipe,
                                                                                'craftableItem',
                                                                                $craftableItem);
                    $errors->add($invalidCraftableItemConstraint);
                }
            }

            // Profession
            $professionValid = false;
            if (array_key_exists('profession', $form)) {
                $profession = $professionRepository->find($form['profession']);

                // Check if profession exists
                if ($profession) {
                    $professionValid = true;
                }
                else {
                    $invalidProfessionConstraint = new ConstraintViolation( 'You selected a profession that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'profession',
                                                                            $profession);
                    $errors->add($invalidProfessionConstraint);
                }
            }

            // Reagents
            $validReagents = [];
            if (array_key_exists('reagents', $form)) {
                foreach ($form['reagents'] as $reagent) {
                    $reagent = $reagentRepository->find($reagent);

                    // Check if reagent exists
                    if ($reagent) {
                        array_push($validReagents, $reagent);
                    }
                    else {
                        $invalidReagentConstraint = new ConstraintViolation('You selected a reagent that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'reagents',
                                                                            $reagent);
                        $errors->add($invalidReagentConstraint);
                    }
                }
            }

            // Trainers
            $validTrainers = [];
            if (array_key_exists('trainers', $form)) {
                foreach ($form['trainers'] as $trainer) {
                    $trainer = $trainerRepository->find($trainer);

                    // Check if trainer exists
                    if ($trainer) {
                        array_push($validTrainers, $trainer);
                    }
                    else {
                        $invalidTrainerConstraint = new ConstraintViolation('You selected a trainer that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipe,
                                                                            'trainers',
                                                                            $trainer);
                        $errors->add($invalidTrainerConstraint);
                    }
                }
            }

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
                                                                                $recipe,
                                                                                'characters',
                                                                                $character);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0 and
                $recipeItemValid and
                $craftableItemValid and
                $professionValid) {

                // Assign single relations
                $recipe->setRecipeItem($recipeItem);
                $recipe->setCraftableItem($craftableItem);
                $recipe->setProfession($profession);

                // Remove all and then add new reagents
                foreach ($recipe->getReagents() as $reagent) {
                    $recipe->removeReagent($reagent);
                }
                if (array_key_exists('reagents', $form)) {
                    foreach ($form['reagents'] as $reagent) {
                        $recipe->addReagent($reagentRepository->find($reagent));
                    }
                }

                // Remove all and then add new trainers
                foreach ($recipe->getTrainers() as $trainer) {
                    $recipe->removeTrainer($trainer);
                }
                if (array_key_exists('trainers', $form)) {
                    foreach ($form['trainers'] as $trainer) {
                        $recipe->addTrainer($trainerRepository->find($trainer));
                    }
                }

                // Remove all and then add new characters
                foreach ($recipe->getCharacters() as $character) {
                    $recipe->removeCharacter($character);
                }
                if (array_key_exists('characters', $form)) {
                    foreach ($form['characters'] as $character) {
                        $recipe->addCharacter($characterRepository->find($character));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the recipe.');
                return $this->redirect($this->generateUrl('recipeShow', ['id' => $id]));
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
     * @Route("/recipes/{id}", name="recipeDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param RecipeRepository $recipeRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(RecipeRepository $recipeRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get recipe
            $recipe = $recipeRepository->find($id);

            // Remove all reagents
            foreach ($recipe->getReagents() as $reagent) {
                $recipe->removeReagent($reagent);
            }

            // Remove all trainers
            foreach ($recipe->getTrainers() as $trainer) {
                $recipe->removeTrainer($trainer);
            }

            // Remove all characters
            foreach ($recipe->getCharacters() as $character) {
                $recipe->removeCharacter($character);
            }

            // Update database
            $entityManager->remove($recipe);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The recipe has been successfully deleted.');
            return $this->redirect($this->generateUrl('recipeIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/recipes", name="recipeApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param RecipeRepository $recipeRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             RecipeRepository $recipeRepository,
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
                $start = $recipeRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $recipeRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $recipeRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'recipeRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/recipes/{id}", name="recipeApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param RecipeRepository $recipeRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            RecipeRepository $recipeRepository,
                            $id) {

        // Get recipe
        $entity = $recipeRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'recipeRelations'
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
     * @Route("/api/recipes/row", name="recipeApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param RecipeRepository $recipeRepository
     * @param UnitConverter $unitConverter
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           RecipeRepository $recipeRepository,
                           UnitConverter $unitConverter) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $recipeRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('recipeShow', ['id' => $id]);
            $amountCreated = $entity['minimumAmountCreated'] === $entity['maximumAmountCreated'] ?
                $entity['minimumAmountCreated'] : $entity['minimumAmountCreated'] . ' - ' . $entity['maximumAmountCreated'];
            $trainingCost = $entity['trainingCost'] !== null ?
                            $unitConverter->convertIntToMoneyAmount($entity['trainingCost']) :
                            'Not trainable';
            $amountOfTrainers = count($entity['trainers']) > 0 ? count($entity['trainers']) : 'Not trainable';
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $entity['recipeLinkUrl'] . '" target="_blank">' .
                        '<img src="' . $entity['iconLinkUrl'] . '" alt="An icon representing the ' . $entity['name'] . ' recipe">' .
                    '</a>' .
                '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $amountCreated . '</td>' .
                '<td class="align-middle">' . $entity['difficultyRequirement'] . '</td>' .
                '<td class="align-middle">' . $trainingCost . '</td>' .
                '<td class="align-middle">' . $amountOfTrainers . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }
        return new Response($serializer->serialize($result, 'json', [
            'groups' => ['attributes', 'recipeRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}