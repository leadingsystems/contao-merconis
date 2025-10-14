<?php
namespace LeadingSystems\MerconisBundle\Messenger;

class OnStatusChangeDaily extends OnStatusChange
{

    public function run(): void
    {
        $this->sendMessagesOnStatusChangeCronDaily();
    }

    public function sendMessagesOnStatusChangeCronDaily(): void
    {
        $this->sendMessagesOnStatusChange('onStatusChangeCronDaily');
    }

}