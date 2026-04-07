<?php

namespace Modules\MultiPOS\Events;

use Modules\MultiPOS\Entities\PosMachine;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosMachineRegistrationRequested
{
    use Dispatchable, SerializesModels;

    public $posMachine;

    /**
     * Create a new event instance.
     *
     * @param PosMachine $posMachine
     */
    public function __construct(PosMachine $posMachine)
    {
        $this->posMachine = $posMachine;
    }
}
