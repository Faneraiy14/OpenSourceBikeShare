<?php

declare(strict_types=1);

namespace BikeShare\Controller\Api\V1;

use BikeShare\Rent\Enum\RentSystemType;
use BikeShare\Rent\RentSystemFactory;
use BikeShare\App\Security\RequiresAppUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class RentalsController extends AbstractController
{
    use RequiresAppUserTrait;

    use RentSystemResponseTrait;

    public function create(
        Request $request,
        RentSystemFactory $rentSystemFactory,
        TranslatorInterface $translator,
    ): Response {
        $payload = $request->getPayload()->all();
        $bikeNumber = isset($payload['bikeNumber']) && is_numeric($payload['bikeNumber'])
            ? (int)$payload['bikeNumber']
            : null;
        if ($bikeNumber === null) {
            return $this->json(['detail' => 'bikeNumber is required'], Response::HTTP_BAD_REQUEST);
        }

        $response = $rentSystemFactory->getRentSystem(RentSystemType::WEB)->rentBike(
            $this->getAppUser()->getUserId(),
            $bikeNumber
        );

        return $this->jsonRentSystemResult($response, $translator);
    }
}
