<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignBaselineTest extends TestCase
{
    public function test_homepage_keeps_its_visual_identity(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('EduFocus')
            ->assertSee('STUDY_BUDDY.EXE')
            ->assertSee('site-hero-art', false)
            ->assertSee('auth-study-scene', false)
            ->assertSee('Interactive Learning');
    }

    public function test_core_site_styles_remain_on_the_approved_baseline(): void
    {
        $styles = file_get_contents(resource_path('css/site.css'));

        $this->assertNotFalse($styles);

        foreach ([
            ".site-page { margin: 0; background: #f8f8f5; color: #161616; font-family: 'Courier New', monospace;",
            ".site-brand span { font-family: 'Press Start 2P', monospace;",
            '.site-hero { display: grid; grid-template-columns: 1.06fr 1fr;',
            '.site-hero-art { border: 1.5px solid #111;',
        ] as $baselineRule) {
            $this->assertStringContainsString($baselineRule, $styles);
        }
    }
}
