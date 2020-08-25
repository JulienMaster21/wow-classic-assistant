<?php

namespace App\Controller;

use App\Entity\Profession;
use App\Form\Entity\ProfessionFormType;
use App\Repository\CharacterRepository;
use App\Repository\ProfessionRepository;
use App\Repository\RecipeItemRepository;
use App\Repository\RecipeRepository;
use App\Repository\TrainerRepository;
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
 * Class ProfessionController
 * @package App\Controller
 */
class ProfessionController extends AbstractController {

    /**
     * @Route("/professions", name="professionIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Icon',
            'Name',
            'Main Profession?',
            '# of Trainers',
            '# of Recipes'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('professionCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('profession/index.html.twig', [
            'title'         => 'Professions',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/professions/{id}", name="professionShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param ProfessionRepository $professionRepository
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         ProfessionRepository $professionRepository,
                         $id) {

        // Get profession
        $profession = $professionRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('professionIndex');
        $editRoute = $this->generateUrl('professionEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('professionDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('profession/show.html.twig', [
            'name'          => $profession->getName(),
            'profession'    => $profession,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/professions/create", name="professionCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_PROFESSION_CREATE')) {

            // Create form
            $form = $this->createForm(ProfessionFormType::class, null, [
                'action' => $this->generateUrl('professionStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('professionIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('profession/create.html.twig', [
                'className'     => 'profession',
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
     * @Route("/professions", name="professionStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param TrainerRepository $trainerRepository
     * @param RecipeRepository $recipeRepository
     * @param RecipeItemRepository $recipeItemRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          TrainerRepository $trainerRepository,
                          RecipeRepository $recipeRepository,
                          RecipeItemRepository $recipeItemRepository,
                          CharacterRepository $characterRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_PROFESSION_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('profession_form');
            $profession = new Profession();
            $profession->setName($form['name']);
            $profession->setProfessionLinkUrl($form['professionLinkUrl']);
            $profession->setIconLinkUrl($form['iconLinkUrl']);
            $profession->setIsMainProfession($form['isMainProfession']);

            // Validate properties
            $errors = $validator->validate($profession);

            // Validate relations
            // Trainers
            if (array_key_exists('trainers', $form)) {
                foreach ($form['trainers'] as $id) {
                    $trainer = $trainerRepository->find($id);

                    // Check if trainer exists
                    if ($trainer) {
                        $profession->addTrainer($trainer);
                    }
                    else {
                        $invalidTrainerConstraint = new ConstraintViolation('You selected a trainer that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $profession,
                                                                            'trainers',
                                                                            $id);
                        $errors->add($invalidTrainerConstraint);
                    }
                }
            }

            // Recipes
            if (array_key_exists('recipes', $form)) {
                foreach ($form['recipes'] as $id) {
                    $recipe = $recipeRepository->find($id);

                    // Check if recipe exists
                    if ($recipe) {
                        $profession->addRecipe($recipe);
                    }
                    else {
                        $invalidRecipeConstraint = new ConstraintViolation( 'You selected a recipe that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $profession,
                                                                            'recipes',
                                                                            $id);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            // Recipe items
            if (array_key_exists('recipeItems', $form)) {
                foreach ($form['recipeItems'] as $id) {
                    $recipeItem = $recipeItemRepository->find($id);

                    // Check if recipe item exists
                    if ($recipeItem) {
                        $profession->addRecipeItem($recipeItem);
                    }
                    else {
                        $invalidRecipeItemConstraint = new ConstraintViolation( 'You selected a recipe item that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $profession,
                                                                                'recipeItems',
                                                                                $id);
                        $errors->add($invalidRecipeItemConstraint);
                    }
                }
            }

            // Characters
            if (array_key_exists('characters', $form)) {
                foreach ($form['characters'] as $id) {
                    $character = $characterRepository->find($id);

                    // Check if character exists
                    if ($character) {
                        $profession->addCharacter($character);
                    }
                    else {
                        $invalidCharacterConstraint = new ConstraintViolation(  'You selected a character that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $profession,
                                                                                'characters',
                                                                                $id);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($profession);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the profession.');
                return $this->redirect($this->generateUrl('professionShow', ['id' => $profession->getId()]));
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
     * @Route("/professions/edit/{id}", name="professionEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param ProfessionRepository $professionRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         ProfessionRepository $professionRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_PROFESSION_EDIT')) {

            // Get profession
            $profession = $professionRepository->find($id);

            // Get relations
            $trainers = $profession->getTrainers();
            $recipes = $profession->getRecipes();
            $recipeItems = $profession->getRecipeItems();
            $characters = $profession->getCharacters();

            // Create form
            $form = $this->createForm(ProfessionFormType::class, $profession, [
                'action' => $this->generateUrl('professionUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('professionShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('profession/edit.html.twig', [
                'className'     => 'profession',
                'form'          => $formView,
                'trainers'      => $trainers,
                'recipes'       => $recipes,
                'recipeItems'   => $recipeItems,
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
     * @Route("/professions/{id}", name="professionUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ProfessionRepository $professionRepository
     * @param TrainerRepository $trainerRepository
     * @param RecipeRepository $recipeRepository
     * @param RecipeItemRepository $recipeItemRepository
     * @param CharacterRepository $characterRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           ProfessionRepository $professionRepository,
                           TrainerRepository $trainerRepository,
                           RecipeRepository $recipeRepository,
                           RecipeItemRepository $recipeItemRepository,
                           CharacterRepository $characterRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_PROFESSION_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('profession_form');
            $profession = $professionRepository->find($id);
            $profession->setName($form['name']);
            $profession->setProfessionLinkUrl($form['professionLinkUrl']);
            $profession->setIconLinkUrl($form['iconLinkUrl']);
            $profession->setIsMainProfession($form['isMainProfession']);

            // Validate properties
            $errors = $validator->validate($profession);

            // Validate relations
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
                                                                            $profession,
                                                                            'trainers',
                                                                            $trainer);
                        $errors->add($invalidTrainerConstraint);
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
                                                                            $profession,
                                                                            'recipes',
                                                                            $recipe);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            // Recipe items
            $validRecipeItems = [];
            if (array_key_exists('recipeItems', $form)) {
                foreach ($form['recipeItems'] as $recipeItem) {
                    $recipeItem = $recipeItemRepository->find($recipeItem);

                    // Check if recipe item exists
                    if ($recipeItem) {
                        array_push($validRecipeItems, $recipeItem);
                    }
                    else {
                        $invalidRecipeItemConstraint = new ConstraintViolation( 'You selected a recipe item that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $profession,
                                                                                'recipeItems',
                                                                                $recipeItem);
                        $errors->add($invalidRecipeItemConstraint);
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
                                                                                $profession,
                                                                                'characters',
                                                                                $character);
                        $errors->add($invalidCharacterConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new trainers
                foreach ($profession->getTrainers() as $trainer) {
                    $profession->removeTrainer($trainer);
                }
                if (array_key_exists('trainers', $form)) {
                    foreach ($form['trainers'] as $trainer) {
                        $profession->addTrainer($trainerRepository->find($trainer));
                    }
                }

                // Remove all and then add new recipes
                foreach ($profession->getRecipes() as $recipe) {
                    $profession->removeRecipe($recipe);
                }
                if (array_key_exists('recipes', $form)) {
                    foreach ($form['recipes'] as $recipe) {
                        $profession->addRecipe($recipeRepository->find($recipe));
                    }
                }

                // Remove all and then add new recipe items
                foreach ($profession->getRecipeItems() as $recipeItem) {
                    $profession->removeRecipeItem($recipeItem);
                }
                if (array_key_exists('recipeItems', $form)) {
                    foreach ($form['recipeItems'] as $recipeItem) {
                        $profession->addRecipeItem($recipeItemRepository->find($recipeItem));
                    }
                }

                // Remove all and then add new characters
                foreach ($profession->getCharacters() as $character) {
                    $profession->removeCharacter($character);
                }
                if (array_key_exists('character', $form)) {
                    foreach ($form['character'] as $character) {
                        $profession->addCharacter($characterRepository->find($character));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the profession.');
                return $this->redirect($this->generateUrl('professionShow', ['id' => $id]));
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
     * @Route("/professions/{id}", name="professionDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param ProfessionRepository $professionRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(ProfessionRepository $professionRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_PROFESSION_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get profession
            $profession = $professionRepository->find($id);

            // Remove all trainers
            foreach ($profession->getTrainers() as $trainer) {
                $profession->removeTrainer($trainer);
            }

            // Remove all recipes
            foreach ($profession->getRecipes() as $recipe) {
                $profession->removeRecipe($recipe);
            }

            // Remove all recipe items
            foreach ($profession->getRecipeItems() as $recipeItem) {
                $profession->removeRecipeItem($recipeItem);
            }

            // Remove all characters
            foreach ($profession->getCharacters() as $character) {
                $profession->removeCharacter($character);
            }

            // Update database
            $entityManager->remove($profession);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The profession has been successfully deleted.');
            return $this->redirect($this->generateUrl('professionIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/professions", name="professionApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ProfessionRepository $professionRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             ProfessionRepository $professionRepository,
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
                $start = $professionRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $professionRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $professionRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'professionRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/professions/{id}", name="professionApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param ProfessionRepository $professionRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            ProfessionRepository $professionRepository,
                            $id) {

        // Get profession
        $entity = $professionRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'professionRelations'
                ]
            ]), 200, [
                'Content-Type' => 'application/json'
            ]);
        }
        else {
            return new Response($serializer->serialize([
                'Response' => 'We couldn\'t find a profession with that id.'
            ],
                'json'
            ),
                404, [
                    'Content-Type' => 'application/json'
            ]);
        }
    }

    /**
     * @Route("/api/professions/row", name="professionApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ProfessionRepository $professionRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           ProfessionRepository $professionRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $professionRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('professionShow', ['id' => $id]);
            $iconLinkUrl =  str_replace('large', 'medium',
                            str_replace('small', 'medium', $entity['iconLinkUrl']));
            $isMainProfession = $entity['isMainProfession'] ? 'Yes' : 'No';
            $amountOfTrainers = count($entity['trainers']);
            $amountOfRecipes = count($entity['recipes']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $entity['professionLinkUrl'] . '" target="_blank">' .
                        '<img src="' . $iconLinkUrl . '" alt="An icon representing the ' . $entity['name'] . ' profession">' .
                    '</a>' .
                '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $isMainProfession . '</td>' .
                '<td class="align-middle">' . $amountOfTrainers . '</td>' .
                '<td class="align-middle">' . $amountOfRecipes . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }
        return new Response($serializer->serialize($result, 'json', [
            'groups' => ['attributes', 'professionRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}