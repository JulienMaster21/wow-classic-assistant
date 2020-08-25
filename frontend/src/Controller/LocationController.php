<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\Entity\LocationFormType;
use App\Repository\LocationRepository;
use App\Repository\TrainerRepository;
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
 * Class LocationController
 * @package App\Controller
 */
class LocationController extends AbstractController {

    /**
     * @Route("/locations", name="locationIndex", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function index(FlashMessageCollector $flashMessageCollector) {

        // Define columns and get rows
        $columns = [
            'Name',
            'Faction status',
            '# of Profession Trainers',
            '# of Vendors'
        ];

        // Generate routes
        $createRoute = $this->generateUrl('locationCreate');

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('location/index.html.twig', [
            'title'         => 'Locations',
            'columns'       => $columns,
            'createRoute'   => $createRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/locations/{id}", name="locationShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param LocationRepository $locationRepository
     * @param $id
     * @return Response
     */
    public function show(FlashMessageCollector $flashMessageCollector,
                         LocationRepository $locationRepository,
                         $id) {

        // Get location
        $location = $locationRepository->find($id);

        // Generate routes
        $indexRoute = $this->generateUrl('locationIndex');
        $editRoute = $this->generateUrl('locationEdit', ['id' => $id]);
        $deleteRoute = $this->generateUrl('locationDelete', ['id' => $id]);

        // Get flash messages
        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('location/show.html.twig', [
            'name'          => $location->getName(),
            'location'      => $location,
            'indexRoute'    => $indexRoute,
            'editRoute'     => $editRoute,
            'deleteRoute'   => $deleteRoute,
            'messages'      => $messages
        ]);
    }

