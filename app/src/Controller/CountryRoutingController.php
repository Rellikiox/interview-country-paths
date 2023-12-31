<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CountryRoutingController extends AbstractController
{
    private $cache;

    public function __construct()
    {
        $this->cache = new FilesystemAdapter();
    }

    #[Route('/routing/{origin}/{destination}', name: 'routing')]
    public function index(string $origin, string $destination): JsonResponse
    {
        $graph = $this->getGraph();
        if (!$graph->countryExists($origin)) {
            return $this->json(['message' => $origin . ' does not exist.'], 400);
        }
        if (!$graph->countryExists($destination)) {
            return $this->json(['message' => $destination . ' does not exist.'], 400);
        }
        if ($origin === $destination) {
            return $this->json(['message' => 'Countries must be different.'], 400);
        }

        $route = $this->findRoute($origin, $destination);
        if ($route === null) {
            return $this->json(['message' => 'No land route between ' . $origin . ' and ' . $destination], 400);
        }

        $response = [
            'route' => $route
        ];
        return $this->json($response);
    }

    // In our case we don't need to cache the graph data, as it only takes a few milliseconds. The caching here is done
    // to demonstrate a case where building this data actually was too expensive to do on each request
    public function getGraph(): CountryGraph
    {
        $graphItem = $this->cache->getItem('countryGraph');
        if (!$graphItem->isHit()) {
            $root = $this->getParameter('kernel.project_dir');
            $file = $root . '/countries.json';
            $countryData = json_decode(file_get_contents($file));
            $graph = new CountryGraph($countryData);
            $graphItem->set($graph);
            $this->cache->save($graphItem);
        } else {
            $graph = $graphItem->get();
        }
        return $graph;
    }

    // Similarly to the above, calculating a route takes a few tens of milliseconds, but for demonstration purposes lets
    // also cache these results. Another improvement here is be to also store the reverse path since they are symmetrical
    public function findRoute(string $origin, string $destination): ?array
    {
        $cacheKey = $origin . $destination;
        $routeItem = $this->cache->getItem($cacheKey);
        if (!$routeItem->isHit()) {
            $route = $this->getGraph()->findRoute($origin, $destination);
            $routeItem->set($route);
            $this->cache->save($routeItem);

            // Also store the reverse path
            $reversePathKey = $destination . $origin;
            $routeItem = $this->cache->getItem($reversePathKey);
            if ($route === null) {
                $routeItem->set($route);
            } else {
                $routeItem->set(array_reverse($route));
            }
            $this->cache->save($routeItem);
        } else {
            $route = $routeItem->get();
        }
        return $route;
    }
}

class CountryGraph
{
    private $graph;

    public function __construct(array $countryData)
    {
        $this->graph = [];
        foreach ($countryData as $country) {
            $this->graph[$country->cca3] = $country->borders;
        }
    }

    // Perform Dijkstra's algorithm to find the shortest path between the two countries
    public function findRoute(string $startCountry, string $endCountry): ?array
    {
        $priorityQueue = new \SplPriorityQueue();
        $priorityQueue->insert([$startCountry], 0);

        $visited = [];

        while (!$priorityQueue->isEmpty()) {
            $path = $priorityQueue->extract();
            $currentCountry = end($path);

            if ($currentCountry === $endCountry) {
                return $path;
            }

            if (isset($visited[$currentCountry])) {
                continue;
            }

            $visited[$currentCountry] = true;

            foreach ($this->graph[$currentCountry] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $newPath = array_merge($path, [$neighbor]);
                    $priorityQueue->insert($newPath, -count($newPath));
                }
            }
        }

        return null; // No route found
    }

    public function countryExists(string $country): bool
    {
        return array_key_exists($country, $this->graph);
    }
}