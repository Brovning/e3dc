<?php
declare(strict_types=1);
include_once __DIR__ . '/../Validator.php';
class SymconCoreStubsValidationTest extends TestCaseSymconValidation
{
    public function testValidateCoreStubs(): void
    {
        $this->validateLibrary(__DIR__ . '/../CoreStubs');
    }
    public function testValidateDNSSDControl(): void
    {
        $this->validateModule(__DIR__ . '/../CoreStubs/DNSSDControl');
    }
}