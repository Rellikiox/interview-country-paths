<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

class CountryRoutingControllerTest extends ApiTestCase
{
    public function testBasicRoute(): void
    {
        $response = static::createClient()->request('GET', '/routing/CZE/ITA');

        $expected = ['route' => ["CZE", "AUT", "ITA"]];
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent());
    }
}
