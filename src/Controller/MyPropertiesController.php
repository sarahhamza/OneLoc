<?php
// src/Controller/MyPropertiesController.php

namespace App\Controller;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my-properties')]
#[IsGranted('ROLE_USER')]
class MyPropertiesController extends AbstractController
{
    #[Route('', name: 'app_my_properties')]
    public function index(EntityManagerInterface $em): Response
    {
        $properties = $em->getRepository(Property::class)
            ->findBy(['owner' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('my_properties/index.html.twig', [
            'properties' => $properties,
        ]);
    }

  

    #[Route('/{id}/delete', name: 'app_my_property_delete', methods: ['POST'])]
    public function delete(Property $property, Request $request, EntityManagerInterface $em): Response
    {
        if ($property->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $property->getId(), $request->request->get('_token'))) {
            $em->remove($property);
            $em->flush();
            $this->addFlash('success', 'Property deleted.');
        }

        return $this->redirectToRoute('app_my_properties');
    }
    
}