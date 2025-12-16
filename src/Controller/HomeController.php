<?php
// src/Controller/PropertyController.php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\Reservation;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

use App\Service\GeminiChatService;

use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/')]
class HomeController extends AbstractController
{
    #[Route('', name: 'app_home')]
    public function index(  

    ): Response {
         return $this->render('index.html.twig', [
        ]);
    }
    #[Route('/about', name: 'app_about')]
    public function about(  

    ): Response {
         return $this->render('about.html.twig', [
        ]);
    }

     #[Route('/contact', name: 'app_contact')]
    public function contact(  

    ): Response {
         return $this->render('contact.html.twig', [
        ]);
    }
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request, GeminiChatService $geminiService): JsonResponse
    {
        // Handle both JSON and form data
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? $request->request->get('message', '');
        
        if (trim($message) === '') {
            return new JsonResponse(['success' => false, 'message' => 'Empty message.'], 400);
        }

        return $geminiService->getResponse($message);
    }
}