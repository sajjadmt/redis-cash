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
        $newName = $request->get('name');
        if ($newName === '' || $newName === null) {
            return new JsonResponse(['error' => 'name is required'], 400);
        }
        $cached = $repository->getName();
        if (!$cached) {
            $repository->saveName($newName);
            $cached = $newName;
        }
        return new JsonResponse(['name' => $cached]);
    }
}
