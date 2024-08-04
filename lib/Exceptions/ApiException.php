<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Exceptions;

class ApiException extends \Exception 
{
    
    protected int $statusCode;
    public function getStatusCode() { return $this->statusCode; }

    protected string $originClass;
    protected string $originFunction;
    protected Array $data;
    protected ?\Exception $previous;
    public function getPayload() {
        $payload = [
            "originClass" => $this->originClass,
            "originFunction" => $this->originFunction,
            "message" => $this->getMessage(),
            "data" => $this->data
        ];
        if ($this->previous !== null) { $payload["previous"] = $this->previous->getMessage(); }
        return $payload;
    }
    
    public function __construct(int $code = 0, string $message = "", Array $data = [], ?\Exception $previous = null) 
    {
        
        // get origin
        $trace = debug_backtrace();
        $this->originClass = $trace[2]['class'] ?? "";
        $this->originFunction = $trace[2]['function'] ?? "";
        
        // set data
        $this->statusCode = $code;
        $this->data = $data;
        $this->previous = $previous;

        parent::__construct($message, $code, $previous);
    }

}