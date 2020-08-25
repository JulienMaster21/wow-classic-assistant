<?php

namespace App\Controller;

use App\Entity\CraftableItem;
use App\Form\Entity\CraftableItemFormType;
use App\Repository\CraftableItemRepository;
use App\Repository\RecipeRepository;
use App\Service\FlashMessageCollector;
use App\Service\UnitConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
 * Class CraftableItemController
 * @package App\Controller
 */
class CraftableItemController extends AbstractController {

    /**
     * @Route("/craftable-items", name="craftableItemIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Icon',
            'Name',
            'Item slot',
            'Sell price',
            '# of Recipes'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('craftableItemCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('craftableItem/index.html.twig', [
            'title'         => 'Craftable Items',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/craftable-items/{id}", name="craftableItemShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param CraftableItemRepository $craftableItemRepository
     * @param UnitConverter $unitConverter
     * @param int $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         CraftableItemRepository $craftableItemRepository,
                         UnitConverter $unitConverter,
                         $id) {

        // Get craftable item
        $craftableItem = $craftableItemRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('craftableItemIndex');
        $editRoute = $this->generateUrl('craftableItemEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('craftableItemDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('craftableItem/show.html.twig', [
            'unitConverter' => $unitConverter,
            'name'          => $craftableItem->getName(),
            'craftableItem' => $craftableItem,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/craftable-items/create", name="craftableItemCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_CRAFTABLE_ITEM_CREATE')) {

            // Create form
            $form = $this->createForm(CraftableItemFormType::class, null, [
                'action' => $this->generateUrl('craftableItemStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('craftableItemIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('craftableItem/create.html.twig', [
                'className'     => 'craftable item',
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
     * @Route("/craftable-items", name="craftableItemStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param RecipeRepository $recipeRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          RecipeRepository $recipeRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_CRAFTABLE_ITEM_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('craftable_item_form');
            $craftableItem = new CraftableItem();
            $craftableItem->setName($form['name']);
            $craftableItem->setItemLinkUrl($form['itemLinkUrl']);
            $craftableItem->setIconLinkUrl($form['iconLinkUrl']);
            $craftableItem->setItemSlot($form['itemSlot']);
            $craftableItem->setSellPrice($form['sellPrice']);

            // Validate properties
            $errors = $validator->validate($craftableItem);

            // Validate relations
            // Recipes
            if (array_key_exists('recipes', $form)) {
                foreach ($form['recipes'] as $id) {
                    $recipe = $recipeRepository->find($id);

                    // Check if recipe exists
                    if ($recipe) {
                        $craftableItem->addRecipe($recipe);
                    }
                    else {
                        $invalidRecipeConstraint = new ConstraintViolation( 'You selected a recipe that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $craftableItem,
                                                                            'recipes',
                                                                            $id);
                        $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {
                // Add entity to database
                $entityManager->persist($craftableItem);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the craftable item.');
                return $this->redirect($this->generateUrl('craftableItemShow', ['id' => $craftableItem->getId()]));
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
     * @Route("/craftable-items/edit/{id}", name="craftableItemEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param CraftableItemRepository $craftableItemRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         CraftableItemRepository $craftableItemRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_CRAFTABLE_ITEM_EDIT')) {

            // Get craftable item
            $craftableItem = $craftableItemRepository->find($id);

            // Get relations
            $recipes = $craftableItem->getRecipes();

            // Create form
            $form = $this->createForm(CraftableItemFormType::class, $craftableItem, [
                'action' => $this->generateUrl('craftableItemUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('craftableItemShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('craftableItem/edit.html.twig', [
                'className' => 'craftable item',
                'form'      => $formView,
                'recipes'   => $recipes,
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
     * @Route("/craftable-items/{id}", name="craftableItemUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param CraftableItemRepository $craftableItemRepository
     * @param RecipeRepository $recipeRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           CraftableItemRepository $craftableItemRepository,
                           RecipeRepository $recipeRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_CRAFTABLE_ITEM_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('craftable_item_form');
            $craftableItem = $craftableItemRepository->find($id);
            $craftableItem->setName($form['name']);
            $craftableItem->setItemLinkUrl($form['itemLinkUrl']);
            $craftableItem->setIconLinkUrl($form['iconLinkUrl']);
            $craftableItem->setItemSlot($form['itemSlot']);
            $craftableItem->setSellPrice($form['sellPrice']);

            // Validate properties
            $errors = $validator->validate($craftableItem);

            // Validate relations
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
                                                                            $craftableItem,
                                                                            'recipes',
                                                                            $recipe);
                                                                            $errors->add($invalidRecipeConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new recipes
                foreach ($craftableItem->getRecipes() as $recipe) {
                    $craftableItem->removeRecipe($recipe);
                }
                if (array_key_exists('recipes', $form)) {
                    foreach ($form['recipes'] as $recipe) {
                        $craftableItem->addRecipe($recipeRepository->find($recipe));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the craftable item.');
                return $this->redirect($this->generateUrl('craftableItemShow', ['id' => $id]));
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
     * @Route("/craftable-items/{id}", name="craftableItemDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param CraftableItemRepository $craftableItemRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(CraftableItemRepository $craftableItemRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_CRAFTABLE_ITEM_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get craftable item
            $craftableItem = $craftableItemRepository->find($id);

            // Remove all recipes
            foreach ($craftableItem->getRecipes() as $recipe) {
                $craftableItem->removeRecipe($recipe);
            }

            // Update database
            $entityManager->remove($craftableItem);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The craftable item has been successfully deleted.');
            return $this->redirect($this->generateUrl('craftableItemIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/craftable-items", name="craftableItemApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param CraftableItemRepository $craftableItemRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             CraftableItemRepository $craftableItemRepository,
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
                $start = $craftableItemRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $craftableItemRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $craftableItemRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'craftableItemRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/craftable-items/{id}", name="craftableItemApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param CraftableItemRepository $craftableItemRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            CraftableItemRepository $craftableItemRepository,
                            $id) {

        // Get craftable item
        $entity = $craftableItemRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'craftableItemRelations'
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
     * @Route("/api/craftable-items/row", name="craftableItemApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param CraftableItemRepository $craftableItemRepository
     * @param UnitConverter $unitConverter
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           CraftableItemRepository $craftableItemRepository,
                           UnitConverter $unitConverter) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $craftableItemRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $iconLinkUrl =  str_replace('large', 'medium',
                            str_replace('small', 'medium', $entity['iconLinkUrl']));
            $showPath = $this->generateUrl('craftableItemShow', ['id' => $id]);
            $sellPrice = $entity['sellPrice'] ? $unitConverter->convertIntToMoneyAmount($entity['sellPrice']) : 'Not sellable';
            $amountOfRecipes = count($entity['recipes']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $entity['itemLinkUrl'] . '" target="_blank">' .
                        '<img src="' . $iconLinkUrl . '" alt="An icon representing the ' . $entity['name'] . ' craftable item">' .
                    '</a>' .
                '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $entity['itemSlot'] . '</td>' .
                '<td class="align-middle">' . $sellPrice . '</td>' .
                '<td class="align-middle">' . $amountOfRecipes . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }

        return new Response($serializer->serialize($result, 'json', [
            'groups' => [
                'attributes',
                'craftableItemRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}