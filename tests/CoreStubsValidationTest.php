<?php
declare(strict_types=1);
include_once __DIR__ . '/../../tests/Validator.php';
class SymconCoreStubsValidationTest extends TestCaseSymconValidation
{
    public function testValidateCoreStubs(): void
    {
        $this->validateLibrary(__DIR__ . '/../../tests/CoreStubs');
    }
    public function testValidateDNSSDControl(): void
    {
        $this->validateModule(__DIR__ . '/../../tests/CoreStubs/DNSSDControl');
    }
}
