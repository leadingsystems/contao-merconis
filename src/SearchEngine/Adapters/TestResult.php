<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters;

class TestResult
{
    private ?bool $success;
    private string $message;

    public function __construct(?bool $success = null, string $message = '')
    {
        $this->success = $success;
        $this->message = $message;
    }

    /**
     * Get the success status.
     */
    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    /**
     * Set the success status.
     */
    public function setSuccess(?bool $success): void
    {
        $this->success = $success;
    }

    /**
     * Get the message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the message.
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setException(\Exception $e): void
    {
        $this->success = false;
        $this->message = 'EXCEPTION: ' . $e->getMessage();
    }

    public function getResultString(): string
    {
        return ($this->success ? 'SUCCESS!' : 'FAILED!') . ' ' . $this->message;
    }
}
