<?php

namespace App\Controller\MyFleet;

use App\Entity\User;
use App\Service\Ship\InfosProvider\ShipInfosProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class MyFleetController extends AbstractController
{
    private Security $security;
    private ShipInfosProviderInterface $shipInfosProvider;

    public function __construct(Security $security, ShipInfosProviderInterface $shipInfosProvider)
    {
        $this->security = $security;
        $this->shipInfosProvider = $shipInfosProvider;
    }

    /**
     * @Route("/api/fleet/my-fleet", name="my_fleet", methods={"GET"})
     */
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->security->getUser();
        $citizen = $user->getCitizen();
        if ($citizen === null) {
            return $this->json([
                'error' => 'no_citizen_created',
                'errorMessage' => 'Your RSI account must be linked first. Go to the <a href="/profile">profile page</a>.',
            ], 400);
        }
        $fleet = $citizen->getLastFleet();

        if ($fleet === null) {
            return $this->json([
                'fleet' => null,
            ]);
        }

        $galaxyIds = [];
        foreach ($fleet->getShips() as $ship) {
            if ($ship->getGalaxyId() !== null) {
                $galaxyIds[] = $ship->getGalaxyId()->toString();
            }
        }

        try {
            $shipInfos = $this->shipInfosProvider->getShipsByIdOrName($galaxyIds);
        } catch (\Exception $e) {
            return $this->json([
                'fleet' => $fleet,
                'shipInfos' => [],
            ], 200, [], ['groups' => ['my-fleet']]);
        }

        return $this->json([
            'fleet' => $fleet,
            'shipInfos' => $shipInfos,
        ], 200, [], ['groups' => ['my-fleet']]);
    }
}
