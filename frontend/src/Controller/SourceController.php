<?php

namespace App\Controller;

use App\Entity\Source;
use App\Form\Entity\SourceFormType;
use App\Repository\ReagentRepository;
use App\Repository\SourceRepository;
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
 * Class SourceController
 * @package App\Controller
 */
class SourceController extends AbstractController {

    /**
     * @Route("/sources", name="sourceIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Name'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('sourceCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('source/index.html.twig', [
            'title'         => 'Sources',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/sources/{id}", name="sourceShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param SourceRepository $sourceRepository
     * @param int $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         SourceRepository $sourceRepository,
                         $id) {

        // Get source
        $source = $sourceRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('sourceIndex');
        $editRoute = $this->generateUrl('sourceEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('sourceDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('source/show.html.twig', [
            'name'          => $source->getName(),
            'source'        => $source,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/sources/create", name="sourceCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_SOURCE_CREATE')) {

            // Create form
            $form = $this->createForm(SourceFormType::class, null, [
                'action' => $this->generateUrl('sourceStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('sourceIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('source/create.html.twig', [
                'className'     => 'source',
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
     * @Route("/sources", name="sourceStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ReagentRepository $reagentRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          ReagentRepository $reagentRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_SOURCE_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('source_form');
            $source = new Source();
            $source->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($source);

            // Validate relations
            // Reagents
            if (array_key_exists('reagents', $form)) {
                foreach ($form['reagents'] as $id) {
                    $reagent = $reagentRepository->find($id);

                    // Check if reagent exists
                    if ($reagent) {
                        $source->addReagent($reagent);
                    }
                    else {
                        $invalidReagentConstraint = new ConstraintViolation('You selected a reagent that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $source,
                                                                            'reagents',
                                                                            $id);
                        $errors->add($invalidReagentConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($source);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the source.');
                return $this->redirect($this->generateUrl('sourceShow', ['id' => $source->getId()]));
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
     * @Route("/sources/edit/{id}", name="sourceEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param SourceRepository $sourceRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         SourceRepository $sourceRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_SOURCE_EDIT')) {

            // Get source
            $source = $sourceRepository->find($id);

            // Get relations
            $reagents = $source->getReagents();

            // Create form
            $form = $this->createForm(SourceFormType::class, $source, [
                'action' => $this->generateUrl('sourceUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('sourceShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('source/edit.html.twig', [
                'className' => 'source',
                'form'      => $formView,
                'reagents'  => $reagents,
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
     * @Route("/sources/{id}", name="sourceUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SourceRepository $sourceRepository
     * @param ReagentRepository $reagentRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           SourceRepository $sourceRepository,
                           ReagentRepository $reagentRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_SOURCE_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('source_form');
            $source = $sourceRepository->find($id);
            $source->setName($form['name']);

            // Validate properties
            $errors = $validator->validate($source);

            // Validate relations
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
                                                                            $source,
                                                                            'reagents',
                                                                            $reagent);
                        $errors->add($invalidReagentConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new reagents
                foreach ($source->getReagents() as $reagent) {
                    $source->removeReagent($reagent);
                }
                if (array_key_exists('reagents', $form)) {
                    foreach ($form['reagents'] as $reagent) {
                        $source->addReagent($reagentRepository->find($reagent));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the source.');
                return $this->redirect($this->generateUrl('sourceShow', ['id' => $id]));
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
     * @Route("/sources/{id}", name="sourceDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param SourceRepository $sourceRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(SourceRepository $sourceRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_SOURCE_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get source
            $source = $sourceRepository->find($id);

            // Remove all reagents
            foreach ($source->getReagents() as $reagent) {
                $source->removeReagent($reagent);
            }

            // Update database
            $entityManager->remove($source);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The source has been successfully deleted.');
            return $this->redirect($this->generateUrl('sourceIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/sources", name="sourceApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param SourceRepository $sourceRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             SourceRepository $sourceRepository,
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
                $start = $sourceRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $sourceRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $sourceRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'sourceRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/sources/{id}", name="sourceApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param SourceRepository $sourceRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            SourceRepository $sourceRepository,
                            $id) {

        // Get source
        $entity = $sourceRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'sourceRelations'
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
     * @Route("/api/sources/row", name="sourceApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param SourceRepository $sourceRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           SourceRepository $sourceRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $sourceRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('sourceShow', ['id' => $id]);
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
                'sourceRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}
