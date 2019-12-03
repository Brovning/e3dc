<?php

declare(strict_types=1);

class DNSSDControl extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('Services', '[]');
    }
}
