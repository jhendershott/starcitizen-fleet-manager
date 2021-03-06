<?php

namespace App\Form\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class FleetUpload
{
    /**
     * @var UploadedFile
     *
     * @Assert\NotBlank(message="You must choose a JSON fleet file.")
     * @Assert\File(maxSize="5m")
     */
    public $fleetFile;
}
