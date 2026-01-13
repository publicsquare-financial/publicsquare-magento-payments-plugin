<?php

namespace PublicSquare\Payments\Exception;


class ResourceNotFoundException extends PSQException
{
    private mixed $resourceIdentifier;
    private ?string $resourceType;


    public function __construct(
        string $resourceIdentifier,
        string $resourceType,
        int    $propagateHttpResponseCode = 400,
    )
    {
        parent::__construct(propagateHttpResponseCode: $propagateHttpResponseCode, message: 'Resource type [' . $resourceType . '] not found with identifier [' . $resourceIdentifier . ']!');
        $this->resourceType = $resourceType;
        $this->resourceIdentifier = $resourceIdentifier;
    }

    public function getResourceIdentifier(): string
    {
        return $this->resourceIdentifier;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }
}