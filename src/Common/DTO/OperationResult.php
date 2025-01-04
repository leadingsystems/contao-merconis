<?php

namespace LeadingSystems\MerconisBundle\Common\DTO;

class OperationResult
{
    private ?bool $success;
    private string $message;
    private ?\Exception $exception;

    public function __construct(?bool $success = null, string $message = '', ?\Exception $exception)
    {
        $this->success = $success;
        $this->message = $message;
        $this->exception = $exception;
    }

    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(?bool $success): void
    {
        $this->success = $success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    public function setException(\Exception $e): void
    {
        $this->success = false;
        $this->message = 'EXCEPTION: ' . $e->getMessage();
        $this->exception = $e;
    }

    public function getResultString(): string
    {
        return ($this->success ? 'SUCCESS!' : 'FAILED!') . ' ' . $this->message;
    }
}