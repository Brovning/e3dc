<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestCaseSymconValidation extends TestCase
{
    private function isValidGUID($guid): bool
    {
        return preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid) == 1;
    }

    private function isValidName($name): bool
    {
        return preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9 _]*[A-Za-z0-9])?$/', $name) == 1;
    }

    private function isValidPrefix($name): bool
    {
        return preg_match('/^[A-Z0-9]+$/', $name) == 1;
    }

    private function isValidURL($name): bool
    {
        return preg_match('/^(?:http:\/\/|https:\/\/)/', $name) == 1;
    }

    private function ignoreFolders(): array
    {
        return ['..', '.', 'libs', 'docs', 'imgs', 'tests'];
    }

    protected function validateLibrary($folder): void
    {
        $library = json_decode(file_get_contents($folder . '/library.json'), true);

        $this->assertArrayHasKey('id', $library);
        $this->assertIsString($library['id']);
        $this->assertTrue($this->isValidGUID($library['id']));

        $this->assertArrayHasKey('author', $library);
        $this->assertIsString($library['author']);

        $this->assertArrayHasKey('name', $library);
        $this->assertIsString($library['name']);

        $this->assertArrayHasKey('url', $library);
        $this->assertIsString($library['url']);
        $this->assertTrue($this->isValidURL($library['url']));

        $this->assertArrayHasKey('version', $library);
        $this->assertIsString($library['version']);

        $this->assertArrayHasKey('build', $library);
        $this->assertIsInt($library['build']);

        $this->assertArrayHasKey('date', $library);
        $this->assertIsInt($library['date']);

        //This is purely optional
        if (!isset($library['compatibility'])) {
            $this->assertCount(7, $library);
        } else {
            $this->assertCount(8, $library);
            $this->assertIsArray($library['compatibility']);
            if (isset($library['compatibility']['version'])) {
                $this->assertIsString($library['compatibility']['version']);
            }
            if (isset($library['compatibility']['date'])) {
                $this->assertIsInt($library['compatibility']['date']);
            }
        }
    }

    protected function validateModule($folder): void
    {
        $module = json_decode(file_get_contents($folder . '/module.json'), true);

        $this->assertArrayHasKey('id', $module);
        $this->assertIsString($module['id']);
        $this->assertTrue($this->isValidGUID($module['id']));

        $this->assertArrayHasKey('name', $module);
        $this->assertIsString($module['name']);
        $this->assertTrue($this->isValidName($module['name']));

        $this->assertArrayHasKey('type', $module);
        $this->assertIsInt($module['type']);
        $this->assertGreaterThanOrEqual(0, $module['type']);
        $this->assertLessThanOrEqual(5, $module['type']);

        $this->assertArrayHasKey('vendor', $module);
        $this->assertIsString($module['vendor']);

        $this->assertArrayHasKey('aliases', $module);
        $this->assertIsArray($module['aliases']);

        $this->assertArrayHasKey('url', $module);
        $this->assertIsString($module['url']);
        $this->assertTrue($this->isValidURL($module['url']));

        $this->assertArrayHasKey('parentRequirements', $module);
        $this->assertIsArray($module['parentRequirements']);
        foreach ($module['parentRequirements'] as $parentRequirement) {
            $this->assertIsString($parentRequirement);
            $this->assertTrue($this->isValidGUID($parentRequirement));
        }

        $this->assertArrayHasKey('childRequirements', $module);
        $this->assertIsArray($module['childRequirements']);
        foreach ($module['childRequirements'] as $childRequirement) {
            $this->assertIsString($childRequirement);
            $this->assertTrue($this->isValidGUID($childRequirement));
        }

        $this->assertArrayHasKey('implemented', $module);
        $this->assertIsArray($module['implemented']);
        foreach ($module['implemented'] as $implemented) {
            $this->assertIsString($implemented);
            $this->assertTrue($this->isValidGUID($implemented));
        }

        $this->assertArrayHasKey('prefix', $module);
        $this->assertIsString($module['prefix']);
        $this->assertTrue($this->isValidPrefix($module['prefix']));

        if (file_exists($folder . '/form.json')) {
            $this->assertTrue(json_decode(file_get_contents($folder . '/form.json')) !== null);
        }

        if (file_exists($folder . '/locale.json')) {
            $this->assertTrue(json_decode(file_get_contents($folder . '/locale.json')) !== null);
        }
    }

    public function testNop(): void
    {
        $this->assertTrue(true);
    }
}