    /**
     * @Route("/locations/create", name="locationCreate", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param null $errors
     * @return Response
     */
    public function create(FlashMessageCollector $flashMessageCollector,
                           $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_LOCATION_CREATE')) {

            // Create form
            $form = $this->createForm(LocationFormType::class, null, [
                'action' => $this->generateUrl('locationStore'),
                'method' => 'PUT'
            ]);
            $formView = $form->createView();

            // Generate routes
            $indexRoute = $this->generateUrl('locationIndex');

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('location/create.html.twig', [
                'className'     => 'location',
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
     * @Route("/locations", name="locationStore", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param TrainerRepository $trainerRepository
     * @param VendorRepository $vendorRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @return RedirectResponse|Response
     */
    public function store(Request $request,
                          ValidatorInterface $validator,
                          TrainerRepository $trainerRepository,
                          VendorRepository $vendorRepository,
                          FlashMessageCollector $flashMessageCollector) {

        // Authorisation
        if ($this->isGranted('ROLE_LOCATION_STORE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('location_form');
            $location = new Location();
            $location->setName($form['name']);
            $location->setLocationLinkUrl($form['locationLinkUrl']);
            $location->setFactionStatus($form['factionStatus']);

            // Validate properties
            $errors = $validator->validate($location);

            // Validate relations
            // Trainers
            if (array_key_exists('trainers', $form)) {
                foreach ($form['trainers'] as $id) {
                    $trainer = $trainerRepository->find($id);

                    // Check if trainer exists
                    if ($trainer) {
                        $location->addTrainer($trainer);
                    } else {
                        $invalidTrainerConstraint = new ConstraintViolation('You selected a trainer that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $location,
                                                                            'trainers',
                                                                            $trainer);
                        $errors->add($invalidTrainerConstraint);
                    }
                }
            }

            // Vendors
            if (array_key_exists('vendors', $form)) {
                foreach ($form['vendors'] as $id) {
                    $vendor = $vendorRepository->find($id);

                    // Check if vendor exists
                    if ($vendor) {
                        $location->addVendor($vendor);
                    } else {
                        $invalidVendorConstraint = new ConstraintViolation( 'You selected a vendor that doesn\'t exist',
                                                                            null,
                                                                            [],
                                                                            $location,
                                                                            'vendors',
                                                                            $vendor);
                        $errors->add($invalidVendorConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Add entity to database
                $entityManager->persist($location);
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully added the location.');
                return $this->redirect($this->generateUrl('locationShow', ['id' => $location->getId()]));
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
     * @Route("/locations/edit/{id}", name="locationEdit", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @param LocationRepository $locationRepository
     * @param $id
     * @param null $errors
     * @return Response
     */
    public function edit(FlashMessageCollector $flashMessageCollector,
                         LocationRepository $locationRepository,
                         $id,
                         $errors = null) {

        // Authorisation
        if ($this->isGranted('ROLE_LOCATION_EDIT')) {

            // Get location
            $location = $locationRepository->find($id);

            // Get relations
            $trainers = $location->getTrainers();
            $vendors = $location->getVendors();

            // Create form
            $form = $this->createForm(LocationFormType::class, $location, [
                'action' => $this->generateUrl('locationUpdate', ['id' => $id]),
                'method' => 'PATCH'
            ]);
            $formView = $form->createView();

            // Generate routes
            $showRoute = $this->generateUrl('locationShow', ['id' => $id]);

            // Get flash messages
            $messages = $flashMessageCollector->getAllMessages();

            return $this->render('location/edit.html.twig', [
                'className' => 'location',
                'form'      => $formView,
                'trainers'  => $trainers,
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
     * @Route("/locations/{id}", name="locationUpdate", requirements={"id" = "\d+"}, methods={"PATCH"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param LocationRepository $locationRepository
     * @param TrainerRepository $trainerRepository
     * @param VendorRepository $vendorRepository
     * @param FlashMessageCollector $flashMessageCollector
     * @param $id
     * @return RedirectResponse|Response
     */
    public function update(Request $request,
                           ValidatorInterface $validator,
                           LocationRepository $locationRepository,
                           TrainerRepository $trainerRepository,
                           VendorRepository $vendorRepository,
                           FlashMessageCollector $flashMessageCollector,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_LOCATION_UPDATE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Process form data
            $form = $request->get('location_form');
            $location = $locationRepository->find($id);
            $location->setName($form['name']);
            $location->setLocationLinkUrl($form['locationLinkUrl']);
            $location->setFactionStatus($form['factionStatus']);

            // Validate properties
            $errors = $validator->validate($location);

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
                                                                            $location,
                                                                            'recipes',
                                                                            $trainer);
                        $errors->add($invalidTrainerConstraint);
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
                                                                            $location,
                                                                            'recipes',
                                                                            $vendor);
                        $errors->add($invalidVendorConstraint);
                    }
                }
            }

            if (sizeof($errors) <= 0) {

                // Remove all and then add new trainers
                foreach ($location->getTrainers() as $trainer) {
                    $location->removeTrainer($trainer);
                }
                if (array_key_exists('trainers', $form)) {
                    foreach ($form['trainers'] as $trainer) {
                        $location->addTrainer($trainerRepository->find($trainer));
                    }
                }

                // Remove all and then add new vendors
                foreach ($location->getVendors() as $vendor) {
                    $location->removeVendor($vendor);
                }
                if (array_key_exists('vendors', $form)) {
                    foreach ($form['vendors'] as $vendor) {
                        $location->addVendor($vendorRepository->find($vendor));
                    }
                }

                // Update database
                $entityManager->flush();

                // Add success flash message
                $this->addFlash('success', 'You successfully updated the location.');
                return $this->redirect($this->generateUrl('locationShow', ['id' => $id]));
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
     * @Route("/locations/{id}", name="locationDelete", requirements={"id" = "\d+"}, methods={"DELETE"})
     * @param LocationRepository $locationRepository
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete(LocationRepository $locationRepository,
                           $id) {

        // Authorisation
        if ($this->isGranted('ROLE_LOCATION_DELETE')) {

            // Get entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // Get location
            $location = $locationRepository->find($id);

            // Remove all trainers
            foreach ($location->getTrainers() as $trainer) {
                $location->removeTrainer($trainer);
            }

            // Remove all vendors
            foreach ($location->getVendors() as $vendor) {
                $location->removeVendor($vendor);
            }

            // Update database
            $entityManager->remove($location);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'The location has been successfully deleted.');
            return $this->redirect($this->generateUrl('locationIndex'));
        }

        return new Response($this->render('bundles/TwigBundle/Exception/error403.html.twig')->getContent(),
            403, [
                'Content-Type' => 'text/html'
        ]);
    }

    /**
     * @Route("/api/locations", name="locationApiIndex", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param LocationRepository $locationRepository
     * @param int|null $start
     * @param int|null $length
     * @param int|null $end
     * @return Response
     */
    public function apiIndex(Request $request,
                             SerializerInterface $serializer,
                             LocationRepository $locationRepository,
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
                $start = $locationRepository->findOneBy([], ['id' => 'ASC'])->getId();
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
            $endCondition = $start + $locationRepository->findOneBy([], ['id' => 'DESC'])->getId();
        }

        // Get the entities
        $entities = [];
        for ($current = $start; $current < $endCondition; $current++) {
            $entity = $locationRepository->find($current);
            if ($entity !== null) {
                array_push($entities, $entity);
            }
        }

        return new Response($serializer->serialize($entities, 'json', [
            'groups' => [
                'attributes',
                'locationRelations']
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/api/locations/{id}", name="locationApiShow", requirements={"id" = "\d+"}, methods={"GET", "HEAD"})
     * @param SerializerInterface $serializer
     * @param LocationRepository $locationRepository
     * @param $id
     * @return Response
     */
    public function apiShow(SerializerInterface $serializer,
                            LocationRepository $locationRepository,
                            $id) {

        // Get location
        $entity = $locationRepository->find($id);

        // Return entity if it exists
        if ($entity !== null) {
            return new Response($serializer->serialize($entity, 'json', [
                'groups' => [
                    'attributes',
                    'locationRelations'
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
     * @Route("/api/locations/row", name="locationApiRow", methods={"GET", "HEAD"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param LocationRepository $locationRepository
     * @return Response
     */
    public function apiRow(Request $request,
                           SerializerInterface $serializer,
                           LocationRepository $locationRepository) {

        // Initialise array serializer
        $arraySerializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        // Get entities JSON response
        $jsonResponse = $this->apiIndex($request, $serializer, $locationRepository);
        $entities = $arraySerializer->decode($jsonResponse->getContent(), 'json');

        // Get id and html string from entities
        $result = [];
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $showPath = $this->generateUrl('locationShow', ['id' => $id]);
            $amountOfTrainers = count($entity['trainers']);
            $amountOfVendors = count($entity['vendors']);
            $htmlString =
            '<tr>' .
                '<td class="align-middle">' .
                    '<a href="' . $showPath . '">' . $entity['name'] . '</a>' .
                '</td>' .
                '<td class="align-middle">' .
                    '<a href="' . $entity['locationLinkUrl'] . '" target="_blank">' . $entity['factionStatus'] . '</a>' .
                '</td>' .
                '<td>' . $amountOfTrainers . '</td>' .
                '<td>' . $amountOfVendors . '</td>' .
            '</tr>';
            array_push($result, ['id' => $id, 'htmlString' => $htmlString]);
        }

        return new Response($serializer->serialize($result, 'json', [
            'groups' => [
                'attributes',
                'locationRelations'
            ]
        ]), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}