<?php
namespace LeadingSystems\MerconisBundle\Messenger;

class OnStatusChangeHourly extends OnStatusChange
{

    public function run(): void
    {
        $this->sendMessagesOnStatusChangeCronHourly();
    }

    public function sendMessagesOnStatusChangeCronHourly(): void
    {
        $this->sendMessagesOnStatusChange('onStatusChangeCronHourly');
    }

}