<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $stats = [
            'users'        => $em->getRepository(User::class)->count([]),
            'properties'   => $em->getRepository(Property::class)->count([]),
            'reservations' => $em->getRepository(Reservation::class)->count([]),
            'pending'      => $em->getRepository(Reservation::class)->count(['status' => 'pending']),
        ];

        // Get last 3 users and last 2 reservations for dashboard
        $lastUsers = $em->getRepository(User::class)->findBy([], ['id' => 'DESC'], 3);
        $lastReservations = $em->getRepository(Reservation::class)->findBy([], ['id' => 'DESC'], 2);

        return $this->render('admin/dashboard.html.twig', [
            'stats'        => $stats,
            'lastUsers'    => $lastUsers,
            'lastReservations' => $lastReservations,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findBy([], ['id' => 'DESC']);
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/properties', name: 'admin_properties')]
    public function properties(EntityManagerInterface $em): Response
    {
        $properties = $em->getRepository(Property::class)->findBy([], ['id' => 'DESC']);
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('admin/properties.html.twig', [
            'properties' => $properties,
            'users' => $users,
        ]);
    }

    #[Route('/reservations', name: 'admin_reservations')]
    public function reservations(EntityManagerInterface $em): Response
    {
        $reservations = $em->getRepository(Reservation::class)->findBy([], ['id' => 'DESC']);
        
        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /* ----------  USER  ---------- */
    #[Route('/user/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setPhone($request->request->get('phone') ?: null);
        $user->setVerified((bool) $request->request->get('verified'));

        $em->flush();

        return $this->json(['success' => true, 'user' => [
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'email'     => $user->getEmail(),
            'phone'     => $user->getPhone(),
            'verified'  => $user->isVerified(),
            'role'      => in_array('ROLE_ADMIN', $user->getRoles()) ? 'Admin' : 'User',
        ]]);
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_users');
    }

    /* ----------  PROPERTY  ---------- */
#[Route('/property/{id}/edit', name: 'admin_property_edit', methods: ['POST'])]
public function editProperty(Property $property, Request $request, EntityManagerInterface $em): JsonResponse
{
    // Update basic fields
    $property
        ->setTitle($request->request->get('title'))
        ->setDescription($request->request->get('description'))
        ->setAddress($request->request->get('address'))
        ->setPrice($request->request->get('price'))
        ->setBedrooms((int) $request->request->get('bedrooms'))
        ->setBathrooms((int) $request->request->get('bathrooms'))
        ->setArea((int) $request->request->get('area'))
        ->setAvailable((bool) $request->request->get('available'));

    // 1. Handle new photo uploads
    $existingPhotos = $property->getPhotos();
    $uploadedFiles = $request->files->get('photos', []);

    foreach ($uploadedFiles as $file) {
        if ($file && $file->isValid()) {
            try {
                $filename = uniqid() . '_' . time() . '.' . $file->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/properties';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $file->move($uploadDir, $filename);
                $existingPhotos[] = $filename;
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Upload error: ' . $e->getMessage()
                ], 400);
            }
        }
    }

    $property->setPhotos($existingPhotos);

    // 2. Change owner (if specified)
    $ownerId = $request->request->get('ownerId');
    if ($ownerId) {
        $owner = $em->getRepository(User::class)->find($ownerId);
        if ($owner) {
            $property->setOwner($owner);
        }
    }

    $em->flush();

    return new JsonResponse([
        'success' => true,
        'message' => 'Property updated successfully.',
        'property' => [
            'id'         => $property->getId(),
            'title'      => $property->getTitle(),
            'description'=> $property->getDescription(),
            'address'    => $property->getAddress(),
            'price'      => $property->getPrice(),
            'bedrooms'   => $property->getBedrooms(),
            'bathrooms'  => $property->getBathrooms(),
            'area'       => $property->getArea(),
            'available'  => $property->isAvailable(),
            'photos'     => $property->getPhotos(),
            'ownerEmail' => $property->getOwner()->getEmail(),
        ]
    ]);
}

    #[Route('/property/{id}/delete', name: 'admin_property_delete', methods: ['POST'])]
    public function deleteProperty(Property $property, EntityManagerInterface $em): Response
    {
        $em->remove($property);
        $em->flush();
        return $this->redirectToRoute('admin_properties');
    }

    /* ----------  RESERVATION  ---------- */
    #[Route('/reservation/{id}/edit', name: 'admin_reservation_edit', methods: ['POST'])]
    public function editReservation(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        $reservation->setStatus($request->request->get('status'));
        $em->flush();

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function deleteReservation(Reservation $reservation, EntityManagerInterface $em): Response
    {
        $em->remove($reservation);
        $em->flush();
        return $this->redirectToRoute('admin_reservations');
    }
}