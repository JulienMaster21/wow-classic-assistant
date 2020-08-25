<?php

namespace App\Tests\feature;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageTest extends WebTestCase {

    public function providePageUrls() {

        return [
            ['/'],
            ['/profession-assistant'],
            ['/database'],
            ['/about'],
            ['/contact']
        ];
    }

    /**
     * @param string $url
     * @dataProvider providePageUrls
     */
    public function testPageIsReachable(string $url) {

        $client = static::createClient();
        $client->request('GET', $url);
        $this->assertEquals(200, $client->getResponse()->getStatusCode(),
                            sprintf('Request is: %s Response is: %s',
                                    $client->getRequest(),
                                    $client->getResponse()));
    }
}