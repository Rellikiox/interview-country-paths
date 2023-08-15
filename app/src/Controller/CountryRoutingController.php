<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CountryRoutingController extends AbstractController
{
    #[Route('/routing/{origin}/{destination}', name: 'country_routing')]
    public function index(string $origin, string $destination): JsonResponse
    {
        $response = [
            'origin' => $origin,
            'destination' => $destination,
        ];

        return $this->json($response);
    }
}
