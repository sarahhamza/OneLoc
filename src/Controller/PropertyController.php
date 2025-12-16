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

#[Route('/property')]
class PropertyController extends AbstractController
{
    #[Route('/new', name: 'app_property_new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_USER')]
public function new(Request $request, EntityManagerInterface $em): Response
{
    if ($request->isMethod('POST')) {
        $property = new Property();
        $property->setTitle($request->request->get('title'));
        $property->setDescription($request->request->get('description'));
        $property->setAddress($request->request->get('address'));
        $property->setPrice($request->request->get('price'));
        $property->setBedrooms((int) $request->request->get('bedrooms'));
        $property->setBathrooms((int) $request->request->get('bathrooms'));
        $property->setArea((int) $request->request->get('area'));
        $property->setAvailable(true);
        $property->setOwner($this->getUser());

        // Handle photos
        $photos = [];
        foreach ($request->files->get('photos', []) as $file) {
            if ($file) {
                $filename = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/properties',
                    $filename
                );
                $photos[] = $filename;
            }
        }
        $property->setPhotos($photos);

        $em->persist($property);
        $em->flush();

        $this->addFlash('success', 'Property added!');
        return $this->redirectToRoute('app_property_index');
    }

    return $this->render('property/new.html.twig');
}
#[Route('', name: 'app_property_index', methods: ['GET'])]
public function index(
    Request $request,
    PropertyRepository $repo
): Response {
    $qb = $repo->createQueryBuilder('p')
        ->where('p.available = :available')
        ->setParameter('available', true);

    // SEARCH
    if ($search = $request->query->get('search')) {
        $qb->andWhere('p.title LIKE :search OR p.address LIKE :search')
           ->setParameter('search', "%$search%");
    }

    // FILTER: Bedrooms
    if ($bedrooms = $request->query->get('bedrooms')) {
        $qb->andWhere('p.bedrooms = :bedrooms')
           ->setParameter('bedrooms', $bedrooms);
    }

    // FILTER: Bathrooms
    if ($bathrooms = $request->query->get('bathrooms')) {
        $qb->andWhere('p.bathrooms = :bathrooms')
           ->setParameter('bathrooms', $bathrooms);
    }

    // FILTER: Price Range
    if ($priceMin = $request->query->get('price_min')) {
        $qb->andWhere('p.price >= :price_min')
           ->setParameter('price_min', $priceMin);
    }
    if ($priceMax = $request->query->get('price_max')) {
        $qb->andWhere('p.price <= :price_max')
           ->setParameter('price_max', $priceMax);
    }

    // SORT
    $sort = $request->query->get('sort');
    switch ($sort) {
        case 'price_asc':  $qb->orderBy('p.price', 'ASC'); break;
        case 'price_desc': $qb->orderBy('p.price', 'DESC'); break;
        case 'newest':     $qb->orderBy('p.createdAt', 'DESC'); break;
        default:           $qb->orderBy('p.id', 'DESC'); break;
    }

    $properties = $qb->getQuery()->getResult();

    return $this->render('property/index.html.twig', [
        'properties' => $properties,
    ]);
}

#[Route('/{id}', name: 'app_property_show', methods: ['GET', 'POST'])]
public function show(
    Property $property,
    Request $request,
    EntityManagerInterface $em
): Response {


    // Check if current user is the owner
    $isOwner = $this->getUser() && $property->getOwner() === $this->getUser();

    if ($request->isMethod('POST')) {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Prevent owner from reserving own property
        if ($isOwner) {
            $this->addFlash('warning', 'You cannot reserve your own property.');
            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setProperty($property);
        $reservation->setTenant($this->getUser());
        $reservation->setCheckIn(new \DateTime($request->request->get('check_in')));
        $reservation->setCheckOut(new \DateTime($request->request->get('check_out')));

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Reservation request sent!');
        return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
    }

    return $this->render('property/show.html.twig', [
        'property' => $property,
        'is_owner' => $isOwner,
    ]);
}


#[Route('/{id}/edit-data', name: 'app_property_edit_data', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function getEditData(Property $property): Response
{
    if ($property->getOwner() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    return $this->json([
        'id' => $property->getId(),
        'title' => $property->getTitle(),
        'description' => $property->getDescription(),
        'address' => $property->getAddress(),
        'price' => $property->getPrice(),
        'bedrooms' => $property->getBedrooms(),
        'bathrooms' => $property->getBathrooms(),
        'area' => $property->getArea(),
    ]);
}
    #[Route('/{id}/edit', name: 'app_property_edit', methods: ['GET', 'POST'])]
    public function edit(
        Property $property,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($property->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $property->setTitle($request->request->get('title'));
            $property->setDescription($request->request->get('description'));
            $property->setAddress($request->request->get('address'));
            $property->setPrice($request->request->get('price'));
            $property->setBedrooms((int) $request->request->get('bedrooms'));
            $property->setBathrooms((int) $request->request->get('bathrooms'));
            $property->setArea((int) $request->request->get('area'));
            $property->setAvailable((bool) $request->request->get('available'));

            // Update photos
            $existing = $property->getPhotos();
            $newPhotos = $request->files->get('photos', []);
            foreach ($newPhotos as $file) {
                if ($file) {
                    $filename = md5(uniqid()) . '.' . $file->guessExtension();
                    $file->move($this->getParameter('uploads_dir') . '/properties', $filename);
                    $existing[] = $filename;
                }
            }
            $property->setPhotos($existing);

            $em->flush();
            $this->addFlash('success', 'Property updated!');
            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}', name: 'app_property_delete', methods: ['POST'])]
    public function delete(
        Property $property,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($property->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $property->getId(), $request->request->get('_token'))) {
            $em->remove($property);
            $em->flush();
            $this->addFlash('success', 'Property deleted.');
        }

        return $this->redirectToRoute('app_property_index');
    }
}