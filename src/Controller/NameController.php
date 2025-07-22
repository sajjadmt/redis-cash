<?php

namespace App\Controller;

use App\Repository\CachedNameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class NameController extends AbstractController
{
    #[Route('/api/name', name: 'api_name', methods: ['POST','GET'])]
    public function setName(Request $request, CachedNameRepository $repository): JsonResponse
    {
        $name = $request->get('name');
        if (!$name) {
            return new JsonResponse(['error' => 'name is required'], 400);
        }

        $cached = $repository->getOrSet('cached_name', $name, 60);

        return new JsonResponse(['cached_name' => $cached]);
    }
}
