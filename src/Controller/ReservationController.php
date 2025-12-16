<?php
namespace App\Controller;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/dashboard', name: 'app_reservation_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $sent = $em->getRepository(Reservation::class)
            ->findBy(['tenant' => $user], ['createdAt' => 'DESC']);

        $received = $em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->join('r.property', 'p')
            ->where('p.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('reservation/dashboard.html.twig', [
            'sent_reservations' => $sent,
            'received_reservations' => $received,
        ]);
    }

    #[Route('/{id}/update', name: 'app_reservation_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        if ($reservation->getProperty()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $status = $request->request->get('status');
        if (in_array($status, ['accepted', 'rejected'])) {
            $reservation->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Reservation ' . $status . '!');
        }

        return $this->redirectToRoute('app_reservation_dashboard');
    }
    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function cancel(Reservation $reservation, EntityManagerInterface $em): Response
{
    if ($reservation->getTenant() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    if ($reservation->getStatus() !== 'pending') {
        $this->addFlash('warning', 'You can only cancel pending reservations.');
    } else {
        $em->remove($reservation);
        $em->flush();
        $this->addFlash('success', 'Reservation canceled.');
    }

    return $this->redirectToRoute('app_reservation_dashboard');
}
}