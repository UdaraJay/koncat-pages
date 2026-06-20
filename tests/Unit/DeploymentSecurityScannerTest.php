<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Services\DeploymentSecurity\BuiltinDeploymentSecurityScanner;
use App\Services\DeploymentSecurity\DeploymentScanFile;
use PHPUnit\Framework\TestCase;

class DeploymentSecurityScannerTest extends TestCase
{
    public function test_builtin_scanner_blocks_high_risk_javascript_html_and_css(): void
    {
        $scanner = new BuiltinDeploymentSecurityScanner;
        $project = $this->project();

        $result = $scanner->scan($project, [
            new DeploymentScanFile('index.html', '<script src="https://cdn.example/app.js"></script><iframe src="/x"></iframe>', 78),
            new DeploymentScanFile('app.js', 'eval("x"); document.cookie; top.location = "/phish";', 51),
            new DeploymentScanFile('style.css', '@import "https://example.com/app.css"; body{background:url(javascript:alert(1))}', 76),
        ]);

        $ids = array_map(fn ($finding) => $finding->id, $result->findings);

        $this->assertContains('html-remote-script', $ids);
        $this->assertContains('html-iframe', $ids);
        $this->assertContains('no-eval', $ids);
        $this->assertContains('no-document-cookie', $ids);
        $this->assertContains('no-top-navigation', $ids);
        $this->assertContains('css-import', $ids);
        $this->assertContains('css-javascript-url', $ids);
        $this->assertSame('high', $result->highestSeverity());
        $this->assertNotEmpty($result->blockedFindings(['critical', 'high']));
    }

    public function test_builtin_scanner_warns_on_network_storage_forms_and_external_assets(): void
    {
        $scanner = new BuiltinDeploymentSecurityScanner;
        $project = $this->project();

        $result = $scanner->scan($project, [
            new DeploymentScanFile('index.html', '<form action="/send"></form><img src="https://example.com/a.png"><a target="_blank" href="/x">x</a>', 95),
            new DeploymentScanFile('app.js', 'fetch("/api"); new XMLHttpRequest(); new WebSocket("wss://example.com"); navigator.sendBeacon("/x"); localStorage.x = "1"; window.open("/x");', 139),
            new DeploymentScanFile('style.css', 'body{background:url(https://example.com/bg.png)}', 48),
        ]);

        $ids = array_map(fn ($finding) => $finding->id, $result->findings);

        $this->assertContains('html-form', $ids);
        $this->assertContains('html-external-asset', $ids);
        $this->assertContains('html-popup-target', $ids);
        $this->assertContains('network-fetch', $ids);
        $this->assertContains('network-xml-http-request', $ids);
        $this->assertContains('network-websocket', $ids);
        $this->assertContains('network-beacon', $ids);
        $this->assertContains('browser-storage', $ids);
        $this->assertContains('popup-open', $ids);
        $this->assertContains('css-external-url', $ids);
        $this->assertSame('medium', $result->highestSeverity());
        $this->assertSame([], $result->blockedFindings(['critical', 'high']));
    }

    protected function project(): Project
    {
        $project = new Project;
        $project->id = '01J00000000000000000000000';

        return $project;
    }
}
