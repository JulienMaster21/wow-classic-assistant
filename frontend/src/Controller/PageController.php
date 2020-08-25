<?php

namespace App\Controller;

use App\Service\FlashMessageCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController {

    /**
     * @var array
     */
    private $pages;

    public function __construct() {

        $this->pages = [
            [
                'name' => 'Home',
                'path' => '/'
            ],
            [
                'name' => 'Profession assistant',
                'path' => '/profession-assistant'
            ],
            [
                'name' => 'Database',
                'path' => '/database'
            ],
            [
                'name' => 'About',
                'path' => '/about'
            ],
            [
                'name' => 'Contact',
                'path' => '/contact'
            ]
        ];
    }

    /**
     * @Route("/", name="home", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function home(FlashMessageCollector $flashMessageCollector) {

        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('page/home.html.twig', [
            'pages' => $this->pages,
            'messages' => $messages
        ]);
    }

    /**
     * @Route("/profession-assistant", name="professionAssistant", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function professionAssistant(FlashMessageCollector $flashMessageCollector) {

        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('page/professionAssistant.html.twig', [
            'pages' => $this->pages,
            'messages' => $messages
        ]);
    }

    /**
     * @Route("/database", name="database", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function database(FlashMessageCollector $flashMessageCollector) {

        $tables = [
            [
                'name'          => 'Characters',
                'path'          => $this->generateUrl('characterIndex'),
                'permission'    => 'ROLE_CHARACTER_INDEX',
                'isPublic'      => false
            ],
            [
                'name'          => 'Craftable Items',
                'path'          => $this->generateUrl('craftableItemIndex'),
                'permission'    => 'ROLE_CRAFTABLE_ITEM_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Factions',
                'path'          => $this->generateUrl('factionIndex'),
                'permission'    => 'ROLE_FACTION_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Locations',
                'path'          => $this->generateUrl('locationIndex'),
                'permission'    => 'ROLE_LOCATION_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Playable Classes',
                'path'          => $this->generateUrl('playableClassIndex'),
                'permission'    => 'ROLE_PLAYABLE_CLASS_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Professions',
                'path'          => $this->generateUrl('professionIndex'),
                'permission'    => 'ROLE_PROFESSION_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Reagents',
                'path'          => $this->generateUrl('reagentIndex'),
                'permission'    => 'ROLE_REAGENT_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Recipes',
                'path'          => $this->generateUrl('recipeIndex'),
                'permission'    => 'ROLE_RECIPE_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Recipe Items',
                'path'          => $this->generateUrl('recipeItemIndex'),
                'permission'    => 'ROLE_RECIPE_ITEM_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Sources',
                'path'          => $this->generateUrl('sourceIndex'),
                'permission'    => 'ROLE_SOURCE_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Trainers',
                'path'          => $this->generateUrl('trainerIndex'),
                'permission'    => 'ROLE_TRAINER_INDEX',
                'isPublic'      => true
            ],
            [
                'name'          => 'Users',
                'path'          => $this->generateUrl('userIndex'),
                'permission'    => 'ROLE_USER_INDEX',
                'isPublic'      => false
            ],
            [
                'name'          => 'Vendors',
                'path'          => $this->generateUrl('vendorIndex'),
                'permission'    => 'ROLE_VENDOR_INDEX',
                'isPublic'      => true
            ]
        ];

        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('page/database.html.twig', [
            'pages' => $this->pages,
            'tables' => $tables,
            'messages' => $messages,
        ]);
    }

    /**
     * @Route("/about", name="about", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function about(FlashMessageCollector $flashMessageCollector) {

        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('page/about.html.twig', [
            'pages' => $this->pages,
            'messages' => $messages
        ]);
    }

    /**
     * @Route("/contact", name="contact", methods={"GET", "HEAD"})
     * @param FlashMessageCollector $flashMessageCollector
     * @return Response
     */
    public function contact(FlashMessageCollector $flashMessageCollector) {

        $messages = $flashMessageCollector->getAllMessages();

        return $this->render('page/contact.html.twig', [
            'pages' => $this->pages,
            'messages' => $messages
        ]);
    }
}