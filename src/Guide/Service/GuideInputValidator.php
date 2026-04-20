<?php

namespace App\Guide\Service;

use App\Guide\Dto\GuideInput;

final class GuideInputValidator
{
    public function validate(GuideInput $in): void
    {
        if ($in->travelTypeSlug === '' || $in->transportSlug === '' || $in->durationBandSlug === '') {
            throw new \InvalidArgumentException('travelType, transport en duration zijn verplicht.');
        }

        // Airline/ticket alleen verplicht bij vliegtuig als je ze al toont in flow.
        if ($in->transportSlug === 'vliegtuig') {
            // Ticket kan optioneel zijn als user "weet ik niet" kiest.
            // Dus: geen harde exception hier. (later in resolver afhandelen)
        }
    }
}