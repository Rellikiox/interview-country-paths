<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class CountryRoutingController extends AbstractController
{

    #[Route('/routing/{origin}/{destination}', name: 'rebuild_routes')]
    public function index(string $origin, string $destination): JsonResponse {
        $graph = $this->getGraph();

        $response = [
            'route' => $graph->findRoutes($origin, $destination)
        ];

        return $this->json($response);
    }

    public function getGraph() {
        $this->cache;
        $root = $this->getParameter('kernel.project_dir');
        $file = $root . '/countries.json';
        $countryData = json_decode(file_get_contents($file));
        $graph = new CountryGraph($countryData);
        return $graph;
    }
}

class CountryGraph
{
    private $graph;

    public function __construct($countryData)
    {
        $graphData = $this->remapCountries($countryData);
        $this->graph = $this->calculateBorderingCountriesDistance($graphData);
    }

    private function remapCountries($countries) {
        $remapped = array();

        foreach($countries as $country) {
            $remapped[$country->cca3] = [
                'cca3' => $country->cca3,
                'borders' => $country->borders,
                'latlng' => $country->latlng
            ];
        }

        return $remapped;
    }

    private function calculateBorderingCountriesDistance($countries) {
        $mapping = [];
        foreach($countries as $cca3 => $country) {
            $borders = array();
            foreach($country['borders'] as $bordering_country_code) {
                $bordering_country = $countries[$bordering_country_code];
                $distance = calculateHaversineDistance($country['latlng'], $bordering_country['latlng']);
                $borders[$bordering_country_code] = $distance;
            }
            $mapping[$cca3] = array(
                'borders' => $borders,
                'latlng' => $country['latlng']
            );
        }
        return $mapping;
    }

    public function findRoutes($startCountry, $endCountry)
    {
        $openSet = new \SplPriorityQueue(); // Priority queue for nodes to be evaluated
        $openSet->insert([$startCountry], 0); // Start node

        while (!$openSet->isEmpty()) {
            // Get the path with the lowest f score from the open set
            $path = $openSet->extract();
            $currentCountry = end($path);

            // Check if we've reached the destination
            if ($currentCountry === $endCountry) {
                return $path;
            }

            // Explore neighbors
            if (isset($this->graph[$currentCountry]['borders'])) {
                foreach ($this->graph[$currentCountry]['borders'] as $neighbor => $distance) {
                    if (!in_array($neighbor, $path)) {
                        // Calculate g score (distance) and heuristic h score (heuristic estimate to goal)
                        $gScore = $distance + $this->calculatePathDistance($path);
                        $hScore = $this->calculateHeuristicDistance($neighbor, $endCountry);

                        // Calculate f score (total cost)
                        $fScore = $gScore + $hScore;

                        // Add path to neighbor to open set with its f score
                        $newPath = array_merge($path, [$neighbor]);
                        $openSet->insert($newPath, -$fScore); // Negative f score for priority
                    }
                }
            }
        }

        return null; // No route found
    }

    // Calculate the total distance of a path
    private function calculatePathDistance(array $path)
    {
        // Calculate the sum of distances along the path
        $totalDistance = 0;
        for ($i = 1; $i < count($path); $i++) {
            $totalDistance += $this->graph[$path[$i - 1]]['borders'][$path[$i]];
        }
        return $totalDistance;
    }

    // Calculate a heuristic estimate of the distance to the goal
    private function calculateHeuristicDistance($currentCountry, $goalCountry)
    {
        // You can use various heuristics here, such as straight-line distance, etc.
        return calculateHaversineDistance($this->graph[$currentCountry]['latlng'], $this->graph[$goalCountry]['latlng']);
    }
}


function calculateHaversineDistance($latlong_1, $latlong_2) {
    // Convert degrees to radians
    $lat1Rad = deg2rad($latlong_1[0]);
    $lon1Rad = deg2rad($latlong_1[1]);
    $lat2Rad = deg2rad($latlong_2[0]);
    $lon2Rad = deg2rad($latlong_1[1]);

    // Earth's radius in kilometers
    $earthRadius = 6371.0;

    // Haversine formula
    $deltaLat = $lat2Rad - $lat1Rad;
    $deltaLon = $lon2Rad - $lon1Rad;

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    // Calculate the distance
    $distance = $earthRadius * $c;

    return $distance;
}