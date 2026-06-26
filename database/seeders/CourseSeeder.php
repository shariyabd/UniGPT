<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Seeds the catalog of courses, department by department. The CSE curriculum is
 * authoritative (exact codes/titles/credits from the curriculum spec); the other
 * departments use realistic Bangladesh-style course lists.
 *
 * Courses are created with faculty_id = null; SectionSeeder assigns the teaching
 * faculty when it creates each course's section(s). Idempotent via the unique
 * course code.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding course catalog...');

        $created = 0;

        foreach ($this->catalog() as $departmentSlug => $bySemester) {
            $department = Department::where('slug', $departmentSlug)->first();

            if (! $department) {
                $this->command->warn("   Department '{$departmentSlug}' not found; skipping its courses.");

                continue;
            }

            foreach ($bySemester as $semester => $courses) {
                foreach ($courses as $i => [$code, $name, $credits]) {
                    $course = Course::firstOrCreate(
                        ['code' => $code],
                        [
                            'name' => $name,
                            'description' => "{$name} — a core course of the {$department->name} programme (semester {$semester}).",
                            'department_id' => $department->id,
                            'faculty_id' => null,
                            'semester' => $semester,
                            'credits' => $credits,
                            'schedule' => [
                                'lectures' => $this->lectureSlot($i),
                                'classroom' => 'Room '.(100 + ($semester * 10) + $i),
                                'office_hours' => 'By appointment',
                            ],
                            'max_enrollment' => 60,
                            'is_active' => true,
                        ],
                    );

                    if ($course->wasRecentlyCreated) {
                        $created++;
                    }
                }
            }
        }

        $created += $this->padBucketsToFullLoad();

        $this->command->info("   ✓ Course catalog seeded ({$created} new courses)");
    }

    /**
     * Real curricula carry more than three courses a semester. The curated lists
     * above give every department its core courses, but several offer only three
     * per semester — too few for a student to take the realistic 4–5 subjects.
     * Pad every (department, semester) bucket up to a full load with generic
     * electives / general-education courses so enrollment can hit that target.
     * Idempotent via the generated course code.
     */
    private function padBucketsToFullLoad(): int
    {
        $target = max(2, (int) config('seeder.min_courses_per_student', 4) + 1);

        $fillerTitles = [
            'General Education',
            'Technical Elective I',
            'Technical Elective II',
            'Communication Skills',
            'Professional Ethics',
        ];

        $created = 0;

        foreach (Department::all() as $department) {
            $bySemester = Course::query()
                ->where('department_id', $department->id)
                ->whereNotNull('semester')
                ->get(['id', 'code', 'semester'])
                ->groupBy('semester');

            foreach ($bySemester as $semester => $courses) {
                $need = $target - $courses->count();
                if ($need <= 0) {
                    continue;
                }

                // Reuse the bucket's existing course-code prefix (e.g. "EEE") so
                // the filler codes read naturally alongside the curated catalog.
                $prefix = preg_match('/^[A-Za-z]+/', (string) $courses->first()->code, $m) ? $m[0] : 'GEN';

                for ($n = 0; $n < $need; $n++) {
                    $code = sprintf('%s %d9%d', $prefix, (int) $semester, $n);
                    $name = $fillerTitles[$n % count($fillerTitles)];

                    $course = Course::firstOrCreate(
                        ['code' => $code],
                        [
                            'name' => $name,
                            'description' => "{$name} — a general course of the {$department->name} programme (semester {$semester}).",
                            'department_id' => $department->id,
                            'faculty_id' => null,
                            'semester' => (int) $semester,
                            'credits' => 3,
                            'schedule' => [
                                'lectures' => $this->lectureSlot($n),
                                'classroom' => 'Room '.(200 + ((int) $semester * 10) + $n),
                                'office_hours' => 'By appointment',
                            ],
                            'max_enrollment' => 60,
                            'is_active' => true,
                        ],
                    );

                    if ($course->wasRecentlyCreated) {
                        $created++;
                    }
                }
            }
        }

        return $created;
    }

    private function lectureSlot(int $i): string
    {
        $days = ['Sun/Tue', 'Mon/Wed', 'Tue/Thu', 'Sat/Mon', 'Wed/Sun'];
        $times = ['08:00–09:30', '10:00–11:30', '12:00–13:30', '14:00–15:30'];

        return $days[$i % count($days)].' '.$times[$i % count($times)];
    }

    /**
     * department-slug => [ semester => [ [code, name, credits], ... ] ].
     *
     * @return array<string, array<int, array<int, array{0: string, 1: string, 2: int}>>>
     */
    private function catalog(): array
    {
        return [
            'computer-science-engineering' => $this->cseCurriculum(),
            'electrical-engineering' => [
                1 => [['EEE 1101', 'Electrical Circuits I', 3], ['EEE 1102', 'Engineering Mathematics I', 3], ['EEE 1103', 'Engineering Drawing', 1]],
                2 => [['EEE 1201', 'Electrical Circuits II', 3], ['EEE 1202', 'Engineering Mathematics II', 3], ['EEE 1203', 'Electronics I', 3]],
                3 => [['EEE 2101', 'Electromagnetic Fields', 3], ['EEE 2102', 'Electronics II', 3], ['EEE 2103', 'Digital Logic Design', 3]],
                4 => [['EEE 2201', 'Signals and Systems', 3], ['EEE 2202', 'Electrical Machines I', 3], ['EEE 2203', 'Numerical Methods', 3]],
                5 => [['EEE 3101', 'Power Systems I', 3], ['EEE 3102', 'Electrical Machines II', 3], ['EEE 3103', 'Control Systems', 3]],
                6 => [['EEE 3201', 'Power Systems II', 3], ['EEE 3202', 'Microprocessors', 3], ['EEE 3203', 'Communication Systems', 3]],
                7 => [['EEE 4101', 'Power Electronics', 3], ['EEE 4102', 'Digital Signal Processing', 3], ['EEE 4103', 'VLSI Design', 3]],
                8 => [['EEE 4201', 'Renewable Energy Systems', 3], ['EEE 4202', 'High Voltage Engineering', 3], ['EEE 4000', 'Project and Thesis', 4]],
            ],
            'mechanical-engineering' => [
                1 => [['ME 1101', 'Engineering Mechanics', 3], ['ME 1102', 'Engineering Mathematics I', 3], ['ME 1103', 'Workshop Practice', 1]],
                2 => [['ME 1201', 'Thermodynamics I', 3], ['ME 1202', 'Engineering Mathematics II', 3], ['ME 1203', 'Materials Science', 3]],
                3 => [['ME 2101', 'Fluid Mechanics', 3], ['ME 2102', 'Strength of Materials', 3], ['ME 2103', 'Manufacturing Processes', 3]],
                4 => [['ME 2201', 'Thermodynamics II', 3], ['ME 2202', 'Machine Design I', 3], ['ME 2203', 'Dynamics of Machinery', 3]],
                5 => [['ME 3101', 'Heat Transfer', 3], ['ME 3102', 'Machine Design II', 3], ['ME 3103', 'Internal Combustion Engines', 3]],
                6 => [['ME 3201', 'Refrigeration and Air Conditioning', 3], ['ME 3202', 'Mechatronics', 3], ['ME 3203', 'Industrial Engineering', 3]],
                7 => [['ME 4101', 'Power Plant Engineering', 3], ['ME 4102', 'Automobile Engineering', 3], ['ME 4103', 'Finite Element Analysis', 3]],
                8 => [['ME 4201', 'Robotics', 3], ['ME 4202', 'Renewable Energy', 3], ['ME 4000', 'Project and Thesis', 4]],
            ],
            'civil-engineering' => [
                1 => [['CE 1101', 'Engineering Mechanics', 3], ['CE 1102', 'Engineering Mathematics I', 3], ['CE 1103', 'Surveying I', 3]],
                2 => [['CE 1201', 'Engineering Geology', 3], ['CE 1202', 'Engineering Mathematics II', 3], ['CE 1203', 'Surveying II', 3]],
                3 => [['CE 2101', 'Mechanics of Solids', 3], ['CE 2102', 'Fluid Mechanics', 3], ['CE 2103', 'Building Materials', 3]],
                4 => [['CE 2201', 'Structural Analysis I', 3], ['CE 2202', 'Hydraulics', 3], ['CE 2203', 'Soil Mechanics', 3]],
                5 => [['CE 3101', 'Structural Analysis II', 3], ['CE 3102', 'Reinforced Concrete Design', 3], ['CE 3103', 'Transportation Engineering', 3]],
                6 => [['CE 3201', 'Foundation Engineering', 3], ['CE 3202', 'Environmental Engineering', 3], ['CE 3203', 'Steel Structures', 3]],
                7 => [['CE 4101', 'Water Resources Engineering', 3], ['CE 4102', 'Construction Management', 3], ['CE 4103', 'Earthquake Engineering', 3]],
                8 => [['CE 4201', 'Bridge Engineering', 3], ['CE 4202', 'Urban Planning', 3], ['CE 4000', 'Project and Thesis', 4]],
            ],
            'business-administration' => [
                1 => [['BBA 1101', 'Principles of Management', 3], ['BBA 1102', 'Microeconomics', 3], ['BBA 1103', 'Business Mathematics', 3]],
                2 => [['BBA 1201', 'Principles of Marketing', 3], ['BBA 1202', 'Macroeconomics', 3], ['BBA 1203', 'Financial Accounting', 3]],
                3 => [['BBA 2101', 'Organizational Behavior', 3], ['BBA 2102', 'Business Statistics', 3], ['BBA 2103', 'Cost Accounting', 3]],
                4 => [['BBA 2201', 'Human Resource Management', 3], ['BBA 2202', 'Business Finance', 3], ['BBA 2203', 'Management Information Systems', 3]],
                5 => [['BBA 3101', 'Marketing Management', 3], ['BBA 3102', 'Financial Management', 3], ['BBA 3103', 'Business Law', 3]],
                6 => [['BBA 3201', 'Operations Management', 3], ['BBA 3202', 'Investment Analysis', 3], ['BBA 3203', 'Entrepreneurship', 3]],
                7 => [['BBA 4101', 'Strategic Management', 3], ['BBA 4102', 'International Business', 3], ['BBA 4103', 'Supply Chain Management', 3]],
                8 => [['BBA 4201', 'Business Research Methods', 3], ['BBA 4202', 'Project Management', 3], ['BBA 4000', 'Internship and Project', 4]],
            ],
            'psychology' => [
                1 => [['PSY 1101', 'Introduction to Psychology', 3], ['PSY 1102', 'General Psychology', 3], ['PSY 1103', 'Statistics for Psychology', 3]],
                2 => [['PSY 1201', 'Developmental Psychology', 3], ['PSY 1202', 'Biological Psychology', 3], ['PSY 1203', 'Social Psychology', 3]],
                3 => [['PSY 2101', 'Cognitive Psychology', 3], ['PSY 2102', 'Personality Psychology', 3], ['PSY 2103', 'Research Methods', 3]],
                4 => [['PSY 2201', 'Abnormal Psychology', 3], ['PSY 2202', 'Psychological Testing', 3], ['PSY 2203', 'Educational Psychology', 3]],
                5 => [['PSY 3101', 'Clinical Psychology', 3], ['PSY 3102', 'Counseling Psychology', 3], ['PSY 3103', 'Industrial Psychology', 3]],
                6 => [['PSY 3201', 'Health Psychology', 3], ['PSY 3202', 'Forensic Psychology', 3], ['PSY 3000', 'Research Project', 4]],
            ],
            'biology' => [
                1 => [['BIO 1101', 'Cell Biology', 3], ['BIO 1102', 'Botany', 3], ['BIO 1103', 'Chemistry for Biologists', 3]],
                2 => [['BIO 1201', 'Zoology', 3], ['BIO 1202', 'Genetics', 3], ['BIO 1203', 'Biostatistics', 3]],
                3 => [['BIO 2101', 'Microbiology', 3], ['BIO 2102', 'Biochemistry', 3], ['BIO 2103', 'Ecology', 3]],
                4 => [['BIO 2201', 'Molecular Biology', 3], ['BIO 2202', 'Physiology', 3], ['BIO 2203', 'Evolutionary Biology', 3]],
                5 => [['BIO 3101', 'Immunology', 3], ['BIO 3102', 'Bioinformatics', 3], ['BIO 3103', 'Developmental Biology', 3]],
                6 => [['BIO 3201', 'Biotechnology', 3], ['BIO 3202', 'Conservation Biology', 3], ['BIO 3000', 'Research Project', 4]],
            ],
            'mathematics' => [
                1 => [['MAT 1101', 'Calculus I', 3], ['MAT 1102', 'Linear Algebra', 3], ['MAT 1103', 'Discrete Mathematics', 3]],
                2 => [['MAT 1201', 'Calculus II', 3], ['MAT 1202', 'Analytic Geometry', 3], ['MAT 1203', 'Programming for Mathematics', 3]],
                3 => [['MAT 2101', 'Real Analysis I', 3], ['MAT 2102', 'Ordinary Differential Equations', 3], ['MAT 2103', 'Probability Theory', 3]],
                4 => [['MAT 2201', 'Real Analysis II', 3], ['MAT 2202', 'Abstract Algebra', 3], ['MAT 2203', 'Numerical Analysis', 3]],
                5 => [['MAT 3101', 'Complex Analysis', 3], ['MAT 3102', 'Partial Differential Equations', 3], ['MAT 3103', 'Mathematical Statistics', 3]],
                6 => [['MAT 3201', 'Topology', 3], ['MAT 3202', 'Operations Research', 3], ['MAT 3000', 'Research Project', 4]],
            ],
            'physics' => [
                1 => [['PHYS 1101', 'Mechanics', 3], ['PHYS 1102', 'Mathematical Physics I', 3], ['PHYS 1103', 'Physics Laboratory I', 1]],
                2 => [['PHYS 1201', 'Waves and Oscillations', 3], ['PHYS 1202', 'Mathematical Physics II', 3], ['PHYS 1203', 'Heat and Thermodynamics', 3]],
                3 => [['PHYS 2101', 'Electricity and Magnetism', 3], ['PHYS 2102', 'Optics', 3], ['PHYS 2103', 'Classical Mechanics', 3]],
                4 => [['PHYS 2201', 'Quantum Mechanics I', 3], ['PHYS 2202', 'Electrodynamics', 3], ['PHYS 2203', 'Statistical Mechanics', 3]],
                5 => [['PHYS 3101', 'Quantum Mechanics II', 3], ['PHYS 3102', 'Solid State Physics', 3], ['PHYS 3103', 'Nuclear Physics', 3]],
                6 => [['PHYS 3201', 'Particle Physics', 3], ['PHYS 3202', 'Astrophysics', 3], ['PHYS 3000', 'Research Project', 4]],
            ],
            'chemistry' => [
                1 => [['CHEM 1101', 'General Chemistry', 3], ['CHEM 1102', 'Inorganic Chemistry I', 3], ['CHEM 1103', 'Chemistry Laboratory I', 1]],
                2 => [['CHEM 1201', 'Physical Chemistry I', 3], ['CHEM 1202', 'Organic Chemistry I', 3], ['CHEM 1203', 'Analytical Chemistry', 3]],
                3 => [['CHEM 2101', 'Inorganic Chemistry II', 3], ['CHEM 2102', 'Organic Chemistry II', 3], ['CHEM 2103', 'Physical Chemistry II', 3]],
                4 => [['CHEM 2201', 'Spectroscopy', 3], ['CHEM 2202', 'Quantum Chemistry', 3], ['CHEM 2203', 'Environmental Chemistry', 3]],
                5 => [['CHEM 3101', 'Polymer Chemistry', 3], ['CHEM 3102', 'Biochemistry', 3], ['CHEM 3103', 'Electrochemistry', 3]],
                6 => [['CHEM 3201', 'Industrial Chemistry', 3], ['CHEM 3202', 'Medicinal Chemistry', 3], ['CHEM 3000', 'Research Project', 4]],
            ],
        ];
    }

    /**
     * The authoritative CSE curriculum (semesters 1–9), used exactly as provided.
     *
     * @return array<int, array<int, array{0: string, 1: string, 2: int}>>
     */
    private function cseCurriculum(): array
    {
        return [
            1 => [
                ['MATH 1101', 'Mathematics I', 3],
                ['ENG 1204', 'English II', 3],
                ['PHY 1302', 'Physics II', 3],
                ['CSE 1102', 'Structured Programming Language', 3],
                ['CSE 1157', 'Structured Programming Lab', 1],
            ],
            2 => [
                ['MATH 1302', 'Mathematics II', 2],
                ['CSE 1205', 'Electrical Engineering and Circuit Analysis', 3],
                ['CSE 1259', 'Electrical Engineering Lab', 1],
                ['CSE 1307', 'OOP I (C++)', 3],
                ['CSE 1360', 'OOP Lab', 1],
                ['CSE 2111', 'Data Structure', 3],
                ['CSE 2162', 'Data Structure Lab', 1],
                ['CSE 1290', 'Software Development I', 1],
            ],
            3 => [
                ['MATH 2103', 'Mathematics III', 3],
                ['CSE 2263', 'Algorithms Design & Analysis', 3],
                ['CSE 2264', 'Algorithms Lab', 1],
                ['CSE 2215', 'Digital Logic Design', 3],
                ['CSE 2265', 'Digital Logic Lab', 1],
                ['CSE 2319', 'Database Management System', 3],
                ['CSE 2367', 'DBMS Lab', 1],
            ],
            4 => [
                ['MATH 2204', 'Mathematics IV', 2],
                ['CSE 3168', 'Numerical Methods', 3],
                ['CSE 3186', 'Numerical Methods Lab', 1],
                ['CSE 3124', 'Microprocessor & Assembly', 3],
                ['CSE 3171', 'Microprocessor Lab', 1],
                ['CSE 2291', 'Software Development II', 1],
            ],
            5 => [
                ['MATH 2305', 'Mathematics V', 2],
                ['CSE 2317', 'Digital Electronics', 3],
                ['CSE 2366', 'Digital Electronics Lab', 1],
                ['CSE 3169', 'Theory of Computation', 3],
                ['CSE 3170', 'Computer Architecture', 3],
            ],
            6 => [
                ['CSE 3228', 'Compiler Design', 3],
                ['CSE 3272', 'Compiler Lab', 1],
                ['CSE 3331', 'Operating System', 3],
                ['CSE 3373', 'OS Lab', 1],
                ['CSE 3333', 'OOP II (Java)', 3],
                ['CSE 3374', 'Java Lab', 1],
                ['CSE 3227', 'Data Communication', 3],
            ],
            7 => [
                ['CSE 3230', 'Software Engineering', 3],
                ['CSE 4136', 'Computer Networks', 3],
                ['CSE 4176', 'Networks Lab', 1],
                ['CSE 4138', 'Computer Peripherals', 3],
                ['CSE 4177', 'Peripherals Lab', 1],
                ['CSE 4349', 'MIS', 3],
                ['CSE 4389', 'Industrial Training', 1],
            ],
            8 => [
                ['IPE 4101', 'Industrial Management', 2],
                ['CSE 4355', 'Artificial Intelligence', 3],
                ['CSE 4385', 'AI Lab', 1],
                ['CSE 4278', 'Computer Graphics', 3],
                ['CSE 4288', 'Graphics Lab', 1],
                ['CSE 4241', 'VLSI Design', 3],
                ['CSE 4279', 'VLSI Lab', 1],
            ],
            9 => [
                ['CSE 4351', 'Image Processing & Computer Vision', 3],
                ['CSE 4383', 'Image Processing Lab', 1],
                ['CSE 4000', 'Project & Thesis', 4],
            ],
        ];
    }
}
