<?php

namespace App\Controller;

use App\Entity\Trainer;
use App\Form\Entity\TrainerFormType;
use App\Repository\LocationRepository;
use App\Repository\ProfessionRepository;
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
 * Class TrainerController
 * @package App\Controller
 */
class TrainerController extends AbstractController {

    /**
     * @Route("/trainers", name="trainerIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Name',
            'Reaction to Alliance',
            'Reaction to Horde',
            'Location',
            '# of recipes'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('trainerCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('trainer/index.html.twig', [
            'title'         => 'Trainers',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/trainers/{id}", name="trainerShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param TrainerRepository $trainerRepository
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         TrainerRepository $trainerRepository,
                         $id) {

        // Get trainer
        $trainer = $trainerRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('trainerIndex');
        $editRoute = $this->generateUrl('trainerEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('trainerDelete', ['id' => $id]);

        // Get trainer recipes by profession
        $trainerRecipes = [];
        foreach ($trainer->getRecipes() as $recipe) {
            $professionName = $recipe->getProfession()->getName();
            if (!isset($trainerRecipes[$professionName])) {
                $trainerRecipes[$professionName] = [$recipe];
            }
            else {
                array_push($trainerRecipes[$professionName], $recipe);
            }
        }

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('trainer/show.html.twig', [
            'name'              => $trainer->getName(),
            'trainer'           => $trainer,
            'trainerRecipes'    => $trainerRecipes,
            'indexRoute'        => $indexRoute,
            'editRoute'         => $editRoute,
            'deleteRoute'       => $deleteRoute,
            'messages'          => $messages
        ]);
    }

    /**
     * @Route("/trainers/create", name="trainerCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_TRAINER_CREATE')) {

            // Create form
            $form = $this->createForm(TrainerFormType::class, null, [
                'action' => $this->generateUrl('trainerStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('trainerIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('trainer/create.html.twig', [
                'className'     => 'trainer',
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
     * @Route("/trainers", name="trainerStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param LocationRepository $locationRepository
     * @param ProfessionRepository $professionRepository
     * @param RecipeRepository $recipeRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          LocationRepository $locationRepository,
                          ProfessionRepository $professionRepository,
                          RecipeRepository $recipeRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_TRAINER_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('trainer_form');
            $trainer = new Trainer();
            $trainer->setName($form['name']);
            $trainer->setTrainerLinkUrl($form['trainerLinkUrl']);
            $trainer->setReactionToAlliance($form['reactionToAlliance']);
            $trainer->setReactionToHorde($form['reactionToHorde']);

            // Validate properties
            $errors = $validator->validate($trainer);

            // Validate relations
            // Location
            if (array_key_exists('location', $form)) {
                $location = $locationRepository->find($form['location']);

                // Check if location exists
                if ($location) {
                    $trainer->setLocation($location);
                }
                else {
                    $invalidLocationConstraint = new ConstraintViolation(   'You selected a location that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $trainer,
                                                                            'location',
                                                                            $form['location']);
                    $errors->add($invalidLocationConstraint);
                }
            }

            // Professions
            if (array_key_exists('professions', $form)) {
                foreach ($form['professions'] as $id) {
                    $profession = $professionRepository->find($id);

                    // Check if profession exists
                    if ($profession) {
                        $trainer->addProfession($profession);
                    }
                    else {
                        $invalidProfessionConstraint = new ConstraintViolation( 'You selected a profession that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $trainer,
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
                        $trainer->addRecipe($recipe);
                    }
                    else {
                        $invalidRecipeConstraint = new ConstraintViolation( 'You selected a recipe that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $trainer,
                                                                            'recipes',
                                                                            $id);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($trainer);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the trainer.');
                return $this->redirect($this->generateUrl('craftableItemShow', ['id' => $trainer->getId()]));
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
     * @Route("/trainers/edit/{id}", name="trainerEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param TrainerRepository $trainerRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         TrainerRepository $trainerRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_TRAINER_EDIT')) {

            // Get trainer
            $trainer = $trainerRepository->find($id);

            // Get relations
            $professions = $trainer->getProfessions();
            $recipes = $trainer->getRecipes();

            // Create form
            $form = $this->createForm(TrainerFormType::class, $trainer, [
                'action' => $this->generateUrl('trainerUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('trainerShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('trainer/edit.html.twig', [
                'className'     => 'trainer',
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
     * @Route("/trainers/{id}", name="trainerUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param TrainerRepository $trainerRepository
     * @param LocationRepository $locationRepository
     * @param ProfessionRepository $professionRepository
     * @param RecipeRepository $recipeRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           TrainerRepository $trainerRepository,
                           LocationRepository $locationRepository,
                           ProfessionRepository $professionRepository,
                           RecipeRepository $recipeRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_TRAINER_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('trainer_form');
            $trainer = $trainerRepository->find($id);
            $trainer->setName($form['name']);
            $trainer->setTrainerLinkUrl($form['trainerLinkUrl']);
            $trainer->setReactionToAlliance($form['reactionToAlliance']);
            $trainer->setReactionToHorde($form['reactionToHorde']);

            // Validate properties
            $errors = $validator->validate($trainer);

            // Validate relations
            // Location
            $locationValid = false;
            if (array_key_exists('location', $form)) {
                $location = $locationRepository->find($form['location']);

                // Check if location exists
                if ($location) {
                    $locationValid = true;
                }
                else {
                    $invalidLocationConstraint = new ConstraintViolation(   'You selected a location that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $trainer,
                                                                            'location',
                                                                            $location);
                    $errors->add($invalidLocationConstraint);
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
                                                                                $trainer,
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
                            $trainer,
                            'recipes',
                            $recipe);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0 and
                $locationValid) {

                // Assign single relations
                $trainer->setLocation($location);

                // Remove all and then add new professions
                foreach ($trainer->getProfessions() as $profession) {
                    $trainer->removeProfession($profession);
                }
                if (array_key_exists('professions', $form)) {
                    foreach ($form['professions'] as $profession) {
                        $trainer->addProfession($professionRepository->find($profession));
                    }
                }

                // Remove all and then add new recipes
                foreach ($trainer->getRecipes() as $recipe) {
                    $trainer->removeRecipe($recipe);
                }
                if (array_key_exists('recipes', $form)) {
                    foreach ($form['recipes'] as $recipe) {
                        $trainer->addRecipe($recipeRepository->find($recipe));
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
     * @Route("/trainers/{id}", name="trainerDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param TrainerRepository $trainerRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(TrainerRepository $trainerRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_TRAINER_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get trainer
            $trainer = $trainerRepository->find($id);

            // Remove all professions
            foreach ($trainer->getProfessions() as $profession) {
                $trainer->removeProfession($profession);
            }

            // Remove all recipes
            foreach ($trainer->getRecipes() as $recipe) {
                $trainer->removeRecipe($recipe);
            }

            // Update database
            $entityManager->remove($trainer);
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
     * @Route("/api/trainers", name="trainerApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param TrainerRepository $trainerRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             TrainerRepository $trainerRepository,
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
                $start = $trainerRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $trainerRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $trainerRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'trainerRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/trainers/{id}", name="trainerApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param TrainerRepository $trainerRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            TrainerRepository $trainerRepository,
                            $id) {

        // Get trainer
        $entity = $trainerRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'trainerRelations'
                ]
            ]), 200, [
                'Content-Type' => 'application/json'
            ]);
        }
        else {
            return new Response($serializer->serialize([
                'Response' => 'We couldn\'t find a trainer with that id.'
            ],
                'json'
            ),
                404, [
                    'Content-Type' => 'application/json'
            ]);
        }
    }

    /**
     * @Route("/api/trainers/row", name="trainerApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param TrainerRepository $trainerRepository
     * @return Response
     */
    public function apiRow(Request $request,
                            SerializerInterface $serializer,
                            TrainerRepository $trainerRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $trainerRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('trainerShow', ['id' => $id]);
            $locationId = isset($entity['location']['id']) ? isset($entity['location']['id']) : null;
            if ($locationId !== null) {
                $locationShowPath = $this->generateUrl('locationShow', ['id' => $locationId]);
                $locationTd = '<a href="' . $locationShowPath . '">' . $entity['location']['name'] . '</a>';
            }
            else {
                $locationTd = 'No known location';
            }
            $amountOfRecipes = count($entity['recipes']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $entity['reactionToAlliance'] . '</td>' .
                '<td class="align-middle">' . $entity['reactionToHorde'] . '</td>' .
                '<td class="align-middle">' . $locationTd . '</td>' .
                '<td class="align-middle">' . $amountOfRecipes . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }
        return new Response($serializer->serialize($result, 'json', [
            'groups' => ['attributes', 'trainerRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}