<?php

namespace App\Controller;

use App\Entity\Vendor;
use App\Form\Entity\VendorFormType;
use App\Repository\LocationRepository;
use App\Repository\ReagentRepository;
use App\Repository\VendorRepository;
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
 * Class VendorController
 * @package App\Controller
 */
class VendorController extends AbstractController {

    /**
     * @Route("/vendors", name="vendorIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Name',
            'Reaction to Alliance',
            'Reaction to Horde',
            '# of Reagents'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('vendorCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('vendor/index.html.twig', [
            'title'         => 'Vendors',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/vendors/{id}", name="vendorShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param VendorRepository $vendorRepository
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         VendorRepository $vendorRepository,
                         $id) {

        // Get vendor
        $vendor = $vendorRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('vendorIndex');
        $editRoute = $this->generateUrl('vendorEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('vendorDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('vendor/show.html.twig', [
            'name'          => $vendor->getName(),
            'vendor'        => $vendor,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/vendors/create", name="vendorCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_VENDOR_CREATE')) {

            // Create form
            $form = $this->createForm(VendorFormType::class, null, [
                'action' => $this->generateUrl('vendorStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('vendorIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('vendor/create.html.twig', [
                'className'     => 'vendor',
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
     * @Route("/vendors", name="vendorStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ReagentRepository $reagentRepository
     * @param LocationRepository $locationRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          ReagentRepository $reagentRepository,
                          LocationRepository $locationRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_VENDOR_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('vendor_form');
            $vendor = new Vendor();
            $vendor->setName($form['name']);
            $vendor->setVendorLinkUrl($form['vendorLinkUrl']);
            $vendor->setReactionToAlliance($form['reactionToAlliance']);
            $vendor->setReactionToHorde($form['reactionToHorde']);

            // Validate properties
            $errors = $validator->validate($vendor);

            // Validate relations
            // Reagents
            if (array_key_exists('reagents', $form)) {
                foreach ($form['reagents'] as $id) {
                    $reagent = $reagentRepository->find($id);

                    // Check if reagent exists
                    if ($reagent) {
                        $vendor->addReagent($reagent);
                    }
                    else {
                        $invalidReagentConstraint = new ConstraintViolation( 'You selected a reagent that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $vendor,
                                                                            'reagents',
                                                                            $id);
                        $errors->add($invalidReagentConstraint);
                    }
                }
            }

            // Locations
            if (array_key_exists('locations', $form)) {
                foreach ($form['locations'] as $id) {
                    $location = $locationRepository->find($id);

                    // Check if location exists
                    if ($location) {
                        $vendor->addLocation($location);
                    }
                    else {
                        $invalidLocationConstraint = new ConstraintViolation(   'You selected a location that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $vendor,
                                                                                'locations',
                                                                                $id);
                        $errors->add($invalidLocationConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($vendor);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the vendor.');
                return $this->redirect($this->generateUrl('vendorShow', ['id' => $vendor->getId()]));
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
     * @Route("/vendors/edit/{id}", name="vendorEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param VendorRepository $vendorRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         VendorRepository $vendorRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_VENDOR_EDIT')) {

            // Get vendor
            $vendor = $vendorRepository->find($id);

            // Get relations
            $reagents = $vendor->getReagents();
            $locations = $vendor->getLocations();

            // Create form
            $form = $this->createForm(VendorFormType::class, $vendor, [
                'action' => $this->generateUrl('vendorUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('vendorShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('vendor/edit.html.twig', [
                'className' => 'vendor',
                'form'      => $formView,
                'reagents'  => $reagents,
                'locations' => $locations,
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
     * @Route("/vendors/{id}", name="vendorUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param VendorRepository $vendorRepository
     * @param ReagentRepository $reagentRepository
     * @param LocationRepository $locationRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           VendorRepository $vendorRepository,
                           ReagentRepository $reagentRepository,
                           LocationRepository $locationRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_VENDOR_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('vendor_form');
            $vendor = $vendorRepository->find($id);
            $vendor->setName($form['name']);
            $vendor->setVendorLinkUrl($form['vendorLinkUrl']);
            $vendor->setReactionToAlliance($form['reactionToAlliance']);
            $vendor->setReactionToHorde($form['reactionToHorde']);

            // Validate properties
            $errors = $validator->validate($vendor);

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
                                                                            $vendor,
                                                                            'reagents',
                                                                            $reagent);
                        $errors->add($invalidReagentConstraint);
                    }
                }
            }

            // Locations
            $validLocations = [];
            if (array_key_exists('locations', $form)) {
                foreach ($form['locations'] as $location) {
                    $location = $locationRepository->find($location);

                    // Check if location exists
                    if ($location) {
                        array_push($validLocations, $location);
                    }
                    else {
                        $invalidLocationConstraint = new ConstraintViolation(   'You selected a location that doesn\'t exist',
                                                                                null,
                                                                                [],
                                                                                $vendor,
                                                                                'locations',
                                                                                $location);
                        $errors->add($invalidLocationConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new reagents
                foreach ($vendor->getReagents() as $reagent) {
                    $vendor->removeReagent($reagent);
                }
                if (array_key_exists('reagents', $form)) {
                    foreach ($form['reagents'] as $reagent) {
                        $vendor->addReagent($reagentRepository->find($reagent));
                    }
                }

                // Remove all and then add new locations
                foreach ($vendor->getLocations() as $location) {
                    $vendor->removeLocation($location);
                }
                if (array_key_exists('locations', $form)) {
                    foreach ($form['locations'] as $location) {
                        $vendor->addLocation($locationRepository->find($location));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the vendor.');
                return $this->redirect($this->generateUrl('vendorShow', ['id' => $id]));
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
     * @Route("/vendors/{id}", name="vendorDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param VendorRepository $vendorRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(VendorRepository $vendorRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_VENDOR_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get vendor
            $vendor = $vendorRepository->find($id);

            // Remove all reagents
            foreach ($vendor->getReagents() as $reagent) {
                $vendor->removeReagent($reagent);
            }

            // Remove all locations
            foreach ($vendor->getLocations() as $location) {
                $vendor->removeLocation($location);
            }

            // Update database
            $entityManager->remove($vendor);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The vendor has been successfully deleted.');
            return $this->redirect($this->generateUrl('vendorIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/vendors", name="vendorApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param VendorRepository $vendorRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             VendorRepository $vendorRepository,
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
                $start = $vendorRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $vendorRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $vendorRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'vendorRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/vendors/{id}", name="vendorApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param VendorRepository $vendorRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            VendorRepository $vendorRepository,
                            $id) {

        // Get vendor
        $entity = $vendorRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'vendorRelations'
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
     * @Route("/api/vendors/row", name="vendorApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param VendorRepository $vendorRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           VendorRepository $vendorRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $vendorRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('vendorShow', ['id' => $id]);
            $amountOfReagents = count($entity['reagents']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' . $entity['reactionToAlliance'] . '</td>' .
                '<td class="align-middle">' . $entity['reactionToHorde'] . '</td>' .
                '<td class="align-middle">' . $amountOfReagents . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }
        return new Response($serializer->serialize($result, 'json', [
            'groups' => ['attributes', 'vendorRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}