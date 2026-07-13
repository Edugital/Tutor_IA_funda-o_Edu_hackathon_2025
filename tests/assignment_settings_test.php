<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace assignfeedback_aitutoria;

use assignfeedback_aitutoria\local\assignment_policy;
use mod_assign_test_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Assignment-level structured rubric setting tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assign_feedback_aitutoria::save_settings
 */
final class assignment_settings_test extends \advanced_testcase {
    use mod_assign_test_generator;

    /**
     * Structured rubric and assistive mode are validated and stored canonically.
     */
    public function test_structured_rubric_and_mode_are_saved(): void {
        $this->resetAfterTest(true);
        set_config('institutionalframeworkjson', $this->framework(), 'assignfeedback_aitutoria');
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->create_instance($course, [
            'assignfeedback_aitutoria_enabled' => 1,
            'assignfeedback_aitutoria_assessmentmode' => assignment_policy::MODE_ASSISTIVE,
            'assignfeedback_aitutoria_structuredrubric' => $this->rubric('communication'),
            'assignfeedback_aitutoria_rubric' => 'Teacher-facing narrative guidance.',
            'assignfeedback_aitutoria_rubricversion' => '',
        ]);

        $plugin = $assign->get_feedback_plugin_by_type('aitutoria');
        $storedrubric = json_decode($plugin->get_config('structuredrubric'), true, 64, JSON_THROW_ON_ERROR);

        $this->assertSame(assignment_policy::MODE_ASSISTIVE, $plugin->get_config('assessmentmode'));
        $this->assertSame('activity-v1', $plugin->get_config('rubricversion'));
        $this->assertSame('communication', $storedrubric['criteria'][0]['competencyid']);
        $this->assertSame('Teacher-facing narrative guidance.', $plugin->get_config('rubric'));
    }

    /**
     * Invalid competency mappings prevent an activity from being saved.
     */
    public function test_unknown_competency_prevents_save(): void {
        $this->resetAfterTest(true);
        set_config('institutionalframeworkjson', $this->framework(), 'assignfeedback_aitutoria');
        $course = $this->getDataGenerator()->create_course();

        $this->expectException(\moodle_exception::class);
        $this->create_instance($course, [
            'assignfeedback_aitutoria_enabled' => 1,
            'assignfeedback_aitutoria_assessmentmode' => assignment_policy::MODE_SHADOW,
            'assignfeedback_aitutoria_structuredrubric' => $this->rubric('unknown'),
        ]);
    }

    /**
     * Build a structured activity rubric.
     *
     * @param string $competencyid Competency id.
     * @return string
     */
    private function rubric(string $competencyid): string {
        return json_encode([
            'version' => 'activity-v1',
            'criteria' => [[
                'id' => 'clarity',
                'title' => 'Clarity',
                'weight' => 1,
                'competencyid' => $competencyid,
                'levels' => [
                    ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                    ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                ],
            ]],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Build a site-level institutional framework.
     *
     * @return string
     */
    private function framework(): string {
        return json_encode([
            'version' => 'institution-v1',
            'competencies' => [[
                'id' => 'communication',
                'title' => 'Communication',
                'indicators' => [[
                    'id' => 'clarity',
                    'title' => 'Communicates clearly',
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);
    }
}
