<?php
declare(strict_types=1);
include_once __DIR__ . './stubs/Validator.php';
class FileValidationTest extends TestCaseSymconValidation
{
    public function testValidateCoreStubs(): void
    {
        $this->validateLibrary(__DIR__ . '/../');
    }
    public function testValidateDNSSDControl(): void
    {
        $this->validateModule(__DIR__ . '/../e3dc');
    }
}
