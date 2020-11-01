<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WowheadScraperController
 * @package App\Controller
 */
class WowheadScraperController extends AbstractController {

    /**
     * @Route("/wowhead-scraper/update", name="wowheadMigrate", methods={"GET", "HEAD"})
     */
    public function update() {

        return $this->render('wowheadScraper/update.html.twig', [

        ]);
    }
}