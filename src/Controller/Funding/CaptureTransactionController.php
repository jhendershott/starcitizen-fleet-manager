<?php

namespace App\Controller\Funding;

use App\Entity\Funding;
use App\Entity\User;
use App\Event\FundingUpdatedEvent;
use App\Exception\UnableToCreatePaypalOrderException;
use App\Form\Dto\PayPalCaptureTransaction;
use App\Message\Funding\SendOrderCaptureSummaryMail;
use App\Repository\FundingRepository;
use App\Service\Funding\PaypalCheckoutInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

class CaptureTransactionController extends AbstractController
{
    private Security $security;
    private PaypalCheckoutInterface $paypalCheckout;
    private SerializerInterface $serializer;
    private FundingRepository $fundingRepository;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $bus;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Security $security,
        PaypalCheckoutInterface $paypalCheckout,
        SerializerInterface $serializer,
        FundingRepository $fundingRepository,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->security = $security;
        $this->paypalCheckout = $paypalCheckout;
        $this->serializer = $serializer;
        $this->fundingRepository = $fundingRepository;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/api/funding/capture-transaction", name="funding_capture_transaction", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->security->getUser();

        /** @var PayPalCaptureTransaction $captureTransaction */
        $captureTransaction = $this->serializer->deserialize($request->getContent(), PayPalCaptureTransaction::class, $request->getContentType());

        /** @var Funding $funding */
        $funding = $this->fundingRepository->findOneBy(['paypalOrderId' => $captureTransaction->orderID, 'user' => $user->getId()]);
        if ($funding === null) {
            return $this->json([
                'error' => 'order_not_exist',
                'errorMessage' => 'Sorry, we cannot find the transaction. Please try again.',
            ], 404);
        }

        if (!in_array($funding->getPaypalStatus(), ['CREATED', 'PENDING'], true)) {
            return $this->json([
                'funding' => $funding,
            ], 200, [], ['groups' => 'supporter']);
        }

        try {
            $this->paypalCheckout->capture($funding);
        } catch (UnableToCreatePaypalOrderException $e) {
            return $this->json([
                'error' => 'paypal_error',
                'paypalError' => $e->paypalError,
            ], 400);
        }

        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(new FundingUpdatedEvent($funding));

        $this->bus->dispatch(new SendOrderCaptureSummaryMail($funding->getId()));

        return $this->json([
            'funding' => $funding,
        ], 200, [], ['groups' => 'supporter']);
    }
}
