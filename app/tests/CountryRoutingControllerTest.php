<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

class CountryRoutingControllerTest extends ApiTestCase
{
    public function testInvalidOrigin(): void
    {
        $response = static::createClient()->request('GET', '/routing/ABC/ITA');

        $this->assertEquals(400, $response->getStatusCode());
        $expected = ['message' => 'ABC does not exist.'];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent(false));
    }

    public function testInvalidDestination(): void
    {
        $response = static::createClient()->request('GET', '/routing/ESP/ABC');

        $this->assertEquals(400, $response->getStatusCode());
        $expected = ['message' => 'ABC does not exist.'];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent(false));
    }

    public function testSameCountry(): void
    {
        $response = static::createClient()->request('GET', '/routing/ESP/ESP');

        $this->assertEquals(400, $response->getStatusCode());
        $expected = ['message' => 'Countries must be different.'];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent(false));
    }

    public function testNoRoute(): void
    {
        $response = static::createClient()->request('GET', '/routing/ESP/AUS');

        $this->assertEquals(400, $response->getStatusCode());
        $expected = ['message' => 'No land route between ESP and AUS'];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent(false));
    }

    public function testBasicRoute(): void
    {
        $response = static::createClient()->request('GET', '/routing/CZE/ITA');

        $expected = ['route' => ["CZE", "AUT", "ITA"]];
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent());
    }

    public function testLongerRoute(): void
    {
        $response = static::createClient()->request('GET', '/routing/ESP/CHN');

        $expected = ['route' => ["ESP", "FRA", "DEU", "POL", "RUS", "CHN"]];
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent());
    }

    public function testReverseRoutesAreEqual(): void
    {
        $response_one = static::createClient()->request('GET', '/routing/ESP/CHN');
        $response_two = static::createClient()->request('GET', '/routing/ESP/CHN');

        $this->assertEquals(json_decode($response_one->getContent())->route, array_reverse(json_decode($response_two->getContent())->route, true));
    }
}