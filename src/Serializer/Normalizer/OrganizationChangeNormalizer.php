<?php

namespace App\Serializer\Normalizer;

use App\Entity\CitizenOrganization;
use App\Entity\OrganizationChange;
use App\Entity\User;
use App\Repository\CitizenOrganizationRepository;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OrganizationChangeNormalizer implements NormalizerInterface
{
    private $normalizer;
    private $citizenOrganizationRepository;
    private $userRepository;

    public function __construct(ObjectNormalizer $normalizer, CitizenOrganizationRepository $citizenOrganizationRepository, UserRepository $userRepository)
    {
        $this->normalizer = $normalizer;
        $this->citizenOrganizationRepository = $citizenOrganizationRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param OrganizationChange $orgaChange
     * @param mixed|null         $format
     */
    public function normalize($orgaChange, $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($orgaChange, $format, $context);
        $data = $this->removeSensitiveFields($data, $orgaChange);

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof OrganizationChange;
    }

    private function removeSensitiveFields(array $data, OrganizationChange $orgaChange): array
    {
        if ($orgaChange->getAuthor() === null || $orgaChange->getOrganization() === null) {
            return $data;
        }

        /** @var CitizenOrganization $citizenOrga */
        $citizenOrga = $this->citizenOrganizationRepository->findOneBy(['organization' => $orgaChange->getOrganization(), 'citizen' => $orgaChange->getAuthor()]);
        if ($citizenOrga === null) {
            return $data;
        }

        /** @var User $user */
        $user = $this->userRepository->findOneBy(['citizen' => $citizenOrga->getCitizen()]);

        if ($orgaChange->getType() === OrganizationChange::TYPE_UPLOAD_FLEET
            && ($citizenOrga->getVisibility() === CitizenOrganization::VISIBILITY_PRIVATE || $user->getPublicChoice() === User::PUBLIC_CHOICE_PRIVATE)) {
            // visibility private = anonymize content
            $data['payload'] = [];
            $data['payloadPrivate'] = true;
        }

        return $data;
    }
}
