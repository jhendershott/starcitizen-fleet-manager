<?php

namespace App\Service;

use App\Domain\CitizenInfos;
use App\Domain\CitizenNumber;
use App\Domain\CitizenOrganizationInfo;
use App\Domain\HandleSC;
use App\Domain\SpectrumIdentification;
use App\Entity\Citizen;
use App\Exception\NotFoundHandleSCException;

class FakeCitizenInfosProvider implements CitizenInfosProviderInterface
{
    private const BLACKLIST_HANDLES = ['not_found', 'not_exist'];

    /** @var Citizen */
    private $citizen;

    /**
     * Simulates a provider DB.
     *
     * @var CitizenInfos[]
     */
    private $knownCitizens = [];

    public function __construct()
    {
        $this->citizen = new Citizen();
        $this->citizen->setActualHandle(new HandleSC('foobar'));
        $this->citizen->setNickname('Foo bar');
        $this->citizen->setNumber(new CitizenNumber('123456'));

        $citizenInfos = new CitizenInfos(new CitizenNumber('135790'), new HandleSC('fake_citizen_1'));
        $this->knownCitizens[] = $citizenInfos;
    }

    public function setCitizen(?Citizen $citizen): void
    {
        $this->citizen = $citizen;
    }

    public function retrieveInfos(HandleSC $handleSC, bool $caching = true): CitizenInfos
    {
        if (in_array($handleSC->getHandle(), self::BLACKLIST_HANDLES, true)) {
            throw new NotFoundHandleSCException('Citizen not found.');
        }
        foreach ($this->knownCitizens as $knownCitizen) {
            if ($knownCitizen->handle->getHandle() === $handleSC->getHandle()) {
                return $knownCitizen;
            }
        }
        if ($this->citizen === null) {
            throw new NotFoundHandleSCException('Citizen not found.');
        }
        $sourceMainOrga = $this->citizen->getMainOrga();
        $mainOrga = null;
        if ($sourceMainOrga !== null) {
            $mainOrga = new CitizenOrganizationInfo(
                new SpectrumIdentification($sourceMainOrga->getOrganizationSid()),
                $sourceMainOrga->getRank(),
                $sourceMainOrga->getRankName()
            );
        }

        $orgas = [];
        foreach ($this->citizen->getOrganizations() as $citizenOrga) {
            $orga = new CitizenOrganizationInfo(
                new SpectrumIdentification($citizenOrga->getOrganizationSid()),
                $citizenOrga->getRank(),
                $citizenOrga->getRankName(),
                );
            $orgas[] = $orga;
        }

        $ci = new CitizenInfos(
            clone $this->citizen->getNumber(),
            clone $this->citizen->getActualHandle()
        );
        $ci->nickname = $this->citizen->getNickname();
        $ci->mainOrga = $mainOrga;
        $ci->organisations = [];
        if ($mainOrga !== null) {
            $ci->organisations[] = $mainOrga;
        }
        $ci->organisations = array_merge($ci->organisations, $orgas);
        $ci->bio = $this->citizen->getBio();
        $ci->avatarUrl = 'http://example.com/fake-avatar.png';
        $ci->registered = new \DateTimeImmutable('2018-01-01 12:00:00');

        return $ci;
    }
}
