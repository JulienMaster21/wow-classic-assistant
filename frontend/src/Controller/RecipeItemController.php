<?php

namespace App\Controller;

use App\Entity\RecipeItem;
use App\Form\Entity\RecipeItemFormType;
use App\Repository\ProfessionRepository;
use App\Repository\RecipeItemRepository;
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
 * Class RecipeItemController
 * @package App\Controller
 */
class RecipeItemController extends AbstractController {

    /**
     * @Route("/recipe-items", name="recipeItemIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Icon',
            'Name',
            'Profession',
            'Required skill level'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('recipeItemCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('recipeItem/index.html.twig', [
            'title'         => 'Recipe Items',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/recipe-items/{id}", name="recipeItemShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param RecipeItemRepository $recipeItemRepository
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         RecipeItemRepository $recipeItemRepository,
                         $id) {

        // Get recipe item
        $recipeItem = $recipeItemRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('recipeItemIndex');
        $editRoute = $this->generateUrl('recipeItemEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('recipeItemDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('recipeItem/show.html.twig', [
            'name'          => $recipeItem->getName(),
            'recipeItem'    => $recipeItem,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/recipe-items/create", name="recipeItemCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_ITEM_CREATE')) {

            // Create form
            $form = $this->createForm(RecipeItemFormType::class, null, [
                'action' => $this->generateUrl('recipeItemStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('recipeItemIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('recipeItem/create.html.twig', [
                'className'     => 'recipe item',
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
     * @Route("/recipe-items", name="recipeItemStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ProfessionRepository $professionRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          ProfessionRepository $professionRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_ITEM_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('recipe_item_form');
            $recipeItem = new RecipeItem();
            $recipeItem->setName($form['name']);
            $recipeItem->setItemLinkUrl($form['itemLinkUrl']);
            $recipeItem->setIconLinkUrl($form['iconLinkUrl']);
            $recipeItem->setRequiredSkillLevel($form['requiredSkillLevel']);

            // Validate properties
            $errors = $validator->validate($recipeItem);

            // Validate relations
            // Profession
            if (array_key_exists('profession', $form)) {
                $profession = $professionRepository->find($form['profession']);

                // Check if profession exists
                if ($profession) {
                    $recipeItem->setProfession($profession);
                }
                else {
                    $invalidProfessionConstraint = new ConstraintViolation( 'You selected a profession that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $recipeItem,
                                                                            'profession',
                                                                            $form['profession']);
                    $errors->add($invalidProfessionConstraint);
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($recipeItem);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the recipe item.');
                return $this->redirect($this->generateUrl('recipeItemShow', ['id' => $recipeItem->getId()]));
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
     * @Route("/recipe-items/edit/{id}", name="recipeItemEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param RecipeItemRepository $recipeItemRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         RecipeItemRepository $recipeItemRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_ITEM_EDIT')) {

            // Get recipe item
            $recipeItem = $recipeItemRepository->find($id);

            // Create form
            $form = $this->createForm(RecipeItemFormType::class, $recipeItem, [
                'action' => $this->generateUrl('recipeItemUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('recipeItemShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('recipeItem/edit.html.twig', [
                'className' => 'recipe item',
                'form'      => $formView,
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
     * @Route("/recipe-items/{id}", name="recipeItemUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param RecipeItemRepository $recipeItemRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           RecipeItemRepository $recipeItemRepository,
                           ProfessionRepository $professionRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_ITEM_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('recipe_item_form');
            $recipeItem = $recipeItemRepository->find($id);
            $recipeItem->setName($form['name']);
            $recipeItem->setItemLinkUrl($form['itemLinkUrl']);
            $recipeItem->setIconLinkUrl($form['iconLinkUrl']);
            $recipeItem->setRequiredSkillLevel($form['requiredSkillLevel']);

            // Validate properties
            $errors = $validator->validate($recipeItem);

            // Validate relations
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
                                                                            $recipeItem,
                                                                            'profession',
                                                                            $profession);
                    $errors->add($invalidProfessionConstraint);
                }
            }

            if (sizeof($errors) <= 0 and
                $professionValid) {

                // Assign single relations
                $recipeItem->setProfession($profession);

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the recipe item.');
                return $this->redirect($this->generateUrl('recipeItemShow', ['id' => $id]));
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
     * @Route("/recipe-items/{id}", name="recipeItemDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param RecipeItemRepository $recipeItemRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(RecipeItemRepository $recipeItemRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_RECIPE_ITEM_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get recipe item
            $recipeItem = $recipeItemRepository->find($id);

            // Update database
            $entityManager->remove($recipeItem);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The recipe item has been successfully deleted.');
            return $this->redirect($this->generateUrl('recipeItemIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/recipe-items", name="recipeItemApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param RecipeItemRepository $recipeItemRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             RecipeItemRepository $recipeItemRepository,
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
                $start = $recipeItemRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $recipeItemRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $recipeItemRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'recipeItemRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/recipe-items/{id}", name="recipeItemApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param RecipeItemRepository $recipeItemRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            RecipeItemRepository $recipeItemRepository,
                            $id) {

        // Get recipe item
        $entity = $recipeItemRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'recipeItemRelations'
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
     * @Route("/api/recipe-items/row", name="recipeItemApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param RecipeItemRepository $recipeItemRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           RecipeItemRepository $recipeItemRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $recipeItemRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('recipeItemShow', ['id' => $id]);
            $profession = $entity['profession']['name'];
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $entity['itemLinkUrl'] . '" target="_blank">' .
                        '<img src="' . $entity['iconLinkUrl'] . '" alt="An icon representing the ' . $entity['name'] . ' recipe item">' .
                    '</a>' .
                '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $profession . '</td>' .
                '<td class="align-middle">' . $entity['requiredSkillLevel'] . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }
        return new Response($serializer->serialize($result, 'json', [
            'groups' => ['attributes', 'recipeItemRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}