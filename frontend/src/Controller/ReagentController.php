<?php

namespace App\Controller;

use App\Entity\Reagent;
use App\Form\Entity\ReagentFormType;
use App\Repository\ReagentRepository;
use App\Repository\RecipeRepository;
use App\Repository\SourceRepository;
use App\Repository\VendorRepository;
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
 * Class ReagentController
 * @package App\Controller
 */
class ReagentController extends AbstractController {

    /**
     * @Route("/reagents", name="reagentIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Icon',
            'Name',
            'Average buy price',
            'Sources',
            '# of Recipes'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('reagentCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('reagent/index.html.twig', [
            'title'         => 'Reagents',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/reagents/{id}", name="reagentShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param ReagentRepository $reagentRepository
     * @param UnitConverter $unitConverter
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         ReagentRepository $reagentRepository,
                         UnitConverter $unitConverter,
                         $id) {

        // Get reagent
        $reagent = $reagentRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('reagentIndex');
        $editRoute = $this->generateUrl('reagentEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('reagentDelete', ['id' => $id]);

        // Get reagent recipes by profession
        $reagentRecipes = [];
        foreach ($reagent->getRecipes() as $recipe) {
            $professionName = $recipe->getProfession()->getName();
            if (!isset($reagentRecipes[$professionName])) {
                $reagentRecipes[$professionName] = [$recipe];
            }
            else {
                array_push($reagentRecipes[$professionName], $recipe);
            }
        }

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('reagent/show.html.twig', [
            'unitConverter'     => $unitConverter,
            'name'              => $reagent->getName(),
            'reagent'           => $reagent,
            'reagentRecipes'    => $reagentRecipes,
            'indexRoute'        => $indexRoute,
            'editRoute'         => $editRoute,
            'deleteRoute'       => $deleteRoute,
            'messages'          => $messages
        ]);
    }

    /**
     * @Route("/reagents/create", name="reagentCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_REAGENT_CREATE')) {

            // Create form
            $form = $this->createForm(ReagentFormType::class, null, [
                'action' => $this->generateUrl('reagentStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('reagentIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('reagent/create.html.twig', [
                'className'     => 'reagent',
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
     * @Route("/reagents", name="reagentStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SourceRepository $sourceRepository
     * @param RecipeRepository $recipeRepository
     * @param VendorRepository $vendorRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          SourceRepository $sourceRepository,
                          RecipeRepository $recipeRepository,
                          VendorRepository $vendorRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_REAGENT_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('reagent_form');
            $reagent = new Reagent();
            $reagent->setName($form['name']);
            $reagent->setItemLinkUrl($form['itemLinkUrl']);
            $reagent->setIconLinkUrl($form['iconLinkUrl']);

            // Validate properties
            $errors = $validator->validate($reagent);

            // Validate relations
            // Sources
            if (array_key_exists('sources', $form)) {
                foreach ($form['sources'] as $id) {
                    $source = $sourceRepository->find($id);

                    // Check if source exists
                    if ($source) {
                        $reagent->addSource($source);
                    }
                    else {
                        $invalidSourceConstraint = new ConstraintViolation( 'You selected a source that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $reagent,
                                                                            'sources',
                                                                            $id);
                        $errors->add($invalidSourceConstraint);
                    }
                }
            }

            // Recipes
            if (array_key_exists('recipes', $form)) {
                foreach ($form['recipes'] as $id) {
                    $recipe = $recipeRepository->find($id);

                    // Check if recipe exists
                    if ($recipe) {
                        $reagent->addRecipe($recipe);
                    }
                    else {
                        $invalidRecipeConstraint = new ConstraintViolation( 'You selected a recipe that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $reagent,
                                                                            'recipes',
                                                                            $id);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            // Vendors
            if (array_key_exists('vendors', $form)) {
                foreach ($form['vendors'] as $id) {
                    $vendor = $vendorRepository->find($id);

                    // Check if vendor exists
                    if ($vendor) {
                        $reagent->addVendor($vendor);
                    }
                    else {
                        $invalidVendorConstraint = new ConstraintViolation( 'You selected a vendor that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $reagent,
                                                                            'vendors',
                                                                            $id);
                        $errors->add($invalidVendorConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($reagent);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the reagent.');
                return $this->redirect($this->generateUrl('reagentShow', ['id' => $reagent->getId()]));
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
     * @Route("/reagents/edit/{id}", name="reagentEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param ReagentRepository $reagentRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         ReagentRepository $reagentRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_REAGENT_EDIT')) {

            // Get reagent
            $reagent = $reagentRepository->find($id);

            // Get relations
            $sources = $reagent->getSources();
            $recipes = $reagent->getRecipes();
            $vendors = $reagent->getVendors();

            // Create form
            $form = $this->createForm(ReagentFormType::class, $reagent, [
                'action' => $this->generateUrl('reagentUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('reagentShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('reagent/edit.html.twig', [
                'className' => 'reagent',
                'form'      => $formView,
                'sources'   => $sources,
                'recipes'   => $recipes,
                'vendors'   => $vendors,
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
     * @Route("/reagents/{id}", name="reagentUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ReagentRepository $reagentRepository
     * @param SourceRepository $sourceRepository
     * @param RecipeRepository $recipeRepository
     * @param VendorRepository $vendorRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           ReagentRepository $reagentRepository,
                           SourceRepository $sourceRepository,
                           RecipeRepository $recipeRepository,
                           VendorRepository $vendorRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_REAGENT_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('reagent_form');
            $reagent = $reagentRepository->find($id);
            $reagent->setName($form['name']);
            $reagent->setItemLinkUrl($form['itemLinkUrl']);
            $reagent->setIconLinkUrl($form['iconLinkUrl']);

            // Validate properties
            $errors = $validator->validate($reagent);

            // Validate relations
            // Sources
            $validSources = [];
            if (array_key_exists('sources', $form)) {
                foreach ($form['sources'] as $source) {
                    $source = $sourceRepository->find($source);

                    // Check if source exists
                    if ($source) {
                        array_push($validSources, $source);
                    }
                    else {
                        $invalidSourceConstraint = new ConstraintViolation( 'You selected a source that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $reagent,
                                                                            'sources',
                                                                            $source);
                        $errors->add($invalidSourceConstraint);
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
                                                                            $reagent,
                                                                            'recipes',
                                                                            $recipe);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            // Vendors
            $validVendors = [];
            if (array_key_exists('vendors', $form)) {
                foreach ($form['vendors'] as $vendor) {
                    $vendor = $vendorRepository->find($vendor);

                    // Check if vendor exists
                    if ($vendor) {
                        array_push($validVendors, $vendor);
                    }
                    else {
                        $invalidVendorConstraint = new ConstraintViolation( 'You selected a vendor that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $reagent,
                                                                            'vendors',
                                                                            $vendor);
                        $errors->add($invalidVendorConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new sources
                foreach ($reagent->getSources() as $source) {
                    $reagent->removeSource($source);
                }
                if (array_key_exists('sources', $form)) {
                    foreach ($form['sources'] as $source) {
                        $reagent->addSource($sourceRepository->find($source));
                    }
                }

                // Remove all and then add new recipes
                foreach ($reagent->getRecipes() as $recipe) {
                    $reagent->removeRecipe($recipe);
                }
                if (array_key_exists('recipes', $form)) {
                    foreach ($form['recipes'] as $recipe) {
                        $reagent->addRecipe($recipeRepository->find($recipe));
                    }
                }

                // Remove all and then add new vendors
                foreach ($reagent->getVendors() as $vendor) {
                    $reagent->removeVendor($vendor);
                }
                if (array_key_exists('vendors', $form)) {
                    foreach ($form['vendors'] as $vendor) {
                        $reagent->addVendor($vendorRepository->find($vendor));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the reagent.');
                return $this->redirect($this->generateUrl('reagentShow', ['id' => $id]));
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
     * @Route("/reagents/{id}", name="reagentDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param ReagentRepository $reagentRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(ReagentRepository $reagentRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_REAGENT_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get reagent
            $reagent = $reagentRepository->find($id);

            // Remove all sources
            foreach ($reagent->getSources() as $source) {
                $reagent->removeSource($source);
            }

            // Remove all recipes
            foreach ($reagent->getRecipes() as $recipe) {
                $reagent->removeRecipe($recipe);
            }

            // Remove all vendors
            foreach ($reagent->getVendors() as $vendor) {
                $reagent->removeVendor($vendor);
            }

            // Update database
            $entityManager->remove($reagent);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The reagent has been successfully deleted.');
            return $this->redirect($this->generateUrl('reagentIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/reagents", name="reagentApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ReagentRepository $reagentRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             ReagentRepository $reagentRepository,
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
                $start = $reagentRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $reagentRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $reagentRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'reagentRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/reagents/{id}", name="reagentApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param ReagentRepository $reagentRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            ReagentRepository $reagentRepository,
                            $id) {

        // Get reagent
        $entity = $reagentRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'reagentRelations'
                ]
            ]), 200, [
                'Content-Type' => 'application/json'
            ]);
        }
        else {
            return new Response($serializer->serialize([
                'Response' => 'We couldn\'t find a reagent with that id.'
            ],
                'json'
            ),
                404, [
                    'Content-Type' => 'application/json'
            ]);
        }
    }

    /**
     * @Route("/api/reagents/row", name="reagentApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ReagentRepository $reagentRepository
     * @param UnitConverter $unitConverter
     * @return Response
     */
    public function apiRow(Request $request,
                            SerializerInterface $serializer,
                            ReagentRepository $reagentRepository,
                            UnitConverter $unitConverter) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $reagentRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('reagentShow', ['id' => $id]);
            // Get the average buy price
            // TODO Either Get price per unit instead or display how many you will buy
            $total = 0;
            $buyPrices = $reagentRepository->findOneBuyPrices($id);
            // Add all of the prices together
            foreach ($buyPrices as $buyPrice) {
                $total += intval($buyPrice['buy_price']);
            }
            // Divide by the length and get coin value
            // Or mark as unbuyable
            $total > 0 ? $averageBuyPrice = $unitConverter->convertIntToMoneyAmount(intval($total/count($buyPrices))) :
                        $averageBuyPrice = 'Can\'t be bought';
            $sources = [];
            foreach ($entity['sources'] as $source) {
                array_push($sources, $source['name']);
            }
            $sources = join(', ', $sources);
            $amountOfRecipes = count($entity['recipes']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $entity['itemLinkUrl'] . '" target="_blank">' .
                        '<img src="' . $entity['iconLinkUrl'] . '" alt="An icon representing the ' . $entity['name'] . ' reagent">' .
                    '</a>' .
                '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $averageBuyPrice . '</td>' .
                '<td class="align-middle">' . $sources . '</td>' .
                '<td class="align-middle">' . $amountOfRecipes . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }
        return new Response($serializer->serialize($result, 'json', [
            'groups' => ['attributes', 'reagentRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}